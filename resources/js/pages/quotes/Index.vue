<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Ban,
    Check,
    ChevronDown,
    FileText,
    Minus,
    Plus,
} from 'lucide-vue-next';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';

import type { BreadcrumbItem } from '@/types';
import { Actions } from '@/types/actions/action-map';
import AlertError from '@/components/AlertError.vue';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTable from '@/components/AppDataTable.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import VehiclePickerField from '@/components/VehiclePickerField.vue';
import { useCurrencyFormat } from '@/composables/useCurrencyFormat';
import { useDataTable } from '@/composables/useDataTable';

import type {
    VehicleOption,
    VehicleOptionCustomer,
} from '@/composables/useVehicleProvider';

import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import {
    index as quotesIndex,
    show as quoteShow,
    pdf as quotePdf,
} from '@/routes/quotes';
import { view as quotePdfView } from '@/routes/quotes/pdf';

type Quote = {
    id: number;
    vehicleId: number | null;
    customer: VehicleOptionCustomer | null;
    vehicle: VehicleOption | null;
    status: string;
    subtotal: string;
    taxTotal: string;
    total: string;
    itemsCount: number;
    createdAt: string;
    updatedAt: string;
};

type CatalogItem = {
    id: number;
    name: string;
    price: string;
    type: 'product' | 'service' | 'bundle';
    taxes: Array<{
        name?: string | null;
        code?: string | null;
        rate: number | string;
        startAt: string | null;
        endAt: string | null;
    }>;
};

