<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\ManyToOne(inversedBy: 'outgoingTransactions')]
    private ?Account $source = null;

    #[ORM\ManyToOne(inversedBy: 'incomingTransactions')]
    private ?Account $target = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => '1970-01-01 00:00:00'])]
    private \DateTimeInterface $date;

    #[ORM\Column(length: 3, options: ['default' => Account::DEFAULT_CURRENCY])]
    private string $sourceCurrency = Account::DEFAULT_CURRENCY;

    #[ORM\Column(options: ['default' => 0])]
    private int $sourceAmount = 0;

    #[ORM\Column(length: 3, options: ['default' => Account::DEFAULT_CURRENCY])]
    private string $targetCurrency = Account::DEFAULT_CURRENCY;

    #[ORM\Column(options: ['default' => 0])]
    private int $targetAmount = 0;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSource(): ?Account
    {
        return $this->source;
    }

    public function setSource(?Account $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getTarget(): ?Account
    {
        return $this->target;
    }

    public function setTarget(?Account $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getSourceCurrency(): string
    {
        return $this->sourceCurrency;
    }

    public function setSourceCurrency(string $currency): static
    {
        $this->sourceCurrency = $currency;

        return $this;
    }

    public function getSourceAmount(): int
    {
        return $this->sourceAmount;
    }

    public function setSourceAmount(int $amount): static
    {
        $this->sourceAmount = $amount;

        return $this;
    }

    public function getTargetCurrency(): string
    {
        return $this->targetCurrency;
    }

    public function setTargetCurrency(string $currency): static
    {
        $this->targetCurrency = $currency;

        return $this;
    }

    public function getTargetAmount(): int
    {
        return $this->targetAmount;
    }

    public function setTargetAmount(int $amount): static
    {
        $this->targetAmount = $amount;

        return $this;
    }
}
