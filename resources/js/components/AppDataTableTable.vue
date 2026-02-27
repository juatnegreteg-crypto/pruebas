<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Table } from '@/components/ui/table';
import { cn } from '@/lib/utils';

type Props = {
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <div :class="cn('overflow-x-auto rounded-md border bg-card', props.class)">
        <Table class="w-full">
            <slot />
        </Table>
    </div>
</template>
