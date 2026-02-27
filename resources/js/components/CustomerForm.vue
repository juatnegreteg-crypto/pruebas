<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PartyAddressList, {
    type PartyAddressForm,
} from '@/components/PartyAddressList.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import FieldError from '@/components/ui/field/FieldError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useCustomerValidation } from '@/composables/useCustomerValidation';
import customersRoutes from '@/routes/customers';

export type CustomerFormData = {
    fullName: string;
    email: string;
    documentType: string;
    documentNumber: string;
    phoneNumber: string;
    observation: string;
    addresses: PartyAddressForm[];
};

type DocumentTypeOption = string;
type AddressTypeOption = string;
type FormField =
    | 'fullName'
    | 'email'
    | 'documentType'
    | 'documentNumber'
    | 'phoneNumber';

const props = defineProps<{
    initialData?: Partial<CustomerFormData>;
    documentTypes: DocumentTypeOption[];
    addressTypes: AddressTypeOption[];
    defaultCountry: string;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
}>();

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'error'): void;
}>();

const form = useForm<CustomerFormData>({
    fullName: props.initialData?.fullName ?? '',
    email: props.initialData?.email ?? '',
    documentType: props.initialData?.documentType ?? '',
    documentNumber: props.initialData?.documentNumber ?? '',
    phoneNumber: props.initialData?.phoneNumber ?? '',
    observation: props.initialData?.observation ?? '',
    addresses: props.initialData?.addresses ?? [],
});

const formData = computed(() => ({
    fullName: form.fullName,
    email: form.email,
    documentType: form.documentType,
    documentNumber: form.documentNumber,
    phoneNumber: form.phoneNumber,
    observation: form.observation,
    addresses: form.addresses,
}));

const {
    errors: validationErrors,
    validateFullName,
    validateEmail,
    validateDocumentType,
    validateDocumentNumber,
    validatePhoneNumber,
    validateAll,
} = useCustomerValidation(formData);

const { t } = useI18n();

const addressErrors = computed(() => ({
    ...form.errors,
    ...validationErrors.value,
}));

const getError = (field: FormField): string | undefined => {
    return form.errors[field] || validationErrors.value[field];
};

function handleSubmit() {
    if (!validateAll()) {
        return;
    }

    form.email = form.email.toLowerCase().trim();
    form.documentNumber = form.documentNumber.trim();
    form.phoneNumber = form.phoneNumber ? form.phoneNumber.trim() : '';

    form.submit(props.method, props.action, {
        onSuccess: () => emit('success'),
        onError: () => emit('error'),
    });
}
</script>

<template>
    <form @submit.prevent="handleSubmit">
        <Card>
            <CardContent class="space-y-6 pt-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Full name -->
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="fullName">
                            {{ t('customers.form.fields.fullName') }}
                        </Label>
                        <Input
                            id="fullName"
                            v-model="form.fullName"
                            type="text"
                            :placeholder="
                                t('customers.form.placeholders.fullName')
                            "
                            :aria-invalid="!!getError('fullName')"
                            @blur="validateFullName"
                        />
                        <FieldError :errors="[getError('fullName')]" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">
                            {{ t('customers.form.fields.email') }}
                        </Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            :placeholder="
                                t('customers.form.placeholders.email')
                            "
                            :aria-invalid="!!getError('email')"
                            @blur="validateEmail"
                        />
                        <FieldError :errors="[getError('email')]" />
                    </div>

                    <!-- Phone number -->
                    <div class="grid gap-2">
                        <Label for="phoneNumber">
                            {{ t('customers.form.fields.phoneNumber') }}
                        </Label>
                        <Input
                            id="phoneNumber"
                            v-model="form.phoneNumber"
                            type="text"
                            :placeholder="
                                t('customers.form.placeholders.phoneNumber')
                            "
                            :aria-invalid="!!getError('phoneNumber')"
                            @blur="validatePhoneNumber"
                        />
                        <FieldError :errors="[getError('phoneNumber')]" />
                    </div>

                    <!-- Document type -->
                    <div class="grid gap-2">
                        <Label for="documentType">
                            {{ t('customers.form.fields.documentType') }}
                        </Label>
                        <Select
                            v-model="form.documentType"
                            @update:model-value="validateDocumentType"
                        >
                            <SelectTrigger
                                id="documentType"
                                :aria-invalid="!!getError('documentType')"
                            >
                                <SelectValue
                                    :placeholder="
                                        t(
                                            'customers.form.placeholders.documentType',
                                        )
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in documentTypes"
                                    :key="opt"
                                    :value="opt"
                                >
                                    {{
                                        t(
                                            `customers.documentType.values.${opt}`,
                                        )
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FieldError :errors="[getError('documentType')]" />
                    </div>

                    <!-- Document number -->
                    <div class="grid gap-2">
                        <Label for="documentNumber">
                            {{ t('customers.form.fields.documentNumber') }}
                        </Label>
                        <Input
                            id="documentNumber"
                            v-model="form.documentNumber"
                            type="text"
                            :placeholder="
                                t('customers.form.placeholders.documentNumber')
                            "
                            :aria-invalid="!!getError('documentNumber')"
                            @blur="validateDocumentNumber"
                        />
                        <FieldError :errors="[getError('documentNumber')]" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="observation">
                            {{ t('customers.form.fields.observations') }}
                        </Label>
                        <Textarea
                            id="observation"
                            v-model="form.observation"
                            :placeholder="
                                t('customers.form.placeholders.observations')
                            "
                            rows="3"
                            :aria-invalid="!!form.errors.observation"
                        />
                        <FieldError :errors="[form.errors.observation]" />
                    </div>
                </div>

                <PartyAddressList
                    v-model:addresses="form.addresses"
                    :address-types="props.addressTypes"
                    :default-country="props.defaultCountry"
                    :errors="addressErrors"
                />
            </CardContent>

            <!-- Actions -->
            <CardFooter class="flex-row justify-end gap-3 border-t">
                <Button variant="outline" as-child>
                    <Link :href="customersRoutes.index()">
                        {{ t('customers.form.cancel') }}
                    </Link>
                </Button>

                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="cursor-pointer"
                >
                    <Spinner v-if="form.processing" class="mr-2" />
                    {{
                        form.processing
                            ? t('customers.form.saving')
                            : submitLabel
                    }}
                </Button>
            </CardFooter>
        </Card>
    </form>
</template>
