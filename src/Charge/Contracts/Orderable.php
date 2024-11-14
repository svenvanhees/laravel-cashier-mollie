<?php

namespace Laravel\Cashier\Charge\Contracts;

use Illuminate\Database\Eloquent\Model;
use Money\Money;

interface Orderable
{
    public function unitPrice(): Money;
    public function description(): string;
}
