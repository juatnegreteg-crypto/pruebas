<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import CustomerForm from '@/components/CustomerForm.vue';
import Heading from '@/components/Heading.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import customersRoutes from '@/routes/customers';

defineOptions({
    layout: AppLayout,
});

type DocumentTypeOption = string;
type AddressTypeOption = string;

const props = defineProps<{
    documentTypes: DocumentTypeOption[];
    addressTypes: AddressTypeOption[];
    defaultCountry: string;
}>();

const { toast, success: successToast, error: errorToast } = useToast();
const { t } = useI18n();

function onSuccess() {
    successToast(t('customers.create.toast.success'));
    router.visit(customersRoutes.index.url());
}

function onError() {
    errorToast(t('customers.create.toast.error'));
}
</script>

<template>
    <Head :title="t('customers.create.headTitle')" />

    <Toast :toast="toast" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                :title="t('customers.create.title')"
                :description="t('customers.create.description')"
            />

            <div class="flex items-center gap-3">
                <Button variant="outline" as-child>
                    <Link :href="customersRoutes.index()">
                        {{ t('customers.create.actions.back') }}
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Form -->
        <div class="mt-6">
            <CustomerForm
                :document-types="props.documentTypes"
                :address-types="props.addressTypes"
                :default-country="props.defaultCountry"
                :action="customersRoutes.store.url()"
                method="post"
                :submit-label="t('customers.create.actions.submit')"
                @success="onSuccess"
                @error="onError"
            />
        </div>
    </div>
</template>
