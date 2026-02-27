<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AlertError from '@/components/AlertError.vue';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTable from '@/components/AppDataTable.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppPageActions from '@/components/AppPageActions.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import ProductForm, {
    type ProductFormData,
} from '@/components/ProductForm.vue';
import ProductImportDialog from '@/components/ProductImportDialog.vue';
import Toast from '@/components/Toast.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useCurrencyFormat } from '@/composables/useCurrencyFormat';
import { useDataTable } from '@/composables/useDataTable';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import productsRoutes from '@/routes/products';

defineOptions({ layout: AppLayout });

type Product = {
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
    taxes?: Array<{
        taxId: number;
        name: string;
        code: string;
        jurisdiction: string | null;
        rate: number;
        startAt: string;
        endAt: string | null;
    }>;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type ProductsPayload = {
    data: Product[];
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

const props = defineProps<{
    products: ProductsPayload;
    unitOptions: string[];
    taxes: Array<{
        id: number;
        name: string;
        code: string;
        jurisdiction: string;
        rate: number;
    }>;
    defaultCurrency: string;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormat();
const {
    toast,
    error: errorToast,
    success: successToast,
    info: infoToast,
} = useToast();
const EXPORT_POLL_INTERVAL_MS = 2000;
const EXPORT_MAX_POLL_ATTEMPTS = 300;

// Dialogs
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const editingProduct = ref<Product | null>(null);

const editInitialData = computed<Partial<ProductFormData> | undefined>(() => {
    if (!editingProduct.value) return undefined;
    return {
        name: editingProduct.value.name,
        description: editingProduct.value.description ?? '',
        observation: editingProduct.value.observations?.[0]?.body ?? '',
        cost: editingProduct.value.cost ?? '',
        price: editingProduct.value.price,
        currency: editingProduct.value.currency,
        unit: editingProduct.value.unit ?? 'unit',
        isActive: editingProduct.value.isActive,
        taxes: editingProduct.value.taxes ?? [],
    };
});

function openEdit(product: Product) {
    editingProduct.value = product;
    isEditOpen.value = true;
}

function onCreateSuccess() {
    isCreateOpen.value = false;
    router.reload({ only: ['products'] });
}

function onEditSuccess() {
    isEditOpen.value = false;
    editingProduct.value = null;
    router.reload({ only: ['products'] });
}

const table = useDataTable({
    route: () => productsRoutes.index.url(),
    initialSearch:
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('q') ?? '')
            : '',
    initialPerPage: props.products.meta.per_page ?? 15,
    searchKey: 'q',
    autoSearch: false,
});
const columns = computed(
    () =>
        [
            { key: 'name', header: t('products.table.name') },
            { key: 'description', header: t('products.table.description') },
            { key: 'price', header: t('products.table.price'), align: 'right' },
            {
                key: 'status',
                header: t('products.table.status'),
                align: 'center',
            },
            {
                key: 'actions',
                header: t('products.table.actions'),
                align: 'right',
            },
        ] as const,
);
const slotNames = {
    description: 'cell(description)',
    price: 'cell(price)',
    status: 'cell(status)',
    actions: 'cell(actions)',
    empty: 'empty()',
} as const;

// Loading state
const isLoading = ref(false);
const isExporting = ref(false);
let unsubscribeStart: (() => void) | null = null;
let unsubscribeFinish: (() => void) | null = null;
let unsubscribeError: (() => void) | null = null;

onMounted(() => {
    unsubscribeStart = router.on('start', () => {
        isLoading.value = true;
    });
    unsubscribeFinish = router.on('finish', () => {
        isLoading.value = false;
    });
    unsubscribeError = router.on('error', () => {
        isLoading.value = false;
    });
});

onBeforeUnmount(() => {
    unsubscribeStart?.();
    unsubscribeFinish?.();
    unsubscribeError?.();
});

// Toggle status
const togglingProductId = ref<number | null>(null);
const actionErrorMessages = ref<string[]>([]);

function toggleStatus(product: Product) {
    actionErrorMessages.value = [];
    togglingProductId.value = product.id;

    router.patch(
        productsRoutes.update.url(product.id),
        {
            name: product.name,
            description: product.description ?? null,
            cost: product.cost ?? null,
            price: Number(product.price),
            currency: product.currency,
            unit: product.unit ?? 'unit',
            isActive: !product.isActive,
            taxes: product.taxes ?? [],
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                togglingProductId.value = null;
            },
            onError: () => {
                actionErrorMessages.value = [t('products.actions.error')];
                togglingProductId.value = null;
            },
        },
    );
}

// Computed
const hasError = computed(
    () => !props.products || !Array.isArray(props.products.data),
);
const paginationLinks = computed(() => props.products.meta.links ?? []);
const summary = computed(() => {
    if (!props.products.meta.total) {
        return t('products.summaryEmpty');
    }

    return t('products.summary', {
        from: props.products.meta.from ?? 0,
        to: props.products.meta.to ?? 0,
        total: props.products.meta.total,
    });
});

const filteredProducts = computed(() => props.products.data);

function applySearch() {
    table.applySearch();
}

function clearSearch() {
    table.clearSearch();
    table.applySearch();
}

async function exportProducts() {
    isExporting.value = true;

    try {
        const response = await fetch('/api/v1/product-exports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                search: query.value || undefined,
            }),
        });

        if (response.status !== 202) {
            throw new Error('Export queue request failed');
        }

        const job = await response.json();

        infoToast('Exportacion en cola. Te avisaremos cuando este lista.');

        let downloadUrl: string | null = null;
        let attempts = 0;

        while (attempts < EXPORT_MAX_POLL_ATTEMPTS) {
            attempts++;

            await new Promise((resolve) => {
                setTimeout(resolve, EXPORT_POLL_INTERVAL_MS);
            });

            const statusResponse = await fetch(job.status_url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!statusResponse.ok) {
                throw new Error('Unable to get export status');
            }

            const statusPayload = await statusResponse.json();

            if (statusPayload.status === 'completed') {
                downloadUrl = statusPayload.download_url ?? job.download_url;
                break;
            }

            if (statusPayload.status === 'failed') {
                throw new Error(
                    statusPayload.error ?? 'No se pudo generar la exportacion.',
                );
            }
        }

        if (!downloadUrl) {
            infoToast(
                'La exportacion sigue en proceso. Intenta descargar nuevamente en unos minutos.',
                7000,
            );
            return;
        }

        window.location.assign(downloadUrl);
        successToast('Exportacion lista. Iniciando descarga.');
    } catch {
        errorToast(
            t('products.export.errors.generic') ||
                'No se pudo exportar el archivo.',
        );
    } finally {
        isExporting.value = false;
    }
}

