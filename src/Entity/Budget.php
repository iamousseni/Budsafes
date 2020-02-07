<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BudgetRepository")
 */
class Budget
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Category", mappedBy="budget", orphanRemoval=true)
     */
    private $categories;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Schedule", inversedBy="budget")
     * @ORM\JoinColumn(nullable=true)
     */
    private $schedule;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="budgets")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BusinessTraffic", mappedBy="budget", orphanRemoval=true)
     */
    private $businessTraffic;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
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

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->setBudget($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
            // set the owning side to null (unless already changed)
            if ($category->getBudget() === $this) {
                $category->setBudget(null);
            }
        }

        return $this;
    }

    public function getSchedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function setSchedule(?Schedule $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

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
            $businessTraffic->setBudget($this);
        }

        return $this;
    }

    public function removeBusinessTraffic(BusinessTraffic $businessTraffic): self
    {
        if ($this->businessTraffic->contains($businessTraffic)) {
            $this->businessTraffic->removeElement($businessTraffic);
            // set the owning side to null (unless already changed)
            if ($businessTraffic->getBudget() === $this) {
                $businessTraffic->setBudget(null);
            }
        }

        return $this;
    }
}
