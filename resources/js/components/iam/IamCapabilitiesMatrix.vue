<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

type CapabilityPermission = {
    id: number;
    name: string;
    required: boolean;
};

type PermissionCapability = {
    key: string;
    label: string;
    description?: string | null;
    order: number;
    permissions: CapabilityPermission[];
};

type CapabilityPermissionItem = {
    id: number;
    name: string;
    isRequired: boolean;
};

type CapabilityWarning = {
    capability_key: string;
    permission_name: string;
    required: boolean;
};

type CapabilitySubject = {
    type: 'user' | 'profile';
    id: number;
    name: string;
};

type PendingBreakageAction = {
    permissionId: number;
    affectedCapabilityKeys: string[];
};

const props = defineProps<{
    capabilities: PermissionCapability[];
    warnings?: CapabilityWarning[];
    modelValue: number[];
    subject?: CapabilitySubject;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number[]): void;
    (e: 'update:selectedCapabilityKeys', value: string[]): void;
}>();

const { t } = useI18n();

const pendingBreakageAction = ref<PendingBreakageAction | null>(null);
const showBreakageDialog = ref(false);

const localSelection = ref<number[]>([...(props.modelValue ?? [])]);
const sortedCapabilities = computed(() =>
    [...props.capabilities].sort((left, right) => left.order - right.order),
);
const capabilityByKey = computed<Map<string, PermissionCapability>>(
    () =>
        new Map(
            props.capabilities.map((capability) => [
                capability.key,
                capability,
            ]),
        ),
);
const warnings = computed(() => props.warnings ?? []);
const subjectLabel = computed(() => {
    if (!props.subject) {
        return null;
    }

    return t(`iam.capabilities.subject.${props.subject.type}`, {
        name: props.subject.name,
    });
});

function toUniqueSortedIds(ids: Array<number | string>): number[] {
    return Array.from(new Set(ids.map((id) => Number(id)))).sort(
        (a, b) => a - b,
    );
}

watch(
    () => props.modelValue,
    (next) => {
        localSelection.value = toUniqueSortedIds(next ?? []);
    },
    { immediate: true },
);

function updateSelection(ids: number[]): void {
    const nextSelection = toUniqueSortedIds(ids);
    localSelection.value = nextSelection;
    emit('update:modelValue', nextSelection);
}

function permissionIdsFromCapabilityKeys(keys: string[]): Set<number> {
    const ids = new Set<number>();

    for (const key of keys) {
        const capability = capabilityByKey.value.get(key);

        if (!capability) {
            continue;
        }

        for (const permission of capability.permissions) {
            ids.add(Number(permission.id));
        }
    }

    return ids;
}

const activeCapabilityMap = computed<Map<string, boolean>>(() => {
    const selected = new Set(localSelection.value ?? []);

    return new Map(
        props.capabilities.map((capability) => [
            capability.key,
            capability.permissions
                .filter((permission) => permission.required)
                .every((permission) => selected.has(permission.id)),
        ]),
    );
});

const activeCapabilityKeys = computed<string[]>(() =>
    props.capabilities
        .filter(
            (capability) =>
                activeCapabilityMap.value.get(capability.key) === true,
        )
        .map((capability) => capability.key),
);

watch(
    activeCapabilityKeys,
    (keys) => {
        emit('update:selectedCapabilityKeys', keys);
    },
    { immediate: true },
);

function isCapabilityActive(capability: PermissionCapability): boolean {
    return activeCapabilityMap.value.get(capability.key) === true;
}

function getCapabilityLabelByKey(key: string): string {
    return capabilityByKey.value.get(key)?.label ?? key;
}

function getCapabilityPermissionsSorted(
    capability: PermissionCapability,
): CapabilityPermissionItem[] {
    return capability.permissions
        .map((permission) => ({
            id: permission.id,
            name: permission.name,
            isRequired: permission.required,
        }))
        .sort((left, right) => left.name.localeCompare(right.name));
}

