<?php

namespace App\Http\Requests;

use App\Enums\UnitOfMeasure;
use App\Models\CatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'unit' => ['nullable', new Enum(UnitOfMeasure::class)],
            'isActive' => ['nullable', 'boolean'],
            'items' => ['sometimes', 'array'],
            'items.*' => ['array:type,id,quantity'],
            'items.*.type' => ['required_with:items', 'string', 'in:product,service,bundle'],
            'items.*.id' => ['required_with:items', 'integer', 'min:1'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'taxes' => ['nullable', 'array'],
            'taxes.*.taxId' => ['required', 'integer', 'exists:taxes,id'],
            'taxes.*.rate' => ['required', 'numeric', 'min:0'],
            'taxes.*.startAt' => ['required', 'date'],
            'taxes.*.endAt' => ['nullable', 'date', 'after_or_equal:taxes.*.startAt'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $currency = $this->input('currency');
        $unit = $this->input('unit');
        $payload = [];

        if ($this->has('currency')) {
            $currency = $currency !== null ? strtoupper(trim((string) $currency)) : null;
            $payload['currency'] = $currency !== '' ? $currency : null;
        }

        if ($this->has('unit')) {
            $unit = $unit !== null ? trim((string) $unit) : null;
            $payload['unit'] = $unit !== '' ? $unit : null;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->validatedItems();

            if ($items === []) {
                return;
            }

            $bundleId = (int) ($this->route('bundle')?->id ?? 0);
            $seen = [];

            foreach ($items as $index => $item) {
                $key = $item['type'].':'.$item['id'];

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "items.{$index}.id",
                        'Este item ya fue agregado al paquete.'
                    );

                    continue;
                }

                $seen[$key] = true;

                if ($item['type'] === 'bundle' && $bundleId > 0 && $item['id'] === $bundleId) {
                    $validator->errors()->add(
                        "items.{$index}.id",
                        'Un paquete no puede agregarse a sí mismo.'
                    );

                    continue;
                }

                $exists = CatalogItem::query()
                    ->where('id', $item['id'])
                    ->where('type', $item['type'])
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add(
                        "items.{$index}.id",
                        'El item seleccionado no existe para el tipo indicado.'
                    );
                }
            }
        });
    }

    /**
     * @return array<int, array{type: string, id: int, quantity: int}>
     */
    private function validatedItems(): array
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'type' => (string) ($item['type'] ?? ''),
                'id' => (int) ($item['id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
            ])
            ->values()
            ->all();
    }
}
