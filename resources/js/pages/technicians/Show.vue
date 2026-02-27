<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    Info,
    ShieldBan,
} from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AppActionButton from '@/components/AppActionButton.vue';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Toast from '@/components/Toast.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import techniciansRoutes from '@/routes/technicians';

defineOptions({ layout: AppLayout });

// --- Types ---

type CdaSchedule = {
    id?: number;
    day_of_week: number;
    is_working_day: boolean;
    start_time: string | null;
    end_time: string | null;
    slot_duration: number | null;
};

type AvailabilityDay = {
    day_of_week: number;
    is_available: boolean;
    start_time: string | null;
    end_time: string | null;
};

type Technician = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    is_active: boolean;
};

type TechnicianBlock = {
    id: number;
    start_date: string;
    end_date: string;
    start_time: string | null;
    end_time: string | null;
    is_full_day: boolean;
    reason: string | null;
};

type ConflictAppointment = {
    id: number;
    date: string;
    time: string;
    status: string;
};

// --- Props ---

const props = defineProps<{
    technician: Technician;
    availability: AvailabilityDay[];
    hasAvailability: boolean;
    cdaSchedule: CdaSchedule[];
    dayValues: number[];
}>();

// --- State ---

const { toast, success: successToast, error: errorToast } = useToast();
const { t } = useI18n();
const saving = ref(false);
const serverErrors = ref<Record<string, string>>({});

// --- Blocks state ---

const blocksList = ref<TechnicianBlock[]>([]);
const loadingBlocks = ref(false);
const savingBlock = ref(false);
const deletingBlockId = ref<number | null>(null);
const showBlockDialog = ref(false);
const showDeleteBlockDialog = ref(false);
const blockToDelete = ref<TechnicianBlock | null>(null);
const blockServerErrors = ref<Record<string, string>>({});
const blockConflicts = ref<ConflictAppointment[]>([]);
const blockOverlap = ref<{
    id: number;
    start_date: string;
    end_date: string;
    reason: string | null;
} | null>(null);
const blockFilter = ref<'future' | 'past' | 'all'>('future');

const blockForm = reactive({
    start_date: '',
    end_date: '',
    is_full_day: true,
    start_time: '',
    end_time: '',
    reason: '',
});

// --- CDA schedule helpers ---

function getCdaForDay(dayOfWeek: number): CdaSchedule | undefined {
    return props.cdaSchedule.find(
        (s) => s.day_of_week === dayOfWeek && s.is_working_day,
    );
}

function formatTime(value: string | null): string {
    if (!value) return '';
    if (value.includes('T')) {
        const date = new Date(value);
        return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    }
    return value.substring(0, 5);
}

function cdaLabel(dayOfWeek: number): string {
    const cda = getCdaForDay(dayOfWeek);
    if (!cda) return t('technicians.availability.nonWorkingDay');
    return `${formatTime(cda.start_time)} – ${formatTime(cda.end_time)}`;
}

function isCdaWorkingDay(dayOfWeek: number): boolean {
    return !!getCdaForDay(dayOfWeek);
}

function cdaMinTime(dayOfWeek: number): string | undefined {
    const cda = getCdaForDay(dayOfWeek);
    return cda?.start_time ? formatTime(cda.start_time) : undefined;
}

function cdaMaxTime(dayOfWeek: number): string | undefined {
    const cda = getCdaForDay(dayOfWeek);
    return cda?.end_time ? formatTime(cda.end_time) : undefined;
}

// --- Availability form ---

type AvailabilityFormDay = {
    day_of_week: number;
    is_available: boolean;
    start_time: string | undefined;
    end_time: string | undefined;
};

function buildForm(): AvailabilityFormDay[] {
    return props.dayValues.map((dayValue) => {
        const existing = props.availability.find(
            (a) => a.day_of_week === dayValue,
        );
        return {
            day_of_week: dayValue,
            is_available: existing?.is_available ?? false,
            start_time: existing?.start_time
                ? formatTime(existing.start_time)
                : undefined,
            end_time: existing?.end_time
                ? formatTime(existing.end_time)
                : undefined,
        };
    });
}

const days = reactive<AvailabilityFormDay[]>(buildForm());

function getDayLabel(dayOfWeek: number): string {
    return t(`dayOfWeek.values.${dayOfWeek}`);
}

