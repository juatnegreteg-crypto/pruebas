<script setup lang="ts">
import { CalendarPlus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { CatalogItemTaxForm } from '@/components/CatalogItemTaxesForm.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type TaxOption = {
    id: number;
    name: string;
    code: string;
    jurisdiction: string;
    rate: number;
};

const props = defineProps<{
    value: CatalogItemTaxForm;
    index: number;
    taxes: TaxOption[];
    errors?: Record<string, string>;
    farFutureDate: string;
    showRangeSummary: boolean;
    isChild: boolean;
    rangeItems: Array<{
        index: number;
        startAt: string;
        endAt: string;
    }>;
}>();

const emit = defineEmits<{
    (e: 'select-tax', index: number, value: string | null): void;
    (
        e: 'update-range',
        payload: {
            index: number;
            startAt: string;
            endAt: string;
        },
    ): void;
    (e: 'activate-range', index: number): void;
    (e: 'remove', index: number): void;
    (e: 'add', index: number, startAt: string, endAt: string): void;
    (e: 'update-rate', index: number, value: string): void;
}>();

const { t, locale } = useI18n();
const isPopoverOpen = ref(false);
const activeRangeIndex = ref<number | null>(null);
const draftRange = ref<{
    index: number | null;
    startAt: string;
    endAt: string;
    mode: 'create' | 'edit';
}>({
    index: null,
    startAt: '',
    endAt: '',
    mode: 'edit',
});

const activeRange = computed(() => {
    if (activeRangeIndex.value === null) {
        return props.value;
    }
    if (activeRangeIndex.value === props.index) {
        return props.value;
    }
    return (
        props.rangeItems.find(
            (item) => item.index === activeRangeIndex.value,
        ) ?? props.value
    );
});

const lastRangeEnd = computed(() => {
    if (props.rangeItems.length === 0) {
        return props.value.endAt;
    }
    return props.rangeItems[props.rangeItems.length - 1].endAt;
});

const canCreateNewRange = computed(
    () => lastRangeEnd.value !== props.farFutureDate,
);

const minStartDateForNew = computed(() => {
    if (!canCreateNewRange.value) {
        return '';
    }
    return lastRangeEnd.value;
});

const isIndefinite = computed(
    () => draftRange.value.endAt === props.farFutureDate,
);
const endDateValue = computed(() =>
    isIndefinite.value ? '' : draftRange.value.endAt,
);

watch(isPopoverOpen, (open) => {
    if (!open) {
        draftRange.value = {
            index: null,
            startAt: '',
            endAt: '',
            mode: 'edit',
        };
        activeRangeIndex.value = null;
    }
});

function errorFor(field: string): string | undefined {
    return props.errors?.[
        `taxes.${draftRange.value.index ?? props.index}.${field}`
    ];
}

function formatDate(dateStr: string): string {
    const datePart = dateStr.substring(0, 10);
    if (!datePart) {
        return '';
    }
    const date = new Date(`${datePart}T12:00:00`);
    const formatter = new Intl.DateTimeFormat(locale.value || 'es-CO', {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
    });

    return formatter.format(date);
}

function rangeLabel(range: { startAt: string; endAt: string }): string {
    const start = formatDate(range.startAt);
    const end = range.endAt;
    const isIndefiniteRange = end === props.farFutureDate;

    if (isIndefiniteRange && range.startAt === props.value.startAt) {
        return t('catalogItemTaxes.range.todayOnwards');
    }

    if (isIndefiniteRange) {
        return t('catalogItemTaxes.range.fromOnwards', { date: start });
    }

    return t('catalogItemTaxes.range.fromTo', {
        start,
        end: formatDate(end),
    });
}

function openRangeEditor(targetIndex: number): void {
    activeRangeIndex.value = targetIndex;
    draftRange.value = {
        index: targetIndex,
        startAt: activeRange.value.startAt,
        endAt: activeRange.value.endAt,
        mode: 'edit',
    };
    isPopoverOpen.value = true;
}

function openRangeCreator(): void {
    if (!canCreateNewRange.value) {
        return;
    }
    draftRange.value = {
        index: null,
        startAt: minStartDateForNew.value,
        endAt: props.farFutureDate,
        mode: 'create',
    };
    isPopoverOpen.value = true;
}

function updateStartAt(value: string | number): void {
    draftRange.value = {
        ...draftRange.value,
        startAt: String(value),
    };
}

function updateEndAt(value: string | number): void {
    draftRange.value = {
        ...draftRange.value,
        endAt: String(value),
    };
}

function setIndefinite(): void {
    draftRange.value = {
        ...draftRange.value,
        endAt: props.farFutureDate,
    };
}

function saveRange(): void {
    if (draftRange.value.mode === 'create') {
        if (
            minStartDateForNew.value &&
            draftRange.value.startAt < minStartDateForNew.value
        ) {
            return;
        }
        emit(
            'add',
            props.index,
            draftRange.value.startAt,
            draftRange.value.endAt,
        );
        isPopoverOpen.value = false;
        return;
    }

    if (draftRange.value.index === null) {
        return;
    }

    emit('activate-range', draftRange.value.index);
    emit('update-range', {
        index: draftRange.value.index,
        startAt: draftRange.value.startAt,
        endAt: draftRange.value.endAt,
    });
    isPopoverOpen.value = false;
}

