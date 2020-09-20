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
interface ContextInterface
{
    public function setUsername(?string $username): void;

    public function getUsername(): ?string;

    public function setOrganizationName(?string $organizationName): void;

    public function getOrganizationName(): ?string;

    public function setStartAt(?\DateTimeInterface $startAt): void;

    public function getStartAt(): ?\DateTimeInterface;
}
