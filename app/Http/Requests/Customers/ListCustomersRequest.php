<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sortParam = (string) $this->query('sort', '');
        $directionParam = (string) $this->query('direction', '');

        $sort = null;
        $direction = null;

        if ($directionParam !== '') {
            $sort = $this->normalizeSortField($sortParam);
            $direction = strtolower($directionParam);
        } elseif ($sortParam !== '') {
            $firstSort = explode(',', $sortParam)[0];
            $direction = str_starts_with($firstSort, '-') ? 'desc' : 'asc';
            $sort = $this->normalizeSortField(ltrim($firstSort, '-'));
        }

        $search = $this->string('q')->toString();
        if ($search === '') {
            $search = $this->string('search')->toString();
        }

        $this->merge([
            'search' => $search !== '' ? $search : null,
            'sort' => $sort ?? 'created_at',
            'direction' => $direction ?? 'desc',
        ]);
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'has_vehicles' => ['nullable', 'boolean'],
            'filter.hasVehicles' => ['nullable', 'boolean'],
            'sort' => ['required', 'string', 'in:full_name,email,document_number,created_at'],
            'direction' => ['required', 'string', 'in:asc,desc'],
        ];
    }

    public function searchTerm(): ?string
    {
        return $this->validated('search');
    }

    public function hasVehiclesFilter(): bool
    {
        $filterHasVehicles = $this->input('filter.hasVehicles');
        if ($filterHasVehicles !== null) {
            return filter_var($filterHasVehicles, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return (bool) $this->boolean('has_vehicles');
    }

    private function normalizeSortField(string $field): string
    {
        return match ($field) {
            'fullName' => 'full_name',
            'documentNumber' => 'document_number',
            'createdAt' => 'created_at',
            default => $field,
        };
    }
}