function getCapabilityBreakageByPermissionRemoval(
    permissionId: number | string,
): string[] {
    const targetId = Number(permissionId);
    const nextSelected = new Set(
        (localSelection.value ?? []).filter((id) => id !== targetId),
    );

    return props.capabilities
        .filter((capability) => {
            if (!isCapabilityActive(capability)) {
                return false;
            }

            return capability.permissions
                .filter((permission) => permission.required)
                .some((permission) => !nextSelected.has(Number(permission.id)));
        })
        .map((capability) => capability.key);
}

function requestBreakageConfirmation(action: PendingBreakageAction): void {
    pendingBreakageAction.value = action;
    showBreakageDialog.value = true;
}

function clearBreakageConfirmation(): void {
    pendingBreakageAction.value = null;
    showBreakageDialog.value = false;
}

function confirmBreakageAction(): void {
    const action = pendingBreakageAction.value;

    if (!action) {
        return;
    }

    updateSelection(
        (localSelection.value ?? []).filter((id) => id !== action.permissionId),
    );
    clearBreakageConfirmation();
}

function toggleAtomicPermission(permissionId: number | string): void {
    const targetId = Number(permissionId);
    const isSelected = (localSelection.value ?? []).includes(targetId);

    if (!isSelected) {
        updateSelection([...(localSelection.value ?? []), targetId]);

        return;
    }

    const affectedCapabilityKeys =
        getCapabilityBreakageByPermissionRemoval(permissionId);

    if (affectedCapabilityKeys.length > 0) {
        requestBreakageConfirmation({
            permissionId,
            affectedCapabilityKeys,
        });

        return;
    }

    updateSelection(
        (localSelection.value ?? []).filter((id) => id !== targetId),
    );
}

function activateCapabilityKeys(keys: string[]): void {
    console.log({ keys });
    const permissionIdsToAdd = permissionIdsFromCapabilityKeys(keys);

    console.log({ permissionIdsToAdd });

    updateSelection([...(localSelection.value ?? []), ...permissionIdsToAdd]);
}

function applyDeactivateCapabilityKeys(keys: string[]): void {
    console.log({ keys });

    const keySet = new Set(keys);
    const desiredActiveKeys = activeCapabilityKeys.value.filter(
        (key) => !keySet.has(key),
    );

    const keepPool = permissionIdsFromCapabilityKeys(desiredActiveKeys);
    const removalPool = permissionIdsFromCapabilityKeys(keys);

    updateSelection(
        (localSelection.value ?? []).filter((permissionId) => {
            if (!removalPool.has(permissionId)) {
                return true;
            }

            return keepPool.has(permissionId);
        }),
    );
}

function toggleCapabilityChecked(
    capability: PermissionCapability,
    checked: boolean | 'indeterminate',
): void {
    if (checked === true) {
        activateCapabilityKeys([capability.key]);
        return;
    }

    applyDeactivateCapabilityKeys([capability.key]);
}

function isPermissionChecked(permissionId: number | string): boolean {
    return (localSelection.value ?? []).includes(Number(permissionId));
}

function setPermissionChecked(
    permissionId: number | string,
    checked: boolean | 'indeterminate',
): void {
    if (checked === true) {
        updateSelection([
            ...(localSelection.value ?? []),
            Number(permissionId),
        ]);
        return;
    }

    toggleAtomicPermission(permissionId);
}
</script>

