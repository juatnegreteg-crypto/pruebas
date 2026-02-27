<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  expandable?: boolean
}>()
</script>

<template>
  <tr
    :class="
      cn(
        'border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted',
        props.class,
      )
    "
    :data-expandable="props.expandable ? 'true' : undefined"
    data-slot="table-row"
  >
    <slot />
  </tr>
</template>
