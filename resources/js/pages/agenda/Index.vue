<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Toast from '@/components/Toast.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as scheduleIndex } from '@/routes/schedule';

defineOptions({ layout: AppLayout });

type TechnicianOption = { id: number; name: string };

type AvailabilitySlot = {
    start: string;
    end: string;
    isAvailable: boolean;
};

type AvailabilityDay = {
    date: string;
    slots: AvailabilitySlot[];
};

type Appointment = {
    id: number;
    startsAt: string;
    endsAt: string;
    technicianId: number | null;
    status: 'pending' | 'confirmed' | 'cancelled' | string | null;
    observations?: Array<{
        body: string;
        context: string;
        audienceTags: string[];
        createdAt?: string | null;
        createdBy?: number | null;
    }> | null;
};

const props = defineProps<{
    technicians: TechnicianOption[];
}>();

const { toast, success: successToast, error: errorToast } = useToast();
const { t } = useI18n();

const loading = ref(true);
const loadingAppointments = ref(true);

const viewDate = ref(new Date());
const technicianFilterId = ref<number | null>(null);

const availabilityByDate = ref<Record<string, AvailabilitySlot[]>>({});
const appointments = ref<Appointment[]>([]);

const showCreateDialog = ref(false);
const createDialogSlot = ref<AvailabilitySlot | null>(null);
const createDialogTechnicianId = ref<number | null>(null);
const creating = ref(false);
const createErrors = ref<Record<string, string[]>>({});
const createObservation = ref('');

const showAppointmentDialog = ref(false);
const selectedAppointment = ref<Appointment | null>(null);
const cancelling = ref(false);
const confirming = ref(false);
const reassigning = ref(false);
const reassignTechnicianId = ref<number | null>(null);
const appointmentErrors = ref<Record<string, string[]>>({});
const adminObservation = ref('');
const shareCustomerObservation = ref(false);

function getAppointmentObservation(
    appointment: Appointment | null,
    context: string,
    requiredTags: string[] = [],
): string | null {
    if (!appointment?.observations?.length) {
        return null;
    }

    const match = appointment.observations.find((observation) => {
        if (observation.context !== context) {
            return false;
        }

        if (!requiredTags.length) {
            return true;
        }

        const tags = observation.audienceTags ?? [];

        return requiredTags.every((tag) => tags.includes(tag));
    });

    return match?.body ?? null;
}

const showRescheduleDialog = ref(false);
const rescheduleAvailabilityByDate = ref<Record<string, AvailabilitySlot[]>>(
    {},
);
const rescheduleSlot = ref<AvailabilitySlot | null>(null);
const rescheduling = ref(false);
const rescheduleErrors = ref<Record<string, string[]>>({});

const weekStart = computed(() => startOfWeek(viewDate.value));
const weekEnd = computed(() => endOfWeek(weekStart.value));

const weekLabel = computed(() => {
    const formatter = new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    return `${formatter.format(weekStart.value)} - ${formatter.format(weekEnd.value)}`;
});

