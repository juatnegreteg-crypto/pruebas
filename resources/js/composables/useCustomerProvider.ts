export type CustomerOption = {
    id: number;
    fullName: string;
    documentNumber: string | null;
    email: string;
};

type CustomersIndexResponse = {
    data?: CustomerOption[];
};

type SearchCustomersOptions = {
    term: string;
    perPage?: number;
    hasVehicles?: boolean;
};

export function useCustomerProvider() {
    async function searchCustomers(
        options: SearchCustomersOptions,
    ): Promise<CustomerOption[]> {
        const term = options.term;
        const perPage = options.perPage ?? 10;
        const trimmed = term.trim();
        const qs = new URLSearchParams();
        qs.set('perPage', String(perPage));
        if (trimmed) {
            qs.set('q', trimmed);
        }
        if (options.hasVehicles) {
            qs.set('filter[hasVehicles]', 'true');
        }
        qs.set('sort', 'fullName');

        const res = await fetch(`/api/v1/customers?${qs.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            throw new Error('customers_search_failed');
        }

        const payload = (await res.json()) as CustomersIndexResponse;
        return payload?.data ?? [];
    }

    return {
        searchCustomers,
    };
}
