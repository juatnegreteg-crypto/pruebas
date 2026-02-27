export type VehicleOptionCustomer = {
    id: number;
    name?: string | null;
    fullName: string;
    documentNumber?: string | null;
};

export type VehicleOption = {
    id: number;
    customerId: number;
    customer: VehicleOptionCustomer | null;
    plate: string;
    make: string;
    model: string;
    year: number;
};

type VehiclesIndexResponse = {
    data?: VehicleOption[];
};

type SearchVehiclesOptions = {
    term: string;
    perPage?: number;
    customerId?: number;
    query?: Record<string, string | number | boolean | null | undefined>;
};

export function useVehicleProvider() {
    async function searchVehicles(
        options: SearchVehiclesOptions,
    ): Promise<VehicleOption[]> {
        const term = options.term;
        const perPage = options.perPage ?? 10;
        const trimmed = term.trim();

        const qs = new URLSearchParams();
        qs.set('perPage', String(perPage));
        if (trimmed) {
            qs.set('q', trimmed);
        }
        if (options.customerId) {
            qs.set('filter[customerId]', String(options.customerId));
        }
        if (options.query) {
            for (const [key, value] of Object.entries(options.query)) {
                if (value == null) {
                    continue;
                }
                qs.set(key, String(value));
            }
        }

        const res = await fetch(`/api/v1/vehicles?${qs.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            throw new Error('vehicles_search_failed');
        }

        const payload = (await res.json()) as VehiclesIndexResponse;
        return payload?.data ?? [];
    }

    return {
        searchVehicles,
    };
}
