<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { TableCell, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

type Props = {
    colspan?: number;
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <TableRow>
        <TableCell
            :colspan="props.colspan"
            :class="
                cn(
                    'px-6 py-8 text-center text-sm text-muted-foreground',
                    props.class,
                )
            "
        >
            <slot />
        </TableCell>
    </TableRow>
</template>
