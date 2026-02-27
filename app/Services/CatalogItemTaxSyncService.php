<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CatalogItemTaxSyncService
{
    /**
     * @param  array<int, array<string, mixed>>|null  $taxes
     */
    public function sync(CatalogItem $catalogItem, ?array $taxes): void
    {
        if ($taxes === null) {
            return;
        }

        $normalized = collect($taxes)
            ->filter(fn ($tax): bool => is_array($tax))
            ->map(function (array $tax, int $index): array {
                $startAt = $this->toDate(Arr::get($tax, 'startAt'));
                $endAt = $this->toDate(Arr::get($tax, 'endAt'));

                return [
                    'index' => $index,
                    'tax_id' => (int) Arr::get($tax, 'taxId'),
                    'start_date' => $startAt,
                    'end_date' => $endAt,
                ];
            })
            ->values();

        $ratesByTaxId = $this->ratesByTaxId($normalized->pluck('tax_id')->unique()->all());
        $normalized = $normalized
            ->map(function (array $tax) use ($ratesByTaxId): array {
                $tax['rate'] = (float) ($ratesByTaxId[$tax['tax_id']] ?? 0);

                return $tax;
            })
            ->values();

        $this->ensureNoOverlaps($normalized);

        $catalogItem->taxRates()->delete();

        $payload = $normalized
            ->map(fn (array $tax): array => Arr::except($tax, ['index']))
            ->all();

        if ($payload !== []) {
            $catalogItem->taxRates()->createMany($payload);
        }
    }

    /**
     * @param  int[]  $taxIds
     * @return array<int, string>
     */
    private function ratesByTaxId(array $taxIds): array
    {
        if ($taxIds === []) {
            return [];
        }

        return Tax::query()
            ->whereIn('id', $taxIds)
            ->pluck('rate', 'id')
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{index:int,tax_id:int,rate:float,start_date:Carbon,end_date:Carbon|null}>  $taxes
     */
    private function ensureNoOverlaps($taxes): void
    {
        $errors = [];

        $taxes
            ->groupBy('tax_id')
            ->each(function ($group) use (&$errors): void {
                $sorted = $group
                    ->sortBy('start_date')
                    ->values();

                $previousEnd = null;
                $previousIndex = null;
                $previousOpen = false;

                foreach ($sorted as $tax) {
                    if ($previousIndex !== null) {
                        $hasOverlap = $previousOpen
                            || ($previousEnd !== null && $previousEnd->gte($tax['start_date']));

                        if ($hasOverlap) {
                            $errors["taxes.{$tax['index']}.startAt"][] =
                                'El rango de fechas se solapa con otro impuesto del mismo tipo.';
                            $errors["taxes.{$previousIndex}.endAt"][] =
                                'El rango de fechas se solapa con otro impuesto del mismo tipo.';
                        }
                    }

                    $previousEnd = $tax['end_date'] ?? null;
                    $previousIndex = $tax['index'];
                    $previousOpen = $tax['end_date'] === null;
                }
            });

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->startOfDay();
    }
}
