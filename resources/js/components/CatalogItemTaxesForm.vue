<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CatalogItemTaxesFormItem from '@/components/CatalogItemTaxesFormItem.vue';
import { Button } from '@/components/ui/button';

export type CatalogItemTaxForm = {
    taxId: number | null;
    rate: string;
    startAt: string;
    endAt: string;
};

type TaxOption = {
    id: number;
    name: string;
    code: string;
    jurisdiction: string;
    rate: number;
};

const props = defineProps<{
    modelValue: CatalogItemTaxForm[];
    taxes: TaxOption[];
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: CatalogItemTaxForm[]): void;
}>();

const hasTaxes = computed(() => props.modelValue.length > 0);
const hasAvailableTaxes = computed(() => props.taxes.length > 0);
const { t } = useI18n();
const FAR_FUTURE_DATE = '2099-12-31';
const rangeActiveByIndex = ref<Record<number, boolean>>({});

const taxesById = computed(() => {
    const map = new Map<number, TaxOption>();
    props.taxes.forEach((tax) => {
        map.set(tax.id, tax);
    });
    return map;
});

const visibleTaxItems = computed(() =>
    props.modelValue
        .map((tax, index) => ({ tax, index }))
        .filter((item) => !isChildRow(item.index)),
);

const selectedTaxIds = computed(() => {
    return new Set(
        props.modelValue
            .map((tax) => tax.taxId)
            .filter((taxId): taxId is number => taxId !== null),
    );
});

function availableTaxesFor(index: number): TaxOption[] {
    const currentId = props.modelValue[index]?.taxId ?? null;
    return props.taxes.filter((tax) => {
        if (currentId !== null && tax.id === currentId) {
            return true;
        }

        return !selectedTaxIds.value.has(tax.id);
    });
}

function formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function defaultStartDate(): string {
    return formatDate(new Date());
}

function buildTaxFormEntry(
    taxId: number | null = null,
    rate = '',
): CatalogItemTaxForm {
    return {
        taxId,
        rate,
        startAt: defaultStartDate(),
        endAt: FAR_FUTURE_DATE,
    };
}

function addTax(): void {
    emit('update:modelValue', [...props.modelValue, buildTaxFormEntry()]);
}

function removeTaxAt(index: number): void {
    const next = props.modelValue.filter((_, i) => i !== index);
    const nextRangeState: Record<number, boolean> = {};
    next.forEach((_, nextIndex) => {
        const previousIndex = nextIndex >= index ? nextIndex + 1 : nextIndex;
        if (rangeActiveByIndex.value[previousIndex]) {
            nextRangeState[nextIndex] = true;
        }
    });
    rangeActiveByIndex.value = nextRangeState;

    emit('update:modelValue', next);
}

function removeTaxGroup(index: number): void {
    const target = props.modelValue[index];
    if (!target) {
        return;
    }

    const targetId = target.taxId;
    let endIndex = index;
    if (targetId !== null) {
        for (let i = index + 1; i < props.modelValue.length; i += 1) {
            if (props.modelValue[i]?.taxId !== targetId) {
                break;
            }
            endIndex = i;
        }
    }

    const next = props.modelValue.filter((_, i) => i < index || i > endIndex);
    const nextRangeState: Record<number, boolean> = {};
    next.forEach((_, nextIndex) => {
        const originalIndex =
            nextIndex < index ? nextIndex : nextIndex + (endIndex - index + 1);
        if (rangeActiveByIndex.value[originalIndex]) {
            nextRangeState[nextIndex] = true;
        }
    });
    rangeActiveByIndex.value = nextRangeState;

    emit('update:modelValue', next);
}

function activateRange(index: number): void {
    rangeActiveByIndex.value = {
        ...rangeActiveByIndex.value,
        [index]: true,
    };
}

function selectTax(index: number, value: string | null): void {
    const previousTaxId = props.modelValue[index]?.taxId ?? null;
    const taxId = value ? Number(value) : null;
    const selectedTax = taxId ? taxesById.value.get(taxId) : null;
    const next = props.modelValue.map((tax, i) => {
        if (i !== index) {
            return tax;
        }

        return {
            ...tax,
            taxId,
            rate:
                selectedTax && Number.isFinite(selectedTax.rate)
                    ? String(selectedTax.rate)
                    : '',
        };
    });

    if (previousTaxId !== null && taxId !== null) {
        for (let i = index + 1; i < next.length; i += 1) {
            if (next[i]?.taxId !== previousTaxId) {
                break;
            }
            next[i] = {
                ...next[i],
                taxId,
                rate:
                    selectedTax && Number.isFinite(selectedTax.rate)
                        ? String(selectedTax.rate)
                        : '',
            };
        }
    }

    emit('update:modelValue', next);
}

