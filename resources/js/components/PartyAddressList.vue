<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

export type PartyAddressForm = {
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

type AddressTypeOption = string;

const props = defineProps<{
    addresses: PartyAddressForm[];
    addressTypes: AddressTypeOption[];
    defaultCountry: string;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    (e: 'update:addresses', value: PartyAddressForm[]): void;
}>();

const { t } = useI18n();

const hasPrimary = computed(() =>
    props.addresses.some((address) => address.isPrimary),
);

function addAddress(): void {
    const next = [...props.addresses];
    next.push({
        type: 'primary',
        isPrimary: !hasPrimary.value,
        street: '',
        complement: '',
        neighborhood: '',
        city: '',
        state: '',
        postalCode: '',
        country: props.defaultCountry,
        reference: '',
    });
    emit('update:addresses', next);
}

function removeAddress(index: number): void {
    const next = props.addresses.filter((_, i) => i !== index);
    emit('update:addresses', next);
}

function updateAddress(
    index: number,
    field: keyof PartyAddressForm,
    value: string | boolean,
): void {
    const next = props.addresses.map((address, i) => {
        if (i !== index) {
            return address;
        }

        return {
            ...address,
            [field]: value,
        };
    });

    emit('update:addresses', next);
}

function setPrimary(index: number, checked: boolean): void {
    const next = props.addresses.map((address, i) => ({
        ...address,
        isPrimary: i === index ? checked : false,
    }));

    emit('update:addresses', next);
}

function errorFor(index: number, field: string): string | undefined {
    return props.errors?.[`addresses.${index}.${field}`];
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-foreground">
                    {{ t('customers.addresses.title') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ t('customers.addresses.subtitle') }}
                </p>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addAddress"
            >
                {{ t('customers.addresses.actions.add') }}
            </Button>
        </div>

        <div
            v-if="addresses.length === 0"
            class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
        >
            {{ t('customers.addresses.empty') }}
        </div>

        <div v-for="(address, index) in addresses" :key="address.id ?? index">
            <Card>
                <CardContent class="space-y-4 pt-4">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="grid gap-2 sm:w-56">
                            <Label :for="`address-type-${index}`">
                                {{ t('customers.addresses.fields.type') }}
                            </Label>
                            <Select
                                :model-value="address.type"
                                @update:model-value="
                                    (value) =>
                                        updateAddress(index, 'type', value)
                                "
                            >
                                <SelectTrigger
                                    :id="`address-type-${index}`"
                                    :aria-invalid="!!errorFor(index, 'type')"
                                >
                                    <SelectValue
                                        :placeholder="
                                            t(
                                                'customers.addresses.placeholders.type',
                                            )
                                        "
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="type in addressTypes"
                                        :key="type"
                                        :value="type"
                                    >
                                        {{
                                            t(
                                                `customers.addressType.values.${type}`,
                                            )
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[errorFor(index, 'type')]" />
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                :id="`address-primary-${index}`"
                                :checked="address.isPrimary"
                                @update:checked="
                                    (value) => setPrimary(index, !!value)
                                "
                            />
                            <Label :for="`address-primary-${index}`">
                                {{ t('customers.addresses.fields.isPrimary') }}
                            </Label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="`address-street-${index}`">
                                {{ t('customers.addresses.fields.street') }}
                            </Label>
                            <Input
                                :id="`address-street-${index}`"
                                :model-value="address.street"
                                :aria-invalid="!!errorFor(index, 'street')"
                                :placeholder="
                                    t('customers.addresses.placeholders.street')
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'street',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError :errors="[errorFor(index, 'street')]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`address-complement-${index}`">
                                {{ t('customers.addresses.fields.complement') }}
                            </Label>
                            <Input
                                :id="`address-complement-${index}`"
                                :model-value="address.complement"
                                :aria-invalid="!!errorFor(index, 'complement')"
                                :placeholder="
                                    t(
                                        'customers.addresses.placeholders.complement',
                                    )
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'complement',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError
                                :errors="[errorFor(index, 'complement')]"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`address-neighborhood-${index}`">
                                {{
                                    t('customers.addresses.fields.neighborhood')
                                }}
                            </Label>
                            <Input
                                :id="`address-neighborhood-${index}`"
                                :model-value="address.neighborhood"
                                :aria-invalid="
                                    !!errorFor(index, 'neighborhood')
                                "
                                :placeholder="
                                    t(
                                        'customers.addresses.placeholders.neighborhood',
                                    )
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'neighborhood',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError
                                :errors="[errorFor(index, 'neighborhood')]"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`address-city-${index}`">
                                {{ t('customers.addresses.fields.city') }}
                            </Label>
                            <Input
                                :id="`address-city-${index}`"
                                :model-value="address.city"
                                :aria-invalid="!!errorFor(index, 'city')"
                                :placeholder="
                                    t('customers.addresses.placeholders.city')
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'city',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError :errors="[errorFor(index, 'city')]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`address-state-${index}`">
                                {{ t('customers.addresses.fields.state') }}
                            </Label>
                            <Input
                                :id="`address-state-${index}`"
                                :model-value="address.state"
                                :aria-invalid="!!errorFor(index, 'state')"
                                :placeholder="
                                    t('customers.addresses.placeholders.state')
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'state',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError :errors="[errorFor(index, 'state')]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`address-postal-${index}`">
                                {{ t('customers.addresses.fields.postalCode') }}
                            </Label>
                            <Input
                                :id="`address-postal-${index}`"
                                :model-value="address.postalCode"
                                :aria-invalid="!!errorFor(index, 'postalCode')"
                                :placeholder="
                                    t(
                                        'customers.addresses.placeholders.postalCode',
                                    )
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'postalCode',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError
                                :errors="[errorFor(index, 'postalCode')]"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`address-country-${index}`">
                                {{ t('customers.addresses.fields.country') }}
                            </Label>
                            <Input
                                :id="`address-country-${index}`"
                                :model-value="address.country"
                                :aria-invalid="!!errorFor(index, 'country')"
                                :placeholder="
                                    t(
                                        'customers.addresses.placeholders.country',
                                    )
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'country',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError
                                :errors="[errorFor(index, 'country')]"
                            />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label :for="`address-reference-${index}`">
                                {{ t('customers.addresses.fields.reference') }}
                            </Label>
                            <Input
                                :id="`address-reference-${index}`"
                                :model-value="address.reference"
                                :aria-invalid="!!errorFor(index, 'reference')"
                                :placeholder="
                                    t(
                                        'customers.addresses.placeholders.reference',
                                    )
                                "
                                @update:model-value="
                                    (value) =>
                                        updateAddress(
                                            index,
                                            'reference',
                                            String(value),
                                        )
                                "
                            />
                            <FieldError
                                :errors="[errorFor(index, 'reference')]"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeAddress(index)"
                        >
                            {{ t('customers.addresses.actions.remove') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
