<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CalendarOff, Clock } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

// --- Types ---
type ScheduleDay = {
    id?: number;
    day_of_week: number;
    is_working_day: boolean;
    start_time: string | undefined;
    end_time: string | undefined;
    slot_duration: number | undefined;
};

type ScheduleOverride = {
    id: number;
    date: string;
    is_working_day: boolean;
    start_time: string | null;
    end_time: string | null;
    reason: string | null;
};

// --- Props ---

const props = defineProps<{
    schedule: ScheduleDay[] | null;
    overrides: ScheduleOverride[];
    isConfigured: boolean;
    dayValues: number[];
}>();

// --- State ---

const page = usePage();
const { toast, success: successToast, error: errorToast } = useToast();
const { t } = useI18n();

const saving = ref(false);
const savingOverride = ref(false);
const deletingOverrideId = ref<number | null>(null);
const showOverrideDialog = ref(false);
const showDeleteDialog = ref(false);
const overrideToDelete = ref<ScheduleOverride | null>(null);
const showEmptyState = ref(!props.isConfigured && !props.schedule?.length);
const serverErrors = ref<Record<string, string>>({});

const overridesList = ref<ScheduleOverride[]>([...props.overrides]);

const overrideForm = reactive({
    date: '',
    is_working_day: false,
    start_time: '',
    end_time: '',
    reason: '',
});

// --- Weekly schedule form ---

function buildScheduleForm(): ScheduleDay[] {
    if (props.schedule && props.schedule.length === 7) {
        return props.schedule.map((day) => ({
            ...day,
            start_time: day.start_time ? formatTime(day.start_time) : undefined,
            end_time: day.end_time ? formatTime(day.end_time) : undefined,
            slot_duration: day.slot_duration ?? undefined,
        }));
    }

    return Array.from({ length: 7 }, (_, i) => ({
        day_of_week: i,
        is_working_day: i < 5,
        start_time: i < 5 ? '08:00' : undefined,
        end_time: i < 5 ? '17:00' : undefined,
        slot_duration: i < 5 ? 30 : undefined,
    }));
}

const days = reactive<ScheduleDay[]>(buildScheduleForm());

function formatTime(value: string): string {
    // Handle datetime formats like "2025-01-01T08:00:00.000000Z" or "08:00:00"
    if (value.includes('T')) {
        const date = new Date(value);
        return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    }
    return value.substring(0, 5);
}

function getDayLabel(dayOfWeek: number): string {
    return t(`dayOfWeek.values.${dayOfWeek}`);
}

function toggleWorkingDay(index: number, checked: boolean) {
    days[index].is_working_day = checked;
    if (!checked) {
        days[index].start_time = undefined;
        days[index].end_time = undefined;
        days[index].slot_duration = undefined;
    } else {
        days[index].start_time = days[index].start_time ?? '08:00';
        days[index].end_time = days[index].end_time ?? '17:00';
        days[index].slot_duration = days[index].slot_duration ?? 30;
    }
}

// --- Actions ---

async function saveSchedule() {
    saving.value = true;
    serverErrors.value = {};

    try {
        const response = await fetch('/api/v1/schedule', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ days }),
        });

        if (response.status === 422) {
            const data = await response.json();
            serverErrors.value = data.errors ?? {};
            errorToast('Revise los errores en el formulario.');
            return;
        }

        if (!response.ok) {
            errorToast('No se pudo guardar la configuración.');
            return;
        }

        showEmptyState.value = false;
        successToast('Configuración semanal guardada exitosamente.');
    } catch {
        errorToast('Error de conexión al guardar la configuración.');
    } finally {
        saving.value = false;
    }
}

function openOverrideDialog() {
    overrideForm.date = '';
    overrideForm.is_working_day = false;
    overrideForm.start_time = '';
    overrideForm.end_time = '';
    overrideForm.reason = '';
    serverErrors.value = {};
    showOverrideDialog.value = true;
}

