<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { BreadcrumbItem } from '@/types';
import { Actions } from '@/types/actions/action-map';
import AppActionButton from '@/components/AppActionButton.vue';
import Heading from '@/components/Heading.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { disable, enable, show } from '@/routes/two-factor';

type Props = {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('settings.twoFactor.breadcrumbsTitle'),
        href: show.url(),
    },
];

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('settings.twoFactor.headTitle')" />

        <h1 class="sr-only">{{ t('settings.twoFactor.srTitle') }}</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    :title="t('settings.twoFactor.headingTitle')"
                    :description="t('settings.twoFactor.headingDescription')"
                />

                <div
                    v-if="!twoFactorEnabled"
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <Badge variant="destructive">
                        {{ t('settings.twoFactor.status.disabled') }}
                    </Badge>

                    <p class="text-muted-foreground">
                        {{ t('settings.twoFactor.description.disabled') }}
                    </p>

                    <div>
                        <AppActionButton
                            v-if="hasSetupData"
                            :action="Actions.enable"
                            :label-key="'settings.twoFactor.actions.continueSetup'"
                            @click="showSetupModal = true"
                        />
                        <Form
                            v-else
                            v-bind="enable.form()"
                            @success="showSetupModal = true"
                            #default="{ processing }"
                        >
                            <AppActionButton
                                :action="Actions.enable"
                                :label-key="'settings.twoFactor.actions.enable'"
                                type="submit"
                                :disabled="processing"
                            />
                        </Form>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <Badge variant="default">
                        {{ t('settings.twoFactor.status.enabled') }}
                    </Badge>

                    <p class="text-muted-foreground">
                        {{ t('settings.twoFactor.description.enabled') }}
                    </p>

                    <TwoFactorRecoveryCodes />

                    <div class="relative inline">
                        <Form v-bind="disable.form()" #default="{ processing }">
                            <AppActionButton
                                :action="Actions.disable"
                                :label-key="'settings.twoFactor.actions.disable'"
                                type="submit"
                                :disabled="processing"
                            />
                        </Form>
                    </div>
                </div>

                <TwoFactorSetupModal
                    v-model:isOpen="showSetupModal"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