function getCsrfHeaders(): Record<string, string> {
    const csrfToken = (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    ).trim();

    const xsrfToken = decodeURIComponent(
        document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );

    return {
        ...(csrfToken !== '' ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        ...(xsrfToken !== '' ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
    };
}

const weekDays = computed(() => {
    const days: Date[] = [];
    for (let i = 0; i < 7; i += 1) {
        const date = new Date(weekStart.value);
        date.setDate(date.getDate() + i);
        days.push(date);
    }
    return days;
});

function dayKey(date: Date): string {
    return formatLocalDate(date);
}

function dayLabel(date: Date): string {
    return new Intl.DateTimeFormat('es-CO', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
    }).format(date);
}

function timeLabel(iso: string): string {
    const date = new Date(iso);
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    return `${hh}:${mm}`;
}

function appointmentForSlot(slot: AvailabilitySlot): Appointment | null {
    return (
        appointments.value.find(
            (a) => a.startsAt === slot.start && a.endsAt === slot.end,
        ) ?? null
    );
}

function cancelledAppointmentsForSlot(slot: AvailabilitySlot): Appointment[] {
    return appointments.value.filter(
        (a) =>
            a.status === 'cancelled' &&
            a.startsAt === slot.start &&
            a.endsAt === slot.end,
    );
}

function statusVariant(
    status: Appointment['status'],
): 'secondary' | 'destructive' {
    if (status === 'cancelled') {
        return 'destructive';
    }
    return 'secondary';
}

function appointmentStatusLabel(status: Appointment['status']): string {
    if (!status) {
        return t('technicians.appointment.status.label');
    }

    return t(`technicians.appointment.status.values.${status}`);
}

function previousWeek() {
    const next = new Date(weekStart.value);
    next.setDate(next.getDate() - 7);
    viewDate.value = next;
}

function nextWeek() {
    const next = new Date(weekStart.value);
    next.setDate(next.getDate() + 7);
    viewDate.value = next;
}

async function refreshWeek() {
    await Promise.all([loadAvailability(), loadAppointments()]);
}

async function loadAvailability() {
    loading.value = true;

    const from = formatLocalDate(weekStart.value);
    const to = formatLocalDate(weekEnd.value);
    const params = new URLSearchParams({ from, to });
    if (technicianFilterId.value !== null) {
        params.set('technicianId', String(technicianFilterId.value));
    }

    try {
        const response = await fetch(
            `/api/v1/appointments/availability?${params.toString()}`,
            {
                headers: { Accept: 'application/json' },
                credentials: 'include',
            },
        );

        if (!response.ok) {
            errorToast(t('agenda.errors.loadAvailability'));
            availabilityByDate.value = {};
            return;
        }

        const data = await response.json();
        const days: AvailabilityDay[] = data.data ?? [];
        availabilityByDate.value = Object.fromEntries(
            days.map((d) => [d.date, d.slots]),
        );
    } catch {
        errorToast(t('agenda.errors.loadAvailabilityConnection'));
        availabilityByDate.value = {};
    } finally {
        loading.value = false;
    }
}

async function loadAppointments() {
    loadingAppointments.value = true;

    const startsAt = `${formatLocalDate(weekStart.value)} 00:00:00`;
    const endsAt = `${formatLocalDate(weekEnd.value)} 23:59:59`;
    const params = new URLSearchParams({ startsAt, endsAt });

    try {
        const response = await fetch(
            `/api/v1/appointments?${params.toString()}`,
            {
                headers: { Accept: 'application/json' },
                credentials: 'include',
            },
        );

        if (!response.ok) {
            errorToast(t('agenda.errors.loadAppointments'));
            appointments.value = [];
            return;
        }

        const data = await response.json();
        appointments.value = data.data ?? [];
    } catch {
        errorToast(t('agenda.errors.loadAppointmentsConnection'));
        appointments.value = [];
    } finally {
        loadingAppointments.value = false;
    }
}

function openCreateDialog(slot: AvailabilitySlot) {
    createDialogSlot.value = slot;
    createDialogTechnicianId.value = technicianFilterId.value;
    createErrors.value = {};
    createObservation.value = '';
    showCreateDialog.value = true;
}

async function createAppointment() {
    if (!createDialogSlot.value) {
        return;
    }

    creating.value = true;
    createErrors.value = {};

    const payload: Record<string, unknown> = {
        startsAt: createDialogSlot.value.start,
        endsAt: createDialogSlot.value.end,
    };

    if (createObservation.value.trim()) {
        payload.observation = createObservation.value.trim();
    }

    if (createDialogTechnicianId.value !== null) {
        payload.technician = { id: createDialogTechnicianId.value };
    }

    try {
        const response = await fetch('/api/v1/appointments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...getCsrfHeaders(),
            },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        if (response.status === 422) {
            const data = await response.json();
            createErrors.value = data.errors ?? {};
            errorToast(data.message ?? t('agenda.errors.validation'));
            return;
        }

        if (!response.ok) {
            errorToast(t('agenda.errors.create'));
            return;
        }

        showCreateDialog.value = false;
        successToast(t('agenda.success.create'));
        await refreshWeek();
    } catch {
        errorToast(t('agenda.errors.createConnection'));
    } finally {
        creating.value = false;
    }
}

function openAppointmentDialog(appointment: Appointment) {
    selectedAppointment.value = appointment;
    reassignTechnicianId.value = appointment.technicianId;
    appointmentErrors.value = {};
    adminObservation.value =
        getAppointmentObservation(appointment, 'appointment_confirmation', [
            'admin',
        ]) ?? '';
    shareCustomerObservation.value = false;
    showAppointmentDialog.value = true;
}

async function cancelSelectedAppointment() {
    if (!selectedAppointment.value) {
        return;
    }

    cancelling.value = true;
    appointmentErrors.value = {};

    try {
        const response = await fetch(
            `/api/v1/appointments/${selectedAppointment.value.id}/cancel`,
            {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    ...getCsrfHeaders(),
                },
                credentials: 'include',
            },
        );

        if (response.status === 422) {
            const data = await response.json();
            appointmentErrors.value = data.errors ?? {};
            errorToast(data.message ?? t('agenda.errors.cancel'));
            return;
        }

        if (!response.ok) {
            errorToast(t('agenda.errors.cancel'));
            return;
        }

        successToast(t('agenda.success.cancel'));
        showAppointmentDialog.value = false;
        await refreshWeek();
    } catch {
        errorToast(t('agenda.errors.cancelConnection'));
    } finally {
        cancelling.value = false;
    }
}

