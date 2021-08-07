<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Pusher;

use Klipper\Component\Pusher\Event\ErrorPushEvent;
use Klipper\Component\Pusher\Event\PartialPushEvent;
use Klipper\Component\Pusher\Event\PostPushEvent;
use Klipper\Component\Pusher\Event\PrePushEvent;
use Klipper\Component\Pusher\Exception\InvalidArgumentException;
use Klipper\Component\Pusher\Exception\RequiredPipelineException;
use Klipper\Component\Pusher\Pipeline\LoggablePipelineInterface;
use Klipper\Component\Pusher\Pipeline\PipelineInterface;
use Klipper\Component\Pusher\Pipeline\RequiredOrganizationPipelineInterface;
use Klipper\Component\Pusher\Pipeline\RequiredPipelinesInterface;
use Klipper\Component\Pusher\Pipeline\RequiredUserPipelineInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Component\Resource\ResourceListInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class PusherManager implements PusherManagerInterface
{
    private LockFactory $lockFactory;

    private DomainManagerInterface $domainManager;

    private EventDispatcherInterface $dispatcher;

    private LoggerInterface $logger;

    private bool $logResourceErrors = true;

    /**
     * @var PipelineInterface[]
     */
    private array $pipelines = [];

    /**
     * @param PipelineInterface[] $pipelines The configured pipelines
     */
    public function __construct(
        LockFactory $lockFactory,
        DomainManagerInterface $domainManager,
        EventDispatcherInterface $dispatcher,
        array $pipelines = [],
        ?LoggerInterface $logger = null
    ) {
        $this->lockFactory = $lockFactory;
        $this->domainManager = $domainManager;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger ?? new NullLogger();

        foreach ($pipelines as $pipeline) {
            $this->pipelines[$pipeline->getName()] = $pipeline;
        }
    }

    public function setLogResourceErrors(bool $logResourceErrors): void
    {
        $this->logResourceErrors = $logResourceErrors;
    }

    public function addPipeline(PipelineInterface $pipeline)
    {
        $this->pipelines[$pipeline->getName()] = $pipeline;

        return $this;
    }

    public function hasPipeline(string $name): bool
    {
        return isset($this->pipelines[$name]);
    }

    /**
     * @throws InvalidArgumentException When the pipeline does not exist
     */
    public function getPipeline(string $name): PipelineInterface
    {
        if ($this->hasPipeline($name)) {
            return $this->pipelines[$name];
        }

        throw new InvalidArgumentException(sprintf(
            'The pusher pipeline "%s" does not exit',
            $name
        ));
    }

    public function getPipelines(): array
    {
        return $this->pipelines;
    }

    public function getStackOfPipelines(array $pipelines): array
    {
        $validPipelines = [];

        foreach ($pipelines as $pipeline) {
            if (!$pipeline instanceof PipelineInterface) {
                try {
                    $validPipelines[] = $this->getPipeline($pipeline);
                } catch (\Throwable $e) {
                    $this->getLogger()->critical($e->getMessage(), [
                        'pusher_pipelines' => $pipelines,
                        'pusher_pipeline' => $pipeline,
                        'exception' => $e,
                    ]);
                }
            } else {
                $validPipelines[] = $pipeline;
            }
        }

        return $this->orderPipelines($validPipelines);
    }

    public function push($pipeline, ContextInterface $context): PushResultInterface
    {
        set_time_limit(0);
        $startedTime = microtime(true);
        $errors = 0;
        $lock = null;
        $pipelineName = $pipeline instanceof PipelineInterface
            ? $pipeline->getName()
            : (string) $pipeline;
        $id = $pipelineName.':'.($startedTime * 10000);

        try {
            $this->domainManager->clear();

            if (!$pipeline instanceof PipelineInterface) {
                $pipeline = $this->getPipeline($pipelineName);
            }

            if ($pipeline instanceof RequiredUserPipelineInterface) {
                $context->setUserIdentifier($pipeline->getUsername());
            }

            if ($pipeline instanceof RequiredOrganizationPipelineInterface) {
                $context->setOrganizationName($pipeline->getOrganizationName());
            }

            $lock = $this->lockFactory->createLock('pusher:'.$pipeline->getName());

            if (!$lock->acquire()) {
                return new PushResult($pipelineName, null);
            }

            $this->dispatcher->dispatch(new PrePushEvent($pipelineName, $id, $context));

            $errors += $this->doPush($pipeline, $id, $context);
            $duration = round((microtime(true) - $startedTime) * 10) / 10;

            if (0 === $errors) {
                $this->getLogger($pipeline)->info(sprintf(
                    'Push data from the "%s" pipeline is finished with successfully in %s s',
                    $pipeline->getName(),
                    $duration
                ), [
                    'pusher_pipeline' => $pipeline->getName(),
                    'id' => $id,
                    'duration' => $duration,
                ]);
            } else {
                ++$errors;
                $msg = sprintf(
                    'Push data from the "%s" pipeline is finished with an error in %s s',
                    $pipeline->getName(),
                    $duration
                );
                $this->dispatcher->dispatch(new ErrorPushEvent($pipelineName, $id, $context, $msg));
                $this->getLogger($pipeline)->error(
                    $msg,
                    [
                        'pusher_pipeline' => $pipeline->getName(),
                        'id' => $id,
                        'duration' => $duration,
                    ],
                );
            }
        } catch (\Throwable $e) {
            ++$errors;
            $this->dispatcher->dispatch(new ErrorPushEvent($pipelineName, $id, $context, $e->getMessage(), $e));
            $this->getLogger()->critical($e->getMessage(), [
                'pusher_pipeline' => $pipelineName,
                'id' => $id,
                'exception' => $e,
            ]);
        }

        $this->dispatcher->dispatch(new PostPushEvent($pipelineName, $id, $context, $errors));

        if ($lock) {
            $lock->release();
        }

        return new PushResult($pipelineName, $errors);
    }

    public function pushs(array $pipelines, ContextInterface $context, bool $stopOnError = true, ?callable $preCallback = null, ?callable $postCallback = null): PushResultListInterface
    {
        $validPipelines = $this->getStackOfPipelines($pipelines);
        $results = [];
        $successPipelines = [];
        $success = true;

        foreach ($validPipelines as $pipeline) {
            $pushContext = clone $context;

            if (null !== $preCallback) {
                $preCallback($pipeline, $validPipelines);
            }

            $res = $stopOnError && !$success && $this->hasDependencyErrors($pipeline, $successPipelines)
                ? new PushResult($pipeline->getName(), null)
                : $this->push($pipeline, $pushContext);
            $success = $success && $res->isSuccess();
            $results[] = $res;

            if ($res->isSuccess()) {
                $successPipelines[] = $pipeline->getName();
            }

            if (null !== $postCallback) {
                $postCallback($res, $validPipelines);
            }
        }

        return new PushResultList($results);
    }

    private function getLogger(?PipelineInterface $pipeline = null): LoggerInterface
    {
        if ($pipeline instanceof LoggablePipelineInterface && $logger = $pipeline->getLogger()) {
            return $logger;
        }

        return $this->logger;
    }

    /**
     * @return int The count of error
     */
    private function doPush(PipelineInterface $pipeline, string $id, ContextInterface $context): int
    {
        $startAt = $context->getStartAt();
        $cursor = 0;
        $errors = 0;
        $finish = false;

        while (!$finish) {
            $result = $pipeline->load($this->domainManager, $cursor, $startAt);
            $finish = empty($result);

            if (!$finish) {
                $resList = $pipeline->push($this->domainManager, $result);
                $errors += $this->getCountErrors($resList);

                $this->dispatcher->dispatch(new PartialPushEvent(
                    $pipeline->getName(),
                    $id,
                    $context,
                    $resList
                ));

                if ($this->logResourceErrors && $resList->hasErrors()) {
                    $this->getLogger($pipeline)->error(
                        sprintf(
                            'Push data from the "%s" pipeline has errors on batch cursor "%s"',
                            $pipeline->getName(),
                            $cursor
                        ),
                        [
                            'pusher_pipeline' => $pipeline->getName(),
                            'id' => $id,
                            'cursor' => $cursor,
                            'errors' => $this->buildResourceErrors($resList),
                            'source_data' => $result,
                        ],
                    );
                }

                ++$cursor;
            }
        }

        return $errors;
    }

    private function buildResourceErrors(ResourceListInterface $resList): array
    {
        $resErrors = [];

        /** @var ConstraintViolation $error */
        foreach ($resList->getErrors() as $i => $error) {
            $pp = $error->getPropertyPath();
            $resErrors['errors'][$i] = $error->getMessage().($pp ? ' ('.$pp.')' : '');
        }

        foreach ($resList->getResources() as $j => $resource) {
            if (!$resource->isValid()) {
                $resErrors['resources'][$j]['batch_position'] = $j;
                /** @var ConstraintViolation $error */
                foreach ($resource->getErrors() as $i => $error) {
                    if (null !== $pp = $error->getPropertyPath()) {
                        $resErrors['resources'][$j]['fields'][$pp]['errors'][$i] = $error->getMessage();
                    } else {
                        $resErrors['resources'][$j]['errors'][$i] = $error->getMessage();
                    }
                }
            }
        }

        return $resErrors;
    }

    private function getCountErrors(ResourceListInterface $resourceList): int
    {
        if (!$resourceList->hasErrors()) {
            return 0;
        }

        $errors = \count($resourceList->getErrors());

        foreach ($resourceList->all() as $resource) {
            $errors += \count($resource->getErrors());
        }

        return $errors;
    }

    /**
     * @param PipelineInterface[] $pipelines
     *
     * @return PipelineInterface[]
     */
    private function orderPipelines(array $pipelines): array
    {
        $usedPipelineNames = [];

        foreach ($pipelines as $pipeline) {
            $usedPipelineNames[] = [$pipeline->getName()];

            if ($pipeline instanceof RequiredPipelinesInterface) {
                $usedPipelineNames[] = $pipeline->getRequiredPipelines();
            }
        }

        $orderedPipelineNames = $this->findPipelineDependencies(array_unique(array_merge(...$usedPipelineNames)));
        $orderedPipelines = [];

        foreach ($orderedPipelineNames as $name) {
            $orderedPipelines[] = $this->getPipeline($name);
        }

        return $orderedPipelines;
    }

    /**
     * @param string[] $names
     *
     * @return string[]
     */
    private function findPipelineDependencies(array $names): array
    {
        $pipelineNames = array_keys($this->pipelines);
        $orderedPipelineNames = [];

        while (!empty($names)) {
            $name = (string) current($names);

            $pipeline = $this->getPipeline($name);
            $requiredPipelines = $pipeline instanceof RequiredPipelinesInterface
                ? $pipeline->getRequiredPipelines()
                : [];

            foreach ($requiredPipelines as $requiredPipeline) {
                $isOptional = 0 === strpos($requiredPipeline, '?');
                $requiredPipeline = ltrim($requiredPipeline, '?');

                if (!$isOptional && !\in_array($requiredPipeline, $pipelineNames, true)) {
                    throw new RequiredPipelineException($requiredPipeline);
                }
            }

            $diff = array_diff($requiredPipelines, $orderedPipelineNames);

            if (empty($diff)) {
                $orderedPipelineNames[] = $name;
                $orderedPipelines[] = $pipeline;
                $names = array_diff($names, [$name]);
            } else {
                $names = array_unique(array_merge($diff, $names));
            }
        }

        return array_unique($orderedPipelineNames);
    }

    private function hasDependencyErrors(PipelineInterface $pipeline, array $successPipelines): bool
    {
        if ($pipeline instanceof RequiredPipelinesInterface) {
            return !empty(array_diff($pipeline->getRequiredPipelines(), $successPipelines));
        }

        return false;
    }
}
