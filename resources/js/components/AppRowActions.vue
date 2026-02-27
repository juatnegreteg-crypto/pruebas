<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

type Props = {
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <div :class="cn('flex items-center justify-end gap-1', props.class)">
        <slot />
    </div>
</template>
