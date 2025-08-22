<?php

namespace Laravel\Cashier\Charge;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Charge\Contracts\Orderable;
use Money\Money;

class ChargeItemBuilder
{
    protected ?Orderable $orderable;

    protected Model $owner;

    protected Money $unitPrice;

    protected string $description;

    protected int $quantity = 1;

    protected float $taxPercentage;

    public function __construct(Model $owner)
    {
        $this->owner = $owner;
        $this->taxPercentage = $owner->taxPercentage();
    }

    public function for(?Orderable $orderable): ChargeItemBuilder
    {
        if (! $orderable) {
            return $this;
        }

        $this->orderable = $orderable;
        $this->taxPercentage = $orderable->taxPercentage();
        $this->unitPrice = $orderable->unitPrice();
        $this->description = $orderable->description();

        return $this;
    }

    public function unitPrice(Money $unitPrice): ChargeItemBuilder
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function description(string $description): ChargeItemBuilder
    {
        $this->description = $description;

        return $this;
    }

    public function taxPercentage(float $taxPercentage): ChargeItemBuilder
    {
        $this->taxPercentage = $taxPercentage;

        return $this;
    }

    public function quantity(int $quantity): ChargeItemBuilder
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function make(): ChargeItem
    {
        return new ChargeItem(
            orderable: $this->orderable,
            owner: $this->owner,
            unitPrice: $this->unitPrice,
            description: $this->description,
            quantity: $this->quantity,
            taxPercentage: $this->taxPercentage
        );
    }
}
