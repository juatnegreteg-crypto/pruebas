<script setup lang="ts">
import { Languages } from 'lucide-vue-next';
import { computed } from 'vue';
import type { BreadcrumbItem } from '@/types';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { i18nKeyMode } from '@/i18n';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const isKeyModeEnabled = computed(() => i18nKeyMode.isEnabled.value);

function toggleKeyMode() {
    i18nKeyMode.toggle();
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <TooltipProvider :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="group h-9 w-9 cursor-pointer"
                            :class="isKeyModeEnabled ? 'bg-muted' : ''"
                            @click="toggleKeyMode"
                        >
                            <span class="sr-only">Toggle i18n key mode</span>
                            <Languages
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>
                            {{
                                isKeyModeEnabled
                                    ? 'Mostrar traducciones'
                                    : 'Mostrar claves i18n'
                            }}
                        </p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
    </header>
</template>
