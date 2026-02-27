<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Key } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import Heading from '@/components/Heading.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useToast } from '@/composables/useToast';
import { webApiFetch } from '@/composables/useWebApiFetch';
import AppLayout from '@/layouts/AppLayout.vue';
import { capabilities as profileCapabilities } from '@/routes/iam/profiles';
import { index as usersIndex } from '@/routes/iam/users';

defineOptions({ layout: AppLayout });

type Option = { id: number; name: string; slug?: string };
type ProfileRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    is_technician_profile: boolean;
    users_count: number;
    permissions: Option[];
    skills: Option[];
};
const { toast, success, error } = useToast();
const { t } = useI18n();

const profiles = ref<ProfileRow[]>([]);
const skills = ref<Option[]>([]);
const loading = ref(false);
const saving = ref(false);
const editingId = ref<number | null>(null);
const saveIntent = ref<'close' | 'configure'>('close');

const form = reactive({
    name: '',
    slug: '',
    description: '',
    is_active: true,
    is_technician_profile: false,
    skill_ids: [] as number[],
});

const isEditing = computed(() => editingId.value !== null);

function toUniqueSortedIds(ids: number[]): number[] {
    return Array.from(new Set(ids)).sort((a, b) => a - b);
}

function resetForm(): void {
    editingId.value = null;
    form.name = '';
    form.slug = '';
    form.description = '';
    form.is_active = true;
    form.is_technician_profile = false;
    form.skill_ids = [];
    saveIntent.value = 'close';
}

async function loadData(): Promise<void> {
    loading.value = true;

    try {
        const [profilesResponse, skillsResponse] = await Promise.all([
            webApiFetch('/api/v1/profiles', {
                headers: { Accept: 'application/json' },
            }),
            webApiFetch('/api/v1/skills', {
                headers: { Accept: 'application/json' },
            }),
        ]);

        if (profilesResponse.ok) {
            profiles.value = (await profilesResponse.json()).data ?? [];
        }

        if (skillsResponse.ok) {
            skills.value = (await skillsResponse.json()).data ?? [];
        }
    } catch {
        error(t('iam.profiles.errors.load'));
    } finally {
        loading.value = false;
    }
}

function editProfile(profile: ProfileRow): void {
    editingId.value = profile.id;
    form.name = profile.name;
    form.slug = profile.slug;
    form.description = profile.description ?? '';
    form.is_active = profile.is_active;
    form.is_technician_profile = profile.is_technician_profile;
    form.skill_ids = toUniqueSortedIds(profile.skills.map((skill) => skill.id));
}

async function saveProfile(): Promise<void> {
    saving.value = true;

    try {
        const payload = {
            name: form.name,
            slug: form.slug,
            description: form.description,
            is_active: form.is_active,
            is_technician_profile: form.is_technician_profile,
        };

        const endpoint = isEditing.value
            ? `/api/v1/profiles/${editingId.value}`
            : '/api/v1/profiles';
        const method = isEditing.value ? 'PATCH' : 'POST';

        const response = await webApiFetch(endpoint, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            error(t('iam.profiles.errors.save'));

            return;
        }

        if (isEditing.value && editingId.value !== null) {
            const skillsResponse = await webApiFetch(
                `/api/v1/profiles/${editingId.value}/skills`,
                {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ skill_ids: form.skill_ids }),
                },
            );

            if (!skillsResponse.ok) {
                const payloadError = await skillsResponse
                    .json()
                    .catch(() => ({}));
                error(payloadError.message ?? t('iam.profiles.errors.skills'));

                return;
            }
        }

        const responsePayload = (await response.json().catch(() => null)) as {
            data?: { id?: number };
        } | null;
        const savedProfileId = isEditing.value
            ? editingId.value
            : (responsePayload?.data?.id ?? null);

        if (
            saveIntent.value === 'configure' &&
            savedProfileId !== null &&
            !isEditing.value
        ) {
            resetForm();
            router.visit(profileCapabilities(savedProfileId).url());
            return;
        }

        success(
            isEditing.value
                ? t('iam.profiles.toast.updated')
                : t('iam.profiles.toast.created'),
        );
        resetForm();
        await loadData();
    } catch {
        error(t('iam.profiles.errors.connection'));
    } finally {
        saving.value = false;
    }
}

function submitProfile(action: 'close' | 'configure'): void {
    saveIntent.value = action;
    void saveProfile();
}