async function confirmSelectedAppointment() {
    if (!selectedAppointment.value) {
        return;
    }

    confirming.value = true;
    appointmentErrors.value = {};

    try {
        const response = await fetch(
            `/api/v1/appointments/${selectedAppointment.value.id}/confirm`,
            {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...getCsrfHeaders(),
                },
                credentials: 'include',
                body: JSON.stringify({
                    adminObservation: adminObservation.value.trim() || null,
                    shareCustomerObservation: shareCustomerObservation.value,
                }),
            },
        );

        if (response.status === 422) {
            const data = await response.json();
            appointmentErrors.value = data.errors ?? {};
            errorToast(data.message ?? t('agenda.errors.confirm'));
            return;
        }

        if (!response.ok) {
            errorToast(t('agenda.errors.confirm'));
            return;
        }

        successToast(t('agenda.success.confirm'));
        showAppointmentDialog.value = false;
        await refreshWeek();
    } catch {
        errorToast(t('agenda.errors.confirmConnection'));
    } finally {
        confirming.value = false;
    }
}

async function reassignSelectedAppointment() {
    if (!selectedAppointment.value) {
        return;
    }

    reassigning.value = true;
    appointmentErrors.value = {};

    if (reassignTechnicianId.value === null) {
        appointmentErrors.value = {
            technician: [t('agenda.errors.selectTechnician')],
        };
        reassigning.value = false;
        return;
    }

    try {
        const response = await fetch(
            `/api/v1/appointments/${selectedAppointment.value.id}/reassign-technician`,
            {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...getCsrfHeaders(),
                },
                credentials: 'include',
                body: JSON.stringify({
                    technician: { id: reassignTechnicianId.value },
                }),
            },
        );

        if (response.status === 422) {
            const data = await response.json();
            appointmentErrors.value = data.errors ?? {};
            errorToast(data.message ?? t('agenda.errors.validation'));
            return;
        }

        if (!response.ok) {
            errorToast(t('agenda.errors.reassign'));
            return;
        }

        successToast(t('agenda.success.reassign'));
        showAppointmentDialog.value = false;
        await refreshWeek();
    } catch {
        errorToast(t('agenda.errors.reassignConnection'));
    } finally {
        reassigning.value = false;
    }
}

