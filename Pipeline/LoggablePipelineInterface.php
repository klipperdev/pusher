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
 * Check if the pipeline support the custom logger.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface LoggablePipelineInterface extends PipelineInterface
{
    /**
     * Get the dedicated logger for the pipeline.
     */
    public function getLogger(): ?LoggerInterface;
}
