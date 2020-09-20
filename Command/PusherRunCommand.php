<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Pusher\Command;

use Klipper\Component\Pusher\Context;
use Klipper\Component\Pusher\PusherManagerInterface;
use Klipper\Component\Pusher\PushResultInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class PusherRunCommand extends Command
{
    private PusherManagerInterface $pusherManager;

    public function __construct(PusherManagerInterface $pusherManager)
    {
        parent::__construct();

        $this->pusherManager = $pusherManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('pusher:run')
            ->setDescription('Push data from a selected pipeline')
            ->addArgument('pipeline', InputArgument::IS_ARRAY, 'The unique names of pipelines')
            ->addOption('start-at', 'S', InputOption::VALUE_OPTIONAL, 'The ISO datetime')
            ->addOption('username', 'U', InputOption::VALUE_OPTIONAL, 'The username of User used to the push')
            ->addOption('organization', 'O', InputOption::VALUE_OPTIONAL, 'The name of Organization used to the push')
            ->addOption('no-stop-on-error', '', InputOption::VALUE_NONE, 'Run all pipelines even if previous pipelines has errors')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $pipelines = (array) $input->getArgument('pipeline');
        $startAt = $input->getOption('start-at');
        $username = $input->getOption('username');
        $organization = $input->getOption('organization');
        $stopOnError = !$input->getOption('no-stop-on-error');

        try {
            $startAt = !empty($startAt) ? new \DateTime($startAt) : null;
        } catch (\Throwable $e) {
            $style->error('The "start-at" option value is not a valid datetime');

            return 1;
        }

        if (empty($pipelines)) {
            $pipelines = array_keys($this->pusherManager->getPipelines());
        }

        $results = $this->pusherManager->pushs(
            $pipelines,
            new Context($username, $organization, $startAt),
            $stopOnError,
            null,
            static function (PushResultInterface $res, array $pipelines) use ($style): void {
                $pipeline = $res->getPipelineName();

                if ($res->isSkipped()) {
                    if (\count($pipelines) > 1) {
                        $style->error(sprintf(
                            'Push data from the "%s" pipeline is skipped because an required pipeline dependency at least one error',
                            $pipeline
                        ));
                    } else {
                        $style->note(sprintf(
                            'Push data from the "%s" pipeline is already being processed',
                            $pipeline
                        ));
                    }
                } elseif ($res->isSuccess()) {
                    $style->success(sprintf(
                        'Push data from the "%s" pipeline is finished with successfully',
                        $pipeline
                    ));
                } else {
                    $style->error(sprintf(
                        'Push data from the "%s" pipeline is finished with %s error(s)',
                        $pipeline,
                        $res->getCountErrors()
                    ));
                }
            }
        );

        return $results->isSuccess() ? 0 : 1;
    }
}
