<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { TableCell } from '@/components/ui/table';
import { cn } from '@/lib/utils';

type Props = {
    align?: 'left' | 'center' | 'right';
    class?: HTMLAttributes['class'];
    width?: string | number;
    minWidth?: string | number;
};

const props = defineProps<Props>();

function normalizeSize(value?: string | number): string | undefined {
    if (value == null || value === '') {
        return undefined;
    }

    return typeof value === 'number' ? `${value}px` : value;
}

const alignClass: Record<NonNullable<Props['align']>, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
};
</script>

<template>
    <TableCell
        :class="
            cn(
                'px-6 py-4 text-sm text-foreground',
                props.align ? alignClass[props.align] : 'text-left',
                props.class,
            )
        "
        :style="{
            width: normalizeSize(props.width),
            minWidth: normalizeSize(props.minWidth),
        }"
    >
        <slot />
    </TableCell>
</template>