<template>
    <Dialog v-model:open="showBreakageDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    t('iam.capabilities.breakage.title')
                }}</DialogTitle>
                <DialogDescription>
                    {{ t('iam.capabilities.breakage.description') }}
                </DialogDescription>
            </DialogHeader>

            <ul class="grid gap-1 text-sm">
                <li
                    v-for="capabilityKey in pendingBreakageAction?.affectedCapabilityKeys ??
                    []"
                    :key="capabilityKey"
                    class="rounded border px-2 py-1"
                >
                    {{ getCapabilityLabelByKey(capabilityKey) }}
                </li>
            </ul>

            <DialogFooter>
                <Button variant="outline" @click="clearBreakageConfirmation">
                    {{ t('iam.capabilities.actions.cancel') }}
                </Button>
                <Button variant="destructive" @click="confirmBreakageAction">
                    {{ t('iam.capabilities.breakage.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <div class="grid gap-4">
        <Card
            v-if="warnings.length > 0"
            class="border-amber-300 bg-amber-50 p-4"
        >
            <h3 class="text-sm font-semibold text-amber-900">
                {{ t('iam.capabilities.warnings.title') }}
            </h3>
            <p class="mt-1 text-xs text-amber-800">
                {{ t('iam.capabilities.warnings.description') }}
            </p>
            <ul class="mt-3 grid gap-1 text-xs text-amber-900">
                <li
                    v-for="warning in warnings"
                    :key="`${warning.capability_key}-${warning.permission_name}`"
                    class="rounded border border-amber-300 bg-amber-100 px-2 py-1"
                >
                    {{ getCapabilityLabelByKey(warning.capability_key) }} ·
                    {{ warning.permission_name }}
                </li>
            </ul>
        </Card>

        <div class="grid gap-3">
            <div v-if="subjectLabel" class="text-sm text-muted-foreground">
                {{ subjectLabel }}
            </div>
            <p class="text-sm font-semibold text-foreground">
                {{ t('iam.capabilities.title') }}
            </p>

            <div class="columns-1 gap-3 md:columns-2 xl:columns-3">
                <Collapsible
                    v-for="capability in sortedCapabilities"
                    :key="capability.key"
                    v-slot="{ open }"
                    class="mb-3 grid break-inside-avoid gap-2 rounded border p-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <Checkbox
                                :id="`capability-${capability.key}`"
                                :model-value="isCapabilityActive(capability)"
                                @update:model-value="
                                    (checked) =>
                                        toggleCapabilityChecked(
                                            capability,
                                            checked,
                                        )
                                "
                            />
                            <Label
                                :for="`capability-${capability.key}`"
                                class="text-sm font-medium"
                            >
                                {{ capability.label }}
                            </Label>
                        </div>

                        <CollapsibleTrigger as-child>
                            <Button type="button" variant="ghost" size="sm">
                                {{
                                    open
                                        ? t('iam.capabilities.hidePermissions')
                                        : t('iam.capabilities.showPermissions')
                                }}
                                <ChevronDown
                                    class="ml-1 h-4 w-4 transition-transform"
                                    :class="open ? 'rotate-180' : ''"
                                />
                            </Button>
                        </CollapsibleTrigger>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ capability.description ?? t('common.notAvailable') }}
                    </p>

                    <CollapsibleContent>
                        <div
                            v-if="capability.permissions.length > 0"
                            class="grid gap-1 rounded bg-slate-50 p-2"
                        >
                            <div
                                class="text-[11px] font-semibold tracking-wide text-slate-700 uppercase"
                            >
                                {{ t('iam.capabilities.permissionsTitle') }}
                            </div>
                            <div
                                v-for="permission in getCapabilityPermissionsSorted(
                                    capability,
                                )"
                                :key="`${capability.key}-permission-${permission.id}`"
                                class="flex items-start gap-3"
                            >
                                <Checkbox
                                    :id="`permission-${capability.key}-${permission.id}`"
                                    :model-value="
                                        isPermissionChecked(permission.id)
                                    "
                                    :disabled="permission.isRequired"
                                    @update:model-value="
                                        (checked) =>
                                            setPermissionChecked(
                                                permission.id,
                                                checked,
                                            )
                                    "
                                />
                                <Label
                                    :for="`permission-${capability.key}-${permission.id}`"
                                    class="text-xs"
                                >
                                    {{ permission.name }}
                                </Label>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            </div>
        </div>
    </div>
</template>
