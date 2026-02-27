<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    CalendarDays,
    Languages,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import Toast from '@/components/Toast.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useToast } from '@/composables/useToast';
import { i18nKeyMode } from '@/i18n';

type TechnicianOption = {
    id: number;
    name: string;
};

type AvailabilitySlot = {
    start: string;
    end: string;
    isAvailable: boolean;
};

type AvailabilityDay = {
    date: string;
    slots: AvailabilitySlot[];
};

const props = defineProps<{
    isConfigured: boolean;
    hasTechnicians: boolean;
    technicians: TechnicianOption[];
}>();

const { t } = useI18n();
const { toast, info, error: errorToast } = useToast();
const isKeyModeEnabled = computed(() => i18nKeyMode.isEnabled.value);

const viewMonth = ref(new Date());
const selectedDateKey = ref<string | null>(null);
const selectedSlotStart = ref<string | null>(null);
const selectedTechnicianId = ref<number | null>(null);

const loadingCalendar = ref(true);
const availabilityByDate = ref<Record<string, AvailabilitySlot[]>>({});
let loadAvailabilityController: AbortController | null = null;

const monthLabel = computed(() =>
    new Intl.DateTimeFormat('es-CO', {
        month: 'long',
        year: 'numeric',
    }).format(viewMonth.value),
);

const monthRange = computed(() => {
    const from = new Date(
        viewMonth.value.getFullYear(),
        viewMonth.value.getMonth(),
        1,
    );
    const to = new Date(
        viewMonth.value.getFullYear(),
        viewMonth.value.getMonth() + 1,
        0,
    );

    return { from, to };
});

const calendarDays = computed(() => {
    const from = monthRange.value.from;
    const to = monthRange.value.to;

    const firstWeekday = from.getDay();
    const daysInMonth = to.getDate();
    const cells: Date[] = [];

    for (let index = firstWeekday; index > 0; index -= 1) {
        cells.push(new Date(from.getFullYear(), from.getMonth(), 1 - index));
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        cells.push(new Date(from.getFullYear(), from.getMonth(), day));
    }

    const remainder = (7 - (cells.length % 7)) % 7;
    for (let index = 1; index <= remainder; index += 1) {
        cells.push(
            new Date(to.getFullYear(), to.getMonth(), to.getDate() + index),
        );
    }

    return cells;
});

const selectedDateLabel = computed(() => {
    if (!selectedDateKey.value) {
        return '';
    }

    const [year, month, day] = selectedDateKey.value.split('-').map(Number);
    return new Intl.DateTimeFormat('es-CO', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(year, (month ?? 1) - 1, day ?? 1));
});

const selectedSlots = computed<AvailabilitySlot[]>(
    () =>
        (selectedDateKey.value
            ? availabilityByDate.value[selectedDateKey.value]
            : undefined) ?? [],
);

const selectedAvailableCount = computed(
    () => selectedSlots.value.filter((slot) => slot.isAvailable).length,
);

const weekdays = computed(() => [
    t('publicAvailability.weekdays.sun'),
    t('publicAvailability.weekdays.mon'),
    t('publicAvailability.weekdays.tue'),
    t('publicAvailability.weekdays.wed'),
    t('publicAvailability.weekdays.thu'),
    t('publicAvailability.weekdays.fri'),
    t('publicAvailability.weekdays.sat'),
]);

function toYmd(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function isPastDate(date: Date): boolean {
    const today = new Date();
    const current = new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
    );
    const currentDay = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate(),
    );

    return current < currentDay;
}

function isCurrentMonth(date: Date): boolean {
    return (
        date.getMonth() === viewMonth.value.getMonth() &&
        date.getFullYear() === viewMonth.value.getFullYear()
    );
}

function availabilitySummary(dayKey: string): {
    total: number;
    available: number;
    ratio: number;
} {
    const slots = availabilityByDate.value[dayKey] ?? [];
    const total = slots.length;
    const available = slots.filter((slot) => slot.isAvailable).length;
    const ratio = total === 0 ? 0 : available / total;

    return { total, available, ratio };
}

function availabilityClass(date: Date): string {
    const summary = availabilitySummary(toYmd(date));

    if (summary.total === 0 || summary.available === 0) {
        return 'border-slate-300 bg-slate-100 text-slate-700';
    }

    if (summary.ratio < 0.2) {
        return 'border-amber-300 bg-amber-100 text-amber-800';
    }

    return 'border-emerald-300 bg-emerald-100 text-emerald-800';
}

function availabilityLabel(date: Date): string {
    const summary = availabilitySummary(toYmd(date));

    if (summary.total === 0 || summary.available === 0) {
        return t('publicAvailability.availabilityLabels.unavailable');
    }

    if (summary.ratio < 0.2) {
        return t('publicAvailability.availabilityLabels.low');
    }

    return t('publicAvailability.availabilityLabels.available');
}

function timeLabel(iso: string): string {
    const date = new Date(iso);
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');

    return `${hh}:${mm}`;
}

