<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CurrencyRepository")
 */
class Currency
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $symbol;

    /**
     * @ORM\Column(type="float")
     */
    private $value;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Schedule", mappedBy="currency", orphanRemoval=true)
     */
    private $schedules;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BusinessTraffic", mappedBy="currency", orphanRemoval=true)
     */
    private $businessTraffic;

    public function __construct()
    {
        $this->schedules = new ArrayCollection();
        $this->businessTraffic = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Collection|Schedule[]
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(Schedule $schedule): self
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules[] = $schedule;
            $schedule->setCurrency($this);
        }

        return $this;
    }

    public function removeSchedule(Schedule $schedule): self
    {
        if ($this->schedules->contains($schedule)) {
            $this->schedules->removeElement($schedule);
            // set the owning side to null (unless already changed)
            if ($schedule->getCurrency() === $this) {
                $schedule->setCurrency(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BusinessTraffic[]
     */
    public function getBusinessTraffic(): Collection
    {
        return $this->businessTraffic;
    }

    public function addBusinessTraffic(BusinessTraffic $businessTraffic): self
    {
        if (!$this->businessTraffic->contains($businessTraffic)) {
            $this->businessTraffic[] = $businessTraffic;
            $businessTraffic->setCurrency($this);
        }

        return $this;
    }

    public function removeBusinessTraffic(BusinessTraffic $businessTraffic): self
    {
        if ($this->businessTraffic->contains($businessTraffic)) {
            $this->businessTraffic->removeElement($businessTraffic);
            // set the owning side to null (unless already changed)
            if ($businessTraffic->getCurrency() === $this) {
                $businessTraffic->setCurrency(null);
            }
        }

        return $this;
    }
}
