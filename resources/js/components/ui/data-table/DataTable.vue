<script setup lang="ts" generic="TData, TValue">
import type { ColumnDef, Row } from "@tanstack/vue-table"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import {
  FlexRender,
  getCoreRowModel,
  getExpandedRowModel,
  useVueTable,
} from "@tanstack/vue-table"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { ButtonExpander } from "@/components/ui/button"

type ColumnMeta = {
  headClass?: HTMLAttributes["class"]
  cellClass?: HTMLAttributes["class"]
}

const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]
  data: TData[]
  getRowCanExpand?: (row: TData) => boolean
  getSubRows?: (row: TData) => TData[] | undefined
  onRowExpand?: (row: Row<TData>) => void
}>()
const table = useVueTable({
  get data() {
    return props.data
  },
  get columns() {
    return props.columns
  },
  getCoreRowModel: getCoreRowModel(),
  getExpandedRowModel: getExpandedRowModel(),
  getSubRows: props.getSubRows
    ? (row) => props.getSubRows?.(row) ?? []
    : (row) => (row as { subRows?: TData[] })?.subRows ?? [],
  getRowCanExpand: props.getRowCanExpand
    ? (row) => Boolean(props.getRowCanExpand?.(row.original))
    : () => false,
})

const columnCount = computed(() => table.getAllLeafColumns().length)

function handleExpanderToggle(row: Row<TData>) {
  if (row.getIsExpanded()) {
    row.toggleExpanded()
    return
  }

  row.toggleExpanded()
  props.onRowExpand?.(row)
}
</script>

<template>
  <Table>
    <TableHeader>
      <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
        <TableHead
          v-for="header in headerGroup.headers"
          :key="header.id"
          :class="(header.column.columnDef.meta as ColumnMeta | undefined)?.headClass"
        >
          <FlexRender
            v-if="!header.isPlaceholder"
            :render="header.column.columnDef.header"
            :props="header.getContext()"
          />
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <template v-if="table.getRowModel().rows?.length">
        <template v-for="row in table.getRowModel().rows" :key="row.id">
          <TableRow
            :data-state="row.getIsSelected?.() ? 'selected' : undefined"
            :expandable="row.getCanExpand?.() ?? false"
          >
            <TableCell
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              :class="(cell.column.columnDef.meta as ColumnMeta | undefined)?.cellClass"
            >
              <template v-if="cell.column.getIndex?.() === 0">
                <div
                  class="flex items-center gap-2"
                  :style="row.depth > 0 ? { paddingLeft: `${row.depth * 1.5}rem` } : undefined"
                >
                  <ButtonExpander
                    v-if="row.getCanExpand?.()"
                    :expanded="row.getIsExpanded()"
                    @toggle="handleExpanderToggle(row)"
                  />
                  <span v-else class="inline-flex size-4" />
                  <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()"
                  />
                </div>
              </template>
              <FlexRender
                v-else
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </TableCell>
          </TableRow>
        </template>
      </template>
      <TableRow v-else>
        <TableCell :colspan="columnCount" class="h-24 text-center">
          <slot name="empty" />
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
</template>
