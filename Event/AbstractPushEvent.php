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
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
abstract class AbstractPushEvent extends Event
{
    protected string $pipelineName;

    protected string $id;

    protected ContextInterface $context;

    public function __construct(string $pipelineName, string $id, ContextInterface $context)
    {
        $this->pipelineName = $pipelineName;
        $this->id = $id;
        $this->context = $context;
    }

    public function getPipelineName(): string
    {
        return $this->pipelineName;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}
