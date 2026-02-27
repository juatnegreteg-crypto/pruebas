<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    searchLabel?: string;
    searchPlaceholder?: string;
    search?: string;
    perPage?: number;
    perPageLabel?: string;
    perPageOptions?: number[];
    summary?: string;
    class?: HTMLAttributes['class'];
};

const props = withDefaults(defineProps<Props>(), {
    perPageOptions: () => [10, 15, 25, 50, 100],
});

const emit = defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'update:perPage', value: number): void;
}>();

const searchValue = computed({
    get: () => props.search ?? '',
    set: (value) => emit('update:search', value),
});

const perPageSelection = computed({
    get: () => (props.perPage != null ? String(props.perPage) : ''),
    set: (value) => emit('update:perPage', Number(value)),
});
</script>

<template>
    <section :class="cn('space-y-6', props.class)">
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <Heading :title="props.title" :description="props.description" />

            <div class="flex w-full flex-col gap-3 lg:w-auto lg:items-end">
                <slot name="actions" />

                <div
                    class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-end"
                >
                    <slot name="search">
                        <div
                            v-if="props.searchLabel"
                            class="grid w-full gap-1 sm:max-w-sm"
                        >
                            <Label
                                class="text-xs font-medium text-muted-foreground"
                            >
                                {{ props.searchLabel }}
                            </Label>
                            <Input
                                v-model="searchValue"
                                :placeholder="props.searchPlaceholder"
                                :aria-label="props.searchLabel"
                            />
                        </div>
                    </slot>

                    <slot name="per-page">
                        <div
                            v-if="props.perPage != null"
                            class="flex items-center gap-2"
                        >
                            <Label
                                class="text-xs font-medium text-muted-foreground"
                            >
                                {{ props.perPageLabel }}
                            </Label>
                            <Select v-model="perPageSelection">
                                <SelectTrigger class="w-24">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in props.perPageOptions"
                                        :key="option"
                                        :value="String(option)"
                                    >
                                        {{ option }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </slot>
                </div>

                <slot name="summary">
                    <div
                        v-if="props.summary"
                        class="text-sm text-muted-foreground"
                    >
                        {{ props.summary }}
                    </div>
                </slot>
            </div>
        </div>

        <slot name="filters" />

        <div class="grid grid-cols-1 gap-3 lg:hidden">
            <slot name="cards" />
        </div>

        <div class="hidden lg:block">
            <slot />
        </div>

        <slot name="pagination" />
    </section>
</template>
