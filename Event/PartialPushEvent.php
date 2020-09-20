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
use Klipper\Component\Resource\ResourceListInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class PartialPushEvent extends AbstractPushEvent
{
    private ResourceListInterface $resourceList;

    public function __construct(
        string $pipelineName,
        string $id,
        ContextInterface $context,
        ResourceListInterface $resourceList
    ) {
        parent::__construct($pipelineName, $id, $context);

        $this->resourceList = $resourceList;
    }

    public function getResourceList(): ResourceListInterface
    {
        return $this->resourceList;
    }

    public function isSuccess(): bool
    {
        return !$this->resourceList->hasErrors();
    }
}
