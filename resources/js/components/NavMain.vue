<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { type NavItem } from '@/types';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

type NavGroup = {
    id: string;
    label: string;
    items: NavItem[];
    showWhenEmpty?: boolean;
};

defineProps<{
    groups: NavGroup[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup
        v-for="group in groups"
        :key="group.id"
        v-show="group.items.length > 0 || group.showWhenEmpty"
        class="px-2 py-0"
    >
        <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
        <SidebarMenu v-if="group.items.length > 0">
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
