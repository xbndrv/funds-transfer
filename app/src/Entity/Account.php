<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    public const DEFAULT_CURRENCY = 'EUR';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\ManyToOne(inversedBy: 'accounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(length: 3, options: ['default' => self::DEFAULT_CURRENCY])]
    private string $currency = self::DEFAULT_CURRENCY;

    #[ORM\Column(options: ['default' => 0])]
    private int $amount = 0;

    #[ORM\OneToMany(mappedBy: 'source', targetEntity: Transaction::class)]
    private Collection $outgoingTransactions;

    #[ORM\OneToMany(mappedBy: 'target', targetEntity: Transaction::class)]
    private Collection $incomingTransactions;

    public function __construct()
    {
        $this->outgoingTransactions = new ArrayCollection();
        $this->incomingTransactions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getOutgoingTransactions(): Collection
    {
        return $this->outgoingTransactions;
    }

    public function addOutgoingTransaction(Transaction $transaction): static
    {
        if (!$this->outgoingTransactions->contains($transaction)) {
            $this->outgoingTransactions->add($transaction);
            $transaction->setSource($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getIncomingTransactions(): Collection
    {
        return $this->incomingTransactions;
    }

    public function addIncomingTransaction(Transaction $transaction): static
    {
        if (!$this->incomingTransactions->contains($transaction)) {
            $this->incomingTransactions->add($transaction);
            $transaction->setTarget($this);
        }

        return $this;
    }
}