async function openRescheduleDialog() {
    if (!selectedAppointment.value) {
        return;
    }

    showRescheduleDialog.value = true;
    rescheduleErrors.value = {};
    rescheduleSlot.value = null;

    const from = formatLocalDate(weekStart.value);
    const to = formatLocalDate(weekEnd.value);
    const params = new URLSearchParams({ from, to });

    if (selectedAppointment.value.technicianId !== null) {
        params.set(
            'technicianId',
            String(selectedAppointment.value.technicianId),
        );
    }
    params.set('excludingAppointmentId', String(selectedAppointment.value.id));

    try {
        const response = await fetch(
            `/api/v1/appointments/availability?${params.toString()}`,
            {
                headers: { Accept: 'application/json' },
                credentials: 'include',
            },
        );

        if (!response.ok) {
            errorToast(t('agenda.errors.loadRescheduleAvailability'));
            rescheduleAvailabilityByDate.value = {};
            return;
        }

        const data = await response.json();
        const days: AvailabilityDay[] = data.data ?? [];
        rescheduleAvailabilityByDate.value = Object.fromEntries(
            days.map((d) => [d.date, d.slots]),
        );
    } catch {
        errorToast(t('agenda.errors.loadAvailabilityConnection'));
        rescheduleAvailabilityByDate.value = {};
    }
}

async function rescheduleSelectedAppointment() {
    if (!selectedAppointment.value || !rescheduleSlot.value) {
        return;
    }

    rescheduling.value = true;
    rescheduleErrors.value = {};

    try {
        const response = await fetch(
            `/api/v1/appointments/${selectedAppointment.value.id}/reschedule`,
            {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...getCsrfHeaders(),
                },
                credentials: 'include',
                body: JSON.stringify({
                    startsAt: rescheduleSlot.value.start,
                    endsAt: rescheduleSlot.value.end,
                }),
            },
        );

        if (response.status === 422) {
            const data = await response.json();
            rescheduleErrors.value = data.errors ?? {};
            errorToast(data.message ?? t('agenda.errors.validation'));
            return;
        }

        if (!response.ok) {
            errorToast(t('agenda.errors.reschedule'));
            return;
        }

        successToast(t('agenda.success.reschedule'));
        showRescheduleDialog.value = false;
        showAppointmentDialog.value = false;
        await refreshWeek();
    } catch {
        errorToast(t('agenda.errors.rescheduleConnection'));
    } finally {
        rescheduling.value = false;
    }
}

onMounted(async () => {
    await refreshWeek();
});

watch([weekStart, technicianFilterId], async () => {
    await refreshWeek();
});

function startOfWeek(date: Date): Date {
    const d = new Date(date);
    const dayIndex = (d.getDay() + 6) % 7; // Monday=0 … Sunday=6
    d.setDate(d.getDate() - dayIndex);
    d.setHours(0, 0, 0, 0);
    return d;
}

function endOfWeek(weekStartDate: Date): Date {
    const d = new Date(weekStartDate);
    d.setDate(d.getDate() + 6);
    d.setHours(23, 59, 59, 999);
    return d;
}

