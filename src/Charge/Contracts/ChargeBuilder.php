<?php

namespace Laravel\Cashier\Charge\Contracts;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Charge\ChargeItem;
use Laravel\Cashier\Charge\ChargeItemCollection;

interface ChargeBuilder
{
    public static function for(Orderable $orderable, Model $billable): self;

    public function addItem(ChargeItem $item): self;

    public function setItems(ChargeItemCollection $items): self;

    public function setRedirectUrl(string $redirectUrl): self;

    public function create();
}
