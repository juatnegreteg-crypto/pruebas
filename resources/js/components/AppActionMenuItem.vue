<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { computed, useAttrs } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Action } from '@/types/actions/action';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

type Props = {
    action: Action;
    labelKey: string;
    class?: HTMLAttributes['class'];
    variant?: 'default' | 'destructive';
    as?: 'button' | 'link' | 'a';
    href?: string;
};

defineOptions({ inheritAttrs: false });

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

const resolvedVariant = computed(
    () =>
        props.variant ?? (props.action.destructive ? 'destructive' : 'default'),
);
</script>

<template>
    <DropdownMenuItem
        v-if="as === 'button'"
        v-bind="delegatedAttrs"
        :variant="resolvedVariant"
        :class="cn('flex items-center gap-2', props.class)"
    >
        <component :is="action.icon" />
        <span>{{ t(labelKey) }}</span>
    </DropdownMenuItem>

    <DropdownMenuItem
        v-else
        as-child
        :variant="resolvedVariant"
        :class="cn('flex items-center gap-2', props.class)"
    >
        <Link v-if="as === 'link'" v-bind="delegatedAttrs" :href="href ?? ''">
            <component :is="action.icon" />
            <span>{{ t(labelKey) }}</span>
        </Link>
        <a v-else v-bind="delegatedAttrs" :href="href ?? ''">
            <component :is="action.icon" />
            <span>{{ t(labelKey) }}</span>
        </a>
    </DropdownMenuItem>
</template>
