<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { type BreadcrumbItem } from '@/types';
import { Actions } from '@/types/actions/action-map';
import AlertError from '@/components/AlertError.vue';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTable from '@/components/AppDataTable.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import CatalogItemTaxesForm, {
    type CatalogItemTaxForm,
} from '@/components/CatalogItemTaxesForm.vue';
import InputError from '@/components/InputError.vue';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { index as servicesIndex } from '@/routes/services';

type Service = {
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
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type ServicesPayload = {
    data: Service[];
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
    services: ServicesPayload;
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
const services = computed(() => props.services);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('services.title'),
        href: servicesIndex().url,
    },
];

const table = useDataTable({
    route: () => servicesIndex(),
    initialSearch:
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('q') ?? '')
            : '',
    initialPerPage: props.services.meta.per_page ?? 15,
    searchKey: 'q',
    autoSearch: false,
});
const columns = computed(
    () =>
        [
            { key: 'name', header: t('services.table.name') },
            { key: 'description', header: t('services.table.description') },
            { key: 'price', header: t('services.table.price'), align: 'right' },
            {
                key: 'status',
                header: t('services.table.status'),
                align: 'center',
            },
            {
                key: 'actions',
                header: t('services.table.actions'),
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
const isLoading = ref(false);
const hasError = computed(
    () => !props.services || !Array.isArray(props.services.data),
);
const isCreateOpen = ref(false);
const isSubmitting = ref(false);
const isImportOpen = ref(false);
const isImporting = ref(false);
const isEditOpen = ref(false);
const isUpdating = ref(false);
const togglingServiceId = ref<number | null>(null);
const editingServiceId = ref<number | null>(null);
const formErrors = ref<Record<string, string>>({});
const formErrorMessages = ref<string[]>([]);
const actionErrorMessages = ref<string[]>([]);
const importErrorMessages = ref<string[]>([]);
const importFileError = ref<string | null>(null);
const importFile = ref<File | null>(null);
const importProgress = ref<{ processed: number; total: number } | null>(null);
const importResult = ref<null | {
    total_rows: number;
    created: number;
    updated: number;
    skipped: number;
    failed: number;
    errors: Array<{
        fila: number;
        campo: string;
        valor: string | number | null;
        mensaje: string;
    }>;
    total_errors: number;
    errors_truncated: boolean;
}>(null);

// ── Export state ────────────────────────────────────────────────────
const EXPORT_UUID_KEY = 'services_export_uuid';
const isExporting = ref(false);
const exportProgress = ref<{ processed: number; total: number } | null>(null);
const exportErrorMessages = ref<string[]>([]);
let exportPollTimer: ReturnType<typeof setInterval> | null = null;

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

const paginationLinks = computed(() => props.services.meta.links ?? []);

const filteredServices = computed(() => props.services.data);

const summary = computed(() => {
    if (!props.services.meta.total) {
        return t('services.summaryEmpty');
    }

    return t('services.summary', {
        from: props.services.meta.from ?? 0,
        to: props.services.meta.to ?? 0,
        total: props.services.meta.total,
    });
});

function applySearch() {
    table.applySearch();
}

function clearSearch() {
    table.clearSearch();
    table.applySearch();
}

function retryLoad() {
    router.reload({ only: ['services'] });
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
    formErrors.value = {};
    formErrorMessages.value = [];
}

function resetImportForm() {
    importFile.value = null;
    importFileError.value = null;
    importErrorMessages.value = [];
    importResult.value = null;
    importProgress.value = null;
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
    editingServiceId.value = null;
    formErrors.value = {};
    formErrorMessages.value = [];
}

function setFormError(field: string, message: string) {
    formErrors.value = {
        ...formErrors.value,
        [field]: message,
    };
}

function setImportFileError(message: string) {
    importFileError.value = message;
}

function handleImportFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    importFile.value = file;
    importFileError.value = null;
}

function validateImportFile(file: File | null) {
    if (!file) {
        setImportFileError(t('services.import.errors.fileRequired'));
        return false;
    }

    const allowedExtensions = ['xls', 'xlsx', 'ods'];
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

    if (!allowedExtensions.includes(extension)) {
        setImportFileError(t('services.import.errors.fileType'));
        return false;
    }

    return true;
}

function normalizeImportResult(result: unknown) {
    if (!result || typeof result !== 'object') {
        return null;
    }

    const raw = result as {
        total_rows?: number | string;
        totalRows?: number | string;
        created?: number | string;
        updated?: number | string;
        skipped?: number | string;
        failed?: number | string;
        errors?: Array<{
            fila: number;
            campo: string;
            valor: string | number | null;
            mensaje: string;
        }>;
    };

    const created = Number(raw.created ?? 0);
    const updated = Number(raw.updated ?? 0);
    const skipped = Number(raw.skipped ?? 0);
    const failed = Number(raw.failed ?? 0);
    const reportedTotal = Number(raw.total_rows ?? raw.totalRows ?? 0);
    const total_rows = reportedTotal || created + updated + skipped + failed;
    const errors = Array.isArray(raw.errors) ? raw.errors : [];
    const total_errors = Number(
        (raw as { total_errors?: number }).total_errors ?? errors.length,
    );
    const errors_truncated = Boolean(
        (raw as { errors_truncated?: boolean }).errors_truncated ?? false,
    );

    return {
        total_rows,
        created,
        updated,
        skipped,
        failed,
        errors,
        total_errors,
        errors_truncated,
    };
}

async function submitImport() {
    importErrorMessages.value = [];
    importFileError.value = null;
    importResult.value = null;
    importProgress.value = null;

    if (!validateImportFile(importFile.value)) {
        return;
    }

    const formData = new FormData();
    formData.append('file', importFile.value as File);

    isImporting.value = true;

    try {
        const response = await fetch('/api/v1/service-imports', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
            },
            body: formData,
        });

        if (response.status === 422) {
            const data = await response.json();
            const errors = data?.errors ?? {};

            if (errors.file?.[0]) {
                importFileError.value = errors.file[0];
            } else if (data?.detail) {
                importErrorMessages.value = [data.detail];
            } else {
                importErrorMessages.value = [
                    t('services.import.errors.generic'),
                ];
            }

            isImporting.value = false;

            return;
        }

        if (!response.ok) {
            importErrorMessages.value = [t('services.import.errors.generic')];
            isImporting.value = false;

            return;
        }

        const data = await response.json();
        const uuid = data?.import_job_uuid;

        if (!uuid) {
            importErrorMessages.value = [t('services.import.errors.generic')];
            isImporting.value = false;

            return;
        }

        // Poll for import completion
        await pollImportStatus(uuid);
    } catch {
        importErrorMessages.value = [t('services.import.errors.generic')];
        isImporting.value = false;
    }
}

