<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { TableHead } from '@/components/ui/table';
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
    <TableHead
        :class="
            cn(
                'h-auto px-6 py-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase',
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
    </TableHead>
</template>
