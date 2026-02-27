<script setup lang="ts">
import { computed, watch, ref } from 'vue';

type ToastType = 'success' | 'error' | 'warning' | 'info';
type ToastPayload = {
    type: ToastType;
    message: string;
    ctaLabel?: string;
    onCta?: () => void;
};

const props = defineProps<{
    toast?: ToastPayload | null;
    durationMs?: number;
}>();

const isVisible = ref(false);
const duration = computed(() => props.durationMs ?? 3000);

const classes = computed(() => {
    const type = props.toast?.type ?? 'info';
    if (type === 'success')
        return 'bg-green-50 text-green-800 border-green-200';
    if (type === 'error') return 'bg-red-50 text-red-800 border-red-200';
    if (type === 'warning')
        return 'bg-yellow-50 text-yellow-800 border-yellow-200';
    return 'bg-blue-50 text-blue-800 border-blue-200';
});

function show() {
    isVisible.value = true;
    window.setTimeout(() => (isVisible.value = false), duration.value);
}

function handleCta(): void {
    props.toast?.onCta?.();
    isVisible.value = false;
}

watch(
    () => props.toast,
    (val) => {
        if (val?.message) show();
    },
    { immediate: true },
);
</script>

<template>
    <div
        v-if="toast && isVisible"
        class="fixed top-4 right-4 z-50 w-full max-w-sm rounded-xl border px-4 py-3 shadow-lg"
        :class="classes"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="grid gap-2">
                <p class="text-sm leading-5 font-medium">{{ toast.message }}</p>
                <button
                    v-if="toast.ctaLabel"
                    type="button"
                    class="w-fit rounded-md border px-2 py-1 text-xs font-medium"
                    @click="handleCta"
                >
                    {{ toast.ctaLabel }}
                </button>
            </div>

            <button
                type="button"
                class="shrink-0 rounded-md px-2 py-1 text-sm opacity-70 hover:opacity-100"
                @click="isVisible = false"
                aria-label="Close notification"
            >
                ✕
            </button>
        </div>
    </div>
</template>
