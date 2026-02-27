<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ChevronDown, FileText, Minus, Plus } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { type BreadcrumbItem } from '@/types';
import { Actions } from '@/types/actions/action-map';
import AlertError from '@/components/AlertError.vue';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import VehiclePickerField from '@/components/VehiclePickerField.vue';
import { useCurrencyFormat } from '@/composables/useCurrencyFormat';
import type {
    VehicleOption,
    VehicleOptionCustomer,
} from '@/composables/useVehicleProvider';

import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import { index as quotesIndex, pdf as quotePdf } from '@/routes/quotes';
import { view as quotePdfView } from '@/routes/quotes/pdf';

type QuoteItem = {
    id: number;
    quoteId: number;
    itemableType: string;
    itemableId: number;
    description: string;
    quantity: number;
    unitPrice: string;
    taxRate: string;
    subtotal: string;
    taxTotal: string;
    total: string;
};

type Quote = {
    id: number;
    status: string;
    customer: VehicleOptionCustomer | null;
    vehicle: VehicleOption | null;
    subtotal: string;
    taxTotal: string;
    total: string;
    items: QuoteItem[];
    createdAt: string;
    updatedAt: string;
};

type Props = {
    quote: Quote;
};

const props = defineProps<Props>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormat();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('quotes.title'),
        href: quotesIndex().url,
    },
    {
        title: `#${props.quote.id}`,
        href: '#',
    },
];

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
    id?: number;
    itemable_type: string;
    itemable_id: number;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    tax_labels: string[];
};

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function getItemType(type: string): string {
    if (type.includes('Product')) return t('quotes.itemTypes.product');
    if (type.includes('Service')) return t('quotes.itemTypes.service');
    if (type.includes('Bundle')) return t('quotes.itemTypes.bundle');
    return type;
}

const isDraft = computed(() => props.quote.status === 'draft');

const isEditOpen = ref(false);
const isSubmitting = ref(false);
const formErrors = ref<Record<string, string>>({});
const formErrorMessages = ref<string[]>([]);

// Association (vehicle/customer) picker
const associationResetNonce = ref(0);
const selectedCustomer = ref<VehicleOptionCustomer | null>(
    props.quote.customer,
);
const selectedVehicle = ref<VehicleOption | null>(props.quote.vehicle);

// Items picker (catalog)
const catalogItems = ref<CatalogItem[]>([]);
const isItemPickerOpen = ref(false);
const itemSearchQuery = ref('');
const itemSearchInput = ref<HTMLInputElement | null>(null);
const selectedItemId = ref<string>('');
const itemQuantity = ref(1);
const formItems = ref<QuoteFormItem[]>([]);

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
        formErrorMessages.value = [t('quotes.edit.errors.generic')];
    }
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

function getItemTypeKey(itemableType: string): CatalogItem['type'] | null {
    if (itemableType.includes('Product')) return 'product';
    if (itemableType.includes('Service')) return 'service';
    if (itemableType.includes('Bundle')) return 'bundle';
    return null;
}

function taxLabelsForItemable(
    itemableType: string,
    itemableId: number,
): string[] {
    const typeKey = getItemTypeKey(itemableType);
    if (!typeKey) {
        return [];
    }

    const match =
        catalogItems.value.find(
            (item) => item.type === typeKey && item.id === itemableId,
        ) ?? null;

    return match ? suggestedTaxLabels(match) : [];
}

function selectCatalogItem(item: CatalogItem) {
    selectedItemId.value = `${item.type}:${item.id}`;
    itemSearchQuery.value = '';
    isItemPickerOpen.value = false;
}