function toggleAvailable(index: number, checked: boolean) {
    days[index].is_available = checked;
    if (!checked) {
        days[index].start_time = undefined;
        days[index].end_time = undefined;
    } else {
        const cda = getCdaForDay(days[index].day_of_week);
        days[index].start_time =
            days[index].start_time ??
            (cda ? formatTime(cda.start_time) : '08:00');
        days[index].end_time =
            days[index].end_time ?? (cda ? formatTime(cda.end_time) : '17:00');
    }
}

// --- Save availability ---

async function saveAvailability() {
    saving.value = true;
    serverErrors.value = {};

    try {
        const response = await fetch(
            `/api/v1/technicians/${props.technician.id}/availability`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ days }),
            },
        );

        if (response.status === 422) {
            const data = await response.json();
            if (data.message) {
                errorToast(data.message);
            } else {
                errorToast(t('technicians.availability.errors.validation'));
            }
            serverErrors.value = data.errors ?? {};
            return;
        }

        if (!response.ok) {
            const data = await response.json().catch(() => null);
            errorToast(
                data?.message ?? t('technicians.availability.errors.save'),
            );
            return;
        }

        successToast(t('technicians.availability.success.save'));
    } catch {
        errorToast(t('technicians.availability.errors.connection'));
    } finally {
        saving.value = false;
    }
}

function getFieldError(field: string): string | undefined {
    const nested = serverErrors.value[field];
    if (nested) return Array.isArray(nested) ? nested[0] : nested;
    return undefined;
}

// --- Blocks helpers ---

function formatDate(dateStr: string): string {
    const datePart = dateStr.substring(0, 10);
    const date = new Date(datePart + 'T12:00:00');
    return date.toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatBlockPeriod(block: TechnicianBlock): string {
    const start = block.start_date.substring(0, 10);
    const end = block.end_date.substring(0, 10);
    if (start === end) return formatDate(start);
    return `${formatDate(start)} – ${formatDate(end)}`;
}

function formatBlockSchedule(block: TechnicianBlock): string {
    if (block.is_full_day) return t('technicians.blocks.fullDay');
    return `${formatTime(block.start_time)} – ${formatTime(block.end_time)}`;
}

function todayString(): string {
    return new Date().toISOString().split('T')[0];
}

// --- Blocks CRUD ---

async function fetchBlocks() {
    loadingBlocks.value = true;

    try {
        let url = `/api/v1/technicians/${props.technician.id}/blocks`;
        const params = new URLSearchParams();

        if (blockFilter.value === 'future') {
            params.set('from', todayString());
        } else if (blockFilter.value === 'past') {
            params.set('to', todayString());
        }

        if (params.toString()) url += `?${params.toString()}`;

        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            errorToast(t('technicians.blocks.errors.load'));
            return;
        }

        blocksList.value = await response.json();
    } catch {
        errorToast(t('technicians.blocks.errors.connection'));
    } finally {
        loadingBlocks.value = false;
    }
}

function openBlockDialog() {
    blockForm.start_date = '';
    blockForm.end_date = '';
    blockForm.is_full_day = true;
    blockForm.start_time = '';
    blockForm.end_time = '';
    blockForm.reason = '';
    blockServerErrors.value = {};
    blockConflicts.value = [];
    blockOverlap.value = null;
    showBlockDialog.value = true;
}

async function saveBlock() {
    savingBlock.value = true;
    blockServerErrors.value = {};
    blockConflicts.value = [];
    blockOverlap.value = null;

    const payload: Record<string, unknown> = {
        start_date: blockForm.start_date,
        end_date: blockForm.end_date,
        is_full_day: blockForm.is_full_day,
        reason: blockForm.reason || null,
    };

    if (!blockForm.is_full_day) {
        payload.start_time = blockForm.start_time;
        payload.end_time = blockForm.end_time;
    }

    try {
        const response = await fetch(
            `/api/v1/technicians/${props.technician.id}/blocks`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            },
        );

        if (response.status === 422) {
            const data = await response.json();

            if (data.conflicts) {
                blockConflicts.value = data.conflicts;
                errorToast(data.message);
                return;
            }

            if (data.overlapping_block) {
                blockOverlap.value = data.overlapping_block;
                errorToast(data.message);
                return;
            }

            blockServerErrors.value = data.errors ?? {};
            errorToast(
                data.message ?? t('technicians.blocks.errors.validation'),
            );
            return;
        }

        if (!response.ok) {
            errorToast(t('technicians.blocks.errors.create'));
            return;
        }

        showBlockDialog.value = false;
        successToast(t('technicians.blocks.success.create'));
        await fetchBlocks();
    } catch {
        errorToast(t('technicians.blocks.errors.createConnection'));
    } finally {
        savingBlock.value = false;
    }
}

