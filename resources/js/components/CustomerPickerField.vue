<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    type CustomerOption,
    useCustomerProvider,
} from '@/composables/useCustomerProvider';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        id?: string;
        modelValue: string;
        selectedLabel?: string | null;
        placeholder?: string;
        searchPlaceholder?: string;
        disabled?: boolean;
    }>(),
    {
        selectedLabel: null,
        placeholder: undefined,
        searchPlaceholder: undefined,
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'selected', customer: CustomerOption): void;
}>();

const { t } = useI18n();
const provider = useCustomerProvider();

const customers = ref<CustomerOption[]>([]);
const isLoading = ref(false);
const hasError = ref(false);
const isOpen = ref(false);
const query = ref('');
const searchInput = ref<{ $el: HTMLInputElement } | HTMLInputElement | null>(
    null,
);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let lastRequestedTerm = '';

async function searchCustomers(term: string): Promise<void> {
    isLoading.value = true;
    hasError.value = false;
    lastRequestedTerm = term;

    try {
        const results = await provider.searchCustomers({
            term,
            perPage: 15,
        });

        // Ignore out-of-order responses if the user typed quickly.
        if (lastRequestedTerm !== term) {
            return;
        }

        customers.value = results;
    } catch {
        hasError.value = true;
        customers.value = [];
    } finally {
        if (lastRequestedTerm === term) {
            isLoading.value = false;
        }
    }
}

const selectedCustomer = computed(() => {
    const selectedId = Number(props.modelValue);
    if (!Number.isFinite(selectedId)) {
        return null;
    }

    return customers.value.find((c) => c.id === selectedId) ?? null;
});

const selectedCustomerLabel = computed(() => {
    if (!props.modelValue) {
        return null;
    }

    return (
        selectedCustomer.value?.fullName ??
        props.selectedLabel ??
        `Cliente #${props.modelValue}`
    );
});

const fallbackPlaceholder = computed(
    () => props.placeholder ?? t('customers.picker.placeholder'),
);

const fallbackSearchPlaceholder = computed(
    () => props.searchPlaceholder ?? t('customers.picker.searchPlaceholder'),
);

const focusSearchInput = () => {
    const candidate = searchInput.value;
    if (!candidate) {
        return;
    }

    if (candidate instanceof HTMLInputElement) {
        candidate.focus();
        return;
    }

    candidate.$el?.focus();
};

function clearQuery() {
    query.value = '';
}

function selectCustomer(customer: CustomerOption) {
    emit('update:modelValue', String(customer.id));
    emit('selected', customer);
    clearQuery();
    isOpen.value = false;
}

watch(isOpen, async (open) => {
    if (open) {
        await searchCustomers(query.value);
        nextTick(() => focusSearchInput());
        return;
    }

    clearQuery();
});

watch(query, (value) => {
    if (!isOpen.value) {
        return;
    }

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        searchCustomers(value);
    }, 250);
});

onBeforeUnmount(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }
});
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                class="h-10 w-full justify-between px-3 font-normal"
                :disabled="disabled"
                :class="
                    cn(
                        !modelValue
                            ? 'text-muted-foreground'
                            : 'text-foreground',
                    )
                "
            >
                <span class="truncate text-left">
                    <template v-if="modelValue">
                        {{ selectedCustomerLabel }}
                    </template>
                    <template v-else>
                        {{ fallbackPlaceholder }}
                    </template>
                </span>
                <ChevronDown class="ml-2 size-4 shrink-0 opacity-60" />
            </Button>
        </PopoverTrigger>

        <PopoverContent
            align="start"
            class="w-[var(--reka-popover-trigger-width)] p-2"
        >
            <div class="grid gap-2">
                <Input
                    ref="searchInput"
                    v-model="query"
                    :placeholder="fallbackSearchPlaceholder"
                    :disabled="isLoading"
                />
            </div>

            <div v-if="isLoading" class="p-2 text-sm text-muted-foreground">
                {{ t('common.loading') }}
            </div>

            <div v-else-if="hasError" class="p-2 text-sm text-muted-foreground">
                {{ t('customers.picker.error') }}
            </div>

            <div v-else class="mt-2 grid max-h-72 gap-1 overflow-auto">
                <button
                    v-for="customer in customers"
                    :key="customer.id"
                    type="button"
                    class="rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    @click="selectCustomer(customer)"
                >
                    <div class="font-medium text-foreground">
                        {{ customer.fullName }}
                    </div>
                    <div
                        v-if="customer.documentNumber"
                        class="text-xs text-muted-foreground"
                    >
                        {{ customer.documentNumber }}
                    </div>
                </button>

                <div
                    v-if="customers.length === 0 && query.trim()"
                    class="p-2 text-sm text-muted-foreground"
                >
                    {{ t('common.noResults') }}
                </div>

                <div
                    v-if="customers.length === 0 && !query.trim()"
                    class="p-2 text-sm text-muted-foreground"
                >
                    No hay clientes disponibles.
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
