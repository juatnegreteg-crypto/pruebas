<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { provide, useId } from "vue"
import { cn } from "@/lib/utils"
import { FORM_ITEM_INJECTION_KEY } from "./constants"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const id = useId()

provide(FORM_ITEM_INJECTION_KEY, {
  id,
})
</script>

<template>
  <div
    data-slot="form-item"
    :class="cn('space-y-2', props.class)"
  >
    <slot />
  </div>
</template>