function addItemToForm() {
    if (!selectedItemId.value) {
        return;
    }

    const [type, id] = selectedItemId.value.split(':');
    const item = catalogItems.value.find(
        (i) => i.type === type && i.id === parseInt(id),
    );

    if (!item) {
        return;
    }

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

function removeItemFromForm(index: number) {
    formItems.value.splice(index, 1);
}

function updateItemQuantity(index: number, delta: number) {
    const newQty = formItems.value[index].quantity + delta;
    if (newQty >= 1) {
        formItems.value[index].quantity = newQty;
    }
}

function openEdit() {
    formErrors.value = {};
    formErrorMessages.value = [];

    selectedCustomer.value = props.quote.customer;
    selectedVehicle.value = props.quote.vehicle;
    associationResetNonce.value += 1;

    formItems.value = (props.quote.items ?? []).map((item) => ({
        id: item.id,
        itemable_type: item.itemableType,
        itemable_id: item.itemableId,
        description: item.description,
        quantity: item.quantity,
        unit_price: parseFloat(item.unitPrice),
        tax_rate: parseFloat(item.taxRate),
        tax_labels: taxLabelsForItemable(item.itemableType, item.itemableId),
    }));

    isEditOpen.value = true;
}

async function submitEdit() {
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
        const response = await fetch(`/api/v1/quotes/${props.quote.id}`, {
            method: 'PATCH',
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
            formErrorMessages.value = [t('quotes.edit.errors.generic')];
            return;
        }

        isEditOpen.value = false;
        router.reload({ only: ['quote'] });
    } catch {
        formErrorMessages.value = [t('quotes.edit.errors.generic')];
    } finally {
        isSubmitting.value = false;
    }
}

watch(isItemPickerOpen, (open) => {
    if (open) {
        nextTick(() => itemSearchInput.value?.focus());
    } else {
        itemSearchQuery.value = '';
    }
});

onMounted(() => {
    loadCatalogItems();

    const editFlag =
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('edit')
            : null;

    if (editFlag === '1' && isDraft.value) {
        openEdit();
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${t('quotes.detail.title')} #${quote.id}`" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="quotesIndex().url">
                            <ArrowLeft class="size-5" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold text-foreground">
                            {{ t('quotes.detail.title') }} #{{ quote.id }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ t('quotes.detail.createdAt') }}:
                            {{ formatDate(quote.createdAt) }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Dialog v-if="isDraft" v-model:open="isEditOpen">
                        <DialogTrigger as-child>
                            <AppActionButton
                                :action="Actions.edit"
                                :label-key="'quotes.actions.edit'"
                                variant="secondary"
                                @click="openEdit"
                            />
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('quotes.edit.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('quotes.edit.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('quotes.edit.errors.title')"
                                />

                                <!-- Association -->
                                <VehiclePickerField
                                    v-model:customer="selectedCustomer"
                                    v-model:vehicle="selectedVehicle"
                                    :reset-nonce="associationResetNonce"
                                    :error-message="
                                        t('quotes.edit.errors.generic')
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
                                        <Label for="item-qty">{{
                                            t('quotes.form.quantity')
                                        }}</Label>
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
                                            :key="item.id ?? index"
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
                                                    :label-key="'quotes.edit.actions.removeItem'"
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
                                    @click="submitEdit"
                                >
                                    {{
                                        isSubmitting
                                            ? t('quotes.edit.saving')
                                            : t('quotes.edit.save')
                                    }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <Button variant="outline" as-child>
                        <a
                            :href="quotePdfView({ quote: quote.id }).url"
                            target="_blank"
                        >
                            <FileText class="mr-2 size-4" />
                            {{ t('quotes.actions.viewPdf') }}
                        </a>
                    </Button>
                    <AppActionButton
                        :action="Actions.download"
                        :label-key="'quotes.actions.downloadPdf'"
                        as="a"
                        :href="quotePdf({ quote: quote.id }).url"
                    />
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            {{ t('quotes.detail.customer') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm font-semibold text-foreground">
                            {{
                                quote.customer?.fullName ??
                                t('common.notAvailable')
                            }}
                        </p>
                        <p
                            v-if="quote.customer?.documentNumber"
                            class="text-xs text-muted-foreground"
                        >
                            {{ quote.customer.documentNumber }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            {{ t('quotes.detail.vehicle') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm font-semibold text-foreground">
                            {{
                                quote.vehicle?.plate ?? t('common.notAvailable')
                            }}
                        </p>
                        <p
                            v-if="quote.vehicle"
                            class="text-xs text-muted-foreground"
                        >
                            {{ quote.vehicle.make }} {{ quote.vehicle.model }}
                            {{ quote.vehicle.year }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            {{ t('quotes.detail.subtotal') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">
                            {{ formatCurrency(quote.subtotal) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            {{ t('quotes.detail.tax') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">
                            {{ formatCurrency(quote.taxTotal) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            {{ t('quotes.detail.total') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-primary">
                            {{ formatCurrency(quote.total) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 bg-card shadow-sm"
            >
                <div class="border-b border-sidebar-border/70 px-4 py-4">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ t('quotes.detail.itemsTitle') }}
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        {{
                            t('quotes.detail.itemsCount', {
                                count: quote.items?.length ?? 0,
                            })
                        }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow
                                class="border-b border-sidebar-border/70 text-left text-xs text-muted-foreground uppercase"
                            >
                                <TableHead class="px-4 py-3">
                                    {{ t('quotes.detail.table.description') }}
                                </TableHead>
                                <TableHead class="px-4 py-3">
                                    {{ t('quotes.detail.table.type') }}
                                </TableHead>
                                <TableHead class="px-4 py-3 text-center">
                                    {{ t('quotes.detail.table.quantity') }}
                                </TableHead>
                                <TableHead class="px-4 py-3 text-right">
                                    {{ t('quotes.detail.table.unitPrice') }}
                                </TableHead>
                                <TableHead class="px-4 py-3 text-right">
                                    {{ t('quotes.detail.table.taxRate') }}
                                </TableHead>
                                <TableHead class="px-4 py-3 text-right">
                                    {{ t('quotes.detail.table.subtotal') }}
                                </TableHead>
                                <TableHead class="px-4 py-3 text-right">
                                    {{ t('quotes.detail.table.total') }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in quote.items"
                                :key="item.id"
                                class="border-b border-sidebar-border/70 last:border-none"
                            >
                                <TableCell
                                    class="px-4 py-3 font-medium text-foreground"
                                >
                                    {{ item.description }}
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge variant="secondary">
                                        {{ getItemType(item.itemableType) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3 text-center">
                                    {{ item.quantity }}
                                </TableCell>
                                <TableCell
                                    class="px-4 py-3 text-right text-muted-foreground"
                                >
                                    {{ formatCurrency(item.unitPrice) }}
                                </TableCell>
                                <TableCell
                                    class="px-4 py-3 text-right text-muted-foreground"
                                >
                                    {{ item.taxRate }}%
                                </TableCell>
                                <TableCell
                                    class="px-4 py-3 text-right text-muted-foreground"
                                >
                                    {{ formatCurrency(item.subtotal) }}
                                </TableCell>
                                <TableCell
                                    class="px-4 py-3 text-right font-medium"
                                >
                                    {{ formatCurrency(item.total) }}
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!quote.items?.length">
                                <TableCell
                                    colspan="7"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('quotes.detail.noItems') }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="border-t border-sidebar-border/70 px-4 py-4">
                    <div class="flex flex-col items-end gap-2">
                        <div class="flex justify-between gap-8 text-sm">
                            <span class="text-muted-foreground"
                                >{{ t('quotes.detail.subtotal') }}:</span
                            >
                            <span>{{ formatCurrency(quote.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between gap-8 text-sm">
                            <span class="text-muted-foreground"
                                >{{ t('quotes.detail.tax') }}:</span
                            >
                            <span>{{ formatCurrency(quote.taxTotal) }}</span>
                        </div>
                        <div
                            class="flex justify-between gap-8 text-lg font-bold"
                        >
                            <span>{{ t('quotes.detail.total') }}:</span>
                            <span class="text-primary">{{
                                formatCurrency(quote.total)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
