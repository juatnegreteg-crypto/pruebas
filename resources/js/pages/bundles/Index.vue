<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';
import { type BreadcrumbItem } from '@/types';
import { Actions } from '@/types/actions/action-map';
import AlertError from '@/components/AlertError.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTable from '@/components/AppDataTable.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import CatalogItemTaxesForm, {
    type CatalogItemTaxForm,
} from '@/components/CatalogItemTaxesForm.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useCurrencyFormat } from '@/composables/useCurrencyFormat';
import { useDataTable } from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import { index as bundlesIndex, show as bundlesShow } from '@/routes/bundles';

type Bundle = {
    id: number;
    name: string;
    description?: string | null;
    observations?: Array<{
        body: string;
        context: string;
        audienceTags: string[];
        createdAt?: string | null;
        createdBy?: number | null;
    }> | null;
    cost?: string | null;
    price: string;
    currency: string;
    unit?: string | null;
    isActive: boolean;
    taxes?: CatalogItemTaxForm[];
    itemsCount: number;
};

type BundleItem = {
    id: number | null;
    type: string | null;
    name: string | null;
    description?: string | null;
    price: string | null;
    currency: string | null;
    isActive: boolean | null;
};

type CatalogItem = {
    id: number;
    name: string;
    price: string;
    type: 'product' | 'service' | 'bundle';
};

type BundleFormItem = {
    type: 'product' | 'service' | 'bundle';
    id: number;
    name: string;
    quantity: number;
};

type BundleRow = Bundle & {
    row_type: 'bundle';
    subRows?: BundleTableRow[];
};

type BundleItemRow = BundleItem & {
    row_type: 'item';
    subRows?: BundleTableRow[];
};

type BundleStateRow = {
    row_type: 'state';
    state: 'loading' | 'error' | 'empty';
    message: string;
    bundle_id: number;
};

type BundleTableRow = BundleRow | BundleItemRow | BundleStateRow;

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type BundlesPayload = {
    data: Bundle[];
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
    bundles: BundlesPayload;
    unitOptions: string[];
    taxes: Array<{
        id: number;
        name: string;
        code: string;
        jurisdiction: string;
        rate: number;
    }>;
    defaultCurrency: string;
};

const props = defineProps<Props>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormat();
const bundles = computed(() => props.bundles);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('bundles.title'),
        href: bundlesIndex().url,
    },
];

const table = useDataTable({
    route: () => bundlesIndex().url,
    initialSearch:
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('q') ?? '')
            : '',
    initialPerPage: props.bundles.meta.per_page ?? 15,
    searchKey: 'q',
    autoSearch: false,
});
const isLoading = ref(false);
const hasError = computed(
    () => !props.bundles || !Array.isArray(props.bundles.data),
);
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isSubmitting = ref(false);
const isUpdating = ref(false);
const editingBundleId = ref<number | null>(null);
const formErrors = ref<Record<string, string>>({});
const formErrorMessages = ref<string[]>([]);

const createForm = reactive({
    name: '',
    description: '',
    observation: '',
    cost: '',
    price: '',
    currency: '',
    unit: 'unit',
    isActive: true,
    taxes: [] as CatalogItemTaxForm[],
});

const editForm = reactive({
    name: '',
    description: '',
    observation: '',
    cost: '',
    price: '',
    currency: '',
    unit: 'unit',
    isActive: true,
    taxes: [] as CatalogItemTaxForm[],
});

createForm.currency = props.defaultCurrency;
editForm.currency = props.defaultCurrency;
const catalogItems = ref<CatalogItem[]>([]);
const catalogItemsLoaded = ref(false);
const isCatalogLoading = ref(false);
const isEditLoading = ref(false);
const createItemPickerOpen = ref(false);
const createSelectedItemId = ref('');
const createItemSearchQuery = ref('');
const createItemQuantity = ref(1);
const createFormItems = ref<BundleFormItem[]>([]);
const editItemPickerOpen = ref(false);
const editSelectedItemId = ref('');
const editItemSearchQuery = ref('');
const editItemQuantity = ref(1);
const editFormItems = ref<BundleFormItem[]>([]);

const paginationLinks = computed(() => props.bundles.meta.links ?? []);
const bundleItems = ref<Record<number, BundleItem[]>>({});
const bundleItemLoading = ref<Record<number, boolean>>({});
const bundleItemErrors = ref<Record<number, string>>({});
function hasLoadedItems(bundleId: number) {
    return Object.prototype.hasOwnProperty.call(bundleItems.value, bundleId);
}

function buildSubRowsFor(bundleId: number): BundleTableRow[] {
    const rows: BundleTableRow[] = [];
    const items = bundleItems.value[bundleId] ?? [];
    const error = bundleItemErrors.value[bundleId];
    const loading = bundleItemLoading.value[bundleId];

    if (loading) {
        rows.push({
            row_type: 'state',
            state: 'loading',
            message: t('common.loading'),
            bundle_id: bundleId,
        });
    } else if (error) {
        rows.push({
            row_type: 'state',
            state: 'error',
            message: error,
            bundle_id: bundleId,
        });
    } else if (hasLoadedItems(bundleId) && items.length === 0) {
        rows.push({
            row_type: 'state',
            state: 'empty',
            message: t('bundles.inline.empty'),
            bundle_id: bundleId,
        });
    } else {
        rows.push(
            ...items.map((item) => {
                const row: BundleItemRow = {
                    ...item,
                    row_type: 'item',
                };

                if (item.type === 'bundle' && item.id) {
                    row.subRows = buildSubRowsFor(item.id);
                }

                return row;
            }),
        );
    }

    return rows;
}

