<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AlertError from '@/components/AlertError.vue';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTable from '@/components/AppDataTable.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import InputError from '@/components/InputError.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { InputGroup, InputGroupAddon } from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { useDataTable } from '@/composables/useDataTable';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import taxesRoutes from '@/routes/taxes';

defineOptions({ layout: AppLayout });

type Tax = {
    id: number;
    name: string;
    code: string;
    jurisdiction: string | null;
    rate: number;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type TaxesPayload = {
    data: Tax[];
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

const props = defineProps<{ taxes: TaxesPayload }>();

const { t } = useI18n();
const { toast, success: successToast, error: errorToast } = useToast();

const table = useDataTable({
    route: () => taxesRoutes.index.url(),
    initialSearch:
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('q') ?? '')
            : '',
    initialPerPage: props.taxes.meta.per_page ?? 15,
    searchKey: 'q',
    debounceMs: 250,
});
const columns = computed(
    () =>
        [
            { key: 'name', header: t('taxes.table.name') },
            { key: 'code', header: t('taxes.table.code') },
            { key: 'jurisdiction', header: t('taxes.table.jurisdiction') },
            { key: 'rate', header: t('taxes.table.rate'), align: 'right' },
            {
                key: 'actions',
                header: t('taxes.table.actions'),
                align: 'right',
            },
        ] as const,
);
const slotNames = {
    code: 'cell(code)',
    jurisdiction: 'cell(jurisdiction)',
    rate: 'cell(rate)',
    actions: 'cell(actions)',
    empty: 'empty()',
} as const;

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const editingTax = ref<Tax | null>(null);

const formErrors = ref<Record<string, string>>({});
const formErrorMessages = ref<string[]>([]);
const isSubmitting = ref(false);
const isUpdating = ref(false);
const isDeleting = ref<number | null>(null);

const createForm = ref({
    name: '',
    rate: '',
});
const editForm = ref({
    name: '',
    rate: '',
});

const hasError = computed(
    () => !props.taxes || !Array.isArray(props.taxes.data),
);
const paginationLinks = computed(() => props.taxes.meta.links ?? []);

const filteredTaxes = computed(() => props.taxes.data);

function resetCreateForm() {
    createForm.value = { name: '', rate: '' };
    formErrors.value = {};
    formErrorMessages.value = [];
}

function resetEditForm() {
    editForm.value = { name: '', rate: '' };
    editingTax.value = null;
    formErrors.value = {};
    formErrorMessages.value = [];
}

function setFormError(field: string, message: string) {
    formErrors.value = { ...formErrors.value, [field]: message };
}

function openEdit(tax: Tax) {
    editingTax.value = tax;
    editForm.value = {
        name: tax.name ?? '',
        rate: tax.rate !== undefined ? String(tax.rate) : '',
    };
    isEditOpen.value = true;
    formErrors.value = {};
    formErrorMessages.value = [];
}

async function submitCreate() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!createForm.value.name.trim()) {
        setFormError('name', t('taxes.form.errors.nameRequired'));
    }

    const rateValue = String(createForm.value.rate ?? '').trim();

    if (!rateValue) {
        setFormError('rate', t('taxes.form.errors.rateRequired'));
    } else if (Number.isNaN(Number(rateValue))) {
        setFormError('rate', t('taxes.form.errors.rateInvalid'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    isSubmitting.value = true;

    try {
        const response = await fetch('/api/v1/taxes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                name: createForm.value.name.trim(),
                rate: Number(rateValue),
            }),
        });

        if (response.status === 422) {
            const data = await response.json();
            const errors = data?.errors ?? {};
            const allowedFields = new Set(['name', 'rate']);
            const fallbackErrors: string[] = [];

            Object.keys(errors).forEach((key) => {
                const message = errors[key]?.[0] ?? '';
                if (allowedFields.has(key)) {
                    setFormError(key, message);
                } else if (message) {
                    fallbackErrors.push(message);
                }
            });

            if (fallbackErrors.length > 0) {
                formErrorMessages.value = fallbackErrors;
            }

            return;
        }

        if (!response.ok) {
            formErrorMessages.value = [t('taxes.form.errors.generic')];
            return;
        }

        resetCreateForm();
        isCreateOpen.value = false;
        successToast(t('taxes.toast.created'));
        router.reload({ only: ['taxes'] });
    } catch {
        formErrorMessages.value = [t('taxes.form.errors.generic')];
    } finally {
        isSubmitting.value = false;
    }
}