type QuoteFormItem = {
    itemable_type: string;
    itemable_id: number;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    tax_labels: string[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type QuotesPayload = {
    data: Quote[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

type Props = {
    quotes: QuotesPayload;
    filters?: {
        q?: string;
    };
};

const props = defineProps<Props>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormat();
const QUOTES_EXPORT_ENDPOINT = '/api/v1/quotes/export';
const QUOTES_DETAILED_EXPORT_ENDPOINT = '/api/v1/quotes/export-detailed';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('quotes.title'),
        href: quotesIndex().url,
    },
];

const table = useDataTable({
    route: () => quotesIndex(),
    initialSearch: props.filters?.q ?? '',
    initialPerPage: props.quotes.meta.per_page ?? 15,
    searchKey: 'q',
    perPageKey: 'perPage',
    autoSearch: false,
});
const columns = computed(
    () =>
        [
            { key: 'id', header: t('quotes.table.id') },
            { key: 'customer', header: t('quotes.table.customer') },
            { key: 'vehicle', header: t('quotes.table.vehicle') },
            { key: 'date', header: t('quotes.table.date') },
            { key: 'status', header: t('quotes.table.status') },
            { key: 'items', header: t('quotes.table.items'), align: 'center' },
            { key: 'total', header: t('quotes.table.total'), align: 'right' },
            {
                key: 'actions',
                header: t('quotes.table.actions'),
                align: 'right',
            },
        ] as const,
);
const slotNames = {
    id: 'cell(id)',
    customer: 'cell(customer)',
    vehicle: 'cell(vehicle)',
    date: 'cell(date)',
    status: 'cell(status)',
    items: 'cell(items)',
    total: 'cell(total)',
    actions: 'cell(actions)',
    empty: 'empty()',
} as const;
const isLoading = ref(false);
const isExporting = ref(false);
const isExportingDetailed = ref(false);
const hasError = computed(
    () => !props.quotes || !Array.isArray(props.quotes.data),
);
const actionErrorMessages = ref<string[]>([]);

// Create form state
const isCreateOpen = ref(false);
const isSubmitting = ref(false);
const catalogItems = ref<CatalogItem[]>([]);
const isItemPickerOpen = ref(false);
const itemSearchQuery = ref('');
const itemSearchInput = ref<HTMLInputElement | null>(null);
const associationResetNonce = ref(0);
const selectedCustomer = ref<VehicleOptionCustomer | null>(null);
const selectedVehicle = ref<VehicleOption | null>(null);
const selectedItemId = ref<string>('');
const itemQuantity = ref(1);
const formItems = ref<QuoteFormItem[]>([]);
const formErrors = ref<Record<string, string>>({});
const formErrorMessages = ref<string[]>([]);

// Action states
const processingQuoteId = ref<number | null>(null);

const paginationLinks = computed(() => props.quotes.meta.links ?? []);

const selectedCatalogItem = computed(() => {
    if (!selectedItemId.value) {
        return null;
    }

    const [type, id] = selectedItemId.value.split(':');
    if (!type || !id) {
        return null;
    }

    return (
        catalogItems.value.find(
            (item) => item.type === type && item.id === Number(id),
        ) ?? null
    );
});

const filteredCatalogItems = computed(() => {
    const term = itemSearchQuery.value.trim().toLowerCase();
    if (!term) {
        return catalogItems.value;
    }

    return catalogItems.value.filter((item) =>
        `${item.name} ${item.type}`.toLowerCase().includes(term),
    );
});

const filteredQuotes = computed(() => {
    return props.quotes.data;
});

function todayString(): string {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function isActiveTaxRange(
    tax: { startAt: string | null; endAt: string | null },
    today: string,
): boolean {
    const start = tax.startAt ?? today;
    const end = tax.endAt ?? '9999-12-31';
    return start <= today && today <= end;
}

function activeTaxes(item: CatalogItem): CatalogItem['taxes'] {
    const today = todayString();
    return item.taxes.filter((tax) => isActiveTaxRange(tax, today));
}

function suggestedTaxRate(item: CatalogItem): number {
    return activeTaxes(item).reduce(
        (total, tax) => total + Number(tax.rate || 0),
        0,
    );
}

function suggestedTaxLabels(item: CatalogItem): string[] {
    return activeTaxes(item)
        .map((tax) => tax.name || tax.code)
        .filter((label): label is string => Boolean(label));
}

const summary = computed(() => {
    if (!props.quotes.meta.total) {
        return t('quotes.summaryEmpty');
    }

    return t('quotes.summary', {
        from: props.quotes.meta.from ?? 0,
        to: props.quotes.meta.to ?? 0,
        total: props.quotes.meta.total,
    });
});

const formTotals = computed(() => {
    let subtotal = 0;
    let taxTotal = 0;

    for (const item of formItems.value) {
        const itemSubtotal = item.quantity * item.unit_price;
        const itemTax = itemSubtotal * (item.tax_rate / 100);
        subtotal += itemSubtotal;
        taxTotal += itemTax;
    }

    return {
        subtotal,
        taxTotal,
        total: subtotal + taxTotal,
    };
});

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function getStatusVariant(
    status: string,
): 'secondary' | 'default' | 'destructive' {
    switch (status) {
        case 'draft':
            return 'secondary';
        case 'confirmed':
            return 'default';
        case 'cancelled':
            return 'destructive';
        default:
            return 'secondary';
    }
}

function applySearch() {
    table.applySearch();
}

function clearSearch() {
    table.clearSearch();
    table.applySearch();
}

function retryLoad() {
    router.reload({ only: ['quotes'] });
}

function paginationLabel(label: string) {
    const normalized = label
        .replace(/&laquo;|&raquo;|&lsaquo;|&rsaquo;|«|»/g, '')
        .trim();

    if (normalized.toLowerCase().includes('previous')) {
        return t('common.previous');
    }

    if (normalized.toLowerCase().includes('next')) {
        return t('common.next');
    }

    return normalized;
}

// Create form functions
async function loadCatalogItems() {
    try {
        const [productsRes, servicesRes, bundlesRes] = await Promise.all([
            fetch('/api/v1/products?perPage=100'),
            fetch('/api/v1/services?perPage=100'),
            fetch('/api/v1/bundles?perPage=100'),
        ]);

        const products = await productsRes.json();
        const services = await servicesRes.json();
        const bundles = await bundlesRes.json();

        catalogItems.value = [
            ...products.data.map(
                (p: {
                    id: number;
                    name: string;
                    price: string;
                    taxes?: CatalogItem['taxes'];
                }) => ({
                    id: p.id,
                    name: p.name,
                    price: p.price,
                    type: 'product' as const,
                    taxes: p.taxes ?? [],
                }),
            ),
            ...services.data.map(
                (s: {
                    id: number;
                    name: string;
                    price: string;
                    taxes?: CatalogItem['taxes'];
                }) => ({
                    id: s.id,
                    name: s.name,
                    price: s.price,
                    type: 'service' as const,
                    taxes: s.taxes ?? [],
                }),
            ),
            ...bundles.data.map(
                (b: {
                    id: number;
                    name: string;
                    price: string;
                    taxes?: CatalogItem['taxes'];
                }) => ({
                    id: b.id,
                    name: b.name,
                    price: b.price,
                    type: 'bundle' as const,
                    taxes: b.taxes ?? [],
                }),
            ),
        ];
    } catch {
        formErrorMessages.value = [t('quotes.form.errors.generic')];
    }
}

function clearAssociation() {
    selectedCustomer.value = null;
    selectedVehicle.value = null;
    associationResetNonce.value += 1;
}

function getItemableType(type: string): string {
    switch (type) {
        case 'product':
            return 'App\\Models\\Product';
        case 'service':
            return 'App\\Models\\Service';
        case 'bundle':
            return 'App\\Models\\Bundle';
        default:
            return '';
    }
}

function addItemToForm() {
    if (!selectedItemId.value) return;

    const [type, id] = selectedItemId.value.split(':');
    const item = catalogItems.value.find(
        (i) => i.type === type && i.id === parseInt(id),
    );

    if (!item) return;

    formItems.value.push({
        itemable_type: getItemableType(item.type),
        itemable_id: item.id,
        description: item.name,
        quantity: itemQuantity.value,
        unit_price: parseFloat(item.price),
        tax_rate: suggestedTaxRate(item),
        tax_labels: suggestedTaxLabels(item),
    });

    selectedItemId.value = '';
    itemQuantity.value = 1;
}

function selectCatalogItem(item: CatalogItem) {
    selectedItemId.value = `${item.type}:${item.id}`;
    itemSearchQuery.value = '';
    isItemPickerOpen.value = false;
}

function removeItemFromForm(index: number) {
    formItems.value.splice(index, 1);
}

function updateItemQuantity(index: number, delta: number) {
    const newQty = formItems.value[index].quantity + delta;
    if (newQty >= 1) {
        formItems.value[index].quantity = newQty;
    }
}

function resetCreateForm() {
    formItems.value = [];
    clearAssociation();
    selectedItemId.value = '';
    itemQuantity.value = 1;
    formErrors.value = {};
    formErrorMessages.value = [];
}

async function submitCreate() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!selectedVehicle.value?.id) {
        formErrorMessages.value = [t('quotes.form.errors.noVehicle')];
        return;
    }

    if (formItems.value.length === 0) {
        formErrorMessages.value = [t('quotes.form.errors.noItems')];
        return;
    }

    isSubmitting.value = true;

    try {
        const response = await fetch('/api/v1/quotes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                vehicle_id: selectedVehicle.value.id,
                items: formItems.value,
            }),
        });

        if (response.status === 422) {
            const data = await response.json();
            const errors = data?.errors ?? {};

            Object.keys(errors).forEach((key) => {
                formErrors.value[key] = errors[key]?.[0] ?? '';
            });

            return;
        }

        if (!response.ok) {
            formErrorMessages.value = [t('quotes.form.errors.generic')];
            return;
        }

        resetCreateForm();
        isCreateOpen.value = false;
        router.reload({ only: ['quotes'] });
    } catch {
        formErrorMessages.value = [t('quotes.form.errors.generic')];
    } finally {
        isSubmitting.value = false;
    }
}