const rangeSegments = computed(() => {
    const segments: Array<{ index: number; label: string }> = [];

    if (
        props.value.taxId &&
        (props.showRangeSummary || props.rangeItems.length === 0)
    ) {
        segments.push({
            index: props.index,
            label: rangeLabel(props.value),
        });
    }

    props.rangeItems.forEach((item) => {
        segments.push({
            index: item.index,
            label: rangeLabel(item),
        });
    });

    return segments;
});
</script>

<template>
    <div class="space-y-1">
        <div class="grid gap-1">
            <div
                :class="[
                    'flex flex-wrap items-start gap-2',
                    isChild ? 'pl-6' : null,
                ]"
            >
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <InputGroup v-if="!isChild" class="flex-1">
                        <Select
                            :model-value="value.taxId ?? undefined"
                            @update:model-value="
                                (val) => emit('select-tax', index, val ?? null)
                            "
                            class="w-12"
                        >
                            <SelectTrigger
                                :id="`tax-select-${index}`"
                                data-slot="input-group-control"
                                :aria-invalid="!!errorFor('taxId')"
                                :aria-label="t('catalogItemTaxes.fields.tax')"
                                class="min-w-0 flex-1 rounded-none border-0 bg-transparent shadow-none focus-visible:ring-0"
                            >
                                <SelectValue
                                    :placeholder="
                                        t('catalogItemTaxes.placeholders.tax')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in taxes"
                                    :key="option.id"
                                    :value="String(option.id)"
                                >
                                    {{ option.name }} · {{ option.code }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputGroupInput
                            :model-value="value.rate"
                            type="number"
                            min="0"
                            step="0.01"
                            data-align="inline-end"
                            :aria-label="t('catalogItemTaxes.fields.rate')"
                            :placeholder="
                                t('catalogItemTaxes.placeholders.rate')
                            "
                            class="shrink-0 border-l border-input text-right"
                            @update:model-value="
                                (val) =>
                                    emit(
                                        'update-rate',
                                        index,
                                        String(val ?? ''),
                                    )
                            "
                        />
                        <InputGroupAddon class="border-l border-input p-0">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-9 w-9 rounded-none"
                                :aria-label="
                                    t('catalogItemTaxes.actions.removeTax')
                                "
                                @click="emit('remove', index)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </InputGroupAddon>
                    </InputGroup>
                </div>

                <div class="flex items-center gap-2">
                    <Popover v-model:open="isPopoverOpen">
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                :disabled="!value.taxId || !canCreateNewRange"
                                :aria-label="
                                    t('catalogItemTaxes.actions.setRange')
                                "
                                @click="openRangeCreator"
                            >
                                <CalendarPlus class="h-4 w-4" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-72 space-y-3 p-3">
                            <div class="grid gap-2">
                                <label
                                    :for="`tax-start-${index}`"
                                    class="text-sm font-medium"
                                >
                                    {{ t('catalogItemTaxes.fields.startAt') }}
                                </label>
                                <Input
                                    :id="`tax-start-${index}`"
                                    :model-value="draftRange.startAt"
                                    type="date"
                                    :min="
                                        draftRange.mode === 'create'
                                            ? minStartDateForNew
                                            : undefined
                                    "
                                    :aria-invalid="!!errorFor('startAt')"
                                    @update:model-value="
                                        (val) => updateStartAt(val ?? '')
                                    "
                                />
                                <InputError :message="errorFor('startAt')" />
                            </div>

                            <div class="grid gap-2">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <label
                                        :for="`tax-end-${index}`"
                                        class="text-sm font-medium"
                                    >
                                        {{ t('catalogItemTaxes.fields.endAt') }}
                                    </label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="setIndefinite"
                                    >
                                        {{
                                            t(
                                                'catalogItemTaxes.actions.clearEnd',
                                            )
                                        }}
                                    </Button>
                                </div>
                                <Input
                                    :id="`tax-end-${index}`"
                                    :model-value="endDateValue"
                                    type="date"
                                    :min="draftRange.startAt || undefined"
                                    :aria-invalid="!!errorFor('endAt')"
                                    @update:model-value="
                                        (val) => updateEndAt(val ?? '')
                                    "
                                />
                                <InputError :message="errorFor('endAt')" />
                            </div>

                            <Button
                                type="button"
                                class="w-full"
                                :disabled="!draftRange.startAt"
                                @click="saveRange"
                            >
                                {{ t('catalogItemTaxes.actions.saveRange') }}
                            </Button>
                        </PopoverContent>
                    </Popover>

                    <Button
                        v-if="isChild"
                        type="button"
                        variant="ghost"
                        size="icon"
                        :aria-label="t('catalogItemTaxes.actions.removeTax')"
                        @click="emit('remove', index)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <InputError :message="errorFor('taxId')" />
            <InputError v-if="errorFor('rate')" :message="errorFor('rate')" />
        </div>

        <div
            v-if="rangeSegments.length"
            class="grid grid-cols-1 gap-2 text-xs text-muted-foreground sm:grid-cols-3"
        >
            <template v-for="segment in rangeSegments" :key="segment.index">
                <button
                    type="button"
                    class="group inline-flex w-full items-center justify-between gap-2 rounded-md border border-border/60 px-2 py-1 hover:border-border"
                    @click="openRangeEditor(segment.index)"
                >
                    <span class="truncate text-left">
                        {{ segment.label }}
                    </span>
                    <span
                        class="rounded-sm p-0.5 text-muted-foreground transition group-hover:text-foreground"
                        @click.stop="emit('remove', segment.index)"
                    >
                        <Trash2 class="h-3 w-3" />
                    </span>
                </button>
            </template>
        </div>
    </div>
</template>