async function submitEdit() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!editingTax.value) {
        formErrorMessages.value = [t('taxes.edit.errors.generic')];
        return;
    }

    if (!editForm.value.name.trim()) {
        setFormError('name', t('taxes.form.errors.nameRequired'));
    }

    const rateValue = String(editForm.value.rate ?? '').trim();

    if (!rateValue) {
        setFormError('rate', t('taxes.form.errors.rateRequired'));
    } else if (Number.isNaN(Number(rateValue))) {
        setFormError('rate', t('taxes.form.errors.rateInvalid'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    isUpdating.value = true;

    try {
        const response = await fetch(`/api/v1/taxes/${editingTax.value.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                name: editForm.value.name.trim(),
                rate: Number(rateValue),
            }),
        });

        if (response.status === 422) {
            const data = await response.json();
            const errors = data?.errors ?? {};
            const allowedFields = new Set(['name', 'rate']);
            const fallbackErrors: string[] = [];

            Object.keys(errors).forEach((key) => {
                const message = errors[key]?.[0] ?? '';
                if (allowedFields.has(key)) {
                    setFormError(key, message);
                } else if (message) {
                    fallbackErrors.push(message);
                }
            });

            if (fallbackErrors.length > 0) {
                formErrorMessages.value = fallbackErrors;
            }

            return;
        }

        if (!response.ok) {
            formErrorMessages.value = [t('taxes.edit.errors.generic')];
            return;
        }

        resetEditForm();
        isEditOpen.value = false;
        successToast(t('taxes.toast.updated'));
        router.reload({ only: ['taxes'] });
    } catch {
        formErrorMessages.value = [t('taxes.edit.errors.generic')];
    } finally {
        isUpdating.value = false;
    }
}

async function deleteTax(tax: Tax) {
    if (!confirm(t('taxes.delete.confirm', { name: tax.name }))) {
        return;
    }

    isDeleting.value = tax.id;

    try {
        const response = await fetch(`/api/v1/taxes/${tax.id}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            errorToast(t('taxes.delete.errors.generic'));
            return;
        }

        successToast(t('taxes.toast.deleted'));
        router.reload({ only: ['taxes'] });
    } catch {
        errorToast(t('taxes.delete.errors.generic'));
    } finally {
        isDeleting.value = null;
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
</script>

<template>
    <Head :title="t('taxes.title')" />
    <Toast :toast="toast" />

    <Dialog
        v-model:open="isEditOpen"
        @update:open="(open) => !open && resetEditForm()"
    >
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ t('taxes.edit.title') }}</DialogTitle>
                <DialogDescription>{{
                    t('taxes.edit.description')
                }}</DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <AlertError
                    v-if="formErrorMessages.length"
                    :errors="formErrorMessages"
                    :title="t('taxes.edit.errors.title')"
                />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="tax-edit-name">{{
                            t('taxes.form.fields.name')
                        }}</Label>
                        <Input
                            id="tax-edit-name"
                            v-model="editForm.name"
                            :placeholder="t('taxes.form.placeholders.name')"
                            :aria-invalid="Boolean(formErrors.name)"
                        />
                        <InputError :message="formErrors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tax-edit-rate">{{
                            t('taxes.form.fields.rate')
                        }}</Label>
                        <InputGroup>
                            <Input
                                id="tax-edit-rate"
                                v-model="editForm.rate"
                                data-slot="input-group-control"
                                type="number"
                                min="0"
                                step="0.01"
                                :placeholder="t('taxes.form.placeholders.rate')"
                                :aria-invalid="Boolean(formErrors.rate)"
                                class="rounded-none border-0 bg-transparent shadow-none focus-visible:ring-0"
                            />
                            <InputGroupAddon class="border-l border-input">
                                %
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="formErrors.rate" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="tax-edit-jurisdiction">{{
                            t('taxes.form.fields.jurisdiction')
                        }}</Label>
                        <Input
                            id="tax-edit-jurisdiction"
                            :model-value="t('taxes.form.defaults.jurisdiction')"
                            readonly
                            class="bg-muted/40 text-muted-foreground"
                        />
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="ghost"
                    type="button"
                    @click="isEditOpen = false"
                >
                    {{ t('taxes.form.cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="isUpdating"
                    @click="submitEdit"
                >
                    {{
                        isUpdating
                            ? t('taxes.edit.saving')
                            : t('taxes.edit.save')
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <AppDataTableShell
            :title="t('taxes.title')"
            :description="t('taxes.subtitle')"
            :search="table.search.value"
            :search-label="t('taxes.searchLabel')"
            :search-placeholder="t('taxes.searchPlaceholder')"
            :per-page="table.perPage.value"
            :per-page-label="t('common.perPage')"
            @update:search="table.updateSearch"
            @update:per-page="table.updatePerPage"
        >
            <template #actions>
                <Dialog
                    v-model:open="isCreateOpen"
                    @update:open="(open) => !open && resetCreateForm()"
                >
                    <DialogTrigger as-child>
                        <AppActionButton
                            :action="Actions.create"
                            :label-key="'taxes.form.open'"
                        />
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>{{
                                t('taxes.form.title')
                            }}</DialogTitle>
                            <DialogDescription>{{
                                t('taxes.form.description')
                            }}</DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-4">
                            <AlertError
                                v-if="formErrorMessages.length"
                                :errors="formErrorMessages"
                                :title="t('taxes.form.errors.title')"
                            />

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="col-span-2 grid gap-2">
                                    <Label for="tax-name">{{
                                        t('taxes.form.fields.name')
                                    }}</Label>
                                    <Input
                                        id="tax-name"
                                        v-model="createForm.name"
                                        :placeholder="
                                            t('taxes.form.placeholders.name')
                                        "
                                        :aria-invalid="Boolean(formErrors.name)"
                                    />
                                    <InputError :message="formErrors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="tax-rate">{{
                                        t('taxes.form.fields.rate')
                                    }}</Label>
                                    <InputGroup>
                                        <Input
                                            id="tax-rate"
                                            v-model="createForm.rate"
                                            data-slot="input-group-control"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :placeholder="
                                                t(
                                                    'taxes.form.placeholders.rate',
                                                )
                                            "
                                            :aria-invalid="
                                                Boolean(formErrors.rate)
                                            "
                                            class="rounded-none border-0 bg-transparent shadow-none focus-visible:ring-0"
                                        />
                                        <InputGroupAddon
                                            class="border-l border-input"
                                        >
                                            %
                                        </InputGroupAddon>
                                    </InputGroup>
                                    <InputError :message="formErrors.rate" />
                                </div>

                                <div class="grid gap-2 sm:col-span-3">
                                    <Label for="tax-jurisdiction">{{
                                        t('taxes.form.fields.jurisdiction')
                                    }}</Label>
                                    <Input
                                        id="tax-jurisdiction"
                                        :model-value="
                                            t(
                                                'taxes.form.defaults.jurisdiction',
                                            )
                                        "
                                        readonly
                                        class="bg-muted/40 text-muted-foreground"
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                variant="ghost"
                                type="button"
                                @click="isCreateOpen = false"
                            >
                                {{ t('taxes.form.cancel') }}
                            </Button>
                            <Button
                                type="button"
                                :disabled="isSubmitting"
                                @click="submitCreate"
                            >
                                {{
                                    isSubmitting
                                        ? t('taxes.form.saving')
                                        : t('taxes.form.save')
                                }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </template>

            <template #cards>
                <Card v-for="tax in filteredTaxes" :key="tax.id" class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold">
                                {{ tax.name }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ tax.code }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ t('taxes.table.rate') }}: {{ tax.rate }}%
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
                                :label-key="'taxes.edit.action'"
                                @click="openEdit(tax)"
                            />
                            <AppActionIconButton
                                :action="Actions.delete"
                                :label-key="'taxes.delete.action'"
                                :disabled="isDeleting === tax.id"
                                @click="deleteTax(tax)"
                            />
                            <AppActionIconButton
                                :action="Actions.more"
                                :label-key="'taxes.actions.more'"
                                :tooltip="false"
                                disabled
                            />
                        </AppRowActions>
                    </div>
                    <div class="mt-4 text-sm text-muted-foreground">
                        {{ tax.jurisdiction || '—' }}
                    </div>
                </Card>

                <Card v-if="!filteredTaxes.length" class="p-6">
                    <p class="text-center text-sm text-muted-foreground">
                        <span v-if="table.search">
                            {{
                                t('taxes.empty.noMatch', {
                                    query: table.search,
                                })
                            }}
                        </span>
                        <span v-else>
                            {{ t('taxes.empty.noTaxes') }}
                        </span>
                    </p>
                </Card>
            </template>

            <div>
                <div
                    v-if="hasError"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    <p>{{ t('taxes.errors.load') }}</p>
                    <Button
                        variant="secondary"
                        size="sm"
                        class="mt-3"
                        @click="router.reload({ only: ['taxes'] })"
                    >
                        {{ t('common.retry') }}
                    </Button>
                </div>
                <AppDataTable
                    v-else
                    :rows="filteredTaxes"
                    :columns="columns"
                    row-key="id"
                >
                    <template v-slot:[slotNames.code]="{ value }">
                        <span class="text-muted-foreground">{{ value }}</span>
                    </template>

                    <template v-slot:[slotNames.jurisdiction]="{ row }">
                        <span class="text-muted-foreground">{{
                            row.jurisdiction || '—'
                        }}</span>
                    </template>

                    <template v-slot:[slotNames.rate]="{ value }">
                        <span class="text-muted-foreground">{{ value }}%</span>
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
                                :label-key="'taxes.edit.action'"
                                variant="ghost"
                                @click="openEdit(row)"
                            />
                            <AppActionIconButton
                                :action="Actions.delete"
                                :label-key="'taxes.delete.action'"
                                variant="ghost"
                                :disabled="isDeleting === row.id"
                                @click="deleteTax(row)"
                            />
                            <AppActionIconButton
                                :action="Actions.more"
                                :label-key="'taxes.actions.more'"
                                :tooltip="false"
                                disabled
                            />
                        </div>
                    </template>

                    <template v-slot:[slotNames.empty]>
                        <div v-if="table.search">
                            {{
                                t('taxes.empty.noMatch', {
                                    query: table.search,
                                })
                            }}
                        </div>
                        <div v-else>
                            {{ t('taxes.empty.noTaxes') }}
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
