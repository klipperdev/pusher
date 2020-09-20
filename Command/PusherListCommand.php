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

use Klipper\Component\Pusher\PusherManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class PusherListCommand extends Command
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
            ->setName('pusher:list')
            ->setDescription('List all available pipelines for the push')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $names = array_keys($this->pusherManager->getPipelines());

        if (!empty($names)) {
            $style->writeln([
                '',
                'Available pipelines:',
                '',
            ]);
            $style->listing($names);
        } else {
            $style->warning('No pipeline is configured');
        }

        return 0;
    }
}
