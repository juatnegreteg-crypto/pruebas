<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
    class?: HTMLAttributes["class"]
}>()
</script>

<template>
    <div
        data-slot="input-group-addon"
        :class="cn('flex items-center px-3 text-muted-foreground', props.class)"
    >
        <slot />
    </div>
</template>