const tableData = computed<BundleTableRow[]>(() =>
    filteredBundles.value.map((bundle) => ({
        ...bundle,
        row_type: 'bundle',
        subRows: buildSubRowsFor(bundle.id),
    })),
);
const columns = computed(
    () =>
        [
            { key: 'name', header: t('bundles.table.name') },
            { key: 'description', header: t('bundles.table.description') },
            { key: 'price', header: t('bundles.table.price'), align: 'right' },
            {
                key: 'itemsCount',
                header: t('bundles.table.items'),
                align: 'center',
            },
            {
                key: 'status',
                header: t('bundles.table.status'),
                align: 'center',
            },
            {
                key: 'actions',
                header: t('bundles.table.actions'),
                align: 'right',
            },
        ] as const,
);
const slotNames = {
    name: 'cell(name)',
    description: 'cell(description)',
    price: 'cell(price)',
    itemsCount: 'cell(itemsCount)',
    status: 'cell(status)',
    actions: 'cell(actions)',
    empty: 'empty()',
} as const;

function bundleRowKey(row: BundleTableRow): string | number {
    if (row.row_type === 'bundle') {
        return row.id;
    }

    if (row.row_type === 'item') {
        return `item-${row.type ?? 'item'}-${row.id ?? row.name ?? 'unknown'}`;
    }

    return `state-${row.bundle_id}-${row.state}`;
}

function canExpandRow(row: BundleTableRow): boolean {
    return (
        row.row_type === 'bundle' ||
        (row.row_type === 'item' && row.type === 'bundle' && row.id !== null)
    );
}

function getSubRowsFor(row: BundleTableRow): BundleTableRow[] {
    if (row.row_type === 'state') {
        return [];
    }

    return row.subRows ?? [];
}

const filteredBundles = computed(() => props.bundles.data);

function isStateRow(row: BundleTableRow): row is BundleStateRow {
    return row.row_type === 'state';
}

function isItemRow(row: BundleTableRow): row is BundleItemRow {
    return row.row_type === 'item';
}

function isBundleRow(row: BundleTableRow): row is BundleRow {
    return row.row_type === 'bundle';
}

const summary = computed(() => {
    if (!props.bundles.meta.total) {
        return t('bundles.summaryEmpty');
    }

    return t('bundles.summary', {
        from: props.bundles.meta.from ?? 0,
        to: props.bundles.meta.to ?? 0,
        total: props.bundles.meta.total,
    });
});

function normalizeItemType(
    type: string | null,
): 'product' | 'service' | 'bundle' {
    const normalized = type ?? '';

    if (normalized === 'product' || normalized.includes('Product')) {
        return 'product';
    }

    if (normalized === 'service' || normalized.includes('Service')) {
        return 'service';
    }

    return 'bundle';
}

function formatItemType(type: 'product' | 'service' | 'bundle'): string {
    return t(`bundles.form.itemTypes.${type}`);
}

function formatCatalogItemLabel(item: CatalogItem): string {
    return `${item.name} (${formatItemType(item.type)})`;
}

function parseSelectedItemId(value: string): {
    type: 'product' | 'service' | 'bundle';
    id: number;
} | null {
    if (!value) {
        return null;
    }

    const [type, id] = value.split(':');
    const parsedId = Number(id);

    if (
        (type !== 'product' && type !== 'service' && type !== 'bundle') ||
        Number.isNaN(parsedId) ||
        parsedId <= 0
    ) {
        return null;
    }

    return { type, id: parsedId };
}

async function ensureCatalogItemsLoaded(): Promise<void> {
    if (catalogItemsLoaded.value || isCatalogLoading.value) {
        return;
    }

    isCatalogLoading.value = true;

    try {
        const [productsResponse, servicesResponse, bundlesResponse] =
            await Promise.all([
                fetch('/api/v1/products?perPage=100', {
                    headers: { Accept: 'application/json' },
                }),
                fetch('/api/v1/services?perPage=100', {
                    headers: { Accept: 'application/json' },
                }),
                fetch('/api/v1/bundles?perPage=100', {
                    headers: { Accept: 'application/json' },
                }),
            ]);

        if (
            !productsResponse.ok ||
            !servicesResponse.ok ||
            !bundlesResponse.ok
        ) {
            throw new Error('Unable to load catalog items');
        }

        const productsPayload = await productsResponse.json();
        const servicesPayload = await servicesResponse.json();
        const bundlesPayload = await bundlesResponse.json();

        catalogItems.value = [
            ...(productsPayload?.data ?? []).map(
                (item: { id: number; name: string; price: string }) => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    type: 'product' as const,
                }),
            ),
            ...(servicesPayload?.data ?? []).map(
                (item: { id: number; name: string; price: string }) => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    type: 'service' as const,
                }),
            ),
            ...(bundlesPayload?.data ?? []).map(
                (item: { id: number; name: string; price: string }) => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    type: 'bundle' as const,
                }),
            ),
        ];

        catalogItemsLoaded.value = true;
    } catch {
        formErrorMessages.value = [t('bundles.form.errors.catalogLoad')];
    } finally {
        isCatalogLoading.value = false;
    }
}

function addItemToCollection(
    selectedItemId: string,
    quantity: number,
    items: BundleFormItem[],
): void {
    const selected = parseSelectedItemId(selectedItemId);

    if (!selected) {
        return;
    }

    const catalogItem = catalogItems.value.find(
        (item) => item.type === selected.type && item.id === selected.id,
    );

    if (!catalogItem) {
        return;
    }

    const existingItem = items.find(
        (item) => item.type === selected.type && item.id === selected.id,
    );

    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        items.push({
            type: selected.type,
            id: selected.id,
            name: catalogItem.name,
            quantity,
        });
    }
}

function removeItemFromForm(
    index: number,
    items: typeof createFormItems,
): void {
    items.value.splice(index, 1);
}

