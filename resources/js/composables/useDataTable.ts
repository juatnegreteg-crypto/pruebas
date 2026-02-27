import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

type UseDataTableOptions = {
    route: string | (() => string);
    initialSearch?: string;
    initialPerPage?: number;
    searchKey?: string;
    perPageKey?: string;
    debounceMs?: number;
    autoSearch?: boolean;
    preserveState?: boolean;
    preserveScroll?: boolean;
    replace?: boolean;
    getQuery?: () => Record<string, unknown>;
};

type QueryOverrides = {
    search?: string;
    perPage?: number;
};

export function useDataTable(options: UseDataTableOptions) {
    const searchKey = options.searchKey ?? 'search';
    const perPageKey = options.perPageKey ?? 'per_page';
    const search = ref(options.initialSearch ?? '');
    const perPage = ref(options.initialPerPage ?? 15);
    const autoSearch = options.autoSearch ?? Boolean(options.debounceMs);
    const debounceMs = options.debounceMs ?? 0;
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;

    function resolveRoute(): string {
        return typeof options.route === 'function'
            ? options.route()
            : options.route;
    }

    function buildQuery(
        overrides: QueryOverrides = {},
    ): Record<string, unknown> {
        const extra = options.getQuery?.() ?? {};
        const nextSearch = overrides.search ?? search.value;
        const nextPerPage = overrides.perPage ?? perPage.value;
        const trimmed = nextSearch.trim();

        return {
            ...extra,
            [perPageKey]: nextPerPage,
            ...(trimmed ? { [searchKey]: trimmed } : {}),
        };
    }

    function visit(overrides: QueryOverrides = {}): void {
        router.get(resolveRoute(), buildQuery(overrides), {
            preserveState: options.preserveState ?? true,
            preserveScroll: options.preserveScroll ?? true,
            replace: options.replace ?? true,
        });
    }

    function setSearch(value: string): void {
        search.value = value;
    }

    function updateSearch(value: string): void {
        search.value = value;
        if (!autoSearch) {
            return;
        }

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        if (debounceMs <= 0) {
            visit({ search: value });
            return;
        }

        debounceTimer = setTimeout(() => {
            visit({ search: value });
        }, debounceMs);
    }

    function applySearch(): void {
        visit({ search: search.value });
    }

    function clearSearch(): void {
        search.value = '';
        if (autoSearch) {
            visit({ search: '' });
        }
    }

    function updatePerPage(value: number): void {
        perPage.value = value;
        visit({ perPage: value });
    }

    const perPageSelection = computed({
        get: () => String(perPage.value),
        set: (value) => {
            updatePerPage(Number(value));
        },
    });

    onBeforeUnmount(() => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
    });

    return {
        search,
        perPage,
        perPageSelection,
        setSearch,
        updateSearch,
        applySearch,
        clearSearch,
        updatePerPage,
    };
}
