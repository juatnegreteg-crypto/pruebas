<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ChevronDown, ChevronRight } from "lucide-vue-next"
import { cn } from "@/lib/utils"

const props = defineProps<{
  expanded: boolean
  disabled?: boolean
  class?: HTMLAttributes["class"]
}>()

const emit = defineEmits<{
  (e: "toggle"): void
}>()
</script>

<template>
  <button
    type="button"
    :disabled="props.disabled"
    :class="
      cn(
        'inline-flex size-4 items-center justify-center text-muted-foreground hover:text-foreground disabled:opacity-40',
        props.class,
      )
    "
    :aria-expanded="props.expanded"
    @click="emit('toggle')"
  >
    <component
      :is="props.expanded ? ChevronDown : ChevronRight"
      class="size-4"
    />
  </button>
</template>