function openDeleteBlockDialog(block: TechnicianBlock) {
    blockToDelete.value = block;
    showDeleteBlockDialog.value = true;
}

async function confirmDeleteBlock() {
    if (!blockToDelete.value) return;

    const id = blockToDelete.value.id;
    deletingBlockId.value = id;

    try {
        const response = await fetch(
            `/api/v1/technicians/${props.technician.id}/blocks/${id}`,
            {
                method: 'DELETE',
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            errorToast(t('technicians.blocks.errors.delete'));
            return;
        }

        blocksList.value = blocksList.value.filter((b) => b.id !== id);
        showDeleteBlockDialog.value = false;
        blockToDelete.value = null;
        successToast(t('technicians.blocks.success.delete'));
    } catch {
        errorToast(t('technicians.blocks.errors.deleteConnection'));
    } finally {
        deletingBlockId.value = null;
    }
}

function getBlockFieldError(field: string): string | undefined {
    const nested = blockServerErrors.value[field];
    if (nested) return Array.isArray(nested) ? nested[0] : nested;
    return undefined;
}

function changeBlockFilter(filter: 'future' | 'past' | 'all') {
    blockFilter.value = filter;
    fetchBlocks();
}

// --- Lifecycle ---

onMounted(() => {
    fetchBlocks();
});
</script>

<template>
    <Head
        :title="
            t('technicians.availability.headTitle', { name: technician.name })
        "
    />

    <Toast :toast="toast" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex items-start gap-4">
                <Button variant="ghost" size="icon" as-child class="mt-1">
                    <Link
                        :href="techniciansRoutes.index().url"
                        :title="t('technicians.actions.back')"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <Heading
                        :title="technician.name"
                        :description="`${technician.email || t('technicians.common.noEmail')} · ${technician.phone || t('technicians.common.noPhone')}`"
                    />
                    <div class="mt-2">
                        <Badge
                            :variant="
                                technician.is_active ? 'default' : 'secondary'
                            "
                        >
                            {{
                                technician.is_active
                                    ? t('technicians.status.active')
                                    : t('technicians.status.inactive')
                            }}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner: no availability -->
        <div
            v-if="!hasAvailability"
            class="mt-6 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200"
        >
            <Info class="h-5 w-5 shrink-0" />
            <p>{{ t('technicians.availability.banner.noAvailability') }}</p>
        </div>

        <div class="mt-6 space-y-8">
            <!-- Availability section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        {{ t('technicians.availability.section.title') }}
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
                                            {{
                                                t(
                                                    'technicians.availability.table.day',
                                                )
                                            }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{
                                                t(
                                                    'technicians.availability.table.available',
                                                )
                                            }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{
                                                t(
                                                    'technicians.availability.table.startTime',
                                                )
                                            }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{
                                                t(
                                                    'technicians.availability.table.endTime',
                                                )
                                            }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{
                                                t(
                                                    'technicians.availability.table.cdaSchedule',
                                                )
                                            }}
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
                                            <TooltipProvider
                                                v-if="
                                                    !isCdaWorkingDay(
                                                        day.day_of_week,
                                                    )
                                                "
                                            >
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <span
                                                            class="inline-block"
                                                        >
                                                            <Switch
                                                                :model-value="
                                                                    false
                                                                "
                                                                disabled
                                                            />
                                                        </span>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        {{
                                                            t(
                                                                'technicians.availability.cdaClosed',
                                                            )
                                                        }}
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                            <Switch
                                                v-else
                                                :model-value="day.is_available"
                                                @update:model-value="
                                                    (val: boolean) =>
                                                        toggleAvailable(
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
                                                :disabled="
                                                    !day.is_available ||
                                                    !isCdaWorkingDay(
                                                        day.day_of_week,
                                                    )
                                                "
                                                :min="
                                                    cdaMinTime(day.day_of_week)
                                                "
                                                :max="
                                                    cdaMaxTime(day.day_of_week)
                                                "
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
                                                :disabled="
                                                    !day.is_available ||
                                                    !isCdaWorkingDay(
                                                        day.day_of_week,
                                                    )
                                                "
                                                :min="
                                                    cdaMinTime(day.day_of_week)
                                                "
                                                :max="
                                                    cdaMaxTime(day.day_of_week)
                                                "
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
                                            <Badge
                                                :variant="
                                                    isCdaWorkingDay(
                                                        day.day_of_week,
                                                    )
                                                        ? 'outline'
                                                        : 'secondary'
                                                "
                                            >
                                                {{ cdaLabel(day.day_of_week) }}
                                            </Badge>
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
                                <TooltipProvider
                                    v-if="!isCdaWorkingDay(day.day_of_week)"
                                >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <span class="inline-block">
                                                <Switch
                                                    :model-value="false"
                                                    disabled
                                                />
                                            </span>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{
                                                t(
                                                    'technicians.availability.cdaClosed',
                                                )
                                            }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <Switch
                                    v-else
                                    :model-value="day.is_available"
                                    @update:model-value="
                                        (val: boolean) =>
                                            toggleAvailable(index, val)
                                    "
                                />
                            </div>

                            <div class="mt-2">
                                <Badge
                                    :variant="
                                        isCdaWorkingDay(day.day_of_week)
                                            ? 'outline'
                                            : 'secondary'
                                    "
                                    class="text-xs"
                                >
                                    {{ t('technicians.availability.cdaLabel') }}
                                    {{ cdaLabel(day.day_of_week) }}
                                </Badge>
                            </div>

                            <div
                                v-if="
                                    day.is_available &&
                                    isCdaWorkingDay(day.day_of_week)
                                "
                                class="mt-4 grid grid-cols-2 gap-3"
                            >
                                <div class="grid gap-1.5">
                                    <Label
                                        :for="`m-start-${index}`"
                                        class="text-xs"
                                        >{{
                                            t(
                                                'technicians.availability.table.startTime',
                                            )
                                        }}</Label
                                    >
                                    <Input
                                        :id="`m-start-${index}`"
                                        v-model="day.start_time"
                                        type="time"
                                        :min="cdaMinTime(day.day_of_week)"
                                        :max="cdaMaxTime(day.day_of_week)"
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
                                    <Label
                                        :for="`m-end-${index}`"
                                        class="text-xs"
                                        >{{
                                            t(
                                                'technicians.availability.table.endTime',
                                            )
                                        }}</Label
                                    >
                                    <Input
                                        :id="`m-end-${index}`"
                                        v-model="day.end_time"
                                        type="time"
                                        :min="cdaMinTime(day.day_of_week)"
                                        :max="cdaMaxTime(day.day_of_week)"
                                    />
                                    <InputError
                                        :message="
                                            getFieldError(
                                                `days.${index}.end_time`,
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
                        :label-key="'technicians.availability.actions.save'"
                        :loading-label-key="'technicians.availability.actions.saving'"
                        :loading="saving"
                        :disabled="saving"
                        @click="saveAvailability"
                    />
                </CardFooter>
            </Card>

            <!-- Blocks section -->
            <Card>
                <CardHeader class="flex-row items-center justify-between">
                    <CardTitle class="flex items-center gap-2">
                        <ShieldBan class="h-5 w-5" />
                        {{ t('technicians.blocks.section.title') }}
                    </CardTitle>
                    <AppActionButton
                        :action="Actions.create"
                        :label-key="'technicians.blocks.actions.add'"
                        size="sm"
                        @click="openBlockDialog"
                    />
                </CardHeader>

                <CardContent>
                    <!-- Filter -->
                    <div class="mb-4 flex gap-2">
                        <Button
                            size="sm"
                            :variant="
                                blockFilter === 'future' ? 'default' : 'outline'
                            "
                            @click="changeBlockFilter('future')"
                        >
                            {{ t('technicians.blocks.filters.future') }}
                        </Button>
                        <Button
                            size="sm"
                            :variant="
                                blockFilter === 'past' ? 'default' : 'outline'
                            "
                            @click="changeBlockFilter('past')"
                        >
                            {{ t('technicians.blocks.filters.past') }}
                        </Button>
                        <Button
                            size="sm"
                            :variant="
                                blockFilter === 'all' ? 'default' : 'outline'
                            "
                            @click="changeBlockFilter('all')"
                        >
                            {{ t('technicians.blocks.filters.all') }}
                        </Button>
                    </div>

                    <!-- Loading -->
                    <div
                        v-if="loadingBlocks"
                        class="flex items-center justify-center py-8"
                    >
                        <Spinner class="mr-2" />
                        <span class="text-sm text-muted-foreground">
                            {{ t('technicians.blocks.loading') }}
                        </span>
                    </div>

                    <template v-else>
                        <!-- Desktop table -->
                        <div class="hidden lg:block">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-sm">
                                    <thead class="border-b bg-muted/50">
                                        <tr>
                                            <th class="px-4 py-3 font-medium">
                                                {{
                                                    t(
                                                        'technicians.blocks.table.period',
                                                    )
                                                }}
                                            </th>
                                            <th class="px-4 py-3 font-medium">
                                                {{
                                                    t(
                                                        'technicians.blocks.table.schedule',
                                                    )
                                                }}
                                            </th>
                                            <th class="px-4 py-3 font-medium">
                                                {{
                                                    t(
                                                        'technicians.blocks.table.reason',
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right font-medium"
                                            >
                                                {{
                                                    t(
                                                        'technicians.blocks.table.actions',
                                                    )
                                                }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        <tr
                                            v-for="block in blocksList"
                                            :key="block.id"
                                            class="hover:bg-muted/50"
                                        >
                                            <td class="px-4 py-3">
                                                {{ formatBlockPeriod(block) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <Badge
                                                    :variant="
                                                        block.is_full_day
                                                            ? 'secondary'
                                                            : 'outline'
                                                    "
                                                >
                                                    {{
                                                        formatBlockSchedule(
                                                            block,
                                                        )
                                                    }}
                                                </Badge>
                                            </td>
                                            <td
                                                class="px-4 py-3 text-muted-foreground"
                                            >
                                                {{
                                                    block.reason ||
                                                    t(
                                                        'technicians.blocks.emptyReason',
                                                    )
                                                }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <AppActionIconButton
                                                    :action="Actions.delete"
                                                    :label-key="'technicians.blocks.actions.delete'"
                                                    @click="
                                                        openDeleteBlockDialog(
                                                            block,
                                                        )
                                                    "
                                                />
                                            </td>
                                        </tr>
                                        <tr v-if="blocksList.length === 0">
                                            <td
                                                colspan="4"
                                                class="px-4 py-8 text-center text-sm text-muted-foreground"
                                            >
                                                {{
                                                    t(
                                                        'technicians.blocks.empty',
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile cards -->
                        <div class="grid grid-cols-1 gap-3 lg:hidden">
                            <Card
                                v-for="block in blocksList"
                                :key="block.id"
                                class="p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold">
                                            {{ formatBlockPeriod(block) }}
                                        </p>
                                        <Badge
                                            :variant="
                                                block.is_full_day
                                                    ? 'secondary'
                                                    : 'outline'
                                            "
                                            class="mt-1 text-xs"
                                        >
                                            {{ formatBlockSchedule(block) }}
                                        </Badge>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            {{
                                                block.reason ||
                                                t(
                                                    'technicians.blocks.emptyReasonText',
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <AppActionIconButton
                                        :action="Actions.delete"
                                        :label-key="'technicians.blocks.actions.delete'"
                                        @click="openDeleteBlockDialog(block)"
                                    />
                                </div>
                            </Card>

                            <Card v-if="blocksList.length === 0" class="p-6">
                                <p
                                    class="text-center text-sm text-muted-foreground"
                                >
                                    {{ t('technicians.blocks.empty') }}
                                </p>
                            </Card>
                        </div>
                    </template>
                </CardContent>
            </Card>
        </div>

        <!-- Block create dialog -->
        <Dialog v-model:open="showBlockDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ t('technicians.blocks.dialogs.create.title') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ t('technicians.blocks.dialogs.create.description') }}
                    </DialogDescription>
                </DialogHeader>

                <!-- Conflict alert -->
                <Alert v-if="blockConflicts.length > 0" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertTitle>
                        {{
                            t('technicians.blocks.conflicts.title', {
                                count: blockConflicts.length,
                            })
                        }}
                    </AlertTitle>
                    <AlertDescription>
                        <ul class="mt-2 list-inside list-disc text-sm">
                            <li
                                v-for="conflict in blockConflicts"
                                :key="conflict.id"
                            >
                                {{
                                    t('technicians.blocks.conflicts.item', {
                                        date: formatDate(conflict.date),
                                        time: conflict.time,
                                    })
                                }}
                            </li>
                        </ul>
                        <p class="mt-2 text-sm font-medium">
                            {{ t('technicians.blocks.conflicts.hint') }}
                        </p>
                    </AlertDescription>
                </Alert>

                <!-- Overlap alert -->
                <Alert v-if="blockOverlap" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertTitle>
                        {{ t('technicians.blocks.overlap.title') }}
                    </AlertTitle>
                    <AlertDescription>
                        <p class="mt-1 text-sm">
                            {{
                                blockOverlap.reason
                                    ? `"${blockOverlap.reason}"`
                                    : t('technicians.blocks.emptyReasonText')
                            }}
                            ({{ formatDate(blockOverlap.start_date) }}
                            {{
                                blockOverlap.start_date !==
                                blockOverlap.end_date
                                    ? `– ${formatDate(blockOverlap.end_date)}`
                                    : ''
                            }})
                        </p>
                    </AlertDescription>
                </Alert>

                <form @submit.prevent="saveBlock" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="block-start-date">
                                {{ t('technicians.blocks.form.startDate') }}
                            </Label>
                            <Input
                                id="block-start-date"
                                v-model="blockForm.start_date"
                                type="date"
                                :min="todayString()"
                            />
                            <InputError
                                :message="getBlockFieldError('start_date')"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="block-end-date">
                                {{ t('technicians.blocks.form.endDate') }}
                            </Label>
                            <Input
                                id="block-end-date"
                                v-model="blockForm.end_date"
                                type="date"
                                :min="blockForm.start_date || todayString()"
                            />
                            <InputError
                                :message="getBlockFieldError('end_date')"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Switch
                            :model-value="blockForm.is_full_day"
                            @update:model-value="
                                (val: boolean) => (blockForm.is_full_day = val)
                            "
                        />
                        <Label>{{
                            t('technicians.blocks.form.fullDay')
                        }}</Label>
                    </div>

                    <div
                        v-if="!blockForm.is_full_day"
                        class="grid grid-cols-2 gap-4"
                    >
                        <div class="grid gap-2">
                            <Label for="block-start-time">
                                {{ t('technicians.blocks.form.startTime') }}
                            </Label>
                            <Input
                                id="block-start-time"
                                v-model="blockForm.start_time"
                                type="time"
                            />
                            <InputError
                                :message="getBlockFieldError('start_time')"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="block-end-time">
                                {{ t('technicians.blocks.form.endTime') }}
                            </Label>
                            <Input
                                id="block-end-time"
                                v-model="blockForm.end_time"
                                type="time"
                            />
                            <InputError
                                :message="getBlockFieldError('end_time')"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="block-reason">
                            {{ t('technicians.blocks.form.reasonLabel') }}
                        </Label>
                        <Input
                            id="block-reason"
                            v-model="blockForm.reason"
                            type="text"
                            :placeholder="
                                t('technicians.blocks.form.reasonPlaceholder')
                            "
                            maxlength="255"
                        />
                        <InputError :message="getBlockFieldError('reason')" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button
                                variant="secondary"
                                type="button"
                                :disabled="savingBlock"
                            >
                                {{ t('technicians.blocks.actions.cancel') }}
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="savingBlock">
                            <Spinner v-if="savingBlock" class="mr-2" />
                            {{
                                savingBlock
                                    ? t('technicians.blocks.actions.saving')
                                    : t('technicians.blocks.actions.save')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete block confirmation dialog -->
        <Dialog v-model:open="showDeleteBlockDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ t('technicians.blocks.delete.title') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            t('technicians.blocks.delete.description', {
                                period: blockToDelete
                                    ? formatBlockPeriod(blockToDelete)
                                    : '',
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            variant="secondary"
                            :disabled="deletingBlockId !== null"
                        >
                            {{ t('technicians.blocks.delete.cancel') }}
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        @click="confirmDeleteBlock"
                        :disabled="deletingBlockId !== null"
                    >
                        <Spinner v-if="deletingBlockId !== null" class="mr-2" />
                        {{
                            deletingBlockId !== null
                                ? t('technicians.blocks.delete.deleting')
                                : t('technicians.blocks.delete.confirm')
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