function mapItemsForPayload(items: BundleFormItem[]): Array<{
    type: 'product' | 'service' | 'bundle';
    id: number;
    quantity: number;
}> {
    return items.map((item) => ({
        type: item.type,
        id: item.id,
        quantity: item.quantity,
    }));
}

const createCatalogItems = computed(() => catalogItems.value);
const createFilteredCatalogItems = computed(() => {
    const term = createItemSearchQuery.value.trim().toLowerCase();

    if (!term) {
        return createCatalogItems.value;
    }

    return createCatalogItems.value.filter((item) =>
        `${item.name} ${item.type}`.toLowerCase().includes(term),
    );
});
const createSelectedCatalogItem = computed(() => {
    const selected = parseSelectedItemId(createSelectedItemId.value);

    if (!selected) {
        return null;
    }

    return (
        createCatalogItems.value.find(
            (item) => item.type === selected.type && item.id === selected.id,
        ) ?? null
    );
});

const editCatalogItems = computed(() =>
    catalogItems.value.filter(
        (item) =>
            !(
                item.type === 'bundle' &&
                editingBundleId.value !== null &&
                item.id === editingBundleId.value
            ),
    ),
);
const editFilteredCatalogItems = computed(() => {
    const term = editItemSearchQuery.value.trim().toLowerCase();

    if (!term) {
        return editCatalogItems.value;
    }

    return editCatalogItems.value.filter((item) =>
        `${item.name} ${item.type}`.toLowerCase().includes(term),
    );
});
const editSelectedCatalogItem = computed(() => {
    const selected = parseSelectedItemId(editSelectedItemId.value);

    if (!selected) {
        return null;
    }

    return (
        editCatalogItems.value.find(
            (item) => item.type === selected.type && item.id === selected.id,
        ) ?? null
    );
});

function selectCreateCatalogItem(item: CatalogItem): void {
    createSelectedItemId.value = `${item.type}:${item.id}`;
    createItemSearchQuery.value = '';
    createItemPickerOpen.value = false;
}

function selectEditCatalogItem(item: CatalogItem): void {
    editSelectedItemId.value = `${item.type}:${item.id}`;
    editItemSearchQuery.value = '';
    editItemPickerOpen.value = false;
}

function addCreateItemToForm(): void {
    addItemToCollection(
        createSelectedItemId.value,
        createItemQuantity.value,
        createFormItems.value,
    );
    createSelectedItemId.value = '';
    createItemSearchQuery.value = '';
    createItemQuantity.value = 1;
}

function addEditItemToForm(): void {
    addItemToCollection(
        editSelectedItemId.value,
        editItemQuantity.value,
        editFormItems.value,
    );
    editSelectedItemId.value = '';
    editItemSearchQuery.value = '';
    editItemQuantity.value = 1;
}

function resetCreateForm() {
    createForm.name = '';
    createForm.description = '';
    createForm.observation = '';
    createForm.cost = '';
    createForm.price = '';
    createForm.currency = props.defaultCurrency;
    createForm.unit = 'unit';
    createForm.isActive = true;
    createForm.taxes = [];
    createItemPickerOpen.value = false;
    createSelectedItemId.value = '';
    createItemSearchQuery.value = '';
    createItemQuantity.value = 1;
    createFormItems.value = [];
    formErrors.value = {};
    formErrorMessages.value = [];
}

function resetEditForm() {
    editForm.name = '';
    editForm.description = '';
    editForm.observation = '';
    editForm.cost = '';
    editForm.price = '';
    editForm.currency = props.defaultCurrency;
    editForm.unit = 'unit';
    editForm.isActive = true;
    editForm.taxes = [];
    editItemPickerOpen.value = false;
    editSelectedItemId.value = '';
    editItemSearchQuery.value = '';
    editItemQuantity.value = 1;
    editFormItems.value = [];
    editingBundleId.value = null;
    formErrors.value = {};
    formErrorMessages.value = [];
}

function setFormError(field: string, message: string) {
    formErrors.value = {
        ...formErrors.value,
        [field]: message,
    };
}

async function submitCreate() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!createForm.name.trim()) {
        setFormError('name', t('bundles.form.errors.nameRequired'));
    }

    if (!String(createForm.price).trim()) {
        setFormError('price', t('bundles.form.errors.priceRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    isSubmitting.value = true;

    try {
        const response = await fetch('/api/v1/bundles', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                name: createForm.name.trim(),
                description: createForm.description.trim() || null,
                observation: createForm.observation.trim() || null,
                cost: createForm.cost ? Number(createForm.cost) : null,
                price: Number(createForm.price),
                currency: createForm.currency.trim() || null,
                unit: createForm.unit,
                isActive: createForm.isActive,
                taxes: createForm.taxes,
                items: mapItemsForPayload(createFormItems.value),
            }),
        });

        if (response.status === 422) {
            const data = await response.json();
            const errors = data?.errors ?? {};

            Object.keys(errors).forEach((key) => {
                setFormError(key, errors[key]?.[0] ?? '');
            });

            return;
        }

        if (!response.ok) {
            formErrorMessages.value = [t('bundles.form.errors.generic')];
            return;
        }

        resetCreateForm();
        isCreateOpen.value = false;
        router.reload({ only: ['bundles'] });
    } catch {
        formErrorMessages.value = [t('bundles.form.errors.generic')];
    } finally {
        isSubmitting.value = false;
    }
}

