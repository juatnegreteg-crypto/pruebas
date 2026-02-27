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

type Customer = {
    id: number;
    fullName: string;
    email: string;
    documentType: string;
    documentNumber: string;
    phoneNumber: string | null;
    observations?: Array<{
        body: string;
        context: string;
        audienceTags: string[];
        createdAt?: string | null;
        createdBy?: number | null;
    }> | null;
    addresses: AddressForm[];
};

type DocumentTypeOption = string;
type AddressTypeOption = string;

type AddressForm = {
    id?: number | null;
    type: string;
    isPrimary: boolean;
    street: string;
    complement: string;
    neighborhood: string;
    city: string;
    state: string;
    postalCode: string;
    country: string;
    reference: string;
};

const props = defineProps<{
    customer: Customer;
    documentTypes: DocumentTypeOption[];
    addressTypes: AddressTypeOption[];
    defaultCountry: string;
}>();

const { toast, success: successToast, error: errorToast } = useToast();
const { t } = useI18n();

const initialData = {
    fullName: props.customer.fullName,
    email: props.customer.email,
    documentType: props.customer.documentType,
    documentNumber: props.customer.documentNumber,
    phoneNumber: props.customer.phoneNumber ?? '',
    observation: props.customer.observations?.[0]?.body ?? '',
    addresses: props.customer.addresses ?? [],
};

function onSuccess() {
    successToast(t('customers.edit.toast.success'));
    router.visit(customersRoutes.index.url());
}

function onError() {
    errorToast(t('customers.edit.toast.error'));
}
</script>

<template>
    <Head :title="t('customers.edit.headTitle')" />

    <Toast :toast="toast" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                :title="t('customers.edit.title')"
                :description="t('customers.edit.description')"
            />

            <div class="flex items-center gap-3">
                <Button variant="outline" as-child>
                    <Link :href="customersRoutes.index()">
                        {{ t('customers.edit.actions.back') }}
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Form -->
        <div class="mt-6">
            <CustomerForm
                :initial-data="initialData"
                :action="customersRoutes.update.url(props.customer.id)"
                :document-types="props.documentTypes"
                :address-types="props.addressTypes"
                :default-country="props.defaultCountry"
                method="put"
                :submit-label="t('customers.edit.actions.submit')"
                @success="onSuccess"
                @error="onError"
            />
        </div>
    </div>
</template>
