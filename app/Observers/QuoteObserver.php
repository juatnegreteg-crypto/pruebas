<?php

namespace App\Observers;

use App\Models\Quote;

class QuoteObserver
{
    public function saving(Quote $quote): void
    {
        // Only recalculate totals if the quote exists (has an ID)
        // This prevents errors when trying to access items on non-existent quotes
        if ($quote->exists) {
            $quote->subtotal = $quote->items()->sum('subtotal');
            $quote->tax_total = $quote->items()->sum('tax_total');
            $quote->total = $quote->items()->sum('total');
        }
    }
}