async function saveOverride() {
    savingOverride.value = true;
    serverErrors.value = {};

    const payload: Record<string, unknown> = {
        date: overrideForm.date,
        is_working_day: overrideForm.is_working_day,
        reason: overrideForm.reason || null,
    };

    if (overrideForm.is_working_day) {
        payload.start_time = overrideForm.start_time;
        payload.end_time = overrideForm.end_time;
    }

    try {
        const response = await fetch('/api/v1/schedule/overrides', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (response.status === 422) {
            const data = await response.json();
            serverErrors.value = data.errors ?? {};
            errorToast('Revise los errores en el formulario.');
            return;
        }

        if (!response.ok) {
            errorToast('No se pudo crear la excepción.');
            return;
        }

        const data = await response.json();
        overridesList.value.push(data.data);
        overridesList.value.sort((a, b) => a.date.localeCompare(b.date));
        showOverrideDialog.value = false;
        successToast('Excepción creada exitosamente.');
    } catch {
        errorToast('Error de conexión al crear la excepción.');
    } finally {
        savingOverride.value = false;
    }
}

function openDeleteDialog(override: ScheduleOverride) {
    overrideToDelete.value = override;
    showDeleteDialog.value = true;
}

async function confirmDeleteOverride() {
    if (!overrideToDelete.value) return;

    const id = overrideToDelete.value.id;
    deletingOverrideId.value = id;

    try {
        const response = await fetch(`/api/v1/schedule/overrides/${id}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            errorToast('No se pudo eliminar la excepción.');
            return;
        }

        overridesList.value = overridesList.value.filter((o) => o.id !== id);
        showDeleteDialog.value = false;
        overrideToDelete.value = null;
        successToast('Excepción eliminada exitosamente.');
    } catch {
        errorToast('Error de conexión al eliminar la excepción.');
    } finally {
        deletingOverrideId.value = null;
    }
}

function formatDate(dateStr: string): string {
    // Extract just the date part (YYYY-MM-DD) whether the input is
    // "2026-02-14" or "2026-02-14T00:00:00.000000Z"
    const datePart = dateStr.substring(0, 10);
    const date = new Date(datePart + 'T12:00:00');
    return date.toLocaleDateString('es-CO', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function getFieldError(field: string): string | undefined {
    const pageErrors = page.props.errors as Record<string, string>;
    if (pageErrors?.[field]) return pageErrors[field];

    // Handle nested errors like "days.0.start_time"
    const nested = serverErrors.value[field];
    if (nested) return Array.isArray(nested) ? nested[0] : nested;

    return undefined;
}
</script>

<template>
    <Head title="Configuración de horarios" />

    <Toast :toast="toast" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                title="Configuración de horarios"
                description="Configure los días y horarios de atención para habilitar el agendamiento de citas."
            />
        </div>

        <!-- Empty state -->
        <div v-if="showEmptyState" class="mt-10">
            <Card class="mx-auto max-w-lg">
                <CardContent
                    class="flex flex-col items-center gap-4 pt-6 text-center"
                >
                    <div class="rounded-full bg-muted p-4">
                        <Clock class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-2">
                        <p class="text-lg font-semibold">
                            No hay configuración de horarios
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Configure los días y horarios de atención para
                            habilitar el agendamiento de citas.
                        </p>
                    </div>
                    <Button @click="showEmptyState = false">
                        Configurar ahora
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Main content -->
        <div v-else class="mt-6 space-y-8">
            <!-- Weekly schedule -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Configuración semanal
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <!-- Desktop table -->
                    <div class="hidden lg:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">
                                            Día
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Laborable
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Hora inicio
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Hora fin
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Duración franja (min)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr
                                        v-for="(day, index) in days"
                                        :key="day.day_of_week"
                                        class="hover:bg-muted/50"
                                    >
                                        <td class="px-4 py-3 font-medium">
                                            {{ getDayLabel(day.day_of_week) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Switch
                                                :model-value="
                                                    day.is_working_day
                                                "
                                                @update:model-value="
                                                    (val: boolean) =>
                                                        toggleWorkingDay(
                                                            index,
                                                            val,
                                                        )
                                                "
                                            />
                                        </td>
                                        <td class="px-4 py-3">
                                            <Input
                                                v-model="day.start_time"
                                                type="time"
                                                :disabled="!day.is_working_day"
                                                class="w-32"
                                            />
                                            <InputError
                                                :message="
                                                    getFieldError(
                                                        `days.${index}.start_time`,
                                                    )
                                                "
                                            />
                                        </td>
                                        <td class="px-4 py-3">
                                            <Input
                                                v-model="day.end_time"
                                                type="time"
                                                :disabled="!day.is_working_day"
                                                class="w-32"
                                            />
                                            <InputError
                                                :message="
                                                    getFieldError(
                                                        `days.${index}.end_time`,
                                                    )
                                                "
                                            />
                                        </td>
                                        <td class="px-4 py-3">
                                            <Input
                                                v-model.number="
                                                    day.slot_duration
                                                "
                                                type="number"
                                                min="5"
                                                max="480"
                                                :disabled="!day.is_working_day"
                                                class="w-24"
                                            />
                                            <InputError
                                                :message="
                                                    getFieldError(
                                                        `days.${index}.slot_duration`,
                                                    )
                                                "
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile cards -->
                    <div class="grid grid-cols-1 gap-4 lg:hidden">
                        <Card
                            v-for="(day, index) in days"
                            :key="day.day_of_week"
                            class="p-4"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-base font-semibold">{{
                                    getDayLabel(day.day_of_week)
                                }}</span>
                                <Switch
                                    :model-value="day.is_working_day"
                                    @update:model-value="
                                        (val: boolean) =>
                                            toggleWorkingDay(index, val)
                                    "
                                />
                            </div>

                            <div
                                v-if="day.is_working_day"
                                class="mt-4 grid grid-cols-2 gap-3"
                            >
                                <div class="grid gap-1.5">
                                    <Label
                                        :for="`start-${index}`"
                                        class="text-xs"
                                        >Hora inicio</Label
                                    >
                                    <Input
                                        :id="`start-${index}`"
                                        v-model="day.start_time"
                                        type="time"
                                    />
                                    <InputError
                                        :message="
                                            getFieldError(
                                                `days.${index}.start_time`,
                                            )
                                        "
                                    />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label :for="`end-${index}`" class="text-xs"
                                        >Hora fin</Label
                                    >
                                    <Input
                                        :id="`end-${index}`"
                                        v-model="day.end_time"
                                        type="time"
                                    />
                                    <InputError
                                        :message="
                                            getFieldError(
                                                `days.${index}.end_time`,
                                            )
                                        "
                                    />
                                </div>
                                <div class="col-span-2 grid gap-1.5">
                                    <Label :for="`dur-${index}`" class="text-xs"
                                        >Duración franja (min)</Label
                                    >
                                    <Input
                                        :id="`dur-${index}`"
                                        v-model.number="day.slot_duration"
                                        type="number"
                                        min="5"
                                        max="480"
                                    />
                                    <InputError
                                        :message="
                                            getFieldError(
                                                `days.${index}.slot_duration`,
                                            )
                                        "
                                    />
                                </div>
                            </div>
                        </Card>
                    </div>
                </CardContent>

                <CardFooter class="flex-row justify-end gap-3 border-t">
                    <AppActionButton
                        :action="Actions.save"
                        :label-key="'schedule.actions.saveConfiguration'"
                        :loading-label-key="'schedule.actions.saving'"
                        :loading="saving"
                        :disabled="saving"
                        @click="saveSchedule"
                    />
                </CardFooter>
            </Card>

            <!-- Overrides / holidays -->
            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="flex items-center gap-2">
                        <CalendarOff class="h-5 w-5" />
                        Días no laborables / Feriados
                    </CardTitle>
                    <AppActionButton
                        :action="Actions.create"
                        :label-key="'schedule.overrides.actions.create'"
                        size="sm"
                        @click="openOverrideDialog"
                    />
                </CardHeader>

                <CardContent>
                    <!-- Desktop table -->
                    <div class="hidden lg:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">
                                            Fecha
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Motivo
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right font-medium"
                                        >
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr
                                        v-for="override in overridesList"
                                        :key="override.id"
                                        class="hover:bg-muted/50"
                                    >
                                        <td class="px-4 py-3">
                                            <span class="capitalize">{{
                                                formatDate(override.date)
                                            }}</span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{ override.reason || '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <AppRowActions class="justify-end">
                                                <AppActionIconButton
                                                    :action="Actions.view"
                                                    :label-key="'common.notAvailable'"
                                                    :tooltip="false"
                                                    disabled
                                                />
                                                <AppActionIconButton
                                                    :action="Actions.edit"
                                                    :label-key="'common.notAvailable'"
                                                    :tooltip="false"
                                                    disabled
                                                />
                                                <AppActionIconButton
                                                    :action="Actions.delete"
                                                    :label-key="'schedule.overrides.actions.delete'"
                                                    :tooltip="false"
                                                    @click="
                                                        openDeleteDialog(
                                                            override,
                                                        )
                                                    "
                                                />
                                                <AppActionIconButton
                                                    :action="Actions.more"
                                                    :label-key="'schedule.overrides.actions.more'"
                                                    :tooltip="false"
                                                    disabled
                                                />
                                            </AppRowActions>
                                        </td>
                                    </tr>
                                    <tr v-if="overridesList.length === 0">
                                        <td
                                            colspan="3"
                                            class="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            No hay feriados o cierres
                                            programados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile cards -->
                    <div class="grid grid-cols-1 gap-3 lg:hidden">
                        <Card
                            v-for="override in overridesList"
                            :key="override.id"
                            class="p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold capitalize">
                                        {{ formatDate(override.date) }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ override.reason || 'Sin motivo' }}
                                    </p>
                                </div>
                                <AppRowActions>
                                    <AppActionIconButton
                                        :action="Actions.view"
                                        :label-key="'common.notAvailable'"
                                        :tooltip="false"
                                        disabled
                                    />
                                    <AppActionIconButton
                                        :action="Actions.edit"
                                        :label-key="'common.notAvailable'"
                                        :tooltip="false"
                                        disabled
                                    />
                                    <AppActionIconButton
                                        :action="Actions.delete"
                                        :label-key="'schedule.overrides.actions.delete'"
                                        :tooltip="false"
                                        @click="openDeleteDialog(override)"
                                    />
                                    <AppActionIconButton
                                        :action="Actions.more"
                                        :label-key="'schedule.overrides.actions.more'"
                                        :tooltip="false"
                                        disabled
                                    />
                                </AppRowActions>
                            </div>
                        </Card>

                        <Card v-if="overridesList.length === 0" class="p-6">
                            <p
                                class="text-center text-sm text-muted-foreground"
                            >
                                No hay feriados o cierres programados.
                            </p>
                        </Card>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Override create dialog -->
        <Dialog v-model:open="showOverrideDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Agregar feriado o cierre</DialogTitle>
                    <DialogDescription>
                        Seleccione la fecha y opcionalmente agregue un motivo
                        para el cierre.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="saveOverride" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="override-date">Fecha</Label>
                        <Input
                            id="override-date"
                            v-model="overrideForm.date"
                            type="date"
                            :min="new Date().toISOString().split('T')[0]"
                        />
                        <InputError :message="getFieldError('date')" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="override-reason">Motivo (opcional)</Label>
                        <Input
                            id="override-reason"
                            v-model="overrideForm.reason"
                            type="text"
                            placeholder="Ej: Día festivo, mantenimiento..."
                            maxlength="255"
                        />
                        <InputError :message="getFieldError('reason')" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button
                                variant="secondary"
                                type="button"
                                :disabled="savingOverride"
                            >
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="savingOverride">
                            <Spinner v-if="savingOverride" class="mr-2" />
                            {{ savingOverride ? 'Guardando...' : 'Guardar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>¿Eliminar excepción?</DialogTitle>
                    <DialogDescription>
                        Se eliminará la excepción del
                        <span
                            v-if="overrideToDelete"
                            class="font-medium capitalize"
                        >
                            {{ formatDate(overrideToDelete.date) }} </span
                        >. Esta acción no se puede deshacer.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            variant="secondary"
                            :disabled="deletingOverrideId !== null"
                        >
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        @click="confirmDeleteOverride"
                        :disabled="deletingOverrideId !== null"
                    >
                        <Spinner
                            v-if="deletingOverrideId !== null"
                            class="mr-2"
                        />
                        {{
                            deletingOverrideId !== null
                                ? 'Eliminando...'
                                : 'Eliminar'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