async function confirmQuote(quote: Quote) {
    if (processingQuoteId.value) return;

    actionErrorMessages.value = [];
    processingQuoteId.value = quote.id;

    try {
        const response = await fetch(`/api/v1/quotes/${quote.id}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            actionErrorMessages.value = [t('quotes.actions.errorTitle')];
            return;
        }

        router.reload({ only: ['quotes'] });
    } catch {
        actionErrorMessages.value = [t('quotes.actions.errorTitle')];
    } finally {
        processingQuoteId.value = null;
    }
}

async function cancelQuote(quote: Quote) {
    if (processingQuoteId.value) return;

    actionErrorMessages.value = [];
    processingQuoteId.value = quote.id;

    try {
        const response = await fetch(`/api/v1/quotes/${quote.id}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            actionErrorMessages.value = [t('quotes.actions.errorTitle')];
            return;
        }

        router.reload({ only: ['quotes'] });
    } catch {
        actionErrorMessages.value = [t('quotes.actions.errorTitle')];
    } finally {
        processingQuoteId.value = null;
    }
}

async function exportQuotes(): Promise<void> {
    if (isExporting.value) {
        return;
    }

    actionErrorMessages.value = [];
    isExporting.value = true;

    try {
        const response = await fetch(QUOTES_EXPORT_ENDPOINT, {
            method: 'GET',
            headers: {
                Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            actionErrorMessages.value = [t('quotes.actions.exportError')];
            return;
        }

        const contentDisposition = response.headers.get('content-disposition');
        const filenameMatch =
            contentDisposition?.match(/filename="?([^"]+)"?$/i) ?? null;
        const filename = filenameMatch?.[1] ?? 'cotizaciones.xlsx';

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);
        window.URL.revokeObjectURL(url);
    } catch {
        actionErrorMessages.value = [t('quotes.actions.exportError')];
    } finally {
        isExporting.value = false;
    }
}

