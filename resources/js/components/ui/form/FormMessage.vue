<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { cn } from "@/lib/utils"
import { useFormField } from "./useFormField"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { error, formMessageId } = useFormField()
const body = computed(() => error.value || "")
</script>

<template>
  <p
    v-if="body"
    :id="formMessageId"
    data-slot="form-message"
    :class="cn('text-destructive text-sm font-medium', props.class)"
  >
    {{ body }}
  </p>
</template>
