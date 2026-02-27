<script setup lang="ts">
import {
    ArrowUpDown,
    ChevronDown,
    Search,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    type VehicleOption,
    type VehicleOptionCustomer,
    useVehicleProvider,
} from '@/composables/useVehicleProvider';
import { cn } from '@/lib/utils';

type Props = {
    id?: string;
    customer?: VehicleOptionCustomer | null;
    vehicle?: VehicleOption | null;
    resetNonce?: number;
    errorMessage?: string;
    label?: string;
    placeholder?: string;
    searchPlaceholder?: string;
    disabled?: boolean;
    showFilterButton?: boolean;
    showSortButton?: boolean;
    perPage?: number;
    query?: Record<string, string | number | boolean | null | undefined>;
};

type Emits = {
    (e: 'update:customer', value: VehicleOptionCustomer | null): void;
    (e: 'update:vehicle', value: VehicleOption | null): void;
    (e: 'error', message: string): void;
    (e: 'filter'): void;
    (e: 'sort'): void;
};

const props = withDefaults(defineProps<Props>(), {
    customer: null,
    vehicle: null,
    resetNonce: 0,
    errorMessage: undefined,
    label: undefined,
    placeholder: undefined,
    searchPlaceholder: undefined,
    disabled: false,
    showFilterButton: false,
    showSortButton: false,
    perPage: 10,
    query: undefined,
});
const emit = defineEmits<Emits>();
const { t } = useI18n();
const provider = useVehicleProvider();

const associationQuery = ref('');
const isAssociationOpen = ref(false);
const associationLoading = ref(false);
const vehicleResults = ref<VehicleOption[]>([]);
const associationSearchInput = ref<HTMLInputElement | null>(null);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function reportError() {
    emit('error', props.errorMessage ?? 'No se pudo buscar vehiculos.');
}

function resetInternal() {
    associationQuery.value = '';
    vehicleResults.value = [];
    isAssociationOpen.value = false;
    associationLoading.value = false;
}

function clearAssociation() {
    resetInternal();
    emit('update:customer', null);
    emit('update:vehicle', null);
}

function selectVehicle(vehicle: VehicleOption) {
    resetInternal();
    emit('update:vehicle', vehicle);
    emit('update:customer', vehicle.customer);
}

async function searchAssociations(term: string) {
    associationLoading.value = true;

    try {
        vehicleResults.value = await provider.searchVehicles({
            term,
            perPage: props.perPage,
            query: props.query,
        });
    } catch {
        reportError();
    } finally {
        associationLoading.value = false;
    }
}

watch(
    () => props.resetNonce,
    () => resetInternal(),
);

watch(isAssociationOpen, (open) => {
    if (open) {
        searchAssociations(associationQuery.value);
        nextTick(() => associationSearchInput.value?.focus());
    }
});

watch(associationQuery, (value) => {
    if (!isAssociationOpen.value) {
        return;
    }

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        searchAssociations(value);
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
    <div class="grid gap-2">
        <Label :for="`${props.id ?? 'vehicle-picker'}-search`">
            {{ label ?? t('common.search') }}
        </Label>

        <Popover v-model:open="isAssociationOpen">
            <PopoverTrigger as-child>
                <Button
                    :id="props.id ?? 'vehicle-picker-trigger'"
                    type="button"
                    variant="outline"
                    class="h-10 w-full justify-between px-3 font-normal"
                    :disabled="disabled"
                    :class="
                        cn(
                            !vehicle
                                ? 'text-muted-foreground'
                                : 'text-foreground',
                        )
                    "
                >
                    <span class="truncate text-left">
                        <template v-if="vehicle">
                            {{ vehicle.plate }}
                            <span class="text-muted-foreground">
                                {{
                                    ` · ${vehicle.make} ${vehicle.model} ${vehicle.year}`
                                }}
                            </span>
                            <span
                                v-if="vehicle.customer?.fullName"
                                class="text-muted-foreground"
                            >
                                {{ ` · ${vehicle.customer.fullName}` }}
                            </span>
                        </template>
                        <template v-else>
                            {{ placeholder ?? t('common.search') }}
                        </template>
                    </span>
                    <ChevronDown class="ml-2 size-4 shrink-0 opacity-60" />
                </Button>
            </PopoverTrigger>

            <PopoverContent
                align="start"
                class="w-(--reka-popover-trigger-width) p-2"
            >
                <div class="flex items-start gap-2">
                    <div class="relative flex-1">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            :id="`${props.id ?? 'vehicle-picker'}-search`"
                            ref="associationSearchInput"
                            v-model="associationQuery"
                            class="pr-9 pl-9"
                            :placeholder="
                                searchPlaceholder ?? t('common.search')
                            "
                            :disabled="disabled"
                        />
                        <button
                            v-if="associationQuery.trim().length > 0"
                            type="button"
                            class="absolute top-1/2 right-2 -translate-y-1/2 rounded-sm p-1 text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :aria-label="t('common.clear')"
                            @click="associationQuery = ''"
                        >
                            <X class="size-4" />
                        </button>
                    </div>

                    <Button
                        v-if="showFilterButton"
                        type="button"
                        variant="outline"
                        size="icon"
                        class="h-10 w-10"
                        :disabled="disabled"
                        aria-label="Filtros"
                        @click="emit('filter')"
                    >
                        <SlidersHorizontal class="size-4" />
                    </Button>

                    <Button
                        v-if="showSortButton"
                        type="button"
                        variant="outline"
                        size="icon"
                        class="h-10 w-10"
                        :disabled="disabled"
                        aria-label="Ordenar"
                        @click="emit('sort')"
                    >
                        <ArrowUpDown class="size-4" />
                    </Button>
                </div>

                <div
                    v-if="associationLoading"
                    class="p-2 text-sm text-muted-foreground"
                >
                    {{ t('common.loading') }}
                </div>

                <div v-else class="mt-2 grid max-h-72 gap-2 overflow-auto">
                    <div v-if="vehicleResults.length" class="grid gap-1">
                        <button
                            v-for="v in vehicleResults"
                            :key="`v-${v.id}`"
                            type="button"
                            class="rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            @click="selectVehicle(v)"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <div class="font-medium text-foreground">
                                        {{ v.plate }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ v.make }} {{ v.model }} {{ v.year }}
                                    </div>
                                </div>
                                <div
                                    v-if="v.customer"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ v.customer.fullName }}
                                </div>
                            </div>
                        </button>
                    </div>

                    <div
                        v-if="!vehicleResults.length"
                        class="p-2 text-sm text-muted-foreground"
                    >
                        {{ t('common.noResults') }}
                    </div>
                </div>
            </PopoverContent>
        </Popover>

        <div
            v-if="customer || vehicle"
            class="rounded-md border bg-muted/50 p-3"
        >
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="grid gap-1">
                    <div class="text-xs font-medium text-muted-foreground">
                        {{ t('quotes.form.selected') }}
                    </div>
                    <div class="text-sm">
                        <span class="font-medium text-foreground">
                            {{ customer?.fullName ?? t('common.notAvailable') }}
                        </span>
                        <span
                            v-if="vehicle?.plate"
                            class="text-muted-foreground"
                        >
                            {{ ` · ${vehicle.plate}` }}
                        </span>
                    </div>
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    type="button"
                    @click="clearAssociation"
                >
                    {{ t('common.clear') }}
                </Button>
            </div>
        </div>
    </div>
</template>