async function openEdit(bundle: Bundle) {
    await ensureCatalogItemsLoaded();

    editingBundleId.value = bundle.id;
    editForm.name = bundle.name ?? '';
    editForm.description = bundle.description ?? '';
    editForm.observation = bundle.observations?.[0]?.body ?? '';
    editForm.cost = bundle.cost ?? '';
    editForm.price = bundle.price ?? '';
    editForm.currency = bundle.currency ?? props.defaultCurrency;
    editForm.unit = bundle.unit ?? 'unit';
    editForm.isActive = bundle.isActive ?? true;
    editForm.taxes = bundle.taxes ?? [];
    editFormItems.value = [];
    formErrors.value = {};
    formErrorMessages.value = [];
    isEditOpen.value = true;

    isEditLoading.value = true;

    try {
        const response = await fetch(`/api/v1/bundles/${bundle.id}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            formErrorMessages.value = [t('bundles.edit.errors.load')];
            return;
        }

        const payload = await response.json();
        const items = Array.isArray(payload?.data?.items)
            ? payload.data.items
            : [];

        editFormItems.value = items
            .filter(
                (
                    item,
                ): item is {
                    id: number;
                    type: string;
                    name: string;
                    quantity: number;
                } =>
                    typeof item?.id === 'number' &&
                    typeof item?.type === 'string' &&
                    typeof item?.name === 'string' &&
                    typeof item?.quantity === 'number',
            )
            .map((item) => ({
                id: item.id,
                type: normalizeItemType(item.type),
                name: item.name,
                quantity: item.quantity,
            }));
    } catch {
        formErrorMessages.value = [t('bundles.edit.errors.load')];
    } finally {
        isEditLoading.value = false;
    }
}

async function submitEdit() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!editForm.name.trim()) {
        setFormError('name', t('bundles.form.errors.nameRequired'));
    }

    if (!String(editForm.price).trim()) {
        setFormError('price', t('bundles.form.errors.priceRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    if (!editingBundleId.value) {
        formErrorMessages.value = [t('bundles.edit.errors.generic')];
        return;
    }

    isUpdating.value = true;

    try {
        const response = await fetch(
            `/api/v1/bundles/${editingBundleId.value}`,
            {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    name: editForm.name.trim(),
                    description: editForm.description.trim() || null,
                    observation: editForm.observation.trim() || null,
                    cost: editForm.cost ? Number(editForm.cost) : null,
                    price: Number(editForm.price),
                    currency: editForm.currency.trim() || null,
                    unit: editForm.unit,
                    isActive: editForm.isActive,
                    taxes: editForm.taxes,
                    items: mapItemsForPayload(editFormItems.value),
                }),
            },
        );

        if (response.status === 422) {
            const data = await response.json();
            const errors = data?.errors ?? {};

            Object.keys(errors).forEach((key) => {
                setFormError(key, errors[key]?.[0] ?? '');
            });

            return;
        }

        if (!response.ok) {
            formErrorMessages.value = [t('bundles.edit.errors.generic')];
            return;
        }

        resetEditForm();
        isEditOpen.value = false;
        router.reload({ only: ['bundles'] });
    } catch {
        formErrorMessages.value = [t('bundles.edit.errors.generic')];
    } finally {
        isUpdating.value = false;
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
    router.reload({ only: ['bundles'] });
}

async function loadBundleItems(bundleId: number) {
    bundleItemErrors.value[bundleId] = '';
    bundleItemLoading.value[bundleId] = true;

    try {
        const response = await fetch(`/api/v1/bundles/${bundleId}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            bundleItemErrors.value[bundleId] = t('bundles.inline.errors.load');
            return;
        }

        const payload = await response.json();
        const items = Array.isArray(payload?.data?.items)
            ? payload.data.items
            : [];

        bundleItems.value[bundleId] = items;
    } catch {
        bundleItemErrors.value[bundleId] = t('bundles.inline.errors.load');
    } finally {
        bundleItemLoading.value[bundleId] = false;
    }
}

function getExpandableBundleId(row: BundleTableRow): number | null {
    if (row.row_type === 'bundle') {
        return row.id;
    }

    if (row.row_type === 'item' && row.type === 'bundle' && row.id) {
        return row.id;
    }

    return null;
}

async function handleRowExpand(row: BundleTableRow) {
    const bundleId = getExpandableBundleId(row);

    if (!bundleId) {
        return;
    }

    if (!bundleItems.value[bundleId]?.length) {
        await loadBundleItems(bundleId);
    }
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
});

watch(isCreateOpen, async (open) => {
    if (!open) {
        return;
    }

    await ensureCatalogItemsLoaded();
});

