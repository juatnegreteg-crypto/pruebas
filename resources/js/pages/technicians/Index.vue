<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AlertError from '@/components/AlertError.vue';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import InputError from '@/components/InputError.vue';
import PartyAddressList, {
    type PartyAddressForm,
} from '@/components/PartyAddressList.vue';
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
import { useDataTable } from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import techniciansRoutes from '@/routes/technicians';

defineOptions({ layout: AppLayout });

type Technician = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    isActive: boolean;
    hasAvailability: boolean;
    addresses?: PartyAddress[];
};

type PartyAddress = {
    id: number;
    type: string;
    isPrimary: boolean;
    street: string;
    complement: string | null;
    neighborhood: string | null;
    city: string;
    state: string;
    postalCode: string | null;
    country: string;
    reference: string | null;
};

type AddressTypeOption = string;

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type TechniciansPaginator = {
    data: Technician[];
    links: PaginationLink[];
    meta?: {
        from: number | null;
        to: number | null;
        total: number;
    };
};

const page = usePage();
const { t } = useI18n();

const technicians = computed(() => {
    const data = page.props.technicians as TechniciansPaginator | undefined;
    return {
        data: data?.data ?? [],
        links: data?.links ?? [],
        meta: data?.meta,
    };
});

const filters = computed(() => {
    const data = page.props.filters as Record<string, string> | undefined;
    return {
        search: data?.search ?? '',
    };
});

const addressTypes = computed(() => {
    return (page.props.addressTypes as AddressTypeOption[] | undefined) ?? [];
});

const defaultCountry = computed(() => {
    return (page.props.defaultCountry as string | undefined) ?? 'Colombia';
});

// Create modal
const isCreateOpen = ref(false);
const isSubmitting = ref(false);
const createForm = reactive({
    name: '',
    email: '',
    phone: '',
    isActive: true,
    addresses: [] as PartyAddressForm[],
});

// Edit modal
const isEditOpen = ref(false);
const isUpdating = ref(false);
const editingTechnicianId = ref<number | null>(null);
const editForm = reactive({
    name: '',
    email: '',
    phone: '',
    isActive: true,
    addresses: [] as PartyAddressForm[],
});

// Toggle status
const togglingId = ref<number | null>(null);

// Errors
const formErrors = ref<Record<string, string>>({});
const formErrorMessages = ref<string[]>([]);
const actionErrorMessages = ref<string[]>([]);

function setFormError(field: string, message: string) {
    formErrors.value = { ...formErrors.value, [field]: message };
}

function resetCreateForm() {
    createForm.name = '';
    createForm.email = '';
    createForm.phone = '';
    createForm.isActive = true;
    createForm.addresses = [];
    formErrors.value = {};
    formErrorMessages.value = [];
}

function resetEditForm() {
    editForm.name = '';
    editForm.email = '';
    editForm.phone = '';
    editForm.isActive = true;
    editForm.addresses = [];
    editingTechnicianId.value = null;
    formErrors.value = {};
    formErrorMessages.value = [];
}

const table = useDataTable({
    route: () => techniciansRoutes.index().url,
    initialSearch: filters.value.search,
    searchKey: 'search',
    debounceMs: 300,
});

// Phone: only digits
function onPhoneInput(event: Event, form: { phone: string }) {
    const input = event.target as HTMLInputElement;
    form.phone = input.value.replace(/\D/g, '');
}

function mapAddressToForm(address: PartyAddress): PartyAddressForm {
    return {
        id: address.id,
        type: address.type,
        isPrimary: address.isPrimary,
        street: address.street ?? '',
        complement: address.complement ?? '',
        neighborhood: address.neighborhood ?? '',
        city: address.city ?? '',
        state: address.state ?? '',
        postalCode: address.postalCode ?? '',
        country: address.country ?? defaultCountry.value,
        reference: address.reference ?? '',
    };
}

