<?php

namespace Laravel\Cashier\FirstPayment\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Charge\Contracts\Orderable;
use Money\Money;

class AddGenericOrderItem extends BaseAction
{
    /**
     * AddGenericOrderItem constructor.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @param  \Money\Money  $unitPrice
     * @param  int  $quantity
     * @param  string  $description
     * @param  int  $roundingMode
     * @param \Laravel\Cashier\Charge\Contracts\Orderable $owner |null
     */
    public function __construct(Model $owner, Money $unitPrice, int $quantity, string $description, int $roundingMode = Money::ROUND_HALF_UP, ?Orderable $orderable = null)
    {
        $this->orderable = $orderable;
        $this->owner = $owner;
        $this->taxPercentage = $this->owner->taxPercentage();
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
        $this->currency = $unitPrice->getCurrency()->getCode();
        $this->description = $description;
        $this->roundingMode = $roundingMode;
    }

    /**
     * @param  array  $payload
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @return self
     */
    public static function createFromPayload(array $payload, Model $owner)
    {
        Log::info('AddGenericOrderItem::createFromPayload: ' . json_encode($payload));
        $orderableId = $payload['orderable_id'] ?? null;
        $orderableType = $payload['orderable_type'] ?? null;

        $orderable = null;
        if ($orderableId && $orderableType && class_exists($orderableType)) {
            $orderable = $orderableType::find($orderableId);
        }

        $taxPercentage = $payload['taxPercentage'] ?? 0;
        $quantity = $payload['quantity'] ?? 1;
        $unit_price = $payload['subtotal'] ?? $payload['unit_price'];

        return (new static(
            orderable: $orderable,
            owner: $owner,
            unitPrice: mollie_array_to_money($unit_price),
            quantity: $quantity,
            description: $payload['description']
        ))->withTaxPercentage($taxPercentage);
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return [
            'handler' => static::class,
            'orderable_id' => $this->getOrderable()->model()?->id,
            'orderable_type' => $this->getOrderable()->model()?->getMorphClass(),
            'description' => $this->getDescription(),
            'unit_price' => money_to_mollie_array($this->getUnitPrice()),
            'quantity' => $this->getQuantity(),
            'taxPercentage' => $this->getTaxPercentage(),
        ];
    }

    /**
     * Prepare a stub of OrderItems processed with the payment.
     *
     * @return \Laravel\Cashier\Order\OrderItemCollection
     */
    public function makeProcessedOrderItems()
    {
        return $this->owner->orderItems()->make([
            'orderable_id' => $this->getOrderable()->model()->id,
            'orderable_type' => $this->getOrderable()->model()->getMorphClass(),
            'description' => $this->getDescription(),
            'currency' => $this->getCurrency(),
            'process_at' => now(),
            'unit_price' => $this->getUnitPrice()->getAmount(),
            'tax_percentage' => $this->getTaxPercentage(),
            'quantity' => $this->getQuantity(),
        ])->toCollection();
    }

    /**
     * Execute this action and return the created OrderItemCollection.
     *
     * @return \Laravel\Cashier\Order\OrderItemCollection
     */
    public function execute()
    {
        return tap($this->makeProcessedOrderItems(), function ($items) {
            $items->save();
        });
    }
}