function formatLocalDate(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
</script>

<template>
    <Head :title="t('agenda.headTitle')" />

    <Toast :toast="toast" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="t('agenda.title')"
                :description="t('agenda.description')"
            />

            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" @click="previousWeek">
                    <ChevronLeft class="h-4 w-4" />
                    <span class="sr-only">
                        {{ t('agenda.actions.previousWeek') }}
                    </span>
                </Button>
                <div
                    class="min-w-[220px] text-sm font-medium text-muted-foreground"
                >
                    {{ weekLabel }}
                </div>
                <Button variant="outline" size="sm" @click="nextWeek">
                    <ChevronRight class="h-4 w-4" />
                    <span class="sr-only">
                        {{ t('agenda.actions.nextWeek') }}
                    </span>
                </Button>
                <Button variant="outline" size="sm" @click="refreshWeek">
                    <RefreshCw class="h-4 w-4" />
                    <span class="sr-only">
                        {{ t('agenda.actions.refresh') }}
                    </span>
                </Button>
            </div>
        </div>

        <div class="mt-6 grid gap-4">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">
                        {{ t('agenda.filters.title') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="technicianFilter">
                            {{ t('agenda.filters.technicianLabel') }}
                        </Label>
                        <Select
                            v-model="technicianFilterId"
                            :disabled="props.technicians.length === 0"
                        >
                            <SelectTrigger id="technicianFilter">
                                <SelectValue
                                    :placeholder="
                                        t(
                                            'agenda.filters.technicianPlaceholder',
                                        )
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">
                                    {{ t('agenda.filters.technicianAll') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="t in props.technicians"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div
                        class="rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground"
                    >
                        {{ t('agenda.filters.note') }}
                        <div class="mt-2">
                            {{ t('agenda.filters.noteLinkPrefix') }}
                            <a class="underline" :href="scheduleIndex().url">
                                {{ t('agenda.filters.noteLinkLabel') }}
                            </a>
                            {{ t('agenda.filters.noteLinkSuffix') }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card v-for="day in weekDays" :key="dayKey(day)">
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle class="text-base">{{
                            dayLabel(day)
                        }}</CardTitle>
                        <Badge variant="outline">{{ dayKey(day) }}</Badge>
                    </CardHeader>
                    <CardContent class="grid gap-2">
                        <template v-if="loading">
                            <div
                                v-for="i in 6"
                                :key="i"
                                class="grid grid-cols-3 items-center gap-2"
                            >
                                <Skeleton class="h-4 w-16" />
                                <Skeleton class="h-8 w-full" />
                                <Skeleton class="h-8 w-full" />
                            </div>
                        </template>

                        <template v-else>
                            <div
                                v-for="slot in availabilityByDate[
                                    dayKey(day)
                                ] || []"
                                :key="slot.start"
                                class="grid grid-cols-3 items-center gap-2 rounded-md border px-3 py-2"
                            >
                                <div class="text-sm font-medium">
                                    {{ timeLabel(slot.start) }}
                                </div>

                                <div class="min-w-0">
                                    <template v-if="loadingAppointments">
                                        <Skeleton class="h-6 w-full" />
                                    </template>
                                    <template v-else>
                                        <button
                                            v-if="
                                                appointmentForSlot(slot) &&
                                                appointmentForSlot(slot)
                                                    ?.status !== 'cancelled'
                                            "
                                            type="button"
                                            class="w-full rounded-md border bg-background px-2 py-1 text-left text-sm hover:bg-muted/40"
                                            @click="
                                                openAppointmentDialog(
                                                    appointmentForSlot(slot)!,
                                                )
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-2"
                                            >
                                                <span class="truncate">
                                                    {{
                                                        t(
                                                            'agenda.labels.appointment',
                                                            {
                                                                id: appointmentForSlot(
                                                                    slot,
                                                                )!.id,
                                                            },
                                                        )
                                                    }}
                                                </span>
                                                <Badge
                                                    :variant="
                                                        statusVariant(
                                                            appointmentForSlot(
                                                                slot,
                                                            )!.status,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        appointmentStatusLabel(
                                                            appointmentForSlot(
                                                                slot,
                                                            )!.status,
                                                        )
                                                    }}
                                                </Badge>
                                            </div>
                                        </button>
                                        <div
                                            v-else
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{
                                                slot.isAvailable
                                                    ? t(
                                                          'agenda.status.available',
                                                      )
                                                    : t(
                                                          'agenda.status.unavailable',
                                                      )
                                            }}
                                        </div>
                                    </template>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        :disabled="
                                            !slot.isAvailable ||
                                            loadingAppointments
                                        "
                                        @click="openCreateDialog(slot)"
                                    >
                                        {{ t('agenda.actions.new') }}
                                    </Button>
                                </div>

                                <div
                                    v-if="
                                        cancelledAppointmentsForSlot(slot)
                                            .length
                                    "
                                    class="col-span-3 mt-2 grid gap-1"
                                >
                                    <div
                                        v-for="a in cancelledAppointmentsForSlot(
                                            slot,
                                        )"
                                        :key="a.id"
                                        class="flex items-center justify-between rounded-md border bg-muted/20 px-2 py-1 text-sm"
                                    >
                                        <button
                                            type="button"
                                            class="truncate text-left underline-offset-2 hover:underline"
                                            @click="openAppointmentDialog(a)"
                                        >
                                            {{
                                                t('agenda.labels.appointment', {
                                                    id: a.id,
                                                })
                                            }}
                                        </button>
                                        <Badge variant="destructive">
                                            {{
                                                appointmentStatusLabel(a.status)
                                            }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="
                                    !(availabilityByDate[dayKey(day)] || [])
                                        .length
                                "
                                class="rounded-md border bg-muted/20 px-3 py-4 text-sm text-muted-foreground"
                            >
                                {{ t('agenda.empty.noSlots') }}
                            </div>
                        </template>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <Dialog v-model:open="showCreateDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    t('agenda.dialogs.create.title')
                }}</DialogTitle>
                <DialogDescription>
                    {{ t('agenda.dialogs.create.description') }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <div class="rounded-md border bg-muted/20 px-3 py-2 text-sm">
                    <div class="font-medium">
                        {{ t('agenda.dialogs.create.slotLabel') }}
                    </div>
                    <div v-if="createDialogSlot" class="text-muted-foreground">
                        {{ createDialogSlot.start }} a
                        {{ createDialogSlot.end }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="createTechnician">
                        {{ t('agenda.dialogs.create.technicianLabel') }}
                    </Label>
                    <Select v-model="createDialogTechnicianId">
                        <SelectTrigger id="createTechnician">
                            <SelectValue
                                :placeholder="
                                    t(
                                        'agenda.dialogs.create.technicianPlaceholder',
                                    )
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">
                                {{ t('agenda.dialogs.create.technicianNone') }}
                            </SelectItem>
                            <SelectItem
                                v-for="t in props.technicians"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="createErrors.technician?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-observation">
                        {{ t('agenda.dialogs.create.observationLabel') }}
                    </Label>
                    <Textarea
                        id="create-observation"
                        v-model="createObservation"
                        rows="3"
                        :placeholder="
                            t('agenda.dialogs.create.observationPlaceholder')
                        "
                    />
                    <InputError :message="createErrors.observation?.[0]" />
                </div>

                <InputError :message="createErrors.slot?.[0]" />
                <InputError :message="createErrors.startsAt?.[0]" />
                <InputError :message="createErrors.endsAt?.[0]" />
            </div>

            <DialogFooter class="flex items-center justify-end gap-2">
                <DialogClose as-child>
                    <Button variant="outline" :disabled="creating">
                        {{ t('agenda.dialogs.create.close') }}
                    </Button>
                </DialogClose>
                <Button :disabled="creating" @click="createAppointment">
                    <Spinner v-if="creating" class="mr-2" />
                    {{ t('agenda.dialogs.create.submit') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="showAppointmentDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    t('agenda.dialogs.details.title')
                }}</DialogTitle>
                <DialogDescription>
                    {{ t('agenda.dialogs.details.description') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedAppointment" class="grid gap-4">
                <div class="rounded-md border bg-muted/20 px-3 py-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-medium">
                            {{
                                t('agenda.labels.appointment', {
                                    id: selectedAppointment.id,
                                })
                            }}
                        </div>
                        <Badge
                            :variant="statusVariant(selectedAppointment.status)"
                        >
                            {{
                                appointmentStatusLabel(
                                    selectedAppointment.status,
                                )
                            }}
                        </Badge>
                    </div>
                    <div class="mt-1 text-muted-foreground">
                        {{ selectedAppointment.startsAt }} a
                        {{ selectedAppointment.endsAt }}
                    </div>
                </div>

                <div
                    v-if="
                        getAppointmentObservation(
                            selectedAppointment,
                            'appointment_request',
                            ['customer'],
                        )
                    "
                    class="grid gap-2"
                >
                    <Label>
                        {{
                            t('agenda.dialogs.details.requestObservationLabel')
                        }}
                    </Label>
                    <div
                        class="rounded-md border bg-muted/20 px-3 py-2 text-sm text-muted-foreground"
                    >
                        {{
                            getAppointmentObservation(
                                selectedAppointment,
                                'appointment_request',
                                ['customer'],
                            )
                        }}
                    </div>
                </div>

                <div
                    v-if="
                        getAppointmentObservation(
                            selectedAppointment,
                            'appointment_technician',
                            ['technician'],
                        )
                    "
                    class="grid gap-2"
                >
                    <Label>
                        {{
                            t(
                                'agenda.dialogs.details.technicianObservationLabel',
                            )
                        }}
                    </Label>
                    <div
                        class="rounded-md border bg-muted/20 px-3 py-2 text-sm text-muted-foreground"
                    >
                        {{
                            getAppointmentObservation(
                                selectedAppointment,
                                'appointment_technician',
                                ['technician'],
                            )
                        }}
                    </div>
                </div>

                <div
                    v-if="
                        selectedAppointment.status !== 'pending' &&
                        getAppointmentObservation(
                            selectedAppointment,
                            'appointment_confirmation',
                            ['admin'],
                        )
                    "
                    class="grid gap-2"
                >
                    <Label>
                        {{ t('agenda.dialogs.details.adminObservationLabel') }}
                    </Label>
                    <div
                        class="rounded-md border bg-muted/20 px-3 py-2 text-sm text-muted-foreground"
                    >
                        {{
                            getAppointmentObservation(
                                selectedAppointment,
                                'appointment_confirmation',
                                ['admin'],
                            )
                        }}
                    </div>
                </div>

                <div
                    v-if="selectedAppointment.status === 'pending'"
                    class="grid gap-2"
                >
                    <Label for="admin-observation">
                        {{ t('agenda.dialogs.details.adminObservationLabel') }}
                    </Label>
                    <Textarea
                        id="admin-observation"
                        v-model="adminObservation"
                        rows="3"
                        :placeholder="
                            t(
                                'agenda.dialogs.details.adminObservationPlaceholder',
                            )
                        "
                    />
                    <InputError
                        :message="appointmentErrors.adminObservation?.[0]"
                    />
                </div>

                <div
                    v-if="
                        selectedAppointment.status === 'pending' &&
                        getAppointmentObservation(
                            selectedAppointment,
                            'appointment_request',
                            ['customer'],
                        )
                    "
                    class="flex items-start gap-3"
                >
                    <Checkbox
                        id="share-customer-observation"
                        v-model:checked="shareCustomerObservation"
                    />
                    <div class="grid gap-1">
                        <Label for="share-customer-observation">
                            {{
                                t(
                                    'agenda.dialogs.details.shareCustomerObservationLabel',
                                )
                            }}
                        </Label>
                        <p class="text-sm text-muted-foreground">
                            {{
                                t(
                                    'agenda.dialogs.details.shareCustomerObservationHelp',
                                )
                            }}
                        </p>
                        <InputError
                            :message="
                                appointmentErrors.shareCustomerObservation?.[0]
                            "
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="reassignTechnician">
                        {{ t('agenda.dialogs.details.reassignLabel') }}
                    </Label>
                    <Select v-model="reassignTechnicianId">
                        <SelectTrigger id="reassignTechnician">
                            <SelectValue
                                :placeholder="
                                    t(
                                        'agenda.dialogs.details.reassignPlaceholder',
                                    )
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="t in props.technicians"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="appointmentErrors.appointment?.[0]" />
                    <InputError :message="appointmentErrors.technician?.[0]" />
                    <InputError :message="appointmentErrors.slot?.[0]" />
                </div>
            </div>

            <DialogFooter class="flex flex-wrap items-center justify-end gap-2">
                <DialogClose as-child>
                    <Button
                        variant="outline"
                        :disabled="cancelling || confirming || reassigning"
                    >
                        {{ t('agenda.dialogs.details.close') }}
                    </Button>
                </DialogClose>

                <Button
                    variant="outline"
                    :disabled="
                        !selectedAppointment ||
                        cancelling ||
                        confirming ||
                        reassigning
                    "
                    @click="openRescheduleDialog"
                >
                    {{ t('agenda.dialogs.details.reschedule') }}
                </Button>

                <Button
                    v-if="selectedAppointment?.status === 'pending'"
                    variant="secondary"
                    :disabled="
                        !selectedAppointment ||
                        cancelling ||
                        confirming ||
                        reassigning
                    "
                    @click="confirmSelectedAppointment"
                >
                    <Spinner v-if="confirming" class="mr-2" />
                    {{ t('agenda.dialogs.details.confirm') }}
                </Button>

                <Button
                    :disabled="
                        !selectedAppointment ||
                        cancelling ||
                        confirming ||
                        reassigning
                    "
                    @click="reassignSelectedAppointment"
                >
                    <Spinner v-if="reassigning" class="mr-2" />
                    {{ t('agenda.dialogs.details.reassign') }}
                </Button>

                <Button
                    variant="destructive"
                    :disabled="
                        !selectedAppointment ||
                        cancelling ||
                        confirming ||
                        reassigning
                    "
                    @click="cancelSelectedAppointment"
                >
                    <Spinner v-if="cancelling" class="mr-2" />
                    {{ t('agenda.dialogs.details.cancel') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="showRescheduleDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ t('agenda.dialogs.reschedule.title') }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('agenda.dialogs.reschedule.description') }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-3">
                <div class="grid gap-2">
                    <Label>{{
                        t('agenda.dialogs.reschedule.slotLabel')
                    }}</Label>
                    <Select v-model="rescheduleSlot">
                        <SelectTrigger>
                            <SelectValue
                                :placeholder="
                                    t(
                                        'agenda.dialogs.reschedule.slotPlaceholder',
                                    )
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <template
                                v-for="day in weekDays"
                                :key="dayKey(day)"
                            >
                                <SelectItem
                                    v-for="slot in (
                                        rescheduleAvailabilityByDate[
                                            dayKey(day)
                                        ] || []
                                    ).filter((s) => s.isAvailable)"
                                    :key="slot.start"
                                    :value="slot"
                                >
                                    {{ dayKey(day) }}
                                    {{ timeLabel(slot.start) }} -
                                    {{ timeLabel(slot.end) }}
                                </SelectItem>
                            </template>
                        </SelectContent>
                    </Select>
                    <InputError :message="rescheduleErrors.appointment?.[0]" />
                    <InputError :message="rescheduleErrors.slot?.[0]" />
                    <InputError :message="rescheduleErrors.startsAt?.[0]" />
                    <InputError :message="rescheduleErrors.endsAt?.[0]" />
                </div>
            </div>

            <DialogFooter class="flex items-center justify-end gap-2">
                <DialogClose as-child>
                    <Button variant="outline" :disabled="rescheduling">
                        {{ t('agenda.dialogs.reschedule.close') }}
                    </Button>
                </DialogClose>
                <Button
                    :disabled="!rescheduleSlot || rescheduling"
                    @click="rescheduleSelectedAppointment"
                >
                    <Spinner v-if="rescheduling" class="mr-2" />
                    {{ t('agenda.dialogs.reschedule.save') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