function mapAddressesFromParty(
    addresses: PartyAddress[] | undefined,
): PartyAddressForm[] {
    if (!addresses || addresses.length === 0) {
        return [];
    }

    return addresses.map(mapAddressToForm);
}

// Create
async function submitCreate() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!createForm.name.trim()) {
        setFormError('name', t('technicians.form.errors.nameRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    const payload = {
        name: createForm.name.trim(),
        email: createForm.email.trim() || null,
        phone: createForm.phone.trim() || null,
        isActive: createForm.isActive,
        addresses: createForm.addresses,
    };

    isSubmitting.value = true;

    try {
        const response = await fetch('/api/v1/technicians', {
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
            formErrorMessages.value = [t('technicians.errors.create')];
            return;
        }

        resetCreateForm();
        isCreateOpen.value = false;
        router.reload({ only: ['technicians'] });
    } catch {
        formErrorMessages.value = [t('technicians.errors.create')];
    } finally {
        isSubmitting.value = false;
    }
}

// Edit
function openEdit(tech: Technician) {
    editingTechnicianId.value = tech.id;
    editForm.name = tech.name ?? '';
    editForm.email = tech.email ?? '';
    editForm.phone = tech.phone ?? '';
    editForm.isActive = tech.isActive ?? true;
    editForm.addresses = mapAddressesFromParty(tech.addresses);
    isEditOpen.value = true;
    formErrors.value = {};
    formErrorMessages.value = [];
}

async function submitEdit() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!editForm.name.trim()) {
        setFormError('name', t('technicians.form.errors.nameRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    if (!editingTechnicianId.value) {
        formErrorMessages.value = [t('technicians.errors.update')];
        return;
    }

    const payload = {
        name: editForm.name.trim(),
        email: editForm.email.trim() || null,
        phone: editForm.phone.trim() || null,
        isActive: editForm.isActive,
        addresses: editForm.addresses,
    };

    isUpdating.value = true;

    try {
        const response = await fetch(
            `/api/v1/technicians/${editingTechnicianId.value}`,
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
            formErrorMessages.value = [t('technicians.errors.update')];
            return;
        }

        resetEditForm();
        isEditOpen.value = false;
        router.reload({ only: ['technicians'] });
    } catch {
        formErrorMessages.value = [t('technicians.errors.update')];
    } finally {
        isUpdating.value = false;
    }
}

// Toggle active/inactive
async function toggleStatus(tech: Technician) {
    actionErrorMessages.value = [];
    togglingId.value = tech.id;

    const payload = {
        name: tech.name,
        email: tech.email,
        phone: tech.phone,
        isActive: !tech.isActive,
    };

    try {
        const response = await fetch(`/api/v1/technicians/${tech.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            actionErrorMessages.value = [t('technicians.errors.toggleStatus')];
            return;
        }

        router.reload({ only: ['technicians'] });
    } catch {
        actionErrorMessages.value = [t('technicians.errors.toggleStatus')];
    } finally {
        togglingId.value = null;
    }
}
</script>

<template>
    <Head :title="t('technicians.index.headTitle')" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <AppDataTableShell
            :title="t('technicians.index.title')"
            :description="t('technicians.index.description')"
            :search="table.search.value"
            :search-label="t('technicians.filters.searchLabel')"
            :search-placeholder="t('technicians.filters.searchPlaceholder')"
            @update:search="table.updateSearch"
        >
            <template #actions>
                <Dialog
                    v-model:open="isCreateOpen"
                    @update:open="(open) => !open && resetCreateForm()"
                >
                    <DialogTrigger as-child>
                        <AppActionButton
                            :action="Actions.create"
                            :label-key="'technicians.index.actions.create'"
                        />
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>
                                {{ t('technicians.create.title') }}
                            </DialogTitle>
                            <DialogDescription>
                                {{ t('technicians.create.description') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-4">
                            <AlertError
                                v-if="formErrorMessages.length"
                                :errors="formErrorMessages"
                                :title="t('technicians.errors.title')"
                            />

                            <div class="grid gap-2">
                                <Label for="create-name">
                                    {{ t('technicians.form.fields.name') }}
                                </Label>
                                <Input
                                    id="create-name"
                                    v-model="createForm.name"
                                    :placeholder="
                                        t('technicians.form.placeholders.name')
                                    "
                                    :aria-invalid="Boolean(formErrors.name)"
                                />
                                <InputError :message="formErrors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="create-email">
                                    {{ t('technicians.form.fields.email') }}
                                </Label>
                                <Input
                                    id="create-email"
                                    v-model="createForm.email"
                                    type="email"
                                    :placeholder="
                                        t('technicians.form.placeholders.email')
                                    "
                                    :aria-invalid="Boolean(formErrors.email)"
                                />
                                <InputError :message="formErrors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="create-phone">
                                    {{ t('technicians.form.fields.phone') }}
                                </Label>
                                <Input
                                    id="create-phone"
                                    :model-value="createForm.phone"
                                    @input="onPhoneInput($event, createForm)"
                                    inputmode="numeric"
                                    maxlength="20"
                                    :placeholder="
                                        t('technicians.form.placeholders.phone')
                                    "
                                    :aria-invalid="Boolean(formErrors.phone)"
                                />
                                <InputError :message="formErrors.phone" />
                            </div>

                            <PartyAddressList
                                v-model:addresses="createForm.addresses"
                                :address-types="addressTypes"
                                :default-country="defaultCountry"
                                :errors="formErrors"
                            />

                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="create-active"
                                    v-model="createForm.isActive"
                                />
                                <Label for="create-active">
                                    {{ t('technicians.form.fields.active') }}
                                </Label>
                            </div>
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button variant="ghost" type="button">
                                    {{ t('technicians.form.actions.cancel') }}
                                </Button>
                            </DialogClose>
                            <Button
                                type="button"
                                :disabled="isSubmitting"
                                @click="submitCreate"
                            >
                                {{
                                    isSubmitting
                                        ? t('technicians.form.actions.saving')
                                        : t('technicians.form.actions.save')
                                }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </template>

            <!-- Edit Dialog (hidden trigger, opened programmatically) -->
            <Dialog
                v-model:open="isEditOpen"
                @update:open="(open) => !open && resetEditForm()"
            >
                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>
                            {{ t('technicians.edit.title') }}
                        </DialogTitle>
                        <DialogDescription>
                            {{ t('technicians.edit.description') }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4">
                        <AlertError
                            v-if="formErrorMessages.length"
                            :errors="formErrorMessages"
                            :title="t('technicians.errors.title')"
                        />

                        <div class="grid gap-2">
                            <Label for="edit-name">
                                {{ t('technicians.form.fields.name') }}
                            </Label>
                            <Input
                                id="edit-name"
                                v-model="editForm.name"
                                :placeholder="
                                    t('technicians.form.placeholders.name')
                                "
                                :aria-invalid="Boolean(formErrors.name)"
                            />
                            <InputError :message="formErrors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-email">
                                {{ t('technicians.form.fields.email') }}
                            </Label>
                            <Input
                                id="edit-email"
                                v-model="editForm.email"
                                type="email"
                                :placeholder="
                                    t('technicians.form.placeholders.email')
                                "
                                :aria-invalid="Boolean(formErrors.email)"
                            />
                            <InputError :message="formErrors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-phone">
                                {{ t('technicians.form.fields.phone') }}
                            </Label>
                            <Input
                                id="edit-phone"
                                :model-value="editForm.phone"
                                @input="onPhoneInput($event, editForm)"
                                inputmode="numeric"
                                maxlength="20"
                                :placeholder="
                                    t('technicians.form.placeholders.phone')
                                "
                                :aria-invalid="Boolean(formErrors.phone)"
                            />
                            <InputError :message="formErrors.phone" />
                        </div>

                        <PartyAddressList
                            v-model:addresses="editForm.addresses"
                            :address-types="addressTypes"
                            :default-country="defaultCountry"
                            :errors="formErrors"
                        />

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="edit-active"
                                v-model="editForm.isActive"
                            />
                            <Label for="edit-active">
                                {{ t('technicians.form.fields.active') }}
                            </Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="ghost" type="button">
                                {{ t('technicians.form.actions.cancel') }}
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            :disabled="isUpdating"
                            @click="submitEdit"
                        >
                            {{
                                isUpdating
                                    ? t('technicians.form.actions.saving')
                                    : t('technicians.form.actions.save')
                            }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <template #summary>
                <div
                    v-if="technicians.meta?.total != null"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('technicians.filters.total') }}:
                    <Badge variant="secondary">{{
                        technicians.meta.total
                    }}</Badge>
                </div>
            </template>

            <template #filters>
                <div v-if="actionErrorMessages.length">
                    <AlertError
                        :errors="actionErrorMessages"
                        :title="t('technicians.errors.title')"
                    />
                </div>
            </template>

            <template #cards>
                <Card
                    v-for="tech in technicians.data"
                    :key="tech.id"
                    class="p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold">
                                {{ tech.name }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{
                                    tech.email || t('technicians.common.empty')
                                }}
                            </p>
                        </div>

                        <AppRowActions>
                            <AppActionIconButton
                                :action="Actions.edit"
                                :label-key="'technicians.table.actionsEdit'"
                                @click="openEdit(tech)"
                            />
                            <AppActionIconButton
                                :action="
                                    tech.isActive
                                        ? Actions.disable
                                        : Actions.enable
                                "
                                :label-key="
                                    tech.isActive
                                        ? 'technicians.table.actionsDisable'
                                        : 'technicians.table.actionsEnable'
                                "
                                :disabled="togglingId === tech.id"
                                @click="toggleStatus(tech)"
                            />
                            <AppActionIconButton
                                :action="Actions.view"
                                :label-key="'technicians.table.actionsAvailability'"
                                as="link"
                                :href="techniciansRoutes.show(tech.id).url"
                            />
                            <AppActionIconButton
                                :action="Actions.more"
                                :label-key="'technicians.table.actions'"
                                :tooltip="false"
                                disabled
                            />
                        </AppRowActions>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">
                                {{ t('technicians.table.phone') }}
                            </span>
                            <span class="font-medium">
                                {{
                                    tech.phone || t('technicians.common.empty')
                                }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">
                                {{ t('technicians.table.status') }}
                            </span>
                            <Badge
                                :variant="
                                    tech.isActive ? 'default' : 'secondary'
                                "
                            >
                                {{
                                    tech.isActive
                                        ? t('technicians.status.active')
                                        : t('technicians.status.inactive')
                                }}
                            </Badge>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted-foreground">
                                {{ t('technicians.table.availability') }}
                            </span>
                            <Badge
                                :variant="
                                    tech.hasAvailability
                                        ? 'default'
                                        : 'destructive'
                                "
                            >
                                {{
                                    tech.hasAvailability
                                        ? t(
                                              'technicians.availability.configured',
                                          )
                                        : t(
                                              'technicians.availability.unconfigured',
                                          )
                                }}
                            </Badge>
                        </div>
                    </div>
                </Card>

                <Card v-if="technicians.data.length === 0" class="p-6">
                    <p class="text-center text-sm text-muted-foreground">
                        {{ t('technicians.empty') }}
                    </p>
                </Card>
            </template>

            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('technicians.table.name') }}
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('technicians.table.email') }}
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('technicians.table.phone') }}
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('technicians.table.status') }}
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    {{ t('technicians.table.availability') }}
                                </th>
                                <th class="px-6 py-3 text-right font-medium">
                                    {{ t('technicians.table.actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="tech in technicians.data"
                                :key="tech.id"
                                class="hover:bg-muted/50"
                            >
                                <td class="px-6 py-4 font-medium">
                                    {{ tech.name }}
                                </td>

                                <td class="px-6 py-4 text-muted-foreground">
                                    {{
                                        tech.email ||
                                        t('technicians.common.empty')
                                    }}
                                </td>

                                <td class="px-6 py-4 text-muted-foreground">
                                    {{
                                        tech.phone ||
                                        t('technicians.common.empty')
                                    }}
                                </td>

                                <td class="px-6 py-4">
                                    <Badge
                                        :variant="
                                            tech.isActive
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            tech.isActive
                                                ? t('technicians.status.active')
                                                : t(
                                                      'technicians.status.inactive',
                                                  )
                                        }}
                                    </Badge>
                                </td>

                                <td class="px-6 py-4">
                                    <Badge
                                        :variant="
                                            tech.hasAvailability
                                                ? 'default'
                                                : 'destructive'
                                        "
                                    >
                                        {{
                                            tech.hasAvailability
                                                ? t(
                                                      'technicians.availability.configured',
                                                  )
                                                : t(
                                                      'technicians.availability.unconfigured',
                                                  )
                                        }}
                                    </Badge>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <AppRowActions class="justify-end">
                                        <AppActionIconButton
                                            :action="Actions.edit"
                                            :label-key="'technicians.table.actionsEdit'"
                                            @click="openEdit(tech)"
                                        />
                                        <AppActionIconButton
                                            :action="
                                                tech.isActive
                                                    ? Actions.disable
                                                    : Actions.enable
                                            "
                                            :label-key="
                                                tech.isActive
                                                    ? 'technicians.table.actionsDisable'
                                                    : 'technicians.table.actionsEnable'
                                            "
                                            :disabled="togglingId === tech.id"
                                            @click="toggleStatus(tech)"
                                        />
                                        <AppActionIconButton
                                            :action="Actions.view"
                                            :label-key="'technicians.table.actionsAvailability'"
                                            as="link"
                                            :href="
                                                techniciansRoutes.show(tech.id)
                                                    .url
                                            "
                                        />
                                        <AppActionIconButton
                                            :action="Actions.more"
                                            :label-key="'technicians.table.actions'"
                                            :tooltip="false"
                                            disabled
                                        />
                                    </AppRowActions>
                                </td>
                            </tr>

                            <tr v-if="technicians.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-6 py-8 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('technicians.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
            <template #pagination>
                <div
                    v-if="technicians.links?.length"
                    class="flex flex-wrap items-center gap-2"
                >
                    <template
                        v-for="(link, idx) in technicians.links"
                        :key="idx"
                    >
                        <Button
                            v-if="link.url === null"
                            variant="outline"
                            size="sm"
                            disabled
                        >
                            <template
                                v-if="link.label.includes(t('common.previous'))"
                            >
                                <span aria-hidden="true">&larr;</span>
                            </template>
                            <template
                                v-else-if="
                                    link.label.includes(t('common.next'))
                                "
                            >
                                <span aria-hidden="true">&rarr;</span>
                            </template>
                            <template v-else>
                                <span v-html="link.label" />
                            </template>
                        </Button>
                        <Button
                            v-else
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            @click="
                                router.visit(link.url || '', {
                                    replace: true,
                                    preserveState: false,
                                })
                            "
                        >
                            <template
                                v-if="link.label.includes(t('common.previous'))"
                            >
                                <span aria-hidden="true">&larr;</span>
                            </template>
                            <template
                                v-else-if="
                                    link.label.includes(t('common.next'))
                                "
                            >
                                <span aria-hidden="true">&rarr;</span>
                            </template>
                            <template v-else>
                                <!-- eslint-disable-next-line vue/no-v-html -->
                                <span v-html="link.label" />
                            </template>
                        </Button>
                    </template>
                </div>
            </template>
        </AppDataTableShell>
    </div>
</template>
