<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import { webApiFetch } from '@/composables/useWebApiFetch';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as profilesIndex } from '@/routes/iam/profiles';

defineOptions({ layout: AppLayout });

type PermissionRow = {
    id: number;
    name: string;
    module: string | null;
    action: string | null;
    description: string | null;
};

const { toast, error } = useToast();
const { t } = useI18n();

const permissions = ref<PermissionRow[]>([]);
const loading = ref(false);

async function loadPermissions(): Promise<void> {
    loading.value = true;

    try {
        const response = await webApiFetch('/api/v1/permissions');

        if (!response.ok) {
            error(t('iam.permissions.errors.load'));
            return;
        }

        permissions.value = (await response.json()).data ?? [];
    } catch {
        error(t('iam.permissions.errors.connection'));
    } finally {
        loading.value = false;
    }
}

onMounted(loadPermissions);
</script>

<template>
    <Head :title="t('iam.permissions.headTitle')" />
    <Toast :toast="toast" />

    <div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <Heading
            :title="t('iam.permissions.title')"
            :description="t('iam.permissions.description')"
        />

        <Card class="p-4">
            <div class="text-sm text-muted-foreground">
                {{ t('iam.permissions.note') }}
            </div>
            <div class="mt-3">
                <Button as-child variant="outline" size="sm">
                    <Link :href="profilesIndex()">
                        {{ t('iam.permissions.actions.assignToProfiles') }}
                    </Link>
                </Button>
            </div>
        </Card>

        <Card class="p-4">
            <h3
                class="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{ t('iam.permissions.table.title') }}
            </h3>
            <div v-if="loading" class="text-sm text-muted-foreground">
                {{ t('iam.permissions.loading') }}
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">
                                {{ t('iam.permissions.table.name') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.permissions.table.module') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.permissions.table.action') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.permissions.table.description') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="permission in permissions"
                            :key="permission.id"
                            class="border-b"
                        >
                            <td class="py-2">{{ permission.name }}</td>
                            <td class="py-2">
                                {{
                                    permission.module ??
                                    t('common.notAvailable')
                                }}
                            </td>
                            <td class="py-2">
                                {{
                                    permission.action ??
                                    t('common.notAvailable')
                                }}
                            </td>
                            <td class="py-2">
                                {{
                                    permission.description ??
                                    t('common.notAvailable')
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
