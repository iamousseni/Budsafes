<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Budget", inversedBy="categories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $budget;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Schedule", mappedBy="category", orphanRemoval=true)
     */
    private $schedules;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BusinessTraffic", mappedBy="category", orphanRemoval=true)
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

    public function getBudget(): ?Budget
    {
        return $this->budget;
    }

    public function setBudget(?Budget $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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
            $schedule->setCategory($this);
        }

        return $this;
    }

    public function removeSchedule(Schedule $schedule): self
    {
        if ($this->schedules->contains($schedule)) {
            $this->schedules->removeElement($schedule);
            // set the owning side to null (unless already changed)
            if ($schedule->getCategory() === $this) {
                $schedule->setCategory(null);
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
            $businessTraffic->setCategory($this);
        }

        return $this;
    }

    public function removeBusinessTraffic(BusinessTraffic $businessTraffic): self
    {
        if ($this->businessTraffic->contains($businessTraffic)) {
            $this->businessTraffic->removeElement($businessTraffic);
            // set the owning side to null (unless already changed)
            if ($businessTraffic->getCategory() === $this) {
                $businessTraffic->setCategory(null);
            }
        }

        return $this;
    }
}