onBeforeUnmount(() => {
    unsubscribeStart?.();
    unsubscribeFinish?.();
    unsubscribeError?.();
    unsubscribeStart = null;
    unsubscribeFinish = null;
    unsubscribeError = null;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('bundles.title')" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        {{ t('bundles.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('bundles.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Dialog
                        v-model:open="isCreateOpen"
                        @update:open="(open) => !open && resetCreateForm()"
                    >
                        <DialogTrigger as-child>
                            <Button>{{ t('bundles.form.open') }}</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-xl">
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('bundles.form.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('bundles.form.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('bundles.form.errors.title')"
                                />

                                <div class="grid gap-2">
                                    <Label for="bundle-name">{{
                                        t('bundles.form.fields.name')
                                    }}</Label>
                                    <Input
                                        id="bundle-name"
                                        v-model="createForm.name"
                                        :placeholder="
                                            t('bundles.form.placeholders.name')
                                        "
                                        :aria-invalid="Boolean(formErrors.name)"
                                    />
                                    <InputError :message="formErrors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="bundle-description">{{
                                        t('bundles.form.fields.description')
                                    }}</Label>
                                    <Input
                                        id="bundle-description"
                                        v-model="createForm.description"
                                        :placeholder="
                                            t(
                                                'bundles.form.placeholders.description',
                                            )
                                        "
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="bundle-observation">
                                        {{
                                            t(
                                                'bundles.form.fields.observations',
                                            )
                                        }}
                                    </Label>
                                    <Textarea
                                        id="bundle-observation"
                                        v-model="createForm.observation"
                                        rows="3"
                                        :placeholder="
                                            t(
                                                'bundles.form.placeholders.observations',
                                            )
                                        "
                                        :aria-invalid="
                                            Boolean(formErrors.observation)
                                        "
                                    />
                                    <InputError
                                        :message="formErrors.observation"
                                    />
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div class="grid gap-2">
                                        <Label for="bundle-cost">Costo</Label>
                                        <Input
                                            id="bundle-cost"
                                            v-model="createForm.cost"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            :aria-invalid="
                                                Boolean(formErrors.cost)
                                            "
                                        />
                                        <InputError
                                            :message="formErrors.cost"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="bundle-price">{{
                                            t('bundles.form.fields.price')
                                        }}</Label>
                                        <Input
                                            id="bundle-price"
                                            v-model="createForm.price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :placeholder="
                                                t(
                                                    'bundles.form.placeholders.price',
                                                )
                                            "
                                            :aria-invalid="
                                                Boolean(formErrors.price)
                                            "
                                        />
                                        <InputError
                                            :message="formErrors.price"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div class="grid gap-2">
                                        <Label for="bundle-currency">
                                            Moneda
                                        </Label>
                                        <Input
                                            id="bundle-currency"
                                            v-model="createForm.currency"
                                            type="text"
                                            maxlength="3"
                                            placeholder="COP"
                                            :aria-invalid="
                                                Boolean(formErrors.currency)
                                            "
                                        />
                                        <InputError
                                            :message="formErrors.currency"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="bundle-unit">
                                            {{ t('catalog.unit.label') }}
                                        </Label>
                                        <Select
                                            :model-value="createForm.unit"
                                            @update:model-value="
                                                (value) =>
                                                    (createForm.unit =
                                                        String(value))
                                            "
                                        >
                                            <SelectTrigger
                                                id="bundle-unit"
                                                :aria-invalid="
                                                    Boolean(formErrors.unit)
                                                "
                                            >
                                                <SelectValue
                                                    :placeholder="
                                                        t(
                                                            'catalog.unit.placeholder',
                                                        )
                                                    "
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="option in props.unitOptions"
                                                    :key="option"
                                                    :value="option"
                                                >
                                                    {{
                                                        t(
                                                            `catalog.unit.values.${option}`,
                                                        )
                                                    }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            :message="formErrors.unit"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        id="bundle-active"
                                        v-model:checked="createForm.isActive"
                                    />
                                    <Label for="bundle-active">{{
                                        t('bundles.form.fields.active')
                                    }}</Label>
                                </div>

                                <CatalogItemTaxesForm
                                    v-model="createForm.taxes"
                                    :taxes="props.taxes"
                                    :errors="formErrors"
                                />

                                <div class="grid gap-3 rounded-md border p-3">
                                    <Label class="text-sm font-medium">{{
                                        t('bundles.form.items.title')
                                    }}</Label>

                                    <div
                                        class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_120px_auto]"
                                    >
                                        <div class="grid min-w-0 gap-1">
                                            <Label for="bundle-item-select">{{
                                                t('bundles.form.items.item')
                                            }}</Label>
                                            <Popover
                                                v-model:open="
                                                    createItemPickerOpen
                                                "
                                            >
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        id="bundle-item-select"
                                                        type="button"
                                                        variant="outline"
                                                        class="h-10 w-full justify-between px-3 font-normal"
                                                        :class="
                                                            cn(
                                                                !createSelectedCatalogItem
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
                                                                    createSelectedCatalogItem
                                                                "
                                                            >
                                                                {{
                                                                    formatCatalogItemLabel(
                                                                        createSelectedCatalogItem,
                                                                    )
                                                                }}
                                                            </template>
                                                            <template v-else>
                                                                {{
                                                                    t(
                                                                        'bundles.form.items.placeholder',
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
                                                    <Input
                                                        v-model="
                                                            createItemSearchQuery
                                                        "
                                                        :placeholder="
                                                            t(
                                                                'bundles.form.items.searchPlaceholder',
                                                            )
                                                        "
                                                    />

                                                    <div
                                                        class="mt-2 max-h-72 overflow-auto"
                                                    >
                                                        <div
                                                            v-if="
                                                                !createFilteredCatalogItems.length
                                                            "
                                                            class="p-2 text-sm text-muted-foreground"
                                                        >
                                                            {{
                                                                t(
                                                                    'common.noResults',
                                                                )
                                                            }}
                                                        </div>
                                                        <div
                                                            v-else
                                                            class="grid gap-1"
                                                        >
                                                            <button
                                                                v-for="item in createFilteredCatalogItems"
                                                                :key="`${item.type}:${item.id}`"
                                                                type="button"
                                                                class="rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                                @click="
                                                                    selectCreateCatalogItem(
                                                                        item,
                                                                    )
                                                                "
                                                            >
                                                                <div
                                                                    class="flex items-start justify-between gap-3"
                                                                >
                                                                    <span
                                                                        class="font-medium text-foreground"
                                                                    >
                                                                        {{
                                                                            item.name
                                                                        }}
                                                                    </span>
                                                                    <Badge
                                                                        variant="outline"
                                                                        class="shrink-0"
                                                                    >
                                                                        {{
                                                                            formatItemType(
                                                                                item.type,
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

                                        <div class="grid min-w-0 gap-1">
                                            <Label for="bundle-item-quantity">{{
                                                t('bundles.form.items.quantity')
                                            }}</Label>
                                            <Input
                                                id="bundle-item-quantity"
                                                v-model.number="
                                                    createItemQuantity
                                                "
                                                type="number"
                                                min="1"
                                            />
                                        </div>

                                        <Button
                                            type="button"
                                            variant="secondary"
                                            class="self-end"
                                            :disabled="
                                                !createSelectedItemId ||
                                                createItemQuantity < 1
                                            "
                                            @click="addCreateItemToForm"
                                        >
                                            {{ t('bundles.form.items.add') }}
                                        </Button>
                                    </div>

                                    <div
                                        v-if="isCatalogLoading"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t('common.loading') }}
                                    </div>

                                    <div
                                        v-if="createFormItems.length === 0"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t('bundles.form.items.empty') }}
                                    </div>
                                    <div
                                        v-else
                                        class="space-y-2 rounded-md border p-2"
                                    >
                                        <div
                                            v-for="(
                                                item, index
                                            ) in createFormItems"
                                            :key="`${item.type}:${item.id}`"
                                            class="grid grid-cols-[1fr_120px_auto] items-center gap-2"
                                        >
                                            <div class="text-sm">
                                                {{ item.name }}
                                                <span
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    ({{
                                                        formatItemType(
                                                            item.type,
                                                        )
                                                    }})
                                                </span>
                                            </div>
                                            <Input
                                                v-model.number="item.quantity"
                                                type="number"
                                                min="1"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    removeItemFromForm(
                                                        index,
                                                        createFormItems,
                                                    )
                                                "
                                            >
                                                {{
                                                    t(
                                                        'bundles.form.items.remove',
                                                    )
                                                }}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="ghost" type="button">
                                        {{ t('bundles.form.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    :disabled="isSubmitting"
                                    @click="submitCreate"
                                >
                                    {{
                                        isSubmitting
                                            ? t('bundles.form.saving')
                                            : t('bundles.form.save')
                                    }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <Dialog
                        v-model:open="isEditOpen"
                        @update:open="(open) => !open && resetEditForm()"
                    >
                        <DialogContent class="sm:max-w-xl">
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('bundles.edit.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('bundles.edit.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div
                                v-if="isEditLoading"
                                class="py-6 text-center text-sm text-muted-foreground"
                            >
                                {{ t('common.loading') }}
                            </div>
                            <div v-else class="grid gap-4">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('bundles.edit.errors.title')"
                                />

                                <div class="grid gap-2">
                                    <Label for="edit-bundle-name">{{
                                        t('bundles.form.fields.name')
                                    }}</Label>
                                    <Input
                                        id="edit-bundle-name"
                                        v-model="editForm.name"
                                        :placeholder="
                                            t('bundles.form.placeholders.name')
                                        "
                                        :aria-invalid="Boolean(formErrors.name)"
                                    />
                                    <InputError :message="formErrors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="edit-bundle-description">{{
                                        t('bundles.form.fields.description')
                                    }}</Label>
                                    <Input
                                        id="edit-bundle-description"
                                        v-model="editForm.description"
                                        :placeholder="
                                            t(
                                                'bundles.form.placeholders.description',
                                            )
                                        "
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit-bundle-observation">
                                        {{
                                            t(
                                                'bundles.form.fields.observations',
                                            )
                                        }}
                                    </Label>
                                    <Textarea
                                        id="edit-bundle-observation"
                                        v-model="editForm.observation"
                                        rows="3"
                                        :placeholder="
                                            t(
                                                'bundles.form.placeholders.observations',
                                            )
                                        "
                                        :aria-invalid="
                                            Boolean(formErrors.observation)
                                        "
                                    />
                                    <InputError
                                        :message="formErrors.observation"
                                    />
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div class="grid gap-2">
                                        <Label for="edit-bundle-cost"
                                            >Costo</Label
                                        >
                                        <Input
                                            id="edit-bundle-cost"
                                            v-model="editForm.cost"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            :aria-invalid="
                                                Boolean(formErrors.cost)
                                            "
                                        />
                                        <InputError
                                            :message="formErrors.cost"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="edit-bundle-price">{{
                                            t('bundles.form.fields.price')
                                        }}</Label>
                                        <Input
                                            id="edit-bundle-price"
                                            v-model="editForm.price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :placeholder="
                                                t(
                                                    'bundles.form.placeholders.price',
                                                )
                                            "
                                            :aria-invalid="
                                                Boolean(formErrors.price)
                                            "
                                        />
                                        <InputError
                                            :message="formErrors.price"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div class="grid gap-2">
                                        <Label for="edit-bundle-currency">
                                            Moneda
                                        </Label>
                                        <Input
                                            id="edit-bundle-currency"
                                            v-model="editForm.currency"
                                            type="text"
                                            maxlength="3"
                                            placeholder="COP"
                                            :aria-invalid="
                                                Boolean(formErrors.currency)
                                            "
                                        />
                                        <InputError
                                            :message="formErrors.currency"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="edit-bundle-unit">
                                            {{ t('catalog.unit.label') }}
                                        </Label>
                                        <Select
                                            :model-value="editForm.unit"
                                            @update:model-value="
                                                (value) =>
                                                    (editForm.unit =
                                                        String(value))
                                            "
                                        >
                                            <SelectTrigger
                                                id="edit-bundle-unit"
                                                :aria-invalid="
                                                    Boolean(formErrors.unit)
                                                "
                                            >
                                                <SelectValue
                                                    :placeholder="
                                                        t(
                                                            'catalog.unit.placeholder',
                                                        )
                                                    "
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="option in props.unitOptions"
                                                    :key="option"
                                                    :value="option"
                                                >
                                                    {{
                                                        t(
                                                            `catalog.unit.values.${option}`,
                                                        )
                                                    }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            :message="formErrors.unit"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        id="edit-bundle-active"
                                        v-model:checked="editForm.isActive"
                                    />
                                    <Label for="edit-bundle-active">{{
                                        t('bundles.form.fields.active')
                                    }}</Label>
                                </div>

                                <CatalogItemTaxesForm
                                    v-model="editForm.taxes"
                                    :taxes="props.taxes"
                                    :errors="formErrors"
                                />

                                <div class="grid gap-3 rounded-md border p-3">
                                    <Label class="text-sm font-medium">{{
                                        t('bundles.form.items.title')
                                    }}</Label>

                                    <div
                                        class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_120px_auto]"
                                    >
                                        <div class="grid min-w-0 gap-1">
                                            <Label
                                                for="edit-bundle-item-select"
                                                >{{
                                                    t('bundles.form.items.item')
                                                }}</Label
                                            >
                                            <Popover
                                                v-model:open="
                                                    editItemPickerOpen
                                                "
                                            >
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        id="edit-bundle-item-select"
                                                        type="button"
                                                        variant="outline"
                                                        class="h-10 w-full justify-between px-3 font-normal"
                                                        :class="
                                                            cn(
                                                                !editSelectedCatalogItem
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
                                                                    editSelectedCatalogItem
                                                                "
                                                            >
                                                                {{
                                                                    formatCatalogItemLabel(
                                                                        editSelectedCatalogItem,
                                                                    )
                                                                }}
                                                            </template>
                                                            <template v-else>
                                                                {{
                                                                    t(
                                                                        'bundles.form.items.placeholder',
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
                                                    <Input
                                                        v-model="
                                                            editItemSearchQuery
                                                        "
                                                        :placeholder="
                                                            t(
                                                                'bundles.form.items.searchPlaceholder',
                                                            )
                                                        "
                                                    />

                                                    <div
                                                        class="mt-2 max-h-72 overflow-auto"
                                                    >
                                                        <div
                                                            v-if="
                                                                !editFilteredCatalogItems.length
                                                            "
                                                            class="p-2 text-sm text-muted-foreground"
                                                        >
                                                            {{
                                                                t(
                                                                    'common.noResults',
                                                                )
                                                            }}
                                                        </div>
                                                        <div
                                                            v-else
                                                            class="grid gap-1"
                                                        >
                                                            <button
                                                                v-for="item in editFilteredCatalogItems"
                                                                :key="`${item.type}:${item.id}`"
                                                                type="button"
                                                                class="rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                                @click="
                                                                    selectEditCatalogItem(
                                                                        item,
                                                                    )
                                                                "
                                                            >
                                                                <div
                                                                    class="flex items-start justify-between gap-3"
                                                                >
                                                                    <span
                                                                        class="font-medium text-foreground"
                                                                    >
                                                                        {{
                                                                            item.name
                                                                        }}
                                                                    </span>
                                                                    <Badge
                                                                        variant="outline"
                                                                        class="shrink-0"
                                                                    >
                                                                        {{
                                                                            formatItemType(
                                                                                item.type,
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

                                        <div class="grid min-w-0 gap-1">
                                            <Label
                                                for="edit-bundle-item-quantity"
                                                >{{
                                                    t(
                                                        'bundles.form.items.quantity',
                                                    )
                                                }}</Label
                                            >
                                            <Input
                                                id="edit-bundle-item-quantity"
                                                v-model.number="
                                                    editItemQuantity
                                                "
                                                type="number"
                                                min="1"
                                            />
                                        </div>

                                        <Button
                                            type="button"
                                            variant="secondary"
                                            class="self-end"
                                            :disabled="
                                                !editSelectedItemId ||
                                                editItemQuantity < 1
                                            "
                                            @click="addEditItemToForm"
                                        >
                                            {{ t('bundles.form.items.add') }}
                                        </Button>
                                    </div>

                                    <div
                                        v-if="editFormItems.length === 0"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t('bundles.form.items.empty') }}
                                    </div>
                                    <div
                                        v-else
                                        class="space-y-2 rounded-md border p-2"
                                    >
                                        <div
                                            v-for="(
                                                item, index
                                            ) in editFormItems"
                                            :key="`${item.type}:${item.id}`"
                                            class="grid grid-cols-[1fr_120px_auto] items-center gap-2"
                                        >
                                            <div class="text-sm">
                                                {{ item.name }}
                                                <span
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    ({{
                                                        formatItemType(
                                                            item.type,
                                                        )
                                                    }})
                                                </span>
                                            </div>
                                            <Input
                                                v-model.number="item.quantity"
                                                type="number"
                                                min="1"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    removeItemFromForm(
                                                        index,
                                                        editFormItems,
                                                    )
                                                "
                                            >
                                                {{
                                                    t(
                                                        'bundles.form.items.remove',
                                                    )
                                                }}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="ghost" type="button">
                                        {{ t('bundles.form.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    :disabled="isUpdating"
                                    @click="submitEdit"
                                >
                                    {{
                                        isUpdating
                                            ? t('bundles.edit.saving')
                                            : t('bundles.edit.save')
                                    }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <AppDataTableShell
                :title="t('bundles.listTitle')"
                :description="summary"
                :search="table.search.value"
                :search-label="t('bundles.searchLabel')"
                :search-placeholder="t('bundles.searchPlaceholder')"
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
                                for="bundle-search"
                            >
                                {{ t('bundles.searchLabel') }}
                            </label>
                            <Input
                                id="bundle-search"
                                v-model="table.search.value"
                                :placeholder="t('bundles.searchPlaceholder')"
                                class="min-w-[220px]"
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

                <template #cards>
                    <Card
                        v-for="bundle in filteredBundles"
                        :key="bundle.id"
                        class="p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold">
                                    {{ bundle.name }}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ bundle.description || '—' }}
                                </p>
                            </div>
                            <AppRowActions>
                                <AppActionIconButton
                                    :action="Actions.view"
                                    :label-key="'bundles.actions.view'"
                                    as="link"
                                    :href="
                                        bundlesShow({ bundle: bundle.id }).url
                                    "
                                />
                                <AppActionIconButton
                                    :action="Actions.edit"
                                    :label-key="'bundles.edit.action'"
                                    @click="openEdit(bundle)"
                                />
                                <AppActionIconButton
                                    :action="Actions.enable"
                                    :label-key="'common.notAvailable'"
                                    disabled
                                />
                                <AppActionIconButton
                                    :action="Actions.more"
                                    :label-key="'bundles.actions.more'"
                                    :tooltip="false"
                                    disabled
                                />
                            </AppRowActions>
                        </div>
                        <div
                            class="mt-4 flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ t('bundles.table.price') }}
                            </span>
                            <span class="font-medium">
                                {{
                                    formatCurrency(
                                        bundle.price,
                                        bundle.currency,
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ t('bundles.table.items') }}
                            </span>
                            <Badge variant="secondary">
                                {{ bundle.itemsCount }}
                            </Badge>
                        </div>
                    </Card>

                    <Card v-if="!filteredBundles.length" class="p-6">
                        <p class="text-center text-sm text-muted-foreground">
                            <span v-if="table.search">
                                {{
                                    t('bundles.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </span>
                            <span v-else>
                                {{ t('bundles.empty.noBundles') }}
                            </span>
                        </p>
                    </Card>
                </template>

                <div class="overflow-x-auto">
                    <div
                        v-if="hasError"
                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        <p>{{ t('bundles.errors.load') }}</p>
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
                        :rows="tableData"
                        :columns="columns"
                        :row-key="bundleRowKey"
                        :get-row-can-expand="canExpandRow"
                        :get-sub-rows="getSubRowsFor"
                        :on-row-expand="handleRowExpand"
                    >
                        <template v-slot:[slotNames.name]="{ row }">
                            <template v-if="isStateRow(row)">
                                <div v-if="row.state !== 'error'">
                                    {{ row.message }}
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <span>{{ row.message }}</span>
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        @click="loadBundleItems(row.bundle_id)"
                                    >
                                        {{ t('common.retry') }}
                                    </Button>
                                </div>
                            </template>
                            <template v-else-if="isItemRow(row)">
                                <Link
                                    v-if="row.type === 'bundle' && row.id"
                                    :href="bundlesShow(row.id).url"
                                    class="hover:underline"
                                >
                                    {{ row.name ?? '—' }}
                                </Link>
                                <span v-else>
                                    {{ row.name ?? '—' }}
                                </span>
                            </template>
                            <template v-else>
                                <Link
                                    :href="bundlesShow(row.id).url"
                                    class="hover:underline"
                                >
                                    {{ row.name }}
                                </Link>
                            </template>
                        </template>

                        <template v-slot:[slotNames.description]="{ row }">
                            <span v-if="isStateRow(row)"></span>
                            <span v-else class="text-muted-foreground">
                                {{ row.description || '—' }}
                            </span>
                        </template>

                        <template v-slot:[slotNames.price]="{ row }">
                            <template v-if="isStateRow(row)"></template>
                            <span v-else class="font-medium">
                                {{
                                    row.price
                                        ? formatCurrency(
                                              row.price,
                                              row.currency,
                                          )
                                        : '—'
                                }}
                            </span>
                        </template>

                        <template v-slot:[slotNames.itemsCount]="{ row }">
                            <span v-if="isBundleRow(row)">
                                {{ row.itemsCount ?? 0 }}
                            </span>
                        </template>

                        <template v-slot:[slotNames.status]="{ row }">
                            <template v-if="isStateRow(row)"></template>
                            <span v-else-if="row.isActive === null">—</span>
                            <span
                                v-else
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="
                                    row.isActive
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-rose-100 text-rose-700'
                                "
                                :aria-label="
                                    row.isActive
                                        ? t('bundles.status.ariaActive')
                                        : t('bundles.status.ariaInactive')
                                "
                            >
                                {{
                                    row.isActive
                                        ? t('bundles.status.active')
                                        : t('bundles.status.inactive')
                                }}
                            </span>
                        </template>

                        <template v-slot:[slotNames.actions]="{ row }">
                            <div
                                v-if="isBundleRow(row)"
                                class="flex items-center justify-end gap-2"
                            >
                                <AppActionIconButton
                                    :action="Actions.view"
                                    :label-key="'bundles.actions.view'"
                                    as="link"
                                    :href="bundlesShow({ bundle: row.id }).url"
                                />
                                <AppActionIconButton
                                    :action="Actions.edit"
                                    :label-key="'bundles.edit.action'"
                                    @click="openEdit(row)"
                                />
                                <AppActionIconButton
                                    :action="Actions.enable"
                                    :label-key="'common.notAvailable'"
                                    disabled
                                />
                                <AppActionIconButton
                                    :action="Actions.more"
                                    :label-key="'bundles.actions.more'"
                                    :tooltip="false"
                                    disabled
                                />
                            </div>
                        </template>

                        <template v-slot:[slotNames.empty]>
                            <div v-if="table.search">
                                {{
                                    t('bundles.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </div>
                            <div v-else>
                                {{ t('bundles.empty.noBundles') }}
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
                                t('bundles.pageSummary', {
                                    current: bundles.meta.current_page,
                                    last: bundles.meta.last_page,
                                })
                            }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <template
                                v-for="link in paginationLinks"
                                :key="link.label"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="rounded-md border border-input px-3 py-1 text-xs font-medium transition hover:bg-muted"
                                    :class="
                                        link.active
                                            ? 'bg-muted text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                    :aria-label="paginationLabel(link.label)"
                                >
                                    {{ paginationLabel(link.label) }}
                                </Link>
                                <span
                                    v-else
                                    class="rounded-md border border-input px-3 py-1 text-xs text-muted-foreground opacity-60"
                                    :aria-label="paginationLabel(link.label)"
                                >
                                    {{ paginationLabel(link.label) }}
                                </span>
                            </template>
                        </div>
                    </div>
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
