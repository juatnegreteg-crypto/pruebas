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
import CustomerPickerField from '@/components/CustomerPickerField.vue';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useDataTable } from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as vehiclesIndex } from '@/routes/vehicles';

type Vehicle = {
    id: number;
    customerId: number;
    customer?: {
        id: number;
        name?: string | null;
        fullName: string;
    } | null;
    plate: string;
    vin?: string | null;
    make: string;
    model: string;
    year: number;
    type?: string | null;
    color?: string | null;
    fuelType?: string | null;
    transmission?: string | null;
    mileage?: number | null;
    observations?: Array<{
        body: string;
        context: string;
        audienceTags: string[];
        createdAt?: string | null;
        createdBy?: number | null;
    }> | null;
    isActive: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type VehiclesPayload = {
    data: Vehicle[];
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
    vehicles: VehiclesPayload;
};

const props = defineProps<Props>();
const { t } = useI18n();
const vehicles = computed(() => props.vehicles);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('vehicles.title'),
        href: vehiclesIndex().url,
    },
];

const table = useDataTable({
    route: () => vehiclesIndex(),
    initialSearch:
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('q') ?? '')
            : '',
    initialPerPage: props.vehicles.meta.per_page ?? 15,
    searchKey: 'q',
    autoSearch: false,
});
const columns = computed(
    () =>
        [
            { key: 'plate', header: t('vehicles.table.plate') },
            { key: 'vehicle', header: t('vehicles.table.vehicle') },
            { key: 'year', header: t('vehicles.table.year') },
            { key: 'type', header: t('vehicles.table.type') },
            {
                key: 'status',
                header: t('vehicles.table.status'),
                align: 'center',
            },
            {
                key: 'actions',
                header: t('vehicles.table.actions'),
                align: 'right',
            },
        ] as const,
);
const slotNames = {
    plate: 'cell(plate)',
    vehicle: 'cell(vehicle)',
    type: 'cell(type)',
    status: 'cell(status)',
    actions: 'cell(actions)',
    empty: 'empty()',
} as const;
const isLoading = ref(false);
const hasError = computed(
    () => !props.vehicles || !Array.isArray(props.vehicles.data),
);
const isCreateOpen = ref(false);
const isSubmitting = ref(false);
const isEditOpen = ref(false);
const isUpdating = ref(false);
const togglingVehicleId = ref<number | null>(null);
const editingVehicleId = ref<number | null>(null);
const formErrors = ref<Record<string, string>>({});
const formErrorMessages = ref<string[]>([]);
const actionErrorMessages = ref<string[]>([]);
const emptySelectValue = 'none';
const editCustomerLabel = ref<string | null>(null);
const createForm = reactive({
    customer_id: '',
    plate: '',
    vin: '',
    make: '',
    model: '',
    year: '',
    type: '',
    color: '',
    fuel_type: '',
    transmission: '',
    mileage: '',
    observation: '',
    is_active: true,
});
const editForm = reactive({
    customer_id: '',
    plate: '',
    vin: '',
    make: '',
    model: '',
    year: '',
    type: '',
    color: '',
    fuel_type: '',
    transmission: '',
    mileage: '',
    observation: '',
    is_active: true,
});

const paginationLinks = computed(() => props.vehicles.meta.links ?? []);
const vehicleTypeOptions = computed(() => [
    { value: 'sedan', label: t('vehicles.form.options.type.sedan') },
    { value: 'suv', label: t('vehicles.form.options.type.suv') },
    { value: 'pickup', label: t('vehicles.form.options.type.pickup') },
    { value: 'van', label: t('vehicles.form.options.type.van') },
    { value: 'motorcycle', label: t('vehicles.form.options.type.motorcycle') },
    { value: 'truck', label: t('vehicles.form.options.type.truck') },
    { value: 'other', label: t('vehicles.form.options.type.other') },
]);
const fuelOptions = computed(() => [
    { value: 'gasoline', label: t('vehicles.form.options.fuel.gasoline') },
    { value: 'diesel', label: t('vehicles.form.options.fuel.diesel') },
    { value: 'electric', label: t('vehicles.form.options.fuel.electric') },
    { value: 'hybrid', label: t('vehicles.form.options.fuel.hybrid') },
    { value: 'gas', label: t('vehicles.form.options.fuel.gas') },
    { value: 'other', label: t('vehicles.form.options.fuel.other') },
]);
const transmissionOptions = computed(() => [
    { value: 'manual', label: t('vehicles.form.options.transmission.manual') },
    {
        value: 'automatic',
        label: t('vehicles.form.options.transmission.automatic'),
    },
]);