function previousMonth(): void {
    viewMonth.value = new Date(
        viewMonth.value.getFullYear(),
        viewMonth.value.getMonth() - 1,
        1,
    );
}

function nextMonth(): void {
    viewMonth.value = new Date(
        viewMonth.value.getFullYear(),
        viewMonth.value.getMonth() + 1,
        1,
    );
}

function pickDate(date: Date): void {
    if (!isCurrentMonth(date) || isPastDate(date)) {
        return;
    }

    selectedDateKey.value = toYmd(date);
}

function selectSlot(slot: AvailabilitySlot): void {
    if (!slot.isAvailable) {
        return;
    }

    selectedSlotStart.value = slot.start;
    info(t('publicAvailability.messages.slotSelected'));
}

function toggleKeyMode() {
    i18nKeyMode.toggle();
}

function ensureSelectedDate(): void {
    if (!selectedDateKey.value) {
        const today = new Date();

        if (
            today.getMonth() === viewMonth.value.getMonth() &&
            today.getFullYear() === viewMonth.value.getFullYear() &&
            !isPastDate(today)
        ) {
            selectedDateKey.value = toYmd(today);
            return;
        }
    }

    if (
        selectedDateKey.value &&
        availabilityByDate.value[selectedDateKey.value]
    ) {
        return;
    }

    const firstCurrentMonthDay = calendarDays.value.find((day) =>
        isCurrentMonth(day),
    );
    selectedDateKey.value = firstCurrentMonthDay
        ? toYmd(firstCurrentMonthDay)
        : null;
}

async function loadMonthAvailability(): Promise<void> {
    loadAvailabilityController?.abort();
    const requestController = new AbortController();
    loadAvailabilityController = requestController;
    loadingCalendar.value = true;

    const params = new URLSearchParams({
        from: toYmd(monthRange.value.from),
        to: toYmd(monthRange.value.to),
    });

    if (selectedTechnicianId.value !== null) {
        params.set('technicianId', String(selectedTechnicianId.value));
    }

    try {
        const response = await fetch(
            `/api/v1/public/appointments/availability?${params.toString()}`,
            {
                headers: { Accept: 'application/json' },
                signal: requestController.signal,
            },
        );

        if (!response.ok) {
            availabilityByDate.value = {};
            errorToast(t('publicAvailability.errors.loadMonth'));
            return;
        }

        const payload = await response.json();
        const days: AvailabilityDay[] = payload.data ?? [];
        availabilityByDate.value = Object.fromEntries(
            days.map((day) => [day.date, day.slots]),
        );
        ensureSelectedDate();
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }

        availabilityByDate.value = {};
        errorToast(t('publicAvailability.errors.connection'));
    } finally {
        if (loadAvailabilityController === requestController) {
            loadingCalendar.value = false;
        }
    }
}

watch([viewMonth, selectedTechnicianId], async () => {
    selectedSlotStart.value = null;
    await loadMonthAvailability();
});

onMounted(async () => {
    await loadMonthAvailability();
});
</script>

