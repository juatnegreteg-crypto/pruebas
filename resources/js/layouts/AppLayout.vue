<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { BreadcrumbItem } from '@/types';
import Toast from '@/components/Toast.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

const toast = computed(() => {
    // @ts-expect-error flash is dynamically added by Laravel
    return page.props.flash?.toast ?? null;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast :toast="toast" />
        <slot />
    </AppLayout>
</template>
