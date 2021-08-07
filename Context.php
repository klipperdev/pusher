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
class Context implements ContextInterface
{
    private ?string $userIdentifier;

    private ?string $organizationName;

    private ?\DateTimeInterface $startAt;

    public function __construct(
        ?string $userIdentifier,
        ?string $organizationName,
        ?\DateTimeInterface $startAt
    ) {
        $this->userIdentifier = $userIdentifier;
        $this->organizationName = $organizationName;
        $this->startAt = $startAt;
    }

    public function setUsername(?string $username): void
    {
        $this->setUserIdentifier($username);
    }

    public function getUsername(): ?string
    {
        return $this->getUserIdentifier();
    }

    public function setUserIdentifier(?string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setOrganizationName(?string $organizationName): void
    {
        $this->organizationName = $organizationName;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setStartAt(?\DateTimeInterface $startAt): void
    {
        $this->startAt = $startAt;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }
}
