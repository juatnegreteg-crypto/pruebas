<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { type BreadcrumbItem } from '@/types';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrencyFormat } from '@/composables/useCurrencyFormat';
import { useSidebarGrouping } from '@/composables/useSidebarGrouping';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';

const { t } = useI18n();
const { groupingView, updateGroupingView } = useSidebarGrouping();
const { currencyFormat, updateCurrencyFormat, formatCurrency, preview } =
    useCurrencyFormat();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('settings.appearance.breadcrumb'),
        href: edit().url,
    },
]);

const pageTitle = computed(() => t('settings.appearance.title'));
const localeOptions = computed(() => [
    {
        value: 'es-CO',
        label: t('settings.appearance.currencyFormat.locales.esCO'),
    },
    {
        value: 'en-US',
        label: t('settings.appearance.currencyFormat.locales.enUS'),
    },
    {
        value: 'es-ES',
        label: t('settings.appearance.currencyFormat.locales.esES'),
    },
]);
const displayOptions = computed(() => [
    {
        value: 'symbol',
        label: t('settings.appearance.currencyFormat.displayOptions.symbol'),
    },
    {
        value: 'code',
        label: t('settings.appearance.currencyFormat.displayOptions.code'),
    },
]);
const decimalsOptions = computed(() => [
    {
        value: '0',
        label: t('settings.appearance.currencyFormat.decimalsOptions.zero'),
    },
    {
        value: '2',
        label: t('settings.appearance.currencyFormat.decimalsOptions.two'),
    },
    {
        value: '4',
        label: t('settings.appearance.currencyFormat.decimalsOptions.four'),
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="pageTitle" />

        <h1 class="sr-only">{{ pageTitle }}</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    :title="t('settings.appearance.title')"
                    :description="t('settings.appearance.description')"
                />
                <AppearanceTabs />
                <div class="space-y-3">
                    <Heading
                        variant="small"
                        :title="
                            t('settings.appearance.navigationGrouping.title')
                        "
                        :description="
                            t(
                                'settings.appearance.navigationGrouping.description',
                            )
                        "
                    />
                    <ButtonGroup>
                        <Button
                            :variant="
                                groupingView === 'operational'
                                    ? 'default'
                                    : 'outline'
                            "
                            :aria-pressed="groupingView === 'operational'"
                            @click="updateGroupingView('operational')"
                        >
                            {{
                                t(
                                    'settings.appearance.navigationGrouping.operational',
                                )
                            }}
                        </Button>
                        <Button
                            :variant="
                                groupingView === 'contractual'
                                    ? 'default'
                                    : 'outline'
                            "
                            :aria-pressed="groupingView === 'contractual'"
                            @click="updateGroupingView('contractual')"
                        >
                            {{
                                t(
                                    'settings.appearance.navigationGrouping.contractual',
                                )
                            }}
                        </Button>
                    </ButtonGroup>
                </div>
                <div class="space-y-3">
                    <Heading
                        variant="small"
                        :title="t('settings.appearance.currencyFormat.title')"
                        :description="
                            t('settings.appearance.currencyFormat.description')
                        "
                    />
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">
                                {{
                                    t(
                                        'settings.appearance.currencyFormat.locale',
                                    )
                                }}
                            </label>
                            <Select
                                :model-value="currencyFormat.locale"
                                @update:model-value="
                                    (value) =>
                                        updateCurrencyFormat({
                                            locale: String(value),
                                        })
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="
                                            t(
                                                'settings.appearance.currencyFormat.locale',
                                            )
                                        "
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in localeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">
                                {{
                                    t(
                                        'settings.appearance.currencyFormat.display',
                                    )
                                }}
                            </label>
                            <Select
                                :model-value="currencyFormat.display"
                                @update:model-value="
                                    (value) =>
                                        updateCurrencyFormat({
                                            display:
                                                value === 'code'
                                                    ? 'code'
                                                    : 'symbol',
                                        })
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="
                                            t(
                                                'settings.appearance.currencyFormat.display',
                                            )
                                        "
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in displayOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">
                                {{
                                    t(
                                        'settings.appearance.currencyFormat.decimals',
                                    )
                                }}
                            </label>
                            <Select
                                :model-value="String(currencyFormat.decimals)"
                                @update:model-value="
                                    (value) =>
                                        updateCurrencyFormat({
                                            decimals: Number(value),
                                        })
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="
                                            t(
                                                'settings.appearance.currencyFormat.decimals',
                                            )
                                        "
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in decimalsOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="rounded-md border bg-muted/40 p-3 text-sm">
                        <p class="text-xs text-muted-foreground">
                            {{
                                t('settings.appearance.currencyFormat.preview')
                            }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-4 font-medium">
                            <span>{{ preview }}</span>
                            <span>{{ formatCurrency(9876.5, 'USD') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
