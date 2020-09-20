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
class ErrorPushEvent extends AbstractPushEvent
{
    private string $message;

    private ?\Throwable $exception;

    public function __construct(
        string $pipelineName,
        string $id,
        ContextInterface $context,
        string $message,
        ?\Throwable $exception = null
    ) {
        parent::__construct($pipelineName, $id, $context);

        $this->message = $message;
        $this->exception = $exception;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getThrowable(): ?\Throwable
    {
        return $this->exception;
    }
}
