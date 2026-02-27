<?php

namespace App\Observers;

use App\Models\Quote;
use App\Models\QuoteItem;

class QuoteItemObserver
{
    public function saving(QuoteItem $item): void
    {
        $item->subtotal = $item->quantity * $item->unit_price;
        $item->tax_total = $item->subtotal * ($item->tax_rate / 100);
        $item->total = $item->subtotal + $item->tax_total;
    }

    public function saved(QuoteItem $item): void
    {
        $this->syncQuoteTotals($item);
    }

    public function deleted(QuoteItem $item): void
    {
        $this->syncQuoteTotals($item);
    }

    public function restored(QuoteItem $item): void
    {
        $this->syncQuoteTotals($item);
    }

    private function syncQuoteTotals(QuoteItem $item): void
    {
        $quote = Quote::query()->find($item->quote_id);

        if (! $quote) {
            return;
        }

        $quote->subtotal = $quote->items()->sum('subtotal');
        $quote->tax_total = $quote->items()->sum('tax_total');
        $quote->total = $quote->items()->sum('total');
        $quote->saveQuietly();
    }
}
