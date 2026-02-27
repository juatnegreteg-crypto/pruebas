<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppPageActions from '@/components/AppPageActions.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import Toast from '@/components/Toast.vue';
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
} from '@/components/ui/dialog';
import DialogDragAndDrop from '@/components/ui/dialog/DialogDragAndDrop.vue';
import { useDataTable } from '@/composables/useDataTable';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import customersRoutes from '@/routes/customers';

defineOptions({ layout: AppLayout });

type Customer = {
    id: number;
    fullName: string;
    email: string;
    documentType: string;
    documentNumber: string;
    phoneNumber: string | null;
    createdAt: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type CustomersPaginator = {
    data: Customer[];
    links: PaginationLink[];
    meta?: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        per_page: number;
        to: number | null;
        total: number;
    };
};

type CustomerImportResult = {
    created: number;
    updated: number;
    failed: number;
    total_rows: number;
};

type CustomerImportProcessedPayload = {
    queued: boolean;
    result: CustomerImportResult | null;
    status: number;
    raw: Record<string, unknown>;
};

const CUSTOMERS_EXPORT_ENDPOINT = '/api/v1/customers/export';
const CUSTOMERS_IMPORT_ENDPOINT = '/api/v1/customers/import';
const CUSTOMERS_TEMPLATE_ENDPOINT = '/api/v1/customers/template';
const CUSTOMERS_IMPORT_STATUS_ENDPOINT_TEMPLATE =
    '/api/v1/customers/imports/{id}/status';

const page = usePage();
const { toast, success: successToast, error: errorToast } = useToast();
const { t } = useI18n();

const showDeleteDialog = ref(false);
const showImportDialog = ref(false);
const customerToDelete = ref<number | null>(null);
const deleteForm = useForm({});
const isDeleting = ref(false);
const shouldRefreshCustomersAfterImportClose = ref(false);

const createCustomerUrl = computed(() => customersRoutes.create().url);

const customers = computed(() => {
    const customersData = page.props.customers as
        | CustomersPaginator
        | undefined;

    return {
        data: customersData?.data ?? [],
        links: customersData?.links ?? [],
        meta: customersData?.meta,
    };
});

const filters = computed(() => {
    const filtersData = page.props.filters as
        | Record<string, string>
        | undefined;

    return {
        search: filtersData?.search ?? '',
        sort: filtersData?.sort ?? '',
        direction: filtersData?.direction ?? '',
    };
});

const paginationLinks = computed(() => customers.value.meta?.links ?? []);
const attributeLabels = computed<Record<string, string>>(() => ({
    fullName: t('customers.import.attributes.fullName'),
    email: t('customers.import.attributes.email'),
    documentType: t('customers.import.attributes.documentType'),
    documentNumber: t('customers.import.attributes.documentNumber'),
    phoneNumber: t('customers.import.attributes.phoneNumber'),
    street: t('customers.import.attributes.street'),
    complement: t('customers.import.attributes.complement'),
    neighborhood: t('customers.import.attributes.neighborhood'),
    city: t('customers.import.attributes.city'),
    state: t('customers.import.attributes.state'),
    postalCode: t('customers.import.attributes.postalCode'),
    country: t('customers.import.attributes.country'),
    reference: t('customers.import.attributes.reference'),
}));

function refreshCustomersList(): void {
    router.visit(customersRoutes.index.url(), {
        preserveState: false,
        preserveScroll: true,
    });
}

const table = useDataTable({
    route: () => customersRoutes.index.url(),
    initialSearch: filters.value.search,
    initialPerPage: customers.value.meta?.per_page ?? 15,
    searchKey: 'search',
    debounceMs: 250,
    getQuery: () => ({
        sort: filters.value.sort,
        direction: filters.value.direction,
    }),
});

function paginationLabel(label: string): string {
    const normalized = label
        .replace(/&laquo;|&raquo;|&lsaquo;|&rsaquo;|«|»/g, '')
        .trim();
    const lower = normalized.toLowerCase();
    const previousLabel = t('common.previous');
    const nextLabel = t('common.next');

    if (
        lower.includes('previous') ||
        lower.includes(previousLabel.toLowerCase())
    ) {
        return previousLabel;
    }

    if (lower.includes('next') || lower.includes(nextLabel.toLowerCase())) {
        return nextLabel;
    }

    return normalized;
}

const summary = computed(() => {
    if (!customers.value.meta?.total) {
        return t('customers.index.summaryEmpty');
    }

    return t('customers.index.summary', {
        from: customers.value.meta.from ?? 0,
        to: customers.value.meta.to ?? 0,
        total: customers.value.meta.total,
    });
});

