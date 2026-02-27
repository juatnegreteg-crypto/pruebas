<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { computed, useAttrs } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Action } from '@/types/actions/action';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

type Props = {
    action: Action;
    labelKey: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    as?: 'button' | 'link' | 'a';
    href?: string;
    tooltip?: boolean;
    class?: HTMLAttributes['class'];
};

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
    tooltip: true,
});

const { t } = useI18n();
const attrs = useAttrs();

const delegatedAttrs = computed(() => {
    const { href, ...rest } = attrs;
    void href;
    return rest;
});

const resolvedVariant = computed(() => props.variant ?? 'ghost');
const resolvedSize = computed(
    () => props.size ?? props.action.defaultSize ?? 'icon-sm',
);
const resolvedLabel = computed(
    () => (attrs['aria-label'] as string) ?? t(props.labelKey),
);
const resolvedClass = computed(() =>
    cn(props.class, props.action.destructive ? 'text-destructive' : null),
);
</script>

<template>
    <TooltipProvider v-if="tooltip" :delay-duration="0">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    v-if="as === 'button'"
                    v-bind="delegatedAttrs"
                    :variant="resolvedVariant"
                    :size="resolvedSize"
                    :class="resolvedClass"
                    :aria-label="resolvedLabel"
                >
                    <component :is="action.icon" />
                </Button>
                <Button
                    v-else
                    as-child
                    :variant="resolvedVariant"
                    :size="resolvedSize"
                    :class="resolvedClass"
                >
                    <Link
                        v-if="as === 'link'"
                        v-bind="delegatedAttrs"
                        :href="href ?? ''"
                        :aria-label="resolvedLabel"
                    >
                        <component :is="action.icon" />
                    </Link>
                    <a
                        v-else
                        v-bind="delegatedAttrs"
                        :href="href ?? ''"
                        :aria-label="resolvedLabel"
                    >
                        <component :is="action.icon" />
                    </a>
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {{ t(labelKey) }}
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>

    <Button
        v-else-if="as === 'button'"
        v-bind="delegatedAttrs"
        :variant="resolvedVariant"
        :size="resolvedSize"
        :class="resolvedClass"
        :aria-label="resolvedLabel"
    >
        <component :is="action.icon" />
    </Button>
    <Button
        v-else
        as-child
        :variant="resolvedVariant"
        :size="resolvedSize"
        :class="resolvedClass"
    >
        <Link
            v-if="as === 'link'"
            v-bind="delegatedAttrs"
            :href="href ?? ''"
            :aria-label="resolvedLabel"
        >
            <component :is="action.icon" />
        </Link>
        <a
            v-else
            v-bind="delegatedAttrs"
            :href="href ?? ''"
            :aria-label="resolvedLabel"
        >
            <component :is="action.icon" />
        </a>
    </Button>
</template>
