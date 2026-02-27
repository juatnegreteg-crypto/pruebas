<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as bundlesIndex } from '@/routes/bundles';

type BundleItem = {
    id: number;
    type: string | null;
    name: string | null;
    description?: string | null;
    price: string | null;
    currency: string | null;
    isActive: boolean | null;
    quantity?: number | null;
};

type Bundle = {
    id: number;
    name: string;
    description?: string | null;
    price: string;
    currency: string;
    isActive: boolean;
    itemsCount: number;
    items?: BundleItem[];
};

type BundlePayload = {
    data: Bundle;
};

type Props = {
    bundle: BundlePayload;
};

const props = defineProps<Props>();
const { t } = useI18n();
const bundle = computed(() => props.bundle.data);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('bundles.title'),
        href: bundlesIndex().url,
    },
    {
        title: bundle.value.name,
    },
];

const isLoading = ref(false);
const onStart = () => {
    isLoading.value = true;
};
const onFinish = () => {
    isLoading.value = false;
};
let unsubscribeStart: (() => void) | null = null;
let unsubscribeFinish: (() => void) | null = null;
let unsubscribeError: (() => void) | null = null;

onMounted(() => {
    unsubscribeStart = router.on('start', onStart);
    unsubscribeFinish = router.on('finish', onFinish);
    unsubscribeError = router.on('error', onFinish);
});

onBeforeUnmount(() => {
    unsubscribeStart?.();
    unsubscribeFinish?.();
    unsubscribeError?.();
    unsubscribeStart = null;
    unsubscribeFinish = null;
    unsubscribeError = null;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="bundle.name" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold text-muted-foreground uppercase"
                    >
                        {{ t('bundles.detail.label') }}
                    </p>
                    <h1 class="text-2xl font-semibold text-foreground">
                        {{ bundle.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{
                            bundle.description ||
                            t('bundles.detail.noDescription')
                        }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="ghost" as-child>
                        <Link :href="bundlesIndex().url">
                            {{ t('bundles.detail.back') }}
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 bg-card shadow-sm"
            >
                <div
                    class="flex flex-col gap-2 border-b border-sidebar-border/70 px-4 py-4"
                >
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ t('bundles.detail.itemsTitle') }}
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        {{
                            t('bundles.detail.itemsSummary', {
                                total: bundle.itemsCount,
                            })
                        }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <div
                        v-if="isLoading"
                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        {{ t('common.loading') }}
                    </div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-sidebar-border/70 text-left text-xs text-muted-foreground uppercase"
                            >
                                <th class="px-4 py-3">
                                    {{ t('bundles.detail.table.name') }}
                                </th>
                                <th class="px-4 py-3">
                                    {{ t('bundles.detail.table.type') }}
                                </th>
                                <th class="px-4 py-3 text-center">
                                    {{ t('bundles.detail.table.quantity') }}
                                </th>
                                <th class="px-4 py-3 text-right">
                                    {{ t('bundles.detail.table.price') }}
                                </th>
                                <th class="px-4 py-3 text-center">
                                    {{ t('bundles.detail.table.status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in bundle.items || []"
                                :key="item.id"
                                class="border-b border-sidebar-border/70 last:border-none"
                            >
                                <td
                                    class="px-4 py-3 font-medium text-foreground"
                                >
                                    {{ item.name || '—' }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ item.type || '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ item.quantity ?? 1 }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ item.currency }}
                                    {{ item.price ?? '0.00' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="
                                            item.isActive
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-rose-100 text-rose-700'
                                        "
                                        :aria-label="
                                            item.isActive
                                                ? t('bundles.status.ariaActive')
                                                : t(
                                                      'bundles.status.ariaInactive',
                                                  )
                                        "
                                    >
                                        {{
                                            item.isActive
                                                ? t('bundles.status.active')
                                                : t('bundles.status.inactive')
                                        }}
                                    </span>
                                </td>
                            </tr>
                            <tr
                                v-if="
                                    !bundle.items || bundle.items.length === 0
                                "
                            >
                                <td
                                    colspan="5"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('bundles.detail.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
