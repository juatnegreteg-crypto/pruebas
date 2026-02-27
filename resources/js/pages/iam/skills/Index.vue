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

type SkillRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
};

const { toast, error } = useToast();
const { t } = useI18n();

const skills = ref<SkillRow[]>([]);
const loading = ref(false);

async function loadSkills(): Promise<void> {
    loading.value = true;

    try {
        const response = await webApiFetch('/api/v1/skills');

        if (!response.ok) {
            error(t('iam.skills.errors.load'));
            return;
        }

        skills.value = (await response.json()).data ?? [];
    } catch {
        error(t('iam.skills.errors.connection'));
    } finally {
        loading.value = false;
    }
}

onMounted(loadSkills);
</script>

<template>
    <Head :title="t('iam.skills.headTitle')" />
    <Toast :toast="toast" />

    <div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <Heading
            :title="t('iam.skills.title')"
            :description="t('iam.skills.description')"
        />

        <Card class="p-4">
            <div class="text-sm text-muted-foreground">
                {{ t('iam.skills.note') }}
            </div>
            <div class="mt-3">
                <Button as-child variant="outline" size="sm">
                    <Link :href="profilesIndex()">
                        {{ t('iam.skills.actions.assignToProfiles') }}
                    </Link>
                </Button>
            </div>
        </Card>

        <Card class="p-4">
            <h3
                class="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{ t('iam.skills.table.title') }}
            </h3>
            <div v-if="loading" class="text-sm text-muted-foreground">
                {{ t('iam.skills.loading') }}
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">
                                {{ t('iam.skills.table.name') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.skills.table.slug') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.skills.table.description') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.skills.table.status') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="skill in skills"
                            :key="skill.id"
                            class="border-b"
                        >
                            <td class="py-2">{{ skill.name }}</td>
                            <td class="py-2">{{ skill.slug }}</td>
                            <td class="py-2">
                                {{
                                    skill.description ??
                                    t('common.notAvailable')
                                }}
                            </td>
                            <td class="py-2">
                                {{
                                    skill.is_active
                                        ? t('iam.skills.status.active')
                                        : t('iam.skills.status.inactive')
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