function updateTaxRange(index: number, startAt: string, endAt: string): void {
    const next = props.modelValue.map((tax, i) => {
        if (i !== index) {
            return tax;
        }

        return {
            ...tax,
            startAt,
            endAt,
        };
    });

    emit('update:modelValue', next);
}

function updateTaxRate(index: number, rate: string): void {
    const next = props.modelValue.map((tax, i) => {
        if (i !== index) {
            return tax;
        }

        return {
            ...tax,
            rate,
        };
    });

    emit('update:modelValue', next);
}

function isChildRow(index: number): boolean {
    if (index === 0) {
        return false;
    }
    const currentTaxId = props.modelValue[index]?.taxId ?? null;
    const previousTaxId = props.modelValue[index - 1]?.taxId ?? null;
    return currentTaxId !== null && currentTaxId === previousTaxId;
}

function addRangeAfter(index: number, startAt: string, endAt: string): void {
    const current = props.modelValue[index];
    if (!current?.taxId) {
        return;
    }

    let insertIndex = index + 1;
    for (let i = index + 1; i < props.modelValue.length; i += 1) {
        if (props.modelValue[i]?.taxId !== current.taxId) {
            break;
        }
        insertIndex = i + 1;
    }

    const next = [...props.modelValue];
    next.splice(insertIndex, 0, {
        taxId: current.taxId,
        rate: current.rate,
        startAt,
        endAt,
    });
    emit('update:modelValue', next);
}

function rangeItemsFor(index: number): Array<{
    index: number;
    startAt: string;
    endAt: string;
}> {
    const base = props.modelValue[index];
    if (!base?.taxId) {
        return [];
    }

    const items: Array<{ index: number; startAt: string; endAt: string }> = [];
    for (let i = index + 1; i < props.modelValue.length; i += 1) {
        const current = props.modelValue[i];
        if (current?.taxId !== base.taxId) {
            break;
        }
        items.push({
            index: i,
            startAt: current.startAt,
            endAt: current.endAt,
        });
    }

    return items;
}

function isDefaultRange(tax: CatalogItemTaxForm): boolean {
    return tax.startAt === defaultStartDate() && tax.endAt === FAR_FUTURE_DATE;
}

function shouldShowRangeSummary(
    tax: CatalogItemTaxForm,
    index: number,
): boolean {
    return rangeActiveByIndex.value[index] || !isDefaultRange(tax);
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-foreground">
                    {{ t('catalogItemTaxes.title') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ t('catalogItemTaxes.subtitle') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="!hasAvailableTaxes"
                    @click="addTax"
                >
                    {{ t('catalogItemTaxes.actions.addTax') }}
                </Button>
            </div>
        </div>

        <div
            v-if="!hasTaxes"
            class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
        >
            {{
                hasAvailableTaxes
                    ? t('catalogItemTaxes.empty')
                    : t('catalogItemTaxes.emptyUnavailable')
            }}
        </div>

        <div v-if="hasTaxes" class="divide-y divide-border/60">
            <div v-for="item in visibleTaxItems" :key="item.index" class="py-3">
                <CatalogItemTaxesFormItem
                    :value="item.tax"
                    :index="item.index"
                    :taxes="availableTaxesFor(item.index)"
                    :errors="errors"
                    :far-future-date="FAR_FUTURE_DATE"
                    :show-range-summary="
                        shouldShowRangeSummary(item.tax, item.index)
                    "
                    :is-child="false"
                    :range-items="rangeItemsFor(item.index)"
                    @select-tax="selectTax"
                    @update-range="
                        (payload) =>
                            updateTaxRange(
                                payload.index,
                                payload.startAt,
                                payload.endAt,
                            )
                    "
                    @update-rate="
                        (rateIndex, value) => updateTaxRate(rateIndex, value)
                    "
                    @activate-range="activateRange"
                    @remove="
                        (removeIndex) =>
                            isChildRow(removeIndex)
                                ? removeTaxAt(removeIndex)
                                : removeTaxGroup(removeIndex)
                    "
                    @add="
                        (addIndex, startAt, endAt) =>
                            addRangeAfter(addIndex, startAt, endAt)
                    "
                />
            </div>
        </div>
    </div>
</template>