const filteredVehicles = computed(() => {
    return props.vehicles.data;
});

const summary = computed(() => {
    if (!props.vehicles.meta.total) {
        return t('vehicles.summaryEmpty');
    }

    return t('vehicles.summary', {
        from: props.vehicles.meta.from ?? 0,
        to: props.vehicles.meta.to ?? 0,
        total: props.vehicles.meta.total,
    });
});

const onStart = () => {
    isLoading.value = true;
};
const onFinish = () => {
    isLoading.value = false;
};
let unsubscribeStart: (() => void) | null = null;
let unsubscribeFinish: (() => void) | null = null;
let unsubscribeError: (() => void) | null = null;

function applySearch() {
    table.applySearch();
}

function clearSearch() {
    table.clearSearch();
    table.applySearch();
}

function retryLoad() {
    router.reload({ only: ['vehicles'] });
}

function resetCreateForm() {
    createForm.customer_id = '';
    createForm.plate = '';
    createForm.vin = '';
    createForm.make = '';
    createForm.model = '';
    createForm.year = '';
    createForm.type = '';
    createForm.color = '';
    createForm.fuel_type = '';
    createForm.transmission = '';
    createForm.mileage = '';
    createForm.observation = '';
    createForm.is_active = true;
    formErrors.value = {};
    formErrorMessages.value = [];
}

function resetEditForm() {
    editForm.customer_id = '';
    editForm.plate = '';
    editForm.vin = '';
    editForm.make = '';
    editForm.model = '';
    editForm.year = '';
    editForm.type = '';
    editForm.color = '';
    editForm.fuel_type = '';
    editForm.transmission = '';
    editForm.mileage = '';
    editForm.observation = '';
    editForm.is_active = true;
    editingVehicleId.value = null;
    editCustomerLabel.value = null;
    formErrors.value = {};
    formErrorMessages.value = [];
}

function setFormError(field: string, message: string) {
    formErrors.value = {
        ...formErrors.value,
        [field]: message,
    };
}

function normalizePayload(form: typeof createForm) {
    return {
        customer_id: Number(form.customer_id),
        plate: form.plate.trim(),
        vin: form.vin.trim() || null,
        make: form.make.trim(),
        model: form.model.trim(),
        year: Number(form.year),
        type: form.type && form.type !== emptySelectValue ? form.type : null,
        color: form.color.trim() || null,
        fuel_type:
            form.fuel_type && form.fuel_type !== emptySelectValue
                ? form.fuel_type
                : null,
        transmission:
            form.transmission && form.transmission !== emptySelectValue
                ? form.transmission
                : null,
        mileage: form.mileage ? Number(form.mileage) : null,
        observation: form.observation.trim() || null,
        is_active: form.is_active,
    };
}

