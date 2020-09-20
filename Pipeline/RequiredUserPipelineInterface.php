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

/**
 * Check if the pipeline required the specific user for the push.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RequiredUserPipelineInterface extends PipelineInterface
{
    public function getUsername(): string;
}
