<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Pusher\Model\Traits;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface LastPushDateableInterface
{
    /**
     * @return string[]
     */
    public function getLastPushDateNames(): array;

    /**
     * @param array|\DateTimeInterface[] $services The map of service names and date times
     */
    public function setLastPushDates(array $services): void;

    public function getLastPushDates(): array;

    public function hasLastPushDate(string $service): bool;

    public function getLastPushDate(string $service): ?\DateTimeInterface;

    public function addLastPushDate(string $service, \DateTimeInterface $datetime): void;

    public function removeLastPushDate(string $service): void;

    public function clearLastPushDates(): void;
}
