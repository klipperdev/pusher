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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class PushResult implements PushResultInterface
{
    private string $pipelineName;

    private bool $skipped;

    private bool $success;

    private int $countErrors;

    public function __construct(string $pipelineName, ?int $countErrors)
    {
        $this->pipelineName = $pipelineName;
        $this->skipped = null === $countErrors;
        $this->success = 0 === $countErrors;
        $this->countErrors = (int) $countErrors;
    }

    public function getPipelineName(): string
    {
        return $this->pipelineName;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getCountErrors(): int
    {
        return $this->countErrors;
    }
}
