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
class PushResultList implements PushResultListInterface
{
    private array $results = [];

    private bool $skipped = false;

    private bool $success = true;

    private int $countErrors = 0;

    public function __construct(array $results)
    {
        foreach ($results as $result) {
            if ($result instanceof PushResultInterface) {
                $this->results[$result->getPipelineName()] = $result;
                $this->skipped = $this->skipped && $result->isSkipped();
                $this->success = $this->success && $result->isSuccess();
                $this->countErrors += $result->getCountErrors();
            }
        }
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

    public function getResults(): array
    {
        return $this->results;
    }
}