async function pollImportStatus(uuid: string) {
    const POLL_INTERVAL = 2000;
    const MAX_POLLS = 300; // 10 minutes max
    let polls = 0;

    const poll = async (): Promise<void> => {
        polls++;

        if (polls > MAX_POLLS) {
            importErrorMessages.value = [t('services.import.errors.timeout')];
            isImporting.value = false;
            return;
        }

        try {
            const response = await fetch(`/api/v1/service-imports/${uuid}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                importErrorMessages.value = [
                    t('services.import.errors.generic'),
                ];
                isImporting.value = false;

                return;
            }

            const data = await response.json();

            // Update progress indicator
            if (data.total_chunks > 0) {
                importProgress.value = {
                    processed: data.processed_chunks ?? 0,
                    total: data.total_chunks,
                };
            }

            if (data.status === 'completed') {
                importResult.value = normalizeImportResult(data.result);
                isImporting.value = false;
                importProgress.value = null;
                router.reload({ only: ['services'] });

                return;
            }

            if (data.status === 'failed') {
                importErrorMessages.value = [
                    data.error || t('services.import.errors.generic'),
                ];
                isImporting.value = false;
                importProgress.value = null;

                return;
            }

            // Still pending or processing — poll again
            await new Promise((resolve) => setTimeout(resolve, POLL_INTERVAL));
            await poll();
        } catch {
            importErrorMessages.value = [t('services.import.errors.generic')];
            isImporting.value = false;
        }
    };

    await poll();
}

// ── Export functions ────────────────────────────────────────────────

function stopExportPoll() {
    if (exportPollTimer) {
        clearInterval(exportPollTimer);
        exportPollTimer = null;
    }
}

function cleanupExport() {
    stopExportPoll();
    localStorage.removeItem(EXPORT_UUID_KEY);
    isExporting.value = false;
    exportProgress.value = null;
}

function generateExportFilename(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}_${pad(now.getHours())}-${pad(now.getMinutes())}-${pad(now.getSeconds())}`;
    return `servicios_export_${stamp}.xlsx`;
}

function triggerExportDownload(uuid: string) {
    const filename = encodeURIComponent(generateExportFilename());
    const url = `/api/v1/service-exports/${uuid}?format=xlsx&filename=${filename}`;

    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = '';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
}

function pollExportStatus(uuid: string) {
    let pollCount = 0;
    const maxPolls = 300; // ~10 min at 2s interval

    exportPollTimer = setInterval(async () => {
        pollCount++;

        if (pollCount > maxPolls) {
            cleanupExport();
            exportErrorMessages.value = [t('services.export.timeout')];
            return;
        }

        try {
            const res = await fetch(`/api/v1/service-exports/${uuid}`, {
                headers: { Accept: 'application/json' },
            });

            if (!res.ok) {
                cleanupExport();
                exportErrorMessages.value = [
                    t('services.export.errors.generic'),
                ];
                return;
            }

            const data = await res.json();

            exportProgress.value = {
                processed: Number(data.processed_chunks ?? 0),
                total: Number(data.total_chunks ?? 0),
            };

            if (data.status === 'completed') {
                cleanupExport();
                triggerExportDownload(uuid);
                router.reload({ only: ['services'] });
            } else if (data.status === 'failed') {
                cleanupExport();
                exportErrorMessages.value = [
                    data.error || t('services.export.errors.generic'),
                ];
            }
        } catch {
            cleanupExport();
            exportErrorMessages.value = [t('services.export.errors.generic')];
        }
    }, 2000);
}

async function submitExport() {
    exportErrorMessages.value = [];
    exportProgress.value = null;
    isExporting.value = true;

    try {
        const res = await fetch('/api/v1/service-exports', {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            isExporting.value = false;
            exportErrorMessages.value = [t('services.export.errors.generic')];
            return;
        }

        const data = await res.json();
        const uuid = data.uuid as string;

        localStorage.setItem(EXPORT_UUID_KEY, uuid);
        pollExportStatus(uuid);
    } catch {
        isExporting.value = false;
        exportErrorMessages.value = [t('services.export.errors.generic')];
    }
}

async function submitCreate() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!createForm.name.trim()) {
        setFormError('name', t('services.form.errors.nameRequired'));
    }

    if (!String(createForm.price).trim()) {
        setFormError('price', t('services.form.errors.priceRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    const payload = {
        name: createForm.name.trim(),
        description: createForm.description.trim() || null,
        observation: createForm.observation.trim() || null,
        cost: createForm.cost ? Number(createForm.cost) : null,
        price: Number(createForm.price),
        currency: createForm.currency.trim() || null,
        unit: createForm.unit,
        isActive: createForm.isActive,
        taxes: createForm.taxes,
    };

    isSubmitting.value = true;

    try {
        const response = await fetch('/api/v1/services', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
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
            formErrorMessages.value = [t('services.form.errors.generic')];
            return;
        }

        resetCreateForm();
        isCreateOpen.value = false;
        router.reload({ only: ['services'] });
    } catch {
        formErrorMessages.value = [t('services.form.errors.generic')];
    } finally {
        isSubmitting.value = false;
    }
}

function openEdit(service: Service) {
    editingServiceId.value = service.id;
    editForm.name = service.name ?? '';
    editForm.description = service.description ?? '';
    editForm.observation = service.observations?.[0]?.body ?? '';
    editForm.cost = service.cost ?? '';
    editForm.price = service.price ?? '';
    editForm.currency = service.currency ?? props.defaultCurrency;
    editForm.unit = service.unit ?? 'unit';
    editForm.isActive = service.isActive ?? true;
    editForm.taxes = service.taxes ?? [];
    isEditOpen.value = true;
    formErrors.value = {};
    formErrorMessages.value = [];
}

async function submitEdit() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!editForm.name.trim()) {
        setFormError('name', t('services.form.errors.nameRequired'));
    }

    if (!String(editForm.price).trim()) {
        setFormError('price', t('services.form.errors.priceRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    const payload = {
        name: editForm.name.trim(),
        description: editForm.description.trim() || null,
        observation: editForm.observation.trim() || null,
        cost: editForm.cost ? Number(editForm.cost) : null,
        price: Number(editForm.price),
        currency: editForm.currency.trim() || null,
        unit: editForm.unit,
        isActive: editForm.isActive,
        taxes: editForm.taxes,
    };

    if (!editingServiceId.value) {
        formErrorMessages.value = [t('services.edit.errors.generic')];
        return;
    }

    isUpdating.value = true;

    try {
        const response = await fetch(
            `/api/v1/services/${editingServiceId.value}`,
            {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
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
            formErrorMessages.value = [t('services.edit.errors.generic')];
            return;
        }

        resetEditForm();
        isEditOpen.value = false;
        router.reload({ only: ['services'] });
    } catch {
        formErrorMessages.value = [t('services.edit.errors.generic')];
    } finally {
        isUpdating.value = false;
    }
}

async function toggleStatus(service: Service) {
    actionErrorMessages.value = [];
    togglingServiceId.value = service.id;

    const payload = {
        name: service.name,
        description: service.description ?? null,
        cost: service.cost ? Number(service.cost) : null,
        price: Number(service.price),
        currency: service.currency ?? props.defaultCurrency,
        unit: service.unit ?? 'unit',
        isActive: !service.isActive,
        taxes: service.taxes ?? [],
    };

    try {
        const response = await fetch(`/api/v1/services/${service.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            actionErrorMessages.value = [t('services.actions.error')];
            return;
        }

        router.reload({ only: ['services'] });
    } catch {
        actionErrorMessages.value = [t('services.actions.error')];
    } finally {
        togglingServiceId.value = null;
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

    // Resume an in-flight export if the user navigated away and came back
    const savedUuid = localStorage.getItem(EXPORT_UUID_KEY);
    if (savedUuid) {
        isExporting.value = true;
        pollExportStatus(savedUuid);
    }
});

onBeforeUnmount(() => {
    unsubscribeStart?.();
    unsubscribeFinish?.();
    unsubscribeError?.();
    unsubscribeStart = null;
    unsubscribeFinish = null;
    unsubscribeError = null;
    stopExportPoll();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('services.title')" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        {{ t('services.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('services.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        variant="secondary"
                        :disabled="isExporting"
                        @click="submitExport"
                    >
                        <template
                            v-if="
                                isExporting &&
                                exportProgress &&
                                exportProgress.total > 0
                            "
                        >
                            {{
                                t('services.export.progressBatches', {
                                    processed: exportProgress.processed,
                                    total: exportProgress.total,
                                })
                            }}
                        </template>
                        <template v-else-if="isExporting">
                            {{ t('services.export.processing') }}
                        </template>
                        <template v-else>
                            {{ t('services.export.open') }}
                        </template>
                    </Button>
                    <Dialog
                        :open="isImportOpen"
                        @update:open="
                            (open) => {
                                if (!open && isImporting) return;
                                if (!open) resetImportForm();
                                isImportOpen = open;
                            }
                        "
                    >
                        <DialogTrigger as-child>
                            <Button variant="secondary">
                                {{ t('services.import.open') }}
                            </Button>
                        </DialogTrigger>
                        <DialogContent
                            class="sm:max-w-2xl"
                            :show-close-button="!isImporting"
                        >
                            <DialogHeader>
                                <DialogTitle>
                                    {{ t('services.import.title') }}
                                </DialogTitle>
                                <DialogDescription>
                                    {{ t('services.import.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4">
                                <div
                                    class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-800/50 dark:bg-blue-950/30 dark:text-blue-300"
                                >
                                    {{ t('services.import.recommendation') }}
                                </div>

                                <AlertError
                                    v-if="importErrorMessages.length"
                                    :errors="importErrorMessages"
                                    :title="t('services.import.errors.title')"
                                />

                                <div class="grid gap-2">
                                    <Label for="services-import-file">
                                        {{ t('services.import.fields.file') }}
                                    </Label>
                                    <Input
                                        id="services-import-file"
                                        type="file"
                                        accept=".xls,.xlsx,.ods"
                                        @change="handleImportFileChange"
                                        :aria-invalid="Boolean(importFileError)"
                                    />
                                    <InputError :message="importFileError" />
                                    <p class="text-xs text-muted-foreground">
                                        {{ t('services.import.helper') }}
                                    </p>
                                </div>

                                <div
                                    v-if="isImporting"
                                    class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-800/50 dark:bg-blue-950/30 dark:text-blue-300"
                                >
                                    <span
                                        v-if="
                                            importProgress &&
                                            importProgress.total > 0
                                        "
                                    >
                                        {{
                                            t(
                                                'services.import.progressBatches',
                                                {
                                                    processed:
                                                        importProgress.processed,
                                                    total: importProgress.total,
                                                },
                                            )
                                        }}
                                    </span>
                                    <span v-else>
                                        {{ t('services.import.processing') }}
                                    </span>
                                </div>

                                <div
                                    v-if="importResult"
                                    class="grid gap-3 rounded-lg border border-dashed border-border/70 bg-muted/30 p-4"
                                >
                                    <p class="text-sm text-foreground">
                                        {{
                                            t('services.import.summary', {
                                                total: importResult.total_rows,
                                                created: importResult.created,
                                                updated: importResult.updated,
                                                skipped: importResult.skipped,
                                                failed: importResult.failed,
                                            })
                                        }}
                                    </p>
                                    <div
                                        v-if="importResult.errors?.length"
                                        class="max-h-52 overflow-auto rounded-md border border-border/60"
                                    >
                                        <div
                                            v-if="importResult.errors_truncated"
                                            class="sticky top-0 border-b border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-300"
                                        >
                                            {{
                                                t(
                                                    'services.import.errors.truncated',
                                                    {
                                                        shown: importResult
                                                            .errors.length,
                                                        total: importResult.total_errors,
                                                    },
                                                )
                                            }}
                                        </div>
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr
                                                    class="bg-muted text-muted-foreground"
                                                >
                                                    <th
                                                        class="px-3 py-2 text-left"
                                                    >
                                                        {{
                                                            t(
                                                                'services.import.errors.row',
                                                            )
                                                        }}
                                                    </th>
                                                    <th
                                                        class="px-3 py-2 text-left"
                                                    >
                                                        {{
                                                            t(
                                                                'services.import.errors.field',
                                                            )
                                                        }}
                                                    </th>
                                                    <th
                                                        class="px-3 py-2 text-left"
                                                    >
                                                        {{
                                                            t(
                                                                'services.import.errors.value',
                                                            )
                                                        }}
                                                    </th>
                                                    <th
                                                        class="px-3 py-2 text-left"
                                                    >
                                                        {{
                                                            t(
                                                                'services.import.errors.message',
                                                            )
                                                        }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="(
                                                        error, index
                                                    ) in importResult.errors"
                                                    :key="`${error.fila}-${error.campo}-${index}`"
                                                    class="border-t border-border/60"
                                                >
                                                    <td class="px-3 py-2">
                                                        {{ error.fila }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ error.campo }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ error.valor ?? '—' }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ error.mensaje }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter class="sm:justify-between">
                                <Button
                                    v-if="!isImporting"
                                    variant="outline"
                                    type="button"
                                    as="a"
                                    href="/api/v1/service-imports/template"
                                    download
                                >
                                    {{ t('services.import.downloadTemplate') }}
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    type="button"
                                    disabled
                                >
                                    {{ t('services.import.downloadTemplate') }}
                                </Button>
                                <div class="flex gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            variant="ghost"
                                            type="button"
                                            :disabled="isImporting"
                                        >
                                            {{ t('services.import.cancel') }}
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="button"
                                        :disabled="isImporting"
                                        @click="submitImport"
                                    >
                                        {{
                                            isImporting
                                                ? t(
                                                      'services.import.processing',
                                                  )
                                                : t('services.import.submit')
                                        }}
                                    </Button>
                                </div>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <Dialog
                        v-model:open="isCreateOpen"
                        @update:open="(open) => !open && resetCreateForm()"
                    >
                        <DialogTrigger as-child>
                            <AppActionButton
                                :action="Actions.create"
                                :label-key="'services.form.open'"
                            />
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-xl">
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('services.form.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('services.form.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('services.form.errors.title')"
                                />

                                <div class="grid gap-2">
                                    <Label for="service-name">{{
                                        t('services.form.fields.name')
                                    }}</Label>
                                    <Input
                                        id="service-name"
                                        v-model="createForm.name"
                                        :placeholder="
                                            t('services.form.placeholders.name')
                                        "
                                        :aria-invalid="Boolean(formErrors.name)"
                                    />
                                    <InputError :message="formErrors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="service-description">{{
                                        t('services.form.fields.description')
                                    }}</Label>
                                    <Input
                                        id="service-description"
                                        v-model="createForm.description"
                                        :placeholder="
                                            t(
                                                'services.form.placeholders.description',
                                            )
                                        "
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="service-observation">
                                        {{
                                            t(
                                                'services.form.fields.observations',
                                            )
                                        }}
                                    </Label>
                                    <Textarea
                                        id="service-observation"
                                        v-model="createForm.observation"
                                        rows="3"
                                        :placeholder="
                                            t(
                                                'services.form.placeholders.observations',
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
                                        <Label for="service-cost">Costo</Label>
                                        <Input
                                            id="service-cost"
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
                                        <Label for="service-price">{{
                                            t('services.form.fields.price')
                                        }}</Label>
                                        <Input
                                            id="service-price"
                                            v-model="createForm.price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :placeholder="
                                                t(
                                                    'services.form.placeholders.price',
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
                                        <Label for="service-currency">
                                            Moneda
                                        </Label>
                                        <Input
                                            id="service-currency"
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
                                        <Label for="service-unit">
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
                                                id="service-unit"
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
                                        id="service-active"
                                        v-model:checked="createForm.isActive"
                                    />
                                    <Label for="service-active">{{
                                        t('services.form.fields.active')
                                    }}</Label>
                                </div>

                                <CatalogItemTaxesForm
                                    v-model="createForm.taxes"
                                    :taxes="props.taxes"
                                    :errors="formErrors"
                                />
                            </div>

                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="ghost" type="button">
                                        {{ t('services.form.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    :disabled="isSubmitting"
                                    @click="submitCreate"
                                >
                                    {{
                                        isSubmitting
                                            ? t('services.form.saving')
                                            : t('services.form.save')
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
                                    t('services.edit.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('services.edit.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('services.edit.errors.title')"
                                />

                                <div class="grid gap-2">
                                    <Label for="edit-service-name">{{
                                        t('services.form.fields.name')
                                    }}</Label>
                                    <Input
                                        id="edit-service-name"
                                        v-model="editForm.name"
                                        :placeholder="
                                            t('services.form.placeholders.name')
                                        "
                                        :aria-invalid="Boolean(formErrors.name)"
                                    />
                                    <InputError :message="formErrors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="edit-service-description">{{
                                        t('services.form.fields.description')
                                    }}</Label>
                                    <Input
                                        id="edit-service-description"
                                        v-model="editForm.description"
                                        :placeholder="
                                            t(
                                                'services.form.placeholders.description',
                                            )
                                        "
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit-service-observation">
                                        {{
                                            t(
                                                'services.form.fields.observations',
                                            )
                                        }}
                                    </Label>
                                    <Textarea
                                        id="edit-service-observation"
                                        v-model="editForm.observation"
                                        rows="3"
                                        :placeholder="
                                            t(
                                                'services.form.placeholders.observations',
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
                                        <Label for="edit-service-cost">
                                            Costo
                                        </Label>
                                        <Input
                                            id="edit-service-cost"
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
                                        <Label for="edit-service-price">{{
                                            t('services.form.fields.price')
                                        }}</Label>
                                        <Input
                                            id="edit-service-price"
                                            v-model="editForm.price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :placeholder="
                                                t(
                                                    'services.form.placeholders.price',
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
                                        <Label for="edit-service-currency">
                                            Moneda
                                        </Label>
                                        <Input
                                            id="edit-service-currency"
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
                                        <Label for="edit-service-unit">
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
                                                id="edit-service-unit"
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
                                        id="edit-service-active"
                                        v-model:checked="editForm.isActive"
                                    />
                                    <Label for="edit-service-active">{{
                                        t('services.form.fields.active')
                                    }}</Label>
                                </div>

                                <CatalogItemTaxesForm
                                    v-model="editForm.taxes"
                                    :taxes="props.taxes"
                                    :errors="formErrors"
                                />
                            </div>

                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="ghost" type="button">
                                        {{ t('services.form.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    :disabled="isUpdating"
                                    @click="submitEdit"
                                >
                                    {{
                                        isUpdating
                                            ? t('services.edit.saving')
                                            : t('services.edit.save')
                                    }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <AppDataTableShell
                :title="t('services.listTitle')"
                :description="summary"
                :search="table.search.value"
                :search-label="t('services.searchLabel')"
                :search-placeholder="t('services.searchPlaceholder')"
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
                                for="service-search"
                            >
                                {{ t('services.searchLabel') }}
                            </label>
                            <Input
                                id="service-search"
                                v-model="table.search.value"
                                :placeholder="t('services.searchPlaceholder')"
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
                            :title="t('services.actions.errorTitle')"
                        />
                    </div>

                    <div v-if="exportErrorMessages.length" class="mt-4">
                        <AlertError
                            :errors="exportErrorMessages"
                            :title="t('services.export.errors.title')"
                        />
                    </div>
                </template>

                <template #cards>
                    <Card
                        v-for="service in filteredServices"
                        :key="service.id"
                        class="p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold">
                                    {{ service.name }}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ service.description || '—' }}
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
                                    :label-key="'services.edit.action'"
                                    @click="openEdit(service)"
                                />
                                <AppActionIconButton
                                    :action="
                                        service.isActive
                                            ? Actions.disable
                                            : Actions.enable
                                    "
                                    :label-key="
                                        service.isActive
                                            ? 'services.actions.disable'
                                            : 'services.actions.enable'
                                    "
                                    :disabled="togglingServiceId === service.id"
                                    @click="toggleStatus(service)"
                                />
                                <AppActionIconButton
                                    :action="Actions.more"
                                    :label-key="'services.actions.more'"
                                    :tooltip="false"
                                    disabled
                                />
                            </AppRowActions>
                        </div>

                        <div
                            class="mt-4 flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ t('services.table.price') }}
                            </span>
                            <span class="font-medium">
                                {{
                                    formatCurrency(
                                        service.price,
                                        service.currency,
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">
                                {{ t('services.table.status') }}
                            </span>
                            <Badge
                                class="border-transparent"
                                :class="
                                    service.isActive
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-rose-100 text-rose-700'
                                "
                            >
                                {{
                                    service.isActive
                                        ? t('services.status.active')
                                        : t('services.status.inactive')
                                }}
                            </Badge>
                        </div>
                    </Card>

                    <Card v-if="!filteredServices.length" class="p-6">
                        <p class="text-center text-sm text-muted-foreground">
                            <span v-if="table.search">
                                {{
                                    t('services.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </span>
                            <span v-else>
                                {{ t('services.empty.noServices') }}
                            </span>
                        </p>
                    </Card>
                </template>

                <div class="overflow-x-auto">
                    <div
                        v-if="hasError"
                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        <p>{{ t('services.errors.load') }}</p>
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
                        :rows="filteredServices"
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
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="
                                    row.isActive
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-rose-100 text-rose-700'
                                "
                                :aria-label="
                                    row.isActive
                                        ? t('services.status.ariaActive')
                                        : t('services.status.ariaInactive')
                                "
                            >
                                {{
                                    row.isActive
                                        ? t('services.status.active')
                                        : t('services.status.inactive')
                                }}
                            </span>
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
                                    :label-key="'services.edit.action'"
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
                                            ? 'services.actions.disable'
                                            : 'services.actions.enable'
                                    "
                                    variant="ghost"
                                    :disabled="togglingServiceId === row.id"
                                    @click="toggleStatus(row)"
                                />
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <AppActionIconButton
                                            :action="Actions.more"
                                            :label-key="'services.actions.more'"
                                            variant="ghost"
                                            :tooltip="false"
                                        />
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem disabled>
                                            {{ t('services.actions.moreSoon') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </template>

                        <template v-slot:[slotNames.empty]>
                            <div v-if="table.search">
                                {{
                                    t('services.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </div>
                            <div v-else>
                                {{ t('services.empty.noServices') }}
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
                                t('services.pageSummary', {
                                    current: services.meta.current_page,
                                    last: services.meta.last_page,
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
