<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ScheduleRepository")
 */
class Schedule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Budget", mappedBy="schedule")
     */
    private $budget;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="schedules")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency", inversedBy="schedules")
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\Column(type="date")
     */
    private $firstInvoice;

    /**
     * @ORM\Column(type="integer")
     */
    private $cycle;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    public function __construct()
    {
        $this->budget = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Budget[]
     */
    public function getBudget(): Collection
    {
        return $this->budget;
    }

    public function addBudget(Budget $budget): self
    {
        if (!$this->budget->contains($budget)) {
            $this->budget[] = $budget;
            $budget->setSchedule($this);
        }

        return $this;
    }

    public function removeBudget(Budget $budget): self
    {
        if ($this->budget->contains($budget)) {
            $this->budget->removeElement($budget);
            // set the owning side to null (unless already changed)
            if ($budget->getSchedule() === $this) {
                $budget->setSchedule(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getFirstInvoice(): ?\DateTimeInterface
    {
        return $this->firstInvoice;
    }

    public function setFirstInvoice(\DateTimeInterface $firstInvoice): self
    {
        $this->firstInvoice = $firstInvoice;

        return $this;
    }

    public function getCycle(): ?int
    {
        return $this->cycle;
    }

    public function setCycle(int $cycle): self
    {
        $this->cycle = $cycle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