async function deleteProfile(profileId: number): Promise<void> {
    try {
        const response = await webApiFetch(`/api/v1/profiles/${profileId}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            error(payload.message ?? t('iam.profiles.errors.delete'));

            return;
        }

        success(t('iam.profiles.toast.deleted'));

        if (editingId.value === profileId) {
            resetForm();
        }

        await loadData();
    } catch {
        error(t('iam.profiles.errors.deleteConnection'));
    }
}

onMounted(loadData);
</script>

<template>
    <Head :title="t('iam.profiles.headTitle')" />
    <Toast :toast="toast" />

    <div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <Heading
            :title="t('iam.profiles.title')"
            :description="t('iam.profiles.description')"
        />

        <Card class="p-4">
            <div class="mb-3 text-sm text-muted-foreground">
                {{ t('iam.profiles.note') }}
            </div>
            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link :href="usersIndex().url">
                        {{ t('iam.profiles.actions.goToUsers') }}
                    </Link>
                </Button>
                <Button
                    v-if="isEditing && editingId !== null"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="profileCapabilities(editingId).url">
                        {{ t('iam.profiles.actions.assignCapabilities') }}
                    </Link>
                </Button>
            </div>
        </Card>

        <Card class="p-4">
            <h3
                class="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{
                    isEditing
                        ? t('iam.profiles.form.editTitle')
                        : t('iam.profiles.form.createTitle')
                }}
            </h3>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>{{ t('iam.profiles.form.name') }}</Label>
                    <Input v-model="form.name" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('iam.profiles.form.slug') }}</Label>
                    <Input
                        v-model="form.slug"
                        :placeholder="t('iam.profiles.form.slugPlaceholder')"
                    />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label>{{ t('iam.profiles.form.description') }}</Label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        class="rounded-md border bg-background p-2 text-sm"
                    />
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <Checkbox
                        id="profile-is-active"
                        :checked="form.is_active"
                        @update:checked="
                            (value) => (form.is_active = Boolean(value))
                        "
                    />
                    <Label for="profile-is-active">
                        {{ t('iam.profiles.form.active') }}
                    </Label>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <Checkbox
                        id="profile-is-technician"
                        :checked="form.is_technician_profile"
                        @update:checked="
                            (value) =>
                                (form.is_technician_profile = Boolean(value))
                        "
                    />
                    <Label for="profile-is-technician">
                        {{ t('iam.profiles.form.technicianProfile') }}
                    </Label>
                </div>
            </div>

            <div class="mt-4 grid gap-6">
                <div class="grid gap-3">
                    <Label>{{ t('iam.profiles.skills.title') }}</Label>
                    <div
                        class="grid max-h-56 gap-2 overflow-auto rounded border p-3"
                    >
                        <label
                            v-for="skill in skills"
                            :key="skill.id"
                            class="flex items-center gap-3 text-sm"
                        >
                            <Checkbox
                                :id="`profile-skill-${skill.id}`"
                                :checked="form.skill_ids.includes(skill.id)"
                                @update:checked="
                                    () =>
                                        (form.skill_ids =
                                            form.skill_ids.includes(skill.id)
                                                ? form.skill_ids.filter(
                                                      (id) => id !== skill.id,
                                                  )
                                                : [...form.skill_ids, skill.id])
                                "
                            />
                            <Label :for="`profile-skill-${skill.id}`">
                                {{ skill.name }}
                            </Label>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <Button :disabled="saving" @click="submitProfile('close')">
                    {{
                        saving
                            ? t('iam.profiles.actions.saving')
                            : isEditing
                              ? t('iam.profiles.actions.update')
                              : t('iam.profiles.actions.create')
                    }}
                </Button>
                <Button
                    v-if="!isEditing"
                    variant="outline"
                    :disabled="saving"
                    @click="submitProfile('configure')"
                >
                    {{ t('iam.profiles.actions.createAndAssign') }}
                </Button>
                <Button variant="outline" @click="resetForm">
                    {{ t('iam.profiles.actions.cancel') }}
                </Button>
            </div>
        </Card>

        <Card class="p-4">
            <h3
                class="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{ t('iam.profiles.table.title') }}
            </h3>
            <div v-if="loading" class="text-sm text-muted-foreground">
                {{ t('iam.profiles.loading') }}
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">
                                {{ t('iam.profiles.table.profile') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.profiles.table.users') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.profiles.table.permissions') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.profiles.table.skills') }}
                            </th>
                            <th class="py-2">
                                {{ t('iam.profiles.table.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="profile in profiles"
                            :key="profile.id"
                            class="border-b"
                        >
                            <td class="py-2">{{ profile.name }}</td>
                            <td class="py-2">{{ profile.users_count }}</td>
                            <td class="py-2">
                                {{ profile.permissions.length }}
                            </td>
                            <td class="py-2">{{ profile.skills.length }}</td>
                            <td class="py-2">
                                <AppRowActions>
                                    <AppActionIconButton
                                        :action="Actions.edit"
                                        :label-key="'iam.profiles.actions.edit'"
                                        @click="editProfile(profile)"
                                    />
                                    <TooltipProvider :delay-duration="0">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    :aria-label="
                                                        t(
                                                            'iam.profiles.actions.assignCapabilities',
                                                        )
                                                    "
                                                    as-child
                                                >
                                                    <Link
                                                        :href="
                                                            profileCapabilities(
                                                                profile.id,
                                                            ).url
                                                        "
                                                    >
                                                        <Key class="size-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {{
                                                    t(
                                                        'iam.profiles.actions.assignCapabilities',
                                                    )
                                                }}
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                    <AppActionIconButton
                                        :action="Actions.delete"
                                        :label-key="'iam.profiles.actions.delete'"
                                        @click="deleteProfile(profile.id)"
                                    />
                                    <AppActionIconButton
                                        :action="Actions.more"
                                        :label-key="'iam.profiles.table.actions'"
                                        :tooltip="false"
                                        disabled
                                    />
                                </AppRowActions>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