async function submitCreate() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!String(createForm.customer_id).trim()) {
        setFormError('customer_id', t('vehicles.form.errors.customerRequired'));
    }

    if (!createForm.plate.trim()) {
        setFormError('plate', t('vehicles.form.errors.plateRequired'));
    }

    if (!createForm.make.trim()) {
        setFormError('make', t('vehicles.form.errors.makeRequired'));
    }

    if (!createForm.model.trim()) {
        setFormError('model', t('vehicles.form.errors.modelRequired'));
    }

    if (!String(createForm.year).trim()) {
        setFormError('year', t('vehicles.form.errors.yearRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    const payload = normalizePayload(createForm);

    isSubmitting.value = true;

    try {
        const response = await fetch('/api/v1/vehicles', {
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
            formErrorMessages.value = [t('vehicles.form.errors.generic')];
            return;
        }

        resetCreateForm();
        isCreateOpen.value = false;
        router.reload({ only: ['vehicles'] });
    } catch {
        formErrorMessages.value = [t('vehicles.form.errors.generic')];
    } finally {
        isSubmitting.value = false;
    }
}

function openEdit(vehicle: Vehicle) {
    editingVehicleId.value = vehicle.id;
    editForm.customer_id = String(vehicle.customerId ?? '');
    editCustomerLabel.value =
        vehicle.customer?.name ?? vehicle.customer?.fullName ?? null;
    editForm.plate = vehicle.plate ?? '';
    editForm.vin = vehicle.vin ?? '';
    editForm.make = vehicle.make ?? '';
    editForm.model = vehicle.model ?? '';
    editForm.year = vehicle.year?.toString() ?? '';
    editForm.type = vehicle.type ?? '';
    editForm.color = vehicle.color ?? '';
    editForm.fuel_type = vehicle.fuelType ?? '';
    editForm.transmission = vehicle.transmission ?? '';
    editForm.mileage = vehicle.mileage?.toString() ?? '';
    editForm.observation = vehicle.observations?.[0]?.body ?? '';
    editForm.is_active = vehicle.isActive ?? true;
    isEditOpen.value = true;
    formErrors.value = {};
    formErrorMessages.value = [];
}

function handleEditCustomerSelected(customer: { fullName: string }): void {
    editCustomerLabel.value = customer.fullName;
}

async function submitEdit() {
    formErrors.value = {};
    formErrorMessages.value = [];

    if (!String(editForm.customer_id).trim()) {
        setFormError('customer_id', t('vehicles.form.errors.customerRequired'));
    }

    if (!editForm.plate.trim()) {
        setFormError('plate', t('vehicles.form.errors.plateRequired'));
    }

    if (!editForm.make.trim()) {
        setFormError('make', t('vehicles.form.errors.makeRequired'));
    }

    if (!editForm.model.trim()) {
        setFormError('model', t('vehicles.form.errors.modelRequired'));
    }

    if (!String(editForm.year).trim()) {
        setFormError('year', t('vehicles.form.errors.yearRequired'));
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    if (!editingVehicleId.value) {
        formErrorMessages.value = [t('vehicles.edit.errors.generic')];
        return;
    }

    const payload = normalizePayload(editForm);

    isUpdating.value = true;

    try {
        const response = await fetch(
            `/api/v1/vehicles/${editingVehicleId.value}`,
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
            formErrorMessages.value = [t('vehicles.edit.errors.generic')];
            return;
        }

        resetEditForm();
        isEditOpen.value = false;
        router.reload({ only: ['vehicles'] });
    } catch {
        formErrorMessages.value = [t('vehicles.edit.errors.generic')];
    } finally {
        isUpdating.value = false;
    }
}

async function toggleStatus(vehicle: Vehicle) {
    actionErrorMessages.value = [];
    togglingVehicleId.value = vehicle.id;

    const payload = {
        plate: vehicle.plate,
        vin: vehicle.vin ?? null,
        make: vehicle.make,
        model: vehicle.model,
        year: vehicle.year,
        type: vehicle.type ?? null,
        color: vehicle.color ?? null,
        fuel_type: vehicle.fuelType ?? null,
        transmission: vehicle.transmission ?? null,
        mileage: vehicle.mileage ?? null,
        is_active: !vehicle.isActive,
    };

    try {
        const response = await fetch(`/api/v1/vehicles/${vehicle.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            actionErrorMessages.value = [t('vehicles.actions.error')];
            return;
        }

        router.reload({ only: ['vehicles'] });
    } catch {
        actionErrorMessages.value = [t('vehicles.actions.error')];
    } finally {
        togglingVehicleId.value = null;
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

onMounted(() => {
    unsubscribeStart = router.on('start', onStart);
    unsubscribeFinish = router.on('finish', onFinish);
    unsubscribeError = router.on('error', onFinish);
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
        <Head :title="t('vehicles.title')" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        {{ t('vehicles.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('vehicles.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Dialog
                        v-model:open="isCreateOpen"
                        @update:open="(open) => !open && resetCreateForm()"
                    >
                        <DialogTrigger as-child>
                            <AppActionButton
                                :action="Actions.create"
                                :label-key="'vehicles.form.open'"
                            />
                        </DialogTrigger>
                        <DialogContent
                            class="max-h-[85vh] overflow-y-auto sm:max-w-2xl"
                        >
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('vehicles.form.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('vehicles.form.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-5">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('vehicles.form.errors.title')"
                                />

                                <FieldSet>
                                    <FieldLegend>
                                        {{ t('vehicles.form.sections.core') }}
                                    </FieldLegend>
                                    <FieldGroup
                                        class="grid gap-4 md:grid-cols-2"
                                    >
                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.customer_id)
                                            "
                                        >
                                            <FieldLabel for="vehicle-customer">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.customer',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <CustomerPickerField
                                                    id="vehicle-customer"
                                                    v-model="
                                                        createForm.customer_id
                                                    "
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.customer',
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[
                                                        formErrors.customer_id,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.plate)
                                            "
                                        >
                                            <FieldLabel for="vehicle-plate">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.plate',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="vehicle-plate"
                                                    v-model="createForm.plate"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.plate',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(
                                                            formErrors.plate,
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.plate]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel for="vehicle-vin">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.vin',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="vehicle-vin"
                                                    v-model="createForm.vin"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.vin',
                                                        )
                                                    "
                                                />
                                                <FieldDescription>
                                                    {{
                                                        t(
                                                            'vehicles.form.help.vin',
                                                        )
                                                    }}
                                                </FieldDescription>
                                                <FieldError
                                                    :errors="[formErrors.vin]"
                                                />
                                            </FieldContent>
                                        </Field>
                                    </FieldGroup>
                                </FieldSet>

                                <FieldSet>
                                    <FieldLegend>
                                        {{ t('vehicles.form.sections.specs') }}
                                    </FieldLegend>
                                    <FieldGroup
                                        class="grid gap-4 md:grid-cols-2"
                                    >
                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.make)
                                            "
                                        >
                                            <FieldLabel for="vehicle-make">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.make',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="vehicle-make"
                                                    v-model="createForm.make"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.make',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(formErrors.make)
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.make]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.model)
                                            "
                                        >
                                            <FieldLabel for="vehicle-model">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.model',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="vehicle-model"
                                                    v-model="createForm.model"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.model',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(
                                                            formErrors.model,
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.model]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.year)
                                            "
                                        >
                                            <FieldLabel for="vehicle-year">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.year',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="vehicle-year"
                                                    v-model="createForm.year"
                                                    type="number"
                                                    min="1900"
                                                    step="1"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.year',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(formErrors.year)
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.year]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel for="vehicle-type">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.type',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Select
                                                    v-model="createForm.type"
                                                >
                                                    <SelectTrigger
                                                        id="vehicle-type"
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            :placeholder="
                                                                t(
                                                                    'vehicles.form.placeholders.type',
                                                                )
                                                            "
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            :value="
                                                                emptySelectValue
                                                            "
                                                        >
                                                            {{
                                                                t(
                                                                    'vehicles.form.options.empty',
                                                                )
                                                            }}
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="option in vehicleTypeOptions"
                                                            :key="option.value"
                                                            :value="
                                                                option.value
                                                            "
                                                        >
                                                            {{ option.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <FieldError
                                                    :errors="[formErrors.type]"
                                                />
                                            </FieldContent>
                                        </Field>
                                    </FieldGroup>

                                    <FieldGroup
                                        class="grid gap-4 md:grid-cols-2"
                                    >
                                        <Field>
                                            <FieldLabel for="vehicle-color">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.color',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="vehicle-color"
                                                    v-model="createForm.color"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.color',
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.color]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel for="vehicle-fuel">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.fuel',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Select
                                                    v-model="
                                                        createForm.fuel_type
                                                    "
                                                >
                                                    <SelectTrigger
                                                        id="vehicle-fuel"
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            :placeholder="
                                                                t(
                                                                    'vehicles.form.placeholders.fuel',
                                                                )
                                                            "
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            :value="
                                                                emptySelectValue
                                                            "
                                                        >
                                                            {{
                                                                t(
                                                                    'vehicles.form.options.empty',
                                                                )
                                                            }}
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="option in fuelOptions"
                                                            :key="option.value"
                                                            :value="
                                                                option.value
                                                            "
                                                        >
                                                            {{ option.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <FieldError
                                                    :errors="[
                                                        formErrors.fuel_type,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel
                                                for="vehicle-transmission"
                                            >
                                                {{
                                                    t(
                                                        'vehicles.form.fields.transmission',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Select
                                                    v-model="
                                                        createForm.transmission
                                                    "
                                                >
                                                    <SelectTrigger
                                                        id="vehicle-transmission"
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            :placeholder="
                                                                t(
                                                                    'vehicles.form.placeholders.transmission',
                                                                )
                                                            "
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            :value="
                                                                emptySelectValue
                                                            "
                                                        >
                                                            {{
                                                                t(
                                                                    'vehicles.form.options.empty',
                                                                )
                                                            }}
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="option in transmissionOptions"
                                                            :key="option.value"
                                                            :value="
                                                                option.value
                                                            "
                                                        >
                                                            {{ option.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <FieldError
                                                    :errors="[
                                                        formErrors.transmission,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel for="vehicle-mileage">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.mileage',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="vehicle-mileage"
                                                    v-model="createForm.mileage"
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.mileage',
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[
                                                        formErrors.mileage,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>
                                    </FieldGroup>
                                </FieldSet>

                                <FieldSet>
                                    <FieldLegend>
                                        {{
                                            t(
                                                'vehicles.form.sections.observations',
                                            )
                                        }}
                                    </FieldLegend>
                                    <Field>
                                        <FieldLabel for="vehicle-observation">
                                            {{
                                                t(
                                                    'vehicles.form.fields.observations',
                                                )
                                            }}
                                        </FieldLabel>
                                        <FieldContent>
                                            <Textarea
                                                id="vehicle-observation"
                                                v-model="createForm.observation"
                                                rows="3"
                                                :placeholder="
                                                    t(
                                                        'vehicles.form.placeholders.observations',
                                                    )
                                                "
                                            />
                                            <FieldError
                                                :errors="[
                                                    formErrors.observation,
                                                ]"
                                            />
                                        </FieldContent>
                                    </Field>
                                </FieldSet>

                                <FieldSet>
                                    <FieldLegend>
                                        {{
                                            t(
                                                'vehicles.form.sections.observations',
                                            )
                                        }}
                                    </FieldLegend>
                                    <Field>
                                        <FieldLabel
                                            for="edit-vehicle-observation"
                                        >
                                            {{
                                                t(
                                                    'vehicles.form.fields.observations',
                                                )
                                            }}
                                        </FieldLabel>
                                        <FieldContent>
                                            <Textarea
                                                id="edit-vehicle-observation"
                                                v-model="editForm.observation"
                                                rows="3"
                                                :placeholder="
                                                    t(
                                                        'vehicles.form.placeholders.observations',
                                                    )
                                                "
                                            />
                                            <FieldError
                                                :errors="[
                                                    formErrors.observation,
                                                ]"
                                            />
                                        </FieldContent>
                                    </Field>
                                </FieldSet>

                                <FieldSet>
                                    <FieldLegend>
                                        {{ t('vehicles.form.sections.status') }}
                                    </FieldLegend>
                                    <Field>
                                        <FieldLabel for="vehicle-active">
                                            {{
                                                t('vehicles.form.fields.active')
                                            }}
                                        </FieldLabel>
                                        <FieldContent>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id="vehicle-active"
                                                    v-model:checked="
                                                        createForm.is_active
                                                    "
                                                />
                                                <span
                                                    class="text-sm text-muted-foreground"
                                                >
                                                    {{
                                                        t(
                                                            'vehicles.form.help.active',
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </FieldContent>
                                    </Field>
                                </FieldSet>
                            </div>

                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="ghost" type="button">
                                        {{ t('vehicles.form.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    :disabled="isSubmitting"
                                    @click="submitCreate"
                                >
                                    {{
                                        isSubmitting
                                            ? t('vehicles.form.saving')
                                            : t('vehicles.form.save')
                                    }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <Dialog
                        v-model:open="isEditOpen"
                        @update:open="(open) => !open && resetEditForm()"
                    >
                        <DialogContent
                            class="max-h-[85vh] overflow-y-auto sm:max-w-2xl"
                        >
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('vehicles.edit.title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('vehicles.edit.description') }}
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-5">
                                <AlertError
                                    v-if="formErrorMessages.length"
                                    :errors="formErrorMessages"
                                    :title="t('vehicles.edit.errors.title')"
                                />

                                <FieldSet>
                                    <FieldLegend>
                                        {{ t('vehicles.form.sections.core') }}
                                    </FieldLegend>
                                    <FieldGroup
                                        class="grid gap-4 md:grid-cols-2"
                                    >
                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.customer_id)
                                            "
                                        >
                                            <FieldLabel
                                                for="edit-vehicle-customer"
                                            >
                                                {{
                                                    t(
                                                        'vehicles.form.fields.customer',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <CustomerPickerField
                                                    id="edit-vehicle-customer"
                                                    v-model="
                                                        editForm.customer_id
                                                    "
                                                    :selected-label="
                                                        editCustomerLabel
                                                    "
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.customer',
                                                        )
                                                    "
                                                    @selected="
                                                        handleEditCustomerSelected
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[
                                                        formErrors.customer_id,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.plate)
                                            "
                                        >
                                            <FieldLabel
                                                for="edit-vehicle-plate"
                                            >
                                                {{
                                                    t(
                                                        'vehicles.form.fields.plate',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="edit-vehicle-plate"
                                                    v-model="editForm.plate"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.plate',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(
                                                            formErrors.plate,
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.plate]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel for="edit-vehicle-vin">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.vin',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="edit-vehicle-vin"
                                                    v-model="editForm.vin"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.vin',
                                                        )
                                                    "
                                                />
                                                <FieldDescription>
                                                    {{
                                                        t(
                                                            'vehicles.form.help.vin',
                                                        )
                                                    }}
                                                </FieldDescription>
                                                <FieldError
                                                    :errors="[formErrors.vin]"
                                                />
                                            </FieldContent>
                                        </Field>
                                    </FieldGroup>
                                </FieldSet>

                                <FieldSet>
                                    <FieldLegend>
                                        {{ t('vehicles.form.sections.specs') }}
                                    </FieldLegend>
                                    <FieldGroup
                                        class="grid gap-4 md:grid-cols-2"
                                    >
                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.make)
                                            "
                                        >
                                            <FieldLabel for="edit-vehicle-make">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.make',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="edit-vehicle-make"
                                                    v-model="editForm.make"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.make',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(formErrors.make)
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.make]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.model)
                                            "
                                        >
                                            <FieldLabel
                                                for="edit-vehicle-model"
                                            >
                                                {{
                                                    t(
                                                        'vehicles.form.fields.model',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="edit-vehicle-model"
                                                    v-model="editForm.model"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.model',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(
                                                            formErrors.model,
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.model]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field
                                            :data-invalid="
                                                Boolean(formErrors.year)
                                            "
                                        >
                                            <FieldLabel for="edit-vehicle-year">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.year',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="edit-vehicle-year"
                                                    v-model="editForm.year"
                                                    type="number"
                                                    min="1900"
                                                    step="1"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.year',
                                                        )
                                                    "
                                                    :aria-invalid="
                                                        Boolean(formErrors.year)
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.year]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel for="edit-vehicle-type">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.type',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Select v-model="editForm.type">
                                                    <SelectTrigger
                                                        id="edit-vehicle-type"
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            :placeholder="
                                                                t(
                                                                    'vehicles.form.placeholders.type',
                                                                )
                                                            "
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            :value="
                                                                emptySelectValue
                                                            "
                                                        >
                                                            {{
                                                                t(
                                                                    'vehicles.form.options.empty',
                                                                )
                                                            }}
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="option in vehicleTypeOptions"
                                                            :key="option.value"
                                                            :value="
                                                                option.value
                                                            "
                                                        >
                                                            {{ option.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <FieldError
                                                    :errors="[formErrors.type]"
                                                />
                                            </FieldContent>
                                        </Field>
                                    </FieldGroup>

                                    <FieldGroup
                                        class="grid gap-4 md:grid-cols-2"
                                    >
                                        <Field>
                                            <FieldLabel
                                                for="edit-vehicle-color"
                                            >
                                                {{
                                                    t(
                                                        'vehicles.form.fields.color',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="edit-vehicle-color"
                                                    v-model="editForm.color"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.color',
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[formErrors.color]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel for="edit-vehicle-fuel">
                                                {{
                                                    t(
                                                        'vehicles.form.fields.fuel',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Select
                                                    v-model="editForm.fuel_type"
                                                >
                                                    <SelectTrigger
                                                        id="edit-vehicle-fuel"
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            :placeholder="
                                                                t(
                                                                    'vehicles.form.placeholders.fuel',
                                                                )
                                                            "
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            :value="
                                                                emptySelectValue
                                                            "
                                                        >
                                                            {{
                                                                t(
                                                                    'vehicles.form.options.empty',
                                                                )
                                                            }}
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="option in fuelOptions"
                                                            :key="option.value"
                                                            :value="
                                                                option.value
                                                            "
                                                        >
                                                            {{ option.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <FieldError
                                                    :errors="[
                                                        formErrors.fuel_type,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel
                                                for="edit-vehicle-transmission"
                                            >
                                                {{
                                                    t(
                                                        'vehicles.form.fields.transmission',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Select
                                                    v-model="
                                                        editForm.transmission
                                                    "
                                                >
                                                    <SelectTrigger
                                                        id="edit-vehicle-transmission"
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            :placeholder="
                                                                t(
                                                                    'vehicles.form.placeholders.transmission',
                                                                )
                                                            "
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            :value="
                                                                emptySelectValue
                                                            "
                                                        >
                                                            {{
                                                                t(
                                                                    'vehicles.form.options.empty',
                                                                )
                                                            }}
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="option in transmissionOptions"
                                                            :key="option.value"
                                                            :value="
                                                                option.value
                                                            "
                                                        >
                                                            {{ option.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <FieldError
                                                    :errors="[
                                                        formErrors.transmission,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>

                                        <Field>
                                            <FieldLabel
                                                for="edit-vehicle-mileage"
                                            >
                                                {{
                                                    t(
                                                        'vehicles.form.fields.mileage',
                                                    )
                                                }}
                                            </FieldLabel>
                                            <FieldContent>
                                                <Input
                                                    id="edit-vehicle-mileage"
                                                    v-model="editForm.mileage"
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    :placeholder="
                                                        t(
                                                            'vehicles.form.placeholders.mileage',
                                                        )
                                                    "
                                                />
                                                <FieldError
                                                    :errors="[
                                                        formErrors.mileage,
                                                    ]"
                                                />
                                            </FieldContent>
                                        </Field>
                                    </FieldGroup>
                                </FieldSet>

                                <FieldSet>
                                    <FieldLegend>
                                        {{ t('vehicles.form.sections.status') }}
                                    </FieldLegend>
                                    <Field>
                                        <FieldLabel for="edit-vehicle-active">
                                            {{
                                                t('vehicles.form.fields.active')
                                            }}
                                        </FieldLabel>
                                        <FieldContent>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id="edit-vehicle-active"
                                                    v-model:checked="
                                                        editForm.is_active
                                                    "
                                                />
                                                <span
                                                    class="text-sm text-muted-foreground"
                                                >
                                                    {{
                                                        t(
                                                            'vehicles.form.help.active',
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </FieldContent>
                                    </Field>
                                </FieldSet>
                            </div>

                            <DialogFooter>
                                <DialogClose as-child>
                                    <Button variant="ghost" type="button">
                                        {{ t('vehicles.form.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    :disabled="isUpdating"
                                    @click="submitEdit"
                                >
                                    {{
                                        isUpdating
                                            ? t('vehicles.edit.saving')
                                            : t('vehicles.edit.save')
                                    }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <AppDataTableShell
                :title="t('vehicles.listTitle')"
                :description="summary"
                :search="table.search.value"
                :search-label="t('vehicles.searchLabel')"
                :search-placeholder="t('vehicles.searchPlaceholder')"
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
                                for="vehicle-search"
                            >
                                {{ t('vehicles.searchLabel') }}
                            </label>
                            <Input
                                id="vehicle-search"
                                v-model="table.search.value"
                                :placeholder="t('vehicles.searchPlaceholder')"
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
                            :title="t('vehicles.actions.errorTitle')"
                        />
                    </div>
                </template>

                <template #cards>
                    <Card
                        v-for="vehicle in filteredVehicles"
                        :key="vehicle.id"
                        class="p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold">
                                    {{ vehicle.plate }}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ vehicle.make }} {{ vehicle.model }}
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
                                    :label-key="'vehicles.edit.action'"
                                    @click="openEdit(vehicle)"
                                />
                                <AppActionIconButton
                                    :action="
                                        vehicle.isActive
                                            ? Actions.disable
                                            : Actions.enable
                                    "
                                    :label-key="
                                        vehicle.isActive
                                            ? 'vehicles.actions.disable'
                                            : 'vehicles.actions.enable'
                                    "
                                    :disabled="togglingVehicleId === vehicle.id"
                                    @click="toggleStatus(vehicle)"
                                />
                                <AppActionIconButton
                                    :action="Actions.more"
                                    :label-key="'vehicles.actions.more'"
                                    :tooltip="false"
                                    disabled
                                />
                            </AppRowActions>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-2 text-sm">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-muted-foreground">
                                    {{ t('vehicles.table.year') }}
                                </span>
                                <span class="font-medium">
                                    {{ vehicle.year }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-muted-foreground">
                                    {{ t('vehicles.table.type') }}
                                </span>
                                <span class="font-medium">
                                    {{
                                        vehicle.type
                                            ? t(
                                                  `vehicles.form.options.type.${vehicle.type}`,
                                              )
                                            : t('vehicles.table.noType')
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-muted-foreground">
                                    {{ t('vehicles.table.status') }}
                                </span>
                                <Badge
                                    class="border-transparent"
                                    :class="
                                        vehicle.isActive
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-rose-100 text-rose-700'
                                    "
                                    :aria-label="
                                        vehicle.isActive
                                            ? t('vehicles.status.ariaActive')
                                            : t('vehicles.status.ariaInactive')
                                    "
                                >
                                    {{
                                        vehicle.isActive
                                            ? t('vehicles.status.active')
                                            : t('vehicles.status.inactive')
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </Card>

                    <Card v-if="!filteredVehicles.length" class="p-6">
                        <p class="text-center text-sm text-muted-foreground">
                            <span v-if="table.search">
                                {{
                                    t('vehicles.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </span>
                            <span v-else>
                                {{ t('vehicles.empty.noVehicles') }}
                            </span>
                        </p>
                    </Card>
                </template>

                <div class="overflow-x-auto">
                    <div
                        v-if="hasError"
                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        <p>{{ t('vehicles.errors.load') }}</p>
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
                        :rows="filteredVehicles"
                        :columns="columns"
                        row-key="id"
                    >
                        <template v-slot:[slotNames.plate]="{ row }">
                            <span class="font-semibold text-foreground">
                                {{ row.plate }}
                            </span>
                        </template>

                        <template v-slot:[slotNames.vehicle]="{ row }">
                            <div class="flex flex-col">
                                <span class="font-medium text-foreground">
                                    {{ row.make }} {{ row.model }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ row.vin || t('vehicles.table.noVin') }}
                                </span>
                            </div>
                        </template>

                        <template v-slot:[slotNames.type]="{ row }">
                            {{
                                row.type
                                    ? t(
                                          `vehicles.form.options.type.${row.type}`,
                                      )
                                    : t('vehicles.table.noType')
                            }}
                        </template>

                        <template v-slot:[slotNames.status]="{ row }">
                            <Badge
                                class="border-transparent"
                                :class="
                                    row.isActive
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-rose-100 text-rose-700'
                                "
                                :aria-label="
                                    row.isActive
                                        ? t('vehicles.status.ariaActive')
                                        : t('vehicles.status.ariaInactive')
                                "
                            >
                                {{
                                    row.isActive
                                        ? t('vehicles.status.active')
                                        : t('vehicles.status.inactive')
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
                                    :label-key="'vehicles.edit.action'"
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
                                            ? 'vehicles.actions.disable'
                                            : 'vehicles.actions.enable'
                                    "
                                    variant="ghost"
                                    :disabled="togglingVehicleId === row.id"
                                    @click="toggleStatus(row)"
                                />
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <AppActionIconButton
                                            :action="Actions.more"
                                            :label-key="'vehicles.actions.more'"
                                            variant="ghost"
                                            :tooltip="false"
                                        />
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem disabled>
                                            {{ t('vehicles.actions.moreSoon') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </template>

                        <template v-slot:[slotNames.empty]>
                            <div v-if="table.search">
                                {{
                                    t('vehicles.empty.noMatch', {
                                        query: table.search,
                                    })
                                }}
                            </div>
                            <div v-else>
                                {{ t('vehicles.empty.noVehicles') }}
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
                                t('vehicles.pageSummary', {
                                    current: vehicles.meta.current_page,
                                    last: vehicles.meta.last_page,
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
