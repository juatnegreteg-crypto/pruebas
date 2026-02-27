<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="flex items-center gap-2">
        <div
            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary/10"
        >
            <img
                src="/images/logotipo.png"
                :alt="t('brand.logoAlt')"
                class="h-8 w-8 object-cover"
            />
        </div>
        <div
            class="ml-1 grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden"
        >
            <span class="truncate font-semibold">{{ t('brand.name') }}</span>
            <span class="truncate text-xs text-muted-foreground">
                {{ t('brand.legalName') }}
            </span>
        </div>
    </div>
</template>