<template>
    <Head :title="t('publicAvailability.headTitle')" />

    <div class="min-h-screen bg-muted/20 px-4 py-8 sm:px-6 lg:px-8">
        <Toast :toast="toast" />

        <div class="mx-auto w-full max-w-6xl">
            <div class="flex items-start justify-between gap-3">
                <Heading
                    :title="t('publicAvailability.heading.title')"
                    :description="t('publicAvailability.heading.description')"
                />

                <TooltipProvider :delay-duration="0">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="group h-9 w-9 cursor-pointer"
                                :class="isKeyModeEnabled ? 'bg-muted' : ''"
                                @click="toggleKeyMode"
                            >
                                <span class="sr-only"
                                    >Toggle i18n key mode</span
                                >
                                <Languages
                                    class="size-5 opacity-80 group-hover:opacity-100"
                                />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>
                                {{
                                    isKeyModeEnabled
                                        ? t(
                                              'publicAvailability.i18n.showTranslations',
                                          )
                                        : t('publicAvailability.i18n.showKeys')
                                }}
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>

            <Card v-if="!props.isConfigured" class="mt-6">
                <CardHeader>
                    <CardTitle class="text-base">{{
                        t('publicAvailability.empty.notConfigured.title')
                    }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <p>
                        {{
                            t(
                                'publicAvailability.empty.notConfigured.description',
                            )
                        }}
                    </p>
                    <a class="font-medium underline" href="/schedule">
                        {{ t('publicAvailability.empty.notConfigured.cta') }}
                    </a>
                </CardContent>
            </Card>

            <Card v-else-if="!props.hasTechnicians" class="mt-6">
                <CardHeader>
                    <CardTitle class="text-base">{{
                        t('publicAvailability.empty.noTechnicians.title')
                    }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <p>
                        {{
                            t(
                                'publicAvailability.empty.noTechnicians.description',
                            )
                        }}
                    </p>
                    <a class="font-medium underline" href="/technicians">
                        {{ t('publicAvailability.empty.noTechnicians.cta') }}
                    </a>
                </CardContent>
            </Card>

            <div v-else class="mt-6 grid gap-6 lg:grid-cols-[2fr_1fr]">
                <Card>
                    <CardHeader class="space-y-4">
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <CardTitle
                                class="flex items-center gap-2 text-base"
                            >
                                <CalendarDays class="h-4 w-4" />
                                {{ monthLabel }}
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="previousMonth"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                    <span class="sr-only">{{
                                        t(
                                            'publicAvailability.controls.previousMonth',
                                        )
                                    }}</span>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="nextMonth"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                    <span class="sr-only">{{
                                        t(
                                            'publicAvailability.controls.nextMonth',
                                        )
                                    }}</span>
                                </Button>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="technicianFilter">{{
                                t('publicAvailability.filters.technician')
                            }}</Label>
                            <Select v-model="selectedTechnicianId">
                                <SelectTrigger id="technicianFilter">
                                    <SelectValue
                                        :placeholder="
                                            t(
                                                'publicAvailability.filters.allTechnicians',
                                            )
                                        "
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">{{
                                        t(
                                            'publicAvailability.filters.allTechnicians',
                                        )
                                    }}</SelectItem>
                                    <SelectItem
                                        v-for="technician in props.technicians"
                                        :key="technician.id"
                                        :value="technician.id"
                                    >
                                        {{ technician.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-3">
                        <template v-if="loadingCalendar">
                            <div class="grid grid-cols-7 gap-2">
                                <Skeleton
                                    v-for="index in 35"
                                    :key="index"
                                    class="h-20 w-full"
                                />
                            </div>
                        </template>

                        <template v-else>
                            <div
                                class="grid grid-cols-7 gap-2 text-xs font-medium"
                            >
                                <span
                                    v-for="weekday in weekdays"
                                    :key="weekday"
                                    class="px-2 text-center text-muted-foreground"
                                >
                                    {{ weekday }}
                                </span>
                            </div>

                            <div class="grid grid-cols-7 gap-2">
                                <button
                                    v-for="day in calendarDays"
                                    :key="toYmd(day)"
                                    type="button"
                                    class="rounded-md border p-2 text-left text-sm transition"
                                    :class="[
                                        isCurrentMonth(day)
                                            ? 'hover:shadow-sm'
                                            : 'opacity-50',
                                        isPastDate(day)
                                            ? 'cursor-not-allowed bg-muted'
                                            : availabilityClass(day),
                                        selectedDateKey === toYmd(day)
                                            ? 'ring-2 ring-primary'
                                            : '',
                                    ]"
                                    :disabled="
                                        !isCurrentMonth(day) || isPastDate(day)
                                    "
                                    @click="pickDate(day)"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="font-medium">
                                            {{ day.getDate() }}
                                        </span>
                                        <Badge
                                            variant="outline"
                                            class="text-[10px]"
                                        >
                                            {{
                                                availabilitySummary(toYmd(day))
                                                    .available
                                            }}
                                        </Badge>
                                    </div>
                                    <p class="mt-2 text-[11px] leading-4">
                                        {{ availabilityLabel(day) }}
                                    </p>
                                </button>
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Franjas</CardTitle>
                        <p
                            v-if="selectedDateKey"
                            class="text-sm text-muted-foreground"
                        >
                            {{
                                t('publicAvailability.slots.summary', {
                                    date: selectedDateLabel,
                                    count: selectedAvailableCount,
                                })
                            }}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <template v-if="loadingCalendar">
                            <div class="flex justify-center py-6">
                                <Spinner class="h-5 w-5" />
                            </div>
                        </template>

                        <template v-else-if="!selectedDateKey">
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t('publicAvailability.slots.selectDateHint')
                                }}
                            </p>
                        </template>

                        <template v-else-if="selectedSlots.length === 0">
                            <p class="text-sm text-muted-foreground">
                                {{ t('publicAvailability.slots.noSlots') }}
                            </p>
                        </template>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="slot in selectedSlots"
                                :key="slot.start"
                                class="rounded-md border p-2"
                                :class="
                                    selectedSlotStart === slot.start
                                        ? 'border-primary ring-1 ring-primary'
                                        : ''
                                "
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <div class="text-sm">
                                        <span class="font-medium">
                                            {{ timeLabel(slot.start) }}
                                        </span>
                                        -
                                        <span class="font-medium">
                                            {{ timeLabel(slot.end) }}
                                        </span>
                                    </div>
                                    <Button
                                        size="sm"
                                        :variant="
                                            selectedSlotStart === slot.start
                                                ? 'secondary'
                                                : 'outline'
                                        "
                                        :disabled="!slot.isAvailable"
                                        @click="selectSlot(slot)"
                                    >
                                        {{
                                            slot.isAvailable
                                                ? selectedSlotStart ===
                                                  slot.start
                                                    ? t(
                                                          'publicAvailability.slots.selected',
                                                      )
                                                    : t(
                                                          'publicAvailability.slots.select',
                                                      )
                                                : t(
                                                      'publicAvailability.slots.unavailable',
                                                  )
                                        }}
                                    </Button>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
