<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Car,
    CalendarClock,
    CalendarDays,
    LayoutGrid,
    Package,
    Percent,
    UserCog,
    Wrench,
    Users,
    FileText,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { type AppPageProps, type NavItem } from '@/types';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useSidebarGrouping } from '@/composables/useSidebarGrouping';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as agendaIndex } from '@/routes/agenda';
import { index as bundlesIndex } from '@/routes/bundles';
import { index as customersIndex } from '@/routes/customers';
import { index as iamPermissionsIndex } from '@/routes/iam/permissions';
import { index as iamProfilesIndex } from '@/routes/iam/profiles';
import { index as iamSkillsIndex } from '@/routes/iam/skills';
import { index as iamUsersIndex } from '@/routes/iam/users';
import { index as productsIndex } from '@/routes/products';
import { index as quotesIndex } from '@/routes/quotes';
import { index as scheduleIndex } from '@/routes/schedule';
import { index as servicesIndex } from '@/routes/services';
import { index as taxesIndex } from '@/routes/taxes';
import { index as techniciansIndex } from '@/routes/technicians';
import { index as vehiclesIndex } from '@/routes/vehicles';
import AppLogo from './AppLogo.vue';

type SidebarGroupDefinition = {
    id: string;
    labelKey: string;
    itemKeys: string[];
    showWhenEmpty?: boolean;
};

const page = usePage<AppPageProps>();
const { t } = useI18n();
const { groupingView } = useSidebarGrouping();
const { isCurrentUrl } = useCurrentUrl();

const userPermissions = computed<string[]>(() => {
    const auth = page.props.auth as {
        authorization?: { permissions?: string[] };
    };

    return auth.authorization?.permissions ?? [];
});

const canSee = (permission: string): boolean =>
    userPermissions.value.includes(permission);

const mainNavItems = computed<NavItem[]>(() =>
    [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Cotizaciones',
            href: quotesIndex(),
            icon: FileText,
        },
        {
            title: 'Paquetes',
            href: bundlesIndex(),
            icon: Package,
        },
        {
            title: 'Productos',
            href: productsIndex(),
            icon: Package,
        },
        {
            title: 'Servicios',
            href: servicesIndex(),
            icon: Wrench,
        },
        {
            title: 'Impuestos',
            href: taxesIndex(),
            icon: Percent,
        },
        {
            title: 'Clientes',
            href: customersIndex(),
            icon: Users,
        },
        {
            title: 'Vehiculos',
            href: vehiclesIndex(),
            icon: Car,
        },
        {
            title: 'Técnicos',
            href: techniciansIndex(),
            icon: UserCog,
        },
        {
            title: 'Usuarios',
            href: iamUsersIndex(),
            icon: Users,
        },
        {
            title: 'Perfiles',
            href: iamProfilesIndex(),
            icon: UserCog,
        },
        {
            title: 'Permisos',
            href: iamPermissionsIndex(),
            icon: BookOpen,
        },
        {
            title: 'Habilidades',
            href: iamSkillsIndex(),
            icon: Wrench,
        },
        {
            title: 'Agenda',
            href: agendaIndex(),
            icon: CalendarDays,
        },
        {
            title: 'Horarios',
            href: scheduleIndex(),
            icon: CalendarClock,
        },
    ].filter((item) => {
        if (item.title === 'Usuarios') {
            return canSee('users.view');
        }

        if (item.title === 'Perfiles') {
            return canSee('profiles.view');
        }

        if (item.title === 'Permisos') {
            return canSee('permissions.view');
        }

        if (item.title === 'Habilidades') {
            return canSee('skills.view');
        }

        return true;
    }),
);

const navItemKeyMap = computed(() => {
    const entries = mainNavItems.value.map((item) => [toUrl(item.href), item]);

    return new Map(entries);
});

const groupingDefinitions = computed<SidebarGroupDefinition[]>(() => {
    const operational: SidebarGroupDefinition[] = [
        {
            id: 'operations',
            labelKey: 'sidebar.groups.operations',
            itemKeys: [
                toUrl(agendaIndex()),
                toUrl(quotesIndex()),
                toUrl(customersIndex()),
                toUrl(vehiclesIndex()),
                toUrl(techniciansIndex()),
            ],
        },
        {
            id: 'services',
            labelKey: 'sidebar.groups.services',
            itemKeys: [
                toUrl(servicesIndex()),
                toUrl(bundlesIndex()),
                toUrl(productsIndex()),
                toUrl(taxesIndex()),
            ],
        },
        {
            id: 'certification',
            labelKey: 'sidebar.groups.certification',
            itemKeys: [],
        },
        {
            id: 'administration',
            labelKey: 'sidebar.groups.administration',
            itemKeys: [
                toUrl(iamUsersIndex()),
                toUrl(iamProfilesIndex()),
                toUrl(iamPermissionsIndex()),
                toUrl(iamSkillsIndex()),
            ],
        },
        {
            id: 'configuration',
            labelKey: 'sidebar.groups.configuration',
            itemKeys: [toUrl(scheduleIndex())],
        },
    ];

    const contractual: SidebarGroupDefinition[] = [
        {
            id: 'technical-quotation',
            labelKey: 'sidebar.groups.technicalMechanicalQuotation',
            itemKeys: [
                toUrl(quotesIndex()),
                toUrl(servicesIndex()),
                toUrl(bundlesIndex()),
                toUrl(productsIndex()),
                toUrl(taxesIndex()),
            ],
        },
        {
            id: 'schedule',
            labelKey: 'sidebar.groups.schedule',
            itemKeys: [
                toUrl(agendaIndex()),
                toUrl(techniciansIndex()),
                toUrl(scheduleIndex()),
            ],
        },
        {
            id: 'digital-certificates',
            labelKey: 'sidebar.groups.digitalCertificates',
            itemKeys: [],
        },
        {
            id: 'customer-service',
            labelKey: 'sidebar.groups.customerService',
            itemKeys: [toUrl(customersIndex()), toUrl(vehiclesIndex())],
        },
        {
            id: 'administration',
            labelKey: 'sidebar.groups.administration',
            itemKeys: [
                toUrl(iamUsersIndex()),
                toUrl(iamProfilesIndex()),
                toUrl(iamPermissionsIndex()),
                toUrl(iamSkillsIndex()),
            ],
        },
    ];

    return groupingView.value === 'contractual' ? contractual : operational;
});

const dashboardItem = computed(() => {
    return navItemKeyMap.value.get(toUrl(dashboard())) ?? null;
});

const groupedNavItems = computed(() => {
    const availableItems = new Map(navItemKeyMap.value);
    availableItems.delete(toUrl(dashboard()));

    const groups = groupingDefinitions.value.map((group) => {
        const items = group.itemKeys
            .map((key) => availableItems.get(key))
            .filter(Boolean) as NavItem[];

        items.forEach((item) => {
            availableItems.delete(toUrl(item.href));
        });

        return {
            id: group.id,
            label: t(group.labelKey),
            items,
            showWhenEmpty: group.showWhenEmpty ?? false,
        };
    });

    const remainingItems = Array.from(availableItems.values());
    if (remainingItems.length > 0) {
        groups.push({
            id: 'other',
            label: t('sidebar.groups.other'),
            items: remainingItems,
        });
    }

    return groups;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarMenu>
                    <SidebarMenuItem v-if="dashboardItem">
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(dashboardItem.href)"
                        >
                            <Link :href="dashboardItem.href">
                                <component :is="dashboardItem.icon" />
                                <span>{{ dashboardItem.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
            <NavMain :groups="groupedNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