async function exportDetailedQuotes(): Promise<void> {
    if (isExportingDetailed.value) {
        return;
    }

    actionErrorMessages.value = [];
    isExportingDetailed.value = true;

    try {
        const response = await fetch(QUOTES_DETAILED_EXPORT_ENDPOINT, {
            method: 'GET',
            headers: {
                Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            actionErrorMessages.value = [t('quotes.actions.exportError')];
            return;
        }

        const contentDisposition = response.headers.get('content-disposition');
        const filenameMatch =
            contentDisposition?.match(/filename="?([^"]+)"?$/i) ?? null;
        const filename = filenameMatch?.[1] ?? 'cotizaciones-detallado.xlsx';

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);
        window.URL.revokeObjectURL(url);
    } catch {
        actionErrorMessages.value = [t('quotes.actions.exportError')];
    } finally {
        isExportingDetailed.value = false;
    }
}

const onStart = () => {
    isLoading.value = true;
};
const onFinish = () => {
    isLoading.value = false;
};
let unsubscribeStart: (() => void) | null = null;
let unsubscribeFinish: (() => void) | null = null;
let unsubscribeError: (() => void) | null = null;

onMounted(() => {
    unsubscribeStart = router.on('start', onStart);
    unsubscribeFinish = router.on('finish', onFinish);
    unsubscribeError = router.on('error', onFinish);
    loadCatalogItems();
});

onBeforeUnmount(() => {
    unsubscribeStart?.();
    unsubscribeFinish?.();
    unsubscribeError?.();
    unsubscribeStart = null;
    unsubscribeFinish = null;
    unsubscribeError = null;
});

