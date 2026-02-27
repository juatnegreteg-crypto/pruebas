<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import IamCapabilitiesMatrix from '@/components/iam/IamCapabilitiesMatrix.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { webApiFetch } from '@/composables/useWebApiFetch';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    capabilities as userCapabilities,
    index as usersIndex,
} from '@/routes/iam/users';

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

type UserSummary = {
    id: number;
    name: string;
    full_name: string | null;
    email: string;
};

const props = defineProps<{
    user: UserSummary;
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
    type: 'user' as const,
    id: props.user.id,
    name: props.user.full_name ?? props.user.name,
}));

function updateSelectedCapabilityKeys(keys: string[]): void {
    selectedCapabilityKeys.value = keys;
}

async function savePermissions(): Promise<void> {
    saving.value = true;

    try {
        const response = await webApiFetch(
            `/api/v1/users/${props.user.id}/permissions`,
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
            error(payload.message ?? t('iam.users.capabilities.errors.save'));
            return;
        }

        success(t('iam.users.capabilities.toast.saved'));
        router.visit(userCapabilities(props.user.id).url(), {
            preserveScroll: true,
        });
    } catch {
        error(t('iam.users.capabilities.errors.saveConnection'));
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Head :title="t('iam.users.capabilities.headTitle')" />
    <Toast :toast="toast" />

    <div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <Heading
            :title="t('iam.users.capabilities.title')"
            :description="t('iam.users.capabilities.description')"
        />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <Button as-child variant="outline" size="sm">
                <Link :href="usersIndex()">
                    {{ t('iam.users.capabilities.actions.back') }}
                </Link>
            </Button>
            <Button :disabled="saving" @click="savePermissions">
                {{
                    saving
                        ? t('iam.users.capabilities.actions.saving')
                        : t('iam.users.capabilities.actions.save')
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
