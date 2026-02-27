<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, ref, useSlots } from 'vue';
import AppDataTableEmptyState from '@/components/AppDataTableEmptyState.vue';
import AppDataTableHeadCell from '@/components/AppDataTableHeadCell.vue';
import AppDataTableRowCell from '@/components/AppDataTableRowCell.vue';
import AppDataTableTable from '@/components/AppDataTableTable.vue';
import { ButtonExpander } from '@/components/ui/button';
import {
    TableBody,
    TableFooter,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type AppDataTableColumn = {
    key: string;
    header?: string;
    align?: 'left' | 'center' | 'right';
    width?: string | number;
    minWidth?: string | number;
};

type Row = Record<string, unknown>;

type Props = {
    rows: Row[];
    columns: AppDataTableColumn[];
    rowKey: string | ((row: Row) => string | number);
    getRowCanExpand?: (row: Row) => boolean;
    getSubRows?: (row: Row) => Row[] | undefined;
    onRowExpand?: (row: Row) => void;
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();

const slots = useSlots();

const emptySlotName = 'empty()';
const headSlotName = 'head()';
const cellSlotName = 'cell()';
const footSlotName = 'foot()';

const hasFooter = computed(() =>
    props.columns.some(
        (column) =>
            Boolean(slots[`foot(${column.key})`]) ||
            Boolean(slots[footSlotName]),
    ),
);
const expandedKeys = ref<Record<string, boolean>>({});
const isExpandable = computed(
    () =>
        typeof props.getRowCanExpand === 'function' ||
        typeof props.getSubRows === 'function',
);

function resolveRowKey(row: Row, index: number): string | number {
    if (typeof props.rowKey === 'function') {
        return props.rowKey(row);
    }

    const key = row[props.rowKey];
    return typeof key === 'string' || typeof key === 'number' ? key : index;
}

function resolveValue(row: Row, column: AppDataTableColumn): unknown {
    return row[column.key];
}

function isRowExpanded(key: string | number): boolean {
    return Boolean(expandedKeys.value[String(key)]);
}

function toggleRowExpanded(row: Row, key: string | number): void {
    const next = { ...expandedKeys.value };
    const stringKey = String(key);
    const nextValue = !next[stringKey];
    next[stringKey] = nextValue;
    expandedKeys.value = next;

    if (nextValue) {
        props.onRowExpand?.(row);
    }
}

function resolveSubRows(row: Row): Row[] {
    if (props.getSubRows) {
        return props.getSubRows(row) ?? [];
    }

    const subRows = (row as { subRows?: Row[] }).subRows;
    return Array.isArray(subRows) ? subRows : [];
}

function canExpandRow(row: Row): boolean {
    if (props.getRowCanExpand) {
        return Boolean(props.getRowCanExpand(row));
    }

    return resolveSubRows(row).length > 0;
}

const flattenedRows = computed(() => {
    const output: Array<{ row: Row; depth: number; key: string | number }> = [];

    function visit(rows: Row[], depth: number): void {
        rows.forEach((row, index) => {
            const key = resolveRowKey(row, index);
            output.push({ row, depth, key });

            if (isRowExpanded(key)) {
                const subRows = resolveSubRows(row);
                if (subRows.length) {
                    visit(subRows, depth + 1);
                }
            }
        });
    }

    visit(props.rows, 0);
    return output;
});

function hasSlot(name: string): boolean {
    return Boolean(slots[name]);
}
</script>

<template>
    <AppDataTableTable :class="props.class">
        <TableHeader>
            <TableRow class="bg-muted/50">
                <AppDataTableHeadCell
                    v-for="column in props.columns"
                    :key="column.key"
                    :align="column.align"
                    :width="column.width"
                    :min-width="column.minWidth"
                >
                    <template v-if="hasSlot(`head(${column.key})`)">
                        <slot :name="`head(${column.key})`" :column="column" />
                    </template>
                    <template v-else-if="hasSlot(headSlotName)">
                        <slot :name="headSlotName" :column="column" />
                    </template>
                    <template v-else>
                        {{ column.header ?? column.key }}
                    </template>
                </AppDataTableHeadCell>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="props.rows.length">
                <TableRow
                    v-for="(entry, rowIndex) in flattenedRows"
                    :key="entry.key"
                    :expandable="isExpandable && canExpandRow(entry.row)"
                >
                    <AppDataTableRowCell
                        v-for="column in props.columns"
                        :key="column.key"
                        :align="column.align"
                        :width="column.width"
                        :min-width="column.minWidth"
                    >
                        <template v-if="hasSlot(`cell(${column.key})`)">
                            <div
                                v-if="
                                    column.key === props.columns[0]?.key &&
                                    isExpandable
                                "
                                class="flex items-center gap-2"
                                :style="
                                    entry.depth > 0
                                        ? {
                                              paddingLeft: `${entry.depth * 1.5}rem`,
                                          }
                                        : undefined
                                "
                            >
                                <ButtonExpander
                                    v-if="canExpandRow(entry.row)"
                                    :expanded="isRowExpanded(entry.key)"
                                    @toggle="
                                        toggleRowExpanded(entry.row, entry.key)
                                    "
                                />
                                <span v-else class="inline-flex size-4" />
                                <slot
                                    :name="`cell(${column.key})`"
                                    :row="entry.row"
                                    :value="resolveValue(entry.row, column)"
                                    :column="column"
                                    :row-index="rowIndex"
                                />
                            </div>
                            <slot
                                v-else
                                :name="`cell(${column.key})`"
                                :row="entry.row"
                                :value="resolveValue(entry.row, column)"
                                :column="column"
                                :row-index="rowIndex"
                            />
                        </template>
                        <template v-else-if="hasSlot(cellSlotName)">
                            <div
                                v-if="
                                    column.key === props.columns[0]?.key &&
                                    isExpandable
                                "
                                class="flex items-center gap-2"
                                :style="
                                    entry.depth > 0
                                        ? {
                                              paddingLeft: `${entry.depth * 1.5}rem`,
                                          }
                                        : undefined
                                "
                            >
                                <ButtonExpander
                                    v-if="canExpandRow(entry.row)"
                                    :expanded="isRowExpanded(entry.key)"
                                    @toggle="
                                        toggleRowExpanded(entry.row, entry.key)
                                    "
                                />
                                <span v-else class="inline-flex size-4" />
                                <slot
                                    :name="cellSlotName"
                                    :row="entry.row"
                                    :value="resolveValue(entry.row, column)"
                                    :column="column"
                                    :row-index="rowIndex"
                                />
                            </div>
                            <slot
                                v-else
                                :name="cellSlotName"
                                :row="entry.row"
                                :value="resolveValue(entry.row, column)"
                                :column="column"
                                :row-index="rowIndex"
                            />
                        </template>
                        <template v-else>
                            <div
                                v-if="
                                    column.key === props.columns[0]?.key &&
                                    isExpandable
                                "
                                class="flex items-center gap-2"
                                :style="
                                    entry.depth > 0
                                        ? {
                                              paddingLeft: `${entry.depth * 1.5}rem`,
                                          }
                                        : undefined
                                "
                            >
                                <ButtonExpander
                                    v-if="canExpandRow(entry.row)"
                                    :expanded="isRowExpanded(entry.key)"
                                    @toggle="
                                        toggleRowExpanded(entry.row, entry.key)
                                    "
                                />
                                <span v-else class="inline-flex size-4" />
                                {{ resolveValue(entry.row, column) }}
                            </div>
                            <template v-else>
                                {{ resolveValue(entry.row, column) }}
                            </template>
                        </template>
                    </AppDataTableRowCell>
                </TableRow>
            </template>
            <AppDataTableEmptyState v-else :colspan="props.columns.length">
                <template v-if="hasSlot(emptySlotName)">
                    <slot :name="emptySlotName" />
                </template>
            </AppDataTableEmptyState>
        </TableBody>
        <TableFooter v-if="hasFooter">
            <TableRow>
                <AppDataTableRowCell
                    v-for="column in props.columns"
                    :key="column.key"
                    :align="column.align"
                    :width="column.width"
                    :min-width="column.minWidth"
                >
                    <template v-if="hasSlot(`foot(${column.key})`)">
                        <slot :name="`foot(${column.key})`" :column="column" />
                    </template>
                    <template v-else-if="hasSlot(footSlotName)">
                        <slot :name="footSlotName" :column="column" />
                    </template>
                </AppDataTableRowCell>
            </TableRow>
        </TableFooter>
    </AppDataTableTable>
</template>
