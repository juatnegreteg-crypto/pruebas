<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import IamCapabilitiesMatrix from '@/components/iam/IamCapabilitiesMatrix.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { webApiFetch } from '@/composables/useWebApiFetch';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as profilesIndex } from '@/routes/iam/profiles';

defineOptions({ layout: AppLayout });

type CapabilityPermission = {
    id: number;
    name: string;
    required: boolean;
};

type PermissionCapability = {
    key: string;
    label: string;
    description?: string | null;
    order: number;
    permissions: CapabilityPermission[];
};

type CapabilityWarning = {
    capability_key: string;
    permission_name: string;
    required: boolean;
};

type ProfileSummary = {
    id: number;
    name: string;
    description: string | null;
};

const props = defineProps<{
    profile: ProfileSummary;
    permissionIds: number[];
    capabilities: PermissionCapability[];
    warnings: CapabilityWarning[];
}>();

const { toast, success, error } = useToast();
const { t } = useI18n();

const saving = ref(false);
const selectedPermissionIds = ref<number[]>([...props.permissionIds]);
const selectedCapabilityKeys = ref<string[]>([]);
const subject = computed(() => ({
    type: 'profile' as const,
    id: props.profile.id,
    name: props.profile.name,
}));

function updateSelectedCapabilityKeys(keys: string[]): void {
    selectedCapabilityKeys.value = keys;
}

async function savePermissions(): Promise<void> {
    saving.value = true;

    try {
        const response = await webApiFetch(
            `/api/v1/profiles/${props.profile.id}/permissions`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    permission_ids: selectedPermissionIds.value,
                    selected_capability_keys: selectedCapabilityKeys.value,
                }),
            },
        );

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            error(
                payload.message ?? t('iam.profiles.capabilities.errors.save'),
            );
            return;
        }

        success(t('iam.profiles.capabilities.toast.saved'));
    } catch {
        error(t('iam.profiles.capabilities.errors.saveConnection'));
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Head :title="t('iam.profiles.capabilities.headTitle')" />
    <Toast :toast="toast" />

    <div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <Heading
            :title="t('iam.profiles.capabilities.title')"
            :description="t('iam.profiles.capabilities.description')"
        />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <Button as-child variant="outline" size="sm">
                <Link :href="profilesIndex()">
                    {{ t('iam.profiles.capabilities.actions.back') }}
                </Link>
            </Button>
            <Button :disabled="saving" @click="savePermissions">
                {{
                    saving
                        ? t('iam.profiles.capabilities.actions.saving')
                        : t('iam.profiles.capabilities.actions.save')
                }}
            </Button>
        </div>

        <IamCapabilitiesMatrix
            v-model="selectedPermissionIds"
            :capabilities="capabilities"
            :warnings="warnings"
            :subject="subject"
            @update:selectedCapabilityKeys="
                (keys) => updateSelectedCapabilityKeys(keys)
            "
        />
    </div>
</template>
