<?php

namespace App\Http\Resources;

use App\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read QuoteItem $resource
 */
class QuoteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quoteItem = $this->resource;

        return [
            'id' => $quoteItem->id,
            'quoteId' => $quoteItem->quote_id,
            'itemableType' => $quoteItem->itemable_type,
            'itemableId' => $quoteItem->itemable_id,
            'description' => $quoteItem->description,
            'quantity' => $quoteItem->quantity,
            'unitPrice' => $quoteItem->unit_price,
            'taxRate' => $quoteItem->tax_rate,
            'subtotal' => $quoteItem->subtotal,
            'taxTotal' => $quoteItem->tax_total,
            'total' => $quoteItem->total,
        ];
    }
}
