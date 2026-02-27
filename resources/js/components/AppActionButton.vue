<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { computed, useAttrs } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Action } from '@/types/actions/action';
import { Button, type ButtonVariants } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

defineOptions({ inheritAttrs: false });

type Props = {
    action: Action;
    labelKey: string;
    loadingLabelKey?: string;
    loading?: boolean;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    as?: 'button' | 'link' | 'a';
    href?: string;
    class?: HTMLAttributes['class'];
};

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
});

const { t } = useI18n();
const attrs = useAttrs();

const delegatedAttrs = computed(() => {
    const { href, ...rest } = attrs;
    void href;
    return rest;
});

const resolvedVariant = computed(() => props.variant ?? props.action.variant);
const resolvedSize = computed(() => props.size ?? 'default');
const resolvedLabel = computed(() =>
    props.loading && props.loadingLabelKey
        ? t(props.loadingLabelKey)
        : t(props.labelKey),
);
</script>

<template>
    <Button
        v-if="as === 'button'"
        v-bind="delegatedAttrs"
        :variant="resolvedVariant"
        :size="resolvedSize"
        :class="props.class"
        :aria-busy="loading || undefined"
    >
        <Spinner v-if="loading" class="size-4" />
        <component v-else :is="action.icon" />
        <span>{{ resolvedLabel }}</span>
    </Button>

    <Button
        v-else
        as-child
        :variant="resolvedVariant"
        :size="resolvedSize"
        :class="props.class"
    >
        <Link v-if="as === 'link'" v-bind="delegatedAttrs" :href="href ?? ''">
            <Spinner v-if="loading" class="size-4" />
            <component v-else :is="action.icon" />
            <span>{{ resolvedLabel }}</span>
        </Link>
        <a v-else v-bind="delegatedAttrs" :href="href ?? ''">
            <Spinner v-if="loading" class="size-4" />
            <component v-else :is="action.icon" />
            <span>{{ resolvedLabel }}</span>
        </a>
    </Button>
</template>
