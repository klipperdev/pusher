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
 * Check if the pipeline required the specific organization for the push.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RequiredOrganizationPipelineInterface extends PipelineInterface
{
    public function getOrganizationName(): ?string;
}
