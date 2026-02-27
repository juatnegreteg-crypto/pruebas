<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CatalogItemTaxesForm, {
    type CatalogItemTaxForm,
} from '@/components/CatalogItemTaxesForm.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { index as productsIndex } from '@/routes/products';

export type ProductFormData = {
    name: string;
    description: string;
    observation: string;
    cost: string;
    price: string;
    currency: string;
    unit: string;
    isActive: boolean;
    taxes: CatalogItemTaxForm[];
};

const props = defineProps<{
    initialData?: Partial<ProductFormData>;
    action: string;
    method: 'post' | 'patch';
    submitLabel: string;
    onCancel?: () => void;
    unitOptions: string[];
    taxes: Array<{
        id: number;
        name: string;
        code: string;
        jurisdiction: string;
        rate: number;
    }>;
    defaultCurrency: string;
}>();

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'error'): void;
}>();

const form = useForm<ProductFormData>({
    name: props.initialData?.name ?? '',
    description: props.initialData?.description ?? '',
    observation: props.initialData?.observation ?? '',
    cost: props.initialData?.cost ?? '',
    price: props.initialData?.price ?? '',
    currency: props.initialData?.currency ?? props.defaultCurrency,
    unit: props.initialData?.unit ?? 'unit',
    isActive: props.initialData?.isActive ?? true,
    taxes: props.initialData?.taxes ?? [],
});

const isActiveModel = computed({
    get: () => form.isActive,
    set: (val: boolean) => {
        form.isActive = val;
    },
});

const { t } = useI18n();

function handleSubmit() {
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
                    <!-- Name -->
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="product-name">
                            {{ t('products.form.fields.name') }}
                        </Label>
                        <Input
                            id="product-name"
                            v-model="form.name"
                            type="text"
                            :placeholder="t('products.form.placeholders.name')"
                            :aria-invalid="!!form.errors.name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="product-description">
                            {{ t('products.form.fields.description') }}
                        </Label>
                        <Input
                            id="product-description"
                            v-model="form.description"
                            type="text"
                            :placeholder="
                                t('products.form.placeholders.description')
                            "
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <!-- Observation -->
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="product-observation">
                            {{ t('products.form.fields.observations') }}
                        </Label>
                        <Textarea
                            id="product-observation"
                            v-model="form.observation"
                            rows="3"
                            :placeholder="
                                t('products.form.placeholders.observations')
                            "
                            :aria-invalid="!!form.errors.observation"
                        />
                        <InputError :message="form.errors.observation" />
                    </div>

                    <!-- Cost -->
                    <div class="grid gap-2">
                        <Label for="product-cost">
                            {{ t('products.form.fields.cost') }}
                        </Label>
                        <Input
                            id="product-cost"
                            v-model="form.cost"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="t('products.form.placeholders.cost')"
                            :aria-invalid="!!form.errors.cost"
                        />
                        <InputError :message="form.errors.cost" />
                    </div>

                    <!-- Price -->
                    <div class="grid gap-2">
                        <Label for="product-price">
                            {{ t('products.form.fields.price') }}
                        </Label>
                        <Input
                            id="product-price"
                            v-model="form.price"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="t('products.form.placeholders.price')"
                            :aria-invalid="!!form.errors.price"
                        />
                        <InputError :message="form.errors.price" />
                    </div>

                    <!-- Currency -->
                    <div class="grid gap-2">
                        <Label for="product-currency">
                            {{ t('products.form.fields.currency') }}
                        </Label>
                        <Input
                            id="product-currency"
                            v-model="form.currency"
                            type="text"
                            maxlength="3"
                            :placeholder="
                                t('products.form.placeholders.currency')
                            "
                            :aria-invalid="!!form.errors.currency"
                        />
                        <InputError :message="form.errors.currency" />
                    </div>

                    <!-- Unit -->
                    <div class="grid gap-2">
                        <Label for="product-unit">
                            {{ t('catalog.unit.label') }}
                        </Label>
                        <Select
                            :model-value="form.unit"
                            @update:model-value="
                                (value) => (form.unit = String(value))
                            "
                        >
                            <SelectTrigger
                                id="product-unit"
                                :aria-invalid="!!form.errors.unit"
                            >
                                <SelectValue
                                    :placeholder="t('catalog.unit.placeholder')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in unitOptions"
                                    :key="option"
                                    :value="option"
                                >
                                    {{ t(`catalog.unit.values.${option}`) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.unit" />
                    </div>

                    <!-- Active -->
                    <div class="flex items-center gap-2 self-end pb-1">
                        <Checkbox
                            id="product-active"
                            v-model:checked="isActiveModel"
                        />
                        <Label for="product-active">
                            {{ t('products.form.fields.active') }}
                        </Label>
                    </div>
                </div>

                <CatalogItemTaxesForm
                    v-model="form.taxes"
                    :taxes="taxes"
                    :errors="form.errors"
                />
            </CardContent>

            <CardFooter class="flex-row justify-end gap-3 border-t">
                <Button variant="outline" as-child v-if="!props.onCancel">
                    <Link :href="productsIndex.url()">
                        {{ t('products.form.cancel') }}
                    </Link>
                </Button>
                <Button
                    variant="outline"
                    type="button"
                    v-else
                    @click="props.onCancel()"
                >
                    {{ t('products.form.cancel') }}
                </Button>

                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="cursor-pointer"
                >
                    <Spinner v-if="form.processing" class="mr-2" />
                    {{
                        form.processing
                            ? t('products.form.saving')
                            : submitLabel
                    }}
                </Button>
            </CardFooter>
        </Card>
    </form>
</template>
