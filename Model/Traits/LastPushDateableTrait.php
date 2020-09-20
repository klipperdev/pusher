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

use Doctrine\ORM\Mapping as ORM;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait LastPushDateableTrait
{
    /**
     * @ORM\Column(type="json")
     */
    protected ?array $lastPushDates = [];

    /**
     * @return string[]
     *
     * @see LastPushAtableInterface::getLastPushDateNames()
     */
    public function getLastPushDateNames(): array
    {
        return array_keys($this->lastPushDates ?? []);
    }

    /**
     * @see LastPushAtableInterface::setLastPushDates()
     */
    public function setLastPushDates(array $services): void
    {
        foreach ($services as $service => $dateTime) {
            if (null === $dateTime) {
                $this->removeLastPushDate($service);
            } else {
                $this->addLastPushDate($service, $dateTime);
            }
        }
    }

    /**
     * @see LastPushAtableInterface::getLastPushDates()
     */
    public function getLastPushDates(): array
    {
        $values = [];

        foreach ($this->getLastPushDateNames() as $service) {
            $values[$service] = $this->getLastPushDate($service);
        }

        return $values;
    }

    /**
     * @see LastPushAtableInterface::hasLastPushDate()
     */
    public function hasLastPushDate(string $service): bool
    {
        return isset($this->lastPushDates[$service]);
    }

    /**
     * @see LastPushAtableInterface::getLagetLastPushDate()
     */
    public function getLastPushDate(string $service): ?\DateTimeInterface
    {
        if (isset($this->lastPushDates[$service])) {
            $date = \DateTimeImmutable::createFromFormat(
                \DateTime::ATOM,
                $this->lastPushDates[$service]
            );

            return $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return null;
    }

    /**
     * @see LastPushAtableInterface::addLastPushDate()
     */
    public function addLastPushDate(string $service, \DateTimeInterface $datetime): void
    {
        $this->lastPushDates[$service] = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $datetime->format(\DateTime::ATOM)
        )
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(\DateTime::ATOM)
        ;
    }

    /**
     * @see LastPushAtableInterface::removeLastPushDate()
     */
    public function removeLastPushDate(string $service): void
    {
        unset($this->lastPushDates[$service]);
    }

    public function clearLastPushDates(): void
    {
        $this->lastPushDates = [];
    }
}
