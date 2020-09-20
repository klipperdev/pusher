<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Pusher\Exception;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RequiredPipelineException extends RuntimeException
{
    private string $pipelineName;

    public function __construct(string $pipelineName, int $code = 0, ?\Throwable $previous = null)
    {
        $this->pipelineName = $pipelineName;

        parent::__construct(
            sprintf('The pusher pipeline "%s" is required', $pipelineName),
            $code,
            $previous
        );
    }

    public function getPipelineName(): string
    {
        return $this->pipelineName;
    }
}
