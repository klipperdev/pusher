<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Pusher\Event;

use Klipper\Component\Pusher\ContextInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class PostPushEvent extends AbstractPushEvent
{
    private int $errors;

    public function __construct(
        string $pipelineName,
        string $id,
        ContextInterface $context,
        int $errors
    ) {
        parent::__construct($pipelineName, $id, $context);

        $this->errors = $errors;
    }

    public function isSuccess(): bool
    {
        return 0 === $this->errors;
    }

    public function getCountErrors(): int
    {
        return $this->errors;
    }
}
