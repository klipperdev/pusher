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

use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Component\Resource\ResourceListInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface PipelineInterface
{
    /**
     * Get the unique name of the configured pipeline.
     */
    public function getName(): string;

    /**
     * Get the size of the batch.
     */
    public function getBatchSize(): int;

    /**
     * Load the data from the main database.
     *
     * @return object[] The list of entity
     */
    public function load(DomainManagerInterface $domainManager, int $cursor, ?\DateTimeInterface $startAt = null): array;

    /**
     * Push the main data in other system.
     *
     * @param object[] $data The main data
     */
    public function push(DomainManagerInterface $domainManager, array $data): ResourceListInterface;
}
