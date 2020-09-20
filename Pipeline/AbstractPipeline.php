<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Pusher\Pipeline;

use Psr\Log\LoggerInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
abstract class AbstractPipeline implements PipelineInterface, LoggablePipelineInterface
{
    protected int $batchSize;

    protected ?LoggerInterface $logger;

    public function __construct(int $batchSize = 500, ?LoggerInterface $logger = null)
    {
        $this->batchSize = $batchSize;
        $this->logger = $logger;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    protected function getMaxResults(): ?int
    {
        return $this->getBatchSize() > 0 ? $this->getBatchSize() : null;
    }

    protected function getFirstResult(int $cursor): ?int
    {
        return null !== $this->getMaxResults() ? $cursor * $this->getBatchSize() : null;
    }
}