watch(isItemPickerOpen, (open) => {
    if (open) {
        nextTick(() => itemSearchInput.value?.focus());
    } else {
        itemSearchQuery.value = '';
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('quotes.title')" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        {{ t('quotes.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('quotes.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <AppActionButton
                        :action="Actions.export"
                        :label-key="'quotes.actions.exportDetailedXls'"
                        :loading-label-key="'quotes.actions.exportingDetailedXls'"
                        :loading="isExportingDetailed"
                        :disabled="isExportingDetailed"
                        class="cursor-pointer"
                        @click="exportDetailedQuotes"
                    />
                    <AppActionButton
                        :action="Actions.export"
                        :label-key="'quotes.actions.exportXls'"
                        :loading-label-key="'quotes.actions.exportingXls'"
                        :loading="isExporting"
                        :disabled="isExporting"
                        @click="exportQuotes"
                    />
                    <Dialog
                        v-model:open="isCreateOpen"
                        @update:open="(open) => !open && resetCreateForm()"
                    >
                        <DialogTrigger as-child>
                            <AppActionButton
                                :action="Actions.create"
                                :label-key="'quotes.form.open'"
                            />
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('quotes.form.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('quotes.form.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('quotes.form.errors.title')"
                                />

                                <!-- Customer / Vehicle association -->
                                <VehiclePickerField
                                    v-model:customer="selectedCustomer"
                                    v-model:vehicle="selectedVehicle"
                                    :reset-nonce="associationResetNonce"
                                    :error-message="
                                        t('quotes.form.errors.generic')
                                    "
                                    @error="
                                        (message) =>
                                            (formErrorMessages = [message])
                                    "
                                />

                                <!-- Item selector -->
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <Label for="item-select">{{
                                            t('quotes.form.selectItem')
                                        }}</Label>
                                        <Popover
                                            v-model:open="isItemPickerOpen"
                                        >
                                            <PopoverTrigger as-child>
                                                <Button
                                                    id="item-select"
                                                    type="button"
                                                    variant="outline"
                                                    class="mt-1 h-10 w-full justify-between px-3 font-normal"
                                                    :class="
                                                        cn(
                                                            !selectedCatalogItem
                                                                ? 'text-muted-foreground'
                                                                : 'text-foreground',
                                                        )
                                                    "
                                                >
                                                    <span
                                                        class="truncate text-left"
                                                    >
                                                        <template
                                                            v-if="
                                                                selectedCatalogItem
                                                            "
                                                        >
                                                            {{
                                                                selectedCatalogItem.name
                                                            }}
                                                        </template>
                                                        <template v-else>
                                                            {{
                                                                t(
                                                                    'quotes.form.selectItemPlaceholder',
                                                                )
                                                            }}
                                                        </template>
                                                    </span>
                                                    <ChevronDown
                                                        class="ml-2 size-4 shrink-0 opacity-60"
                                                    />
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                align="start"
                                                class="w-[var(--reka-popover-trigger-width)] p-2"
                                            >
                                                <div class="grid gap-2">
                                                    <Input
                                                        ref="itemSearchInput"
                                                        v-model="
                                                            itemSearchQuery
                                                        "
                                                        :placeholder="
                                                            t(
                                                                'quotes.form.selectItemPlaceholder',
                                                            )
                                                        "
                                                    />
                                                </div>

                                                <div
                                                    class="mt-2 max-h-72 overflow-auto"
                                                >
                                                    <div
                                                        v-if="
                                                            !filteredCatalogItems.length
                                                        "
                                                        class="p-2 text-sm text-muted-foreground"
                                                    >
                                                        {{
                                                            t(
                                                                'common.noResults',
                                                            )
                                                        }}
                                                    </div>
                                                    <div v-else class="grid">
                                                        <button
                                                            v-for="item in filteredCatalogItems"
                                                            :key="`${item.type}:${item.id}`"
                                                            type="button"
                                                            class="rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                            @click="
                                                                selectCatalogItem(
                                                                    item,
                                                                )
                                                            "
                                                        >
                                                            <div
                                                                class="flex items-start justify-between gap-3"
                                                            >
                                                                <div>
                                                                    <div
                                                                        class="font-medium text-foreground"
                                                                    >
                                                                        {{
                                                                            item.name
                                                                        }}
                                                                    </div>
                                                                    <div
                                                                        class="text-xs text-muted-foreground"
                                                                    >
                                                                        {{
                                                                            formatCurrency(
                                                                                item.price,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </div>
                                                                <Badge
                                                                    variant="outline"
                                                                    class="shrink-0"
                                                                >
                                                                    {{
                                                                        t(
                                                                            `quotes.itemTypes.${item.type}`,
                                                                        )
                                                                    }}
                                                                </Badge>
                                                            </div>
                                                        </button>
                                                    </div>
                                                </div>
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                    <div class="w-24">
                                        <Label for="item-qty">
                                            {{ t('quotes.form.quantity') }}
                                        </Label>
                                        <Input
                                            id="item-qty"
                                            v-model.number="itemQuantity"
                                            type="number"
                                            min="1"
                                            class="mt-1"
                                        />
                                    </div>
                                    <div class="flex items-end">
                                        <Button
                                            type="button"
                                            size="sm"
                                            :disabled="!selectedItemId"
                                            @click="addItemToForm"
                                        >
                                            {{ t('quotes.form.addItem') }}
                                        </Button>
                                    </div>
                                </div>

                                <!-- Items list -->
                                <div class="rounded-md border">
                                    <div
                                        class="border-b px-4 py-2 text-sm font-medium"
                                    >
                                        {{ t('quotes.form.itemsTitle') }}
                                    </div>
                                    <div
                                        v-if="formItems.length === 0"
                                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                                    >
                                        {{ t('quotes.form.noItems') }}
                                    </div>
                                    <div v-else class="divide-y">
                                        <div
                                            v-for="(item, index) in formItems"
                                            :key="index"
                                            class="flex items-center justify-between gap-4 px-4 py-3"
                                        >
                                            <div class="flex-1">
                                                <p class="text-sm font-medium">
                                                    {{ item.description }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            item.unit_price,
                                                        )
                                                    }}
                                                    x {{ item.quantity }} =
                                                    {{
                                                        formatCurrency(
                                                            item.unit_price *
                                                                item.quantity,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        t(
                                                            'quotes.form.taxLabels',
                                                            {
                                                                labels: item
                                                                    .tax_labels
                                                                    .length
                                                                    ? item.tax_labels.join(
                                                                          ', ',
                                                                      )
                                                                    : t(
                                                                          'quotes.form.taxLabelsEmpty',
                                                                      ),
                                                            },
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="size-8"
                                                    :disabled="
                                                        item.quantity <= 1
                                                    "
                                                    @click="
                                                        updateItemQuantity(
                                                            index,
                                                            -1,
                                                        )
                                                    "
                                                >
                                                    <Minus class="size-4" />
                                                </Button>
                                                <span
                                                    class="w-8 text-center text-sm"
                                                >
                                                    {{ item.quantity }}
                                                </span>
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="size-8"
                                                    @click="
                                                        updateItemQuantity(
                                                            index,
                                                            1,
                                                        )
                                                    "
                                                >
                                                    <Plus class="size-4" />
                                                </Button>
                                                <AppActionIconButton
                                                    :action="Actions.remove"
                                                    :label-key="'quotes.form.actions.removeItem'"
                                                    variant="ghost"
                                                    class="size-8 text-destructive"
                                                    @click="
                                                        removeItemFromForm(
                                                            index,
                                                        )
                                                    "
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Totals -->
                                <div
                                    v-if="formItems.length > 0"
                                    class="rounded-md border bg-muted/50 p-4"
                                >
                                    <div class="flex justify-between text-sm">
                                        <span>Subtotal:</span>
                                        <span>{{
                                            formatCurrency(formTotals.subtotal)
                                        }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>
                                            {{ t('quotes.detail.tax') }}
                                        </span>
                                        <span>{{
                                            formatCurrency(formTotals.taxTotal)
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-2 flex justify-between border-t pt-2 text-lg font-bold"
                                    >
                                        <span>Total:</span>
                                        <span>{{
                                            formatCurrency(formTotals.total)
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="ghost" type="button">
                                        {{ t('quotes.form.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    :disabled="
                                        isSubmitting ||
                                        formItems.length === 0 ||
                                        !selectedVehicle
                                    "
                                    @click="submitCreate"
                                >
                                    {{
                                        isSubmitting
                                            ? t('quotes.form.saving')
                                            : t('quotes.form.save')
                                    }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <AppDataTableShell
                :title="t('quotes.listTitle')"
                :description="summary"
                :search="table.search.value"
                :search-label="t('quotes.searchLabel')"
                :search-placeholder="t('quotes.searchPlaceholder')"
                :per-page="table.perPage.value"
                :per-page-label="t('common.perPage')"
                @update:per-page="table.updatePerPage"
            >
                <template #search>
                    <form
                        class="flex flex-wrap items-end gap-2"
                        @submit.prevent="applySearch"
                    >
                        <div class="grid gap-1">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                for="quote-search"
                            >
                                {{ t('quotes.searchLabel') }}
                            </label>
                            <Input
                                id="quote-search"
                                v-model="table.search.value"
                                :placeholder="t('quotes.searchPlaceholder')"
                                class="min-w-55"
                            />
                        </div>
                        <Button
                            variant="secondary"
                            size="sm"
                            type="submit"
                            class="self-end"
                        >
                            {{ t('common.search') }}
                        </Button>
                        <Button
                            v-if="table.search"
                            variant="ghost"
                            size="sm"
                            type="button"
                            class="self-end"
                            @click="clearSearch"
                        >
                            {{ t('common.clear') }}
                        </Button>
                    </form>
                </template>

                <template #filters>
                    <div v-if="actionErrorMessages.length">
                        <AlertError
                            :errors="actionErrorMessages"
                            :title="t('quotes.actions.errorTitle')"
                        />
                    </div>
                </template>

                <template #cards>
                    <Card
                        v-for="quote in filteredQuotes"
                        :key="quote.id"
                        class="p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold">
                                    {{
                                        quote.customer?.fullName ??
                                        t('common.notAvailable')
                                    }}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{
                                        quote.vehicle?.plate ??
                                        t('common.notAvailable')
                                    }}
                                </p>
                            </div>
                            <AppRowActions>
                                <AppActionIconButton
                                    :action="Actions.view"
                                    :label-key="'quotes.actions.view'"
                                    as="link"
                                    :href="quoteShow({ quote: quote.id }).url"
                                />
                                <AppActionIconButton
                                    v-if="quote.status === 'draft'"
                                    :action="Actions.edit"
                                    :label-key="'quotes.actions.edit'"
                                    as="link"
                                    :href="
                                        quoteShow(
                                            { quote: quote.id },
                                            { query: { edit: 1 } },
                                        ).url
                                    "
                                />
                                <AppActionIconButton
                                    v-else
                                    :action="Actions.edit"
                                    :label-key="'quotes.actions.edit'"
                                    disabled
                                />
                                <AppActionIconButton
                                    :action="Actions.download"
                                    :label-key="'quotes.actions.downloadPdf'"
                                    as="a"
                                    :href="quotePdf({ quote: quote.id }).url"
                                />
                                <AppActionIconButton
                                    :action="Actions.more"
                                    :label-key="'quotes.actions.more'"
                                    :tooltip="false"
                                    disabled
                                />
                            </AppRowActions>
                        </div>
                        <div
                            class="mt-4 flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ t('quotes.table.status') }}
                            </span>
                            <Badge :variant="getStatusVariant(quote.status)">
                                {{ t(`quotes.status.values.${quote.status}`) }}
                            </Badge>
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ t('quotes.table.total') }}
                            </span>
                            <span class="font-medium">
                                {{ formatCurrency(quote.total) }}
                            </span>
                        </div>
                    </Card>

                    <Card v-if="!filteredQuotes.length" class="p-6">
                        <p class="text-center text-sm text-muted-foreground">
                            <span v-if="table.search">
                                {{
                                    t('quotes.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </span>
                            <span v-else>
                                {{ t('quotes.empty.noQuotes') }}
                            </span>
                        </p>
                    </Card>
                </template>

                <div class="overflow-x-auto">
                    <div
                        v-if="hasError"
                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        <p>{{ t('quotes.errors.load') }}</p>
                        <Button
                            variant="secondary"
                            size="sm"
                            class="mt-3"
                            @click="retryLoad"
                        >
                            {{ t('common.retry') }}
                        </Button>
                    </div>
                    <div
                        v-else-if="isLoading"
                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        {{ t('common.loading') }}
                    </div>
                    <AppDataTable
                        v-else
                        :rows="filteredQuotes"
                        :columns="columns"
                        row-key="id"
                    >
                        <template v-slot:[slotNames.id]="{ row }">
                            <Link
                                :href="quoteShow({ quote: row.id }).url"
                                class="font-medium text-foreground hover:underline"
                            >
                                #{{ row.id }}
                            </Link>
                        </template>

                        <template v-slot:[slotNames.customer]="{ row }">
                            <div
                                v-if="row.customer"
                                class="text-sm text-foreground"
                            >
                                {{ row.customer.fullName }}
                            </div>
                            <div v-else class="text-sm text-muted-foreground">
                                {{ t('common.notAvailable') }}
                            </div>
                        </template>

                        <template v-slot:[slotNames.vehicle]="{ row }">
                            <div
                                v-if="row.vehicle"
                                class="text-sm text-foreground"
                            >
                                {{ row.vehicle.plate }}
                            </div>
                            <div v-else class="text-sm text-muted-foreground">
                                {{ t('common.notAvailable') }}
                            </div>
                        </template>

                        <template v-slot:[slotNames.date]="{ row }">
                            <span class="text-muted-foreground">
                                {{ formatDate(row.createdAt) }}
                            </span>
                        </template>

                        <template v-slot:[slotNames.status]="{ row }">
                            <Badge :variant="getStatusVariant(row.status)">
                                {{ t(`quotes.status.values.${row.status}`) }}
                            </Badge>
                        </template>

                        <template v-slot:[slotNames.items]="{ row }">
                            <Badge variant="secondary">
                                {{ row.itemsCount }}
                            </Badge>
                        </template>

                        <template v-slot:[slotNames.total]="{ row }">
                            <span class="font-medium">
                                {{ formatCurrency(row.total) }}
                            </span>
                        </template>

                        <template v-slot:[slotNames.actions]="{ row }">
                            <div class="flex items-center justify-end gap-2">
                                <AppActionIconButton
                                    :action="Actions.view"
                                    :label-key="'quotes.actions.view'"
                                    variant="ghost"
                                    as="link"
                                    :href="quoteShow({ quote: row.id }).url"
                                />
                                <AppActionIconButton
                                    v-if="row.status === 'draft'"
                                    :action="Actions.edit"
                                    :label-key="'quotes.actions.edit'"
                                    variant="ghost"
                                    as="link"
                                    :href="
                                        quoteShow(
                                            { quote: row.id },
                                            { query: { edit: 1 } },
                                        ).url
                                    "
                                />
                                <AppActionIconButton
                                    v-else
                                    :action="Actions.edit"
                                    :label-key="'quotes.actions.edit'"
                                    variant="ghost"
                                    disabled
                                />
                                <AppActionIconButton
                                    :action="Actions.download"
                                    :label-key="'quotes.actions.downloadPdf'"
                                    variant="ghost"
                                    as="a"
                                    :href="quotePdf({ quote: row.id }).url"
                                />
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <AppActionIconButton
                                            :action="Actions.more"
                                            :label-key="'quotes.actions.more'"
                                            :tooltip="false"
                                            :disabled="
                                                processingQuoteId === row.id
                                            "
                                        />
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <a
                                                :href="
                                                    quotePdfView({
                                                        quote: row.id,
                                                    }).url
                                                "
                                                target="_blank"
                                            >
                                                <FileText class="mr-2 size-4" />
                                                {{
                                                    t('quotes.actions.viewPdf')
                                                }}
                                            </a>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator
                                            v-if="row.status !== 'cancelled'"
                                        />
                                        <DropdownMenuItem
                                            v-if="row.status === 'draft'"
                                            @click="confirmQuote(row)"
                                        >
                                            <Check class="mr-2 size-4" />
                                            {{ t('quotes.actions.confirm') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="row.status !== 'cancelled'"
                                            class="text-destructive"
                                            @click="cancelQuote(row)"
                                        >
                                            <Ban class="mr-2 size-4" />
                                            {{ t('quotes.actions.cancel') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </template>

                        <template v-slot:[slotNames.empty]>
                            <div v-if="table.search">
                                {{
                                    t('quotes.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </div>
                            <div v-else>
                                {{ t('quotes.empty.noQuotes') }}
                            </div>
                        </template>
                    </AppDataTable>
                </div>

                <template #pagination>
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 px-4 py-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{
                                t('quotes.pageSummary', {
                                    current: quotes.meta.current_page,
                                    last: quotes.meta.last_page,
                                })
                            }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <template
                                v-for="link in paginationLinks"
                                :key="link.label"
                            >
                                <Button
                                    v-if="link.url"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                    class="h-7 px-3 py-1 text-xs"
                                    :class="
                                        link.active
                                            ? 'bg-muted text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                    :aria-label="paginationLabel(link.label)"
                                >
                                    <Link :href="link.url">
                                        {{ paginationLabel(link.label) }}
                                    </Link>
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    size="sm"
                                    as="span"
                                    class="h-7 px-3 py-1 text-xs text-muted-foreground opacity-60"
                                    :aria-label="paginationLabel(link.label)"
                                    disabled
                                >
                                    {{ paginationLabel(link.label) }}
                                </Button>
                            </template>
                        </div>
                    </div>
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