function paginationLabel(label: string) {
    const normalized = label
        .replace(/&laquo;|&raquo;|&lsaquo;|&rsaquo;/g, '')
        .trim();
    if (normalized.toLowerCase().includes('previous'))
        return t('common.previous');
    if (normalized.toLowerCase().includes('next')) return t('common.next');
    return normalized;
}
</script>

<template>
    <Head :title="t('products.title')" />
    <Toast :toast="toast" />

    <!-- Edit Dialog -->
    <Dialog
        v-model:open="isEditOpen"
        @update:open="
            (open) => {
                if (!open) editingProduct = null;
            }
        "
    >
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ t('products.edit.title') }}</DialogTitle>
                <DialogDescription>{{
                    t('products.edit.description')
                }}</DialogDescription>
            </DialogHeader>
            <ProductForm
                v-if="editingProduct"
                :initial-data="editInitialData"
                :action="productsRoutes.update.url(editingProduct.id)"
                method="patch"
                :submit-label="t('products.edit.save')"
                :unit-options="props.unitOptions"
                :taxes="props.taxes"
                :default-currency="props.defaultCurrency"
                :on-cancel="
                    () => {
                        isEditOpen = false;
                    }
                "
                @success="onEditSuccess"
            />
        </DialogContent>
    </Dialog>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <AppDataTableShell
            :title="t('products.title')"
            :description="t('products.subtitle')"
            :search="table.search.value"
            :per-page="table.perPage.value"
            :per-page-label="t('common.perPage')"
            :summary="summary"
            @update:per-page="table.updatePerPage"
        >
            <template #actions>
                <AppPageActions>
                    <AppActionButton
                        :action="Actions.export"
                        :label-key="'products.actions.export'"
                        :loading-label-key="'products.actions.exporting'"
                        :loading="isExporting"
                        :disabled="isExporting"
                        @click="exportProducts"
                    />
                    <ProductImportDialog />

                    <Dialog v-model:open="isCreateOpen">
                        <DialogTrigger as-child>
                            <AppActionButton
                                :action="Actions.create"
                                :label-key="'products.form.open'"
                            />
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-xl">
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('products.form.title')
                                }}</DialogTitle>
                                <DialogDescription>{{
                                    t('products.form.description')
                                }}</DialogDescription>
                            </DialogHeader>
                            <ProductForm
                                :action="productsRoutes.store.url()"
                                method="post"
                                :submit-label="t('products.form.save')"
                                :unit-options="props.unitOptions"
                                :taxes="props.taxes"
                                :default-currency="props.defaultCurrency"
                                :on-cancel="
                                    () => {
                                        isCreateOpen = false;
                                    }
                                "
                                @success="onCreateSuccess"
                            />
                        </DialogContent>
                    </Dialog>
                </AppPageActions>
            </template>

            <template #search>
                <div class="flex flex-wrap items-end gap-2">
                    <div class="grid gap-1">
                        <label
                            class="text-xs font-medium text-muted-foreground"
                        >
                            {{ t('products.searchLabel') }}
                        </label>
                        <Input
                            v-model="table.search.value"
                            :placeholder="t('products.searchPlaceholder')"
                            class="min-w-55"
                        />
                    </div>
                    <Button variant="secondary" size="sm" @click="applySearch">
                        {{ t('common.search') }}
                    </Button>
                    <Button
                        v-if="table.search"
                        variant="ghost"
                        size="sm"
                        @click="clearSearch"
                    >
                        {{ t('common.clear') }}
                    </Button>
                </div>
            </template>

            <template #filters>
                <div v-if="actionErrorMessages.length">
                    <AlertError
                        :errors="actionErrorMessages"
                        :title="t('products.actions.errorTitle')"
                    />
                </div>
            </template>

            <template #cards>
                <Card
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold">
                                {{ product.name }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ product.description || '—' }}
                            </p>
                        </div>
                        <AppRowActions>
                            <AppActionIconButton
                                :action="Actions.view"
                                :label-key="'common.notAvailable'"
                                disabled
                            />
                            <AppActionIconButton
                                :action="Actions.edit"
                                :label-key="'products.edit.action'"
                                @click="openEdit(product)"
                            />
                            <AppActionIconButton
                                :action="
                                    product.isActive
                                        ? Actions.disable
                                        : Actions.enable
                                "
                                :label-key="
                                    product.isActive
                                        ? 'products.actions.disable'
                                        : 'products.actions.enable'
                                "
                                :disabled="togglingProductId === product.id"
                                @click="toggleStatus(product)"
                            />
                            <AppActionIconButton
                                :action="Actions.more"
                                :label-key="'products.actions.more'"
                                :tooltip="false"
                                disabled
                            />
                        </AppRowActions>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            {{ t('products.table.price') }}
                        </span>
                        <span class="font-medium">
                            {{
                                formatCurrency(product.price, product.currency)
                            }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            {{ t('products.table.status') }}
                        </span>
                        <Badge
                            class="border-transparent"
                            :class="
                                product.isActive
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-rose-100 text-rose-700'
                            "
                        >
                            {{
                                product.isActive
                                    ? t('products.status.active')
                                    : t('products.status.inactive')
                            }}
                        </Badge>
                    </div>
                </Card>

                <Card v-if="!filteredProducts.length" class="p-6">
                    <p class="text-center text-sm text-muted-foreground">
                        <span v-if="table.search">
                            {{
                                t('products.empty.noMatch', {
                                    query: table.search,
                                })
                            }}
                        </span>
                        <span v-else>
                            {{ t('products.empty.noProducts') }}
                        </span>
                    </p>
                </Card>
            </template>

            <div>
                <div
                    v-if="hasError"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    <p>{{ t('products.errors.load') }}</p>
                    <Button
                        variant="secondary"
                        size="sm"
                        class="mt-3"
                        @click="router.reload({ only: ['products'] })"
                    >
                        {{ t('common.retry') }}
                    </Button>
                </div>
                <div
                    v-else-if="isLoading"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    {{ t('common.loading') }}
                </div>
                <AppDataTable
                    v-else
                    :rows="filteredProducts"
                    :columns="columns"
                    row-key="id"
                >
                    <template v-slot:[slotNames.description]="{ row }">
                        <span class="text-muted-foreground">
                            {{ row.description || '—' }}
                        </span>
                    </template>

                    <template v-slot:[slotNames.price]="{ row }">
                        <span class="font-medium">
                            {{ formatCurrency(row.price, row.currency) }}
                        </span>
                    </template>

                    <template v-slot:[slotNames.status]="{ row }">
                        <Badge
                            class="border-transparent"
                            :class="
                                row.isActive
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-rose-100 text-rose-700'
                            "
                        >
                            {{
                                row.isActive
                                    ? t('products.status.active')
                                    : t('products.status.inactive')
                            }}
                        </Badge>
                    </template>

                    <template v-slot:[slotNames.actions]="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <AppActionIconButton
                                :action="Actions.view"
                                :label-key="'common.notAvailable'"
                                variant="ghost"
                                disabled
                            />
                            <AppActionIconButton
                                :action="Actions.edit"
                                :label-key="'products.edit.action'"
                                variant="ghost"
                                @click="openEdit(row)"
                            />
                            <AppActionIconButton
                                :action="
                                    row.isActive
                                        ? Actions.disable
                                        : Actions.enable
                                "
                                :label-key="
                                    row.isActive
                                        ? 'products.actions.disable'
                                        : 'products.actions.enable'
                                "
                                variant="ghost"
                                :disabled="togglingProductId === row.id"
                                @click="toggleStatus(row)"
                            />
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <AppActionIconButton
                                        :action="Actions.more"
                                        :label-key="'products.actions.more'"
                                        variant="ghost"
                                        :tooltip="false"
                                    />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem disabled>
                                        {{ t('products.actions.moreSoon') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </template>

                    <template v-slot:[slotNames.empty]>
                        <div v-if="table.search">
                            {{
                                t('products.empty.noMatch', {
                                    query: table.search,
                                })
                            }}
                        </div>
                        <div v-else>
                            {{ t('products.empty.noProducts') }}
                        </div>
                    </template>
                </AppDataTable>
            </div>

            <template #pagination>
                <div
                    v-if="paginationLinks.length"
                    class="flex flex-wrap items-center gap-2"
                >
                    <template v-for="(link, idx) in paginationLinks" :key="idx">
                        <Button
                            v-if="link.url === null"
                            variant="outline"
                            size="sm"
                            disabled
                        >
                            {{ paginationLabel(link.label) }}
                        </Button>
                        <Button
                            v-else
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            @click="
                                router.visit(link.url, {
                                    replace: true,
                                    preserveState: false,
                                })
                            "
                        >
                            {{ paginationLabel(link.label) }}
                        </Button>
                    </template>
                </div>
            </template>
        </AppDataTableShell>
    </div>
</template>