const pageSummary = computed(() => {
    if (!customers.value.meta) {
        return '';
    }

    return t('customers.index.pageSummary', {
        current: customers.value.meta.current_page,
        last: customers.value.meta.last_page,
    });
});

function openDeleteDialog(id: number) {
    customerToDelete.value = id;
    showDeleteDialog.value = true;
}

function confirmDelete() {
    if (customerToDelete.value === null) {
        return;
    }

    isDeleting.value = true;
    const currentCustomerId = customerToDelete.value;

    deleteForm.delete(customersRoutes.destroy.url(currentCustomerId), {
        onSuccess: () => {
            successToast(t('customers.index.toast.deleteSuccess'));
            showDeleteDialog.value = false;
            customerToDelete.value = null;
            refreshCustomersList();
        },
        onError: () => {
            errorToast(t('customers.index.toast.deleteError'));
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

function cancelDelete() {
    showDeleteDialog.value = false;
    customerToDelete.value = null;
}

function handleCustomerImportProcessed(
    payload: CustomerImportProcessedPayload,
): void {
    if (payload.queued) {
        successToast(t('customers.import.toast.queued'));
        return;
    }

    if (!payload.result) {
        return;
    }

    const hasErrors = payload.result.failed > 0;
    const hasSuccess = payload.result.created > 0 || payload.result.updated > 0;

    if (hasErrors && !hasSuccess) {
        errorToast(
            t('customers.import.toast.failedAll', {
                failed: payload.result.failed,
            }),
        );
        return;
    }

    if (hasErrors && hasSuccess) {
        errorToast(
            t('customers.import.toast.completedWithErrors', {
                failed: payload.result.failed,
            }),
        );
        shouldRefreshCustomersAfterImportClose.value = true;
        return;
    }

    if (hasSuccess) {
        successToast(t('customers.import.toast.completed'));
        shouldRefreshCustomersAfterImportClose.value = true;
    }
}

watch(showImportDialog, (isOpen) => {
    if (!isOpen && shouldRefreshCustomersAfterImportClose.value) {
        shouldRefreshCustomersAfterImportClose.value = false;
        refreshCustomersList();
    }
});
</script>

<template>
    <Head :title="t('customers.index.headTitle')" />

    <Toast :toast="toast" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <AppDataTableShell
            :title="t('customers.index.title')"
            :description="t('customers.index.description')"
            :search="table.search.value"
            :search-label="t('customers.index.searchLabel')"
            :search-placeholder="t('customers.index.searchPlaceholder')"
            :per-page="table.perPage.value"
            :per-page-label="t('customers.index.perPage')"
            :summary="summary"
            @update:search="table.updateSearch"
            @update:per-page="table.updatePerPage"
        >
            <template #actions>
                <AppPageActions class="w-full sm:w-auto">
                    <AppActionButton
                        :action="Actions.export"
                        :label-key="'customers.index.actions.export'"
                        as="a"
                        :href="CUSTOMERS_EXPORT_ENDPOINT"
                        class="w-full sm:w-auto"
                    />
                    <AppActionButton
                        :action="Actions.import"
                        :label-key="'customers.index.actions.import'"
                        class="w-full sm:w-auto"
                        @click="showImportDialog = true"
                    />
                    <AppActionButton
                        :action="Actions.create"
                        :label-key="'customers.index.actions.create'"
                        as="link"
                        :href="createCustomerUrl"
                        class="w-full sm:w-auto"
                    />
                </AppPageActions>
            </template>

            <template #cards>
                <Card
                    v-for="customer in customers.data"
                    :key="customer.id"
                    class="p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold">
                                {{ customer.fullName }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{
                                    customer.email ||
                                    t('customers.index.emptyValue')
                                }}
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
                                :label-key="'customers.index.actions.edit'"
                                as="link"
                                :href="customersRoutes.edit(customer.id).url"
                            />
                            <AppActionIconButton
                                :action="Actions.delete"
                                :label-key="'customers.index.actions.delete'"
                                @click="openDeleteDialog(customer.id)"
                            />
                            <AppActionIconButton
                                :action="Actions.more"
                                :label-key="'customers.index.actions.more'"
                                :tooltip="false"
                                disabled
                            />
                        </AppRowActions>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">
                                {{ t('customers.index.table.document') }}
                            </span>
                            <span class="font-medium">
                                {{
                                    t(
                                        `customers.documentType.values.${customer.documentType}`,
                                    )
                                }}
                                ·
                                {{ customer.documentNumber }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">
                                {{ t('customers.index.table.phone') }}
                            </span>
                            <span class="font-medium">
                                {{
                                    customer.phoneNumber ||
                                    t('customers.index.emptyValue')
                                }}
                            </span>
                        </div>
                    </div>
                </Card>

                <Card v-if="customers.data.length === 0" class="p-6">
                    <p class="text-center text-sm text-muted-foreground">
                        {{ t('customers.index.empty') }}
                    </p>
                </Card>
            </template>

            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('customers.index.table.fullName') }}
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('customers.index.table.email') }}
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('customers.index.table.document') }}
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('customers.index.table.phone') }}
                                </th>
                                <th class="px-6 py-3 text-right font-medium">
                                    {{ t('customers.index.table.actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                class="hover:bg-muted/50"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium">
                                        {{ customer.fullName }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-muted-foreground">
                                    {{
                                        customer.email ||
                                        t('customers.index.emptyValue')
                                    }}
                                </td>

                                <td class="px-6 py-4 text-muted-foreground">
                                    {{
                                        t(
                                            `customers.documentType.values.${customer.documentType}`,
                                        )
                                    }}
                                    -
                                    {{ customer.documentNumber }}
                                </td>

                                <td class="px-6 py-4 text-muted-foreground">
                                    {{
                                        customer.phoneNumber ||
                                        t('customers.index.emptyValue')
                                    }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <AppRowActions class="justify-end">
                                        <AppActionIconButton
                                            :action="Actions.view"
                                            :label-key="'common.notAvailable'"
                                            disabled
                                        />
                                        <AppActionIconButton
                                            :action="Actions.edit"
                                            :label-key="'customers.index.actions.edit'"
                                            as="link"
                                            :href="
                                                customersRoutes.edit(
                                                    customer.id,
                                                ).url
                                            "
                                        />
                                        <AppActionIconButton
                                            :action="Actions.delete"
                                            :label-key="'customers.index.actions.delete'"
                                            @click="
                                                openDeleteDialog(customer.id)
                                            "
                                        />
                                        <AppActionIconButton
                                            :action="Actions.more"
                                            :label-key="'customers.index.actions.more'"
                                            :tooltip="false"
                                            disabled
                                        />
                                    </AppRowActions>
                                </td>
                            </tr>

                            <tr v-if="customers.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-8 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('customers.index.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
            <template #pagination>
                <div
                    v-if="paginationLinks.length > 0"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 px-4 py-4"
                >
                    <p class="text-xs text-muted-foreground">
                        {{ pageSummary }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template
                            v-for="(link, idx) in paginationLinks"
                            :key="idx"
                        >
                            <Button
                                v-if="link.url"
                                variant="outline"
                                size="sm"
                                as-child
                                :class="[
                                    'h-7 px-3 py-1 text-xs',
                                    link.active
                                        ? 'bg-muted text-foreground'
                                        : 'text-muted-foreground',
                                ]"
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

        <DialogDragAndDrop
            v-model:open="showImportDialog"
            :import-endpoint="CUSTOMERS_IMPORT_ENDPOINT"
            :template-endpoint="CUSTOMERS_TEMPLATE_ENDPOINT"
            :status-endpoint-template="
                CUSTOMERS_IMPORT_STATUS_ENDPOINT_TEMPLATE
            "
            :title="t('customers.import.title')"
            :description="t('customers.import.description')"
            :file-label="t('customers.import.fileLabel')"
            :template-help-text="t('customers.import.templateHelpText')"
            :template-button-text="t('customers.import.templateButtonText')"
            :note="t('customers.import.note')"
            :processing-text="t('customers.import.processingText')"
            :result-title-success="t('customers.import.resultTitleSuccess')"
            :result-title-with-errors="
                t('customers.import.resultTitleWithErrors')
            "
            :attribute-labels="attributeLabels"
            :icon="Actions.import.icon"
            @processed="handleCustomerImportProcessed"
        />

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ t('customers.delete.title') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ t('customers.delete.description') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            variant="secondary"
                            @click="cancelDelete"
                            :disabled="deleteForm.processing"
                        >
                            {{ t('customers.delete.cancel') }}
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        @click="confirmDelete"
                        :disabled="deleteForm.processing"
                    >
                        {{
                            deleteForm.processing
                                ? t('customers.delete.deleting')
                                : t('customers.delete.confirm')
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
