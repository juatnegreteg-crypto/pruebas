<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { useForm } from 'vee-validate';
import { computed, onMounted, reactive, ref, watch, type Component } from 'vue';
import { useI18n } from 'vue-i18n';
import { Actions } from '@/types/actions/action-map';
import AppActionIconButton from '@/components/AppActionIconButton.vue';
import AppDataTable from '@/components/AppDataTable.vue';
import AppDataTableShell from '@/components/AppDataTableShell.vue';
import AppRowActions from '@/components/AppRowActions.vue';
import Toast from '@/components/Toast.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ButtonGroup,
    ButtonGroupSeparator,
} from '@/components/ui/button-group';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useToast } from '@/composables/useToast';
import { webApiFetch } from '@/composables/useWebApiFetch';
import AppLayout from '@/layouts/AppLayout.vue';
import { capabilities as userCapabilities } from '@/routes/iam/users';

defineOptions({ layout: AppLayout });

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

type Option = { id: number; name: string; slug?: string };

type UserRow = {
    id: number;
    name: string;
    username: string;
    full_name: string | null;
    email: string;
    is_active: boolean;
    profile: Option | null;
    skills: Option[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type UsersMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
};

type Paginator<T> = {
    data: T[];
    links?: PaginationLink[];
    meta?: UsersMeta;
};

type UserFormValues = {
    username: string;
    full_name: string;
    email: string;
    party_id: number | null;
    profile_id: string;
    skill_ids: number[];
    is_active: boolean;
    assign_direct_skills: boolean;
};

type LinkCandidate = {
    party_id: number;
    display_name: string;
    primary_email: string | null;
    is_active: boolean;
    party_type: 'person' | 'organization';
    entity_types: string[];
};

const { toast, success, error } = useToast();
const { t } = useI18n();

const loading = ref(false);
const saving = ref(false);
const togglingUserId = ref<number | null>(null);
const search = ref('');
const perPage = ref(15);
const currentPage = ref(1);
const users = ref<UserRow[]>([]);
const usersMeta = ref<UsersMeta>({
    current_page: 1,
    from: null,
    last_page: 1,
    links: [],
    path: '',
    per_page: 15,
    to: null,
    total: 0,
});
const columns = computed(
    () =>
        [
            { key: 'username', header: t('iam.users.table.username') },
            { key: 'full_name', header: t('iam.users.table.fullName') },
            { key: 'email', header: t('iam.users.table.email') },
            { key: 'profile', header: t('iam.users.table.profile') },
            {
                key: 'status',
                header: t('iam.users.table.status'),
                align: 'center',
            },
            {
                key: 'actions',
                header: t('iam.users.table.actions'),
                align: 'right',
            },
        ] as const,
);
const slotNames = {
    fullName: 'cell(full_name)',
    email: 'cell(email)',
    profile: 'cell(profile)',
    status: 'cell(status)',
    actions: 'cell(actions)',
    empty: 'empty()',
} as const;
const profiles = ref<Option[]>([]);
const skills = ref<Option[]>([]);
const actionErrorMessages = ref<string[]>([]);
const emailCheck = reactive({
    status: 'idle' as 'idle' | 'checking' | 'conflict',
    message: '',
    lastEmail: '',
});
const nameCheck = reactive({
    status: 'idle' as 'idle' | 'checking',
    message: '',
    lastName: '',
});
const emailCandidates = ref<LinkCandidate[]>([]);
const nameCandidates = ref<LinkCandidate[]>([]);
const selectedLinkCandidate = ref<LinkCandidate | null>(null);
const applyingCandidate = ref(false);
const usernameCheck = reactive({
    status: 'idle' as 'idle' | 'checking' | 'conflict',
    message: '',
    lastUsername: '',
});
const formErrorMessage = ref<string | null>(null);
const isProfileComboboxOpen = ref(false);
const profileSearch = ref('');

const isFormOpen = ref(false);
const editingUserId = ref<number | null>(null);
const editingUserEmail = ref('');
const editingUsername = ref('');
const isActiveSelection = ref(true);
const {
    values,
    errors: validationErrors,
    handleSubmit,
    setValues,
    setFieldValue,
    setFieldError,
    resetForm: resetVeeForm,
    validateField,
} = useForm<UserFormValues>({
    initialValues: {
        username: '',
        full_name: '',
        email: '',
        party_id: null,
        profile_id: '',
        skill_ids: [],
        is_active: true,
        assign_direct_skills: false,
    },
    validationSchema: (values) => {
        const safeValues: UserFormValues = {
            username: values?.username ?? '',
            full_name: values?.full_name ?? '',
            email: values?.email ?? '',
            party_id: values?.party_id ?? null,
            profile_id: values?.profile_id ?? '',
            skill_ids: values?.skill_ids ?? [],
            is_active: values?.is_active ?? true,
            assign_direct_skills: values?.assign_direct_skills ?? false,
        };
        const errors: Partial<Record<keyof UserFormValues, string>> = {};

        if (isEditing.value && !safeValues.username.trim()) {
            errors.username = t('iam.users.validation.usernameRequired');
        }

        if (!safeValues.full_name.trim()) {
            errors.full_name = t('iam.users.validation.fullNameRequired');
        }

        if (!safeValues.email.trim()) {
            errors.email = t('iam.users.validation.emailRequired');
        } else if (!EMAIL_REGEX.test(safeValues.email.trim().toLowerCase())) {
            errors.email = t('iam.users.validation.emailInvalid');
        }

        return errors;
    },
});
const isEditing = computed(() => editingUserId.value !== null);
const paginationLinks = computed(() => usersMeta.value.links ?? []);
const isEmailConflict = computed(() => emailCheck.status === 'conflict');
const isCheckingEmail = computed(() => emailCheck.status === 'checking');
const isUsernameConflict = computed(() => usernameCheck.status === 'conflict');
const isCheckingUsername = computed(() => usernameCheck.status === 'checking');

const selectedProfileValue = computed({
    get: () => (values.profile_id === '' ? 'none' : values.profile_id),
    set: (value: string) => {
        setFieldValue('profile_id', value === 'none' ? '' : value);
    },
});
const shouldShowUsernameField = computed(() => isEditing.value);
const shouldShowLinkCandidates = computed(() => !isEditing.value);
const filteredProfiles = computed(() => {
    const term = profileSearch.value.trim().toLowerCase();

    if (!term) {
        return profiles.value;
    }

    return profiles.value.filter((profile) =>
        profile.name.toLowerCase().includes(term),
    );
});
const selectedProfileLabel = computed(() => {
    if (selectedProfileValue.value === 'none') {
        return t('iam.users.profile.none');
    }

    return (
        profiles.value.find(
            (profile) => String(profile.id) === selectedProfileValue.value,
        )?.name ?? t('iam.users.profile.none')
    );
});
const showDirectSkillsSection = computed(
    () => values.assign_direct_skills || values.skill_ids.length > 0,
);
type SaveAction = 'close' | 'configure';
const saveIntent = ref<SaveAction>('close');
const selectedSaveAction = ref<SaveAction>('close');
const SAVE_ACTION_OPTIONS: Record<
    SaveAction,
    { labelKey: string; icon: Component }
> = {
    close: {
        labelKey: 'iam.users.actions.create',
        icon: Actions.create.icon,
    },
    configure: {
        labelKey: 'iam.users.actions.createAndAssign',
        icon: Actions.permissions.icon,
    },
};
const SAVE_ACTION_KEYS: SaveAction[] = ['close', 'configure'];
const selectedSaveActionDetails = computed(
    () => SAVE_ACTION_OPTIONS[selectedSaveAction.value],
);
const selectedSaveActionLabel = computed(() =>
    t(selectedSaveActionDetails.value.labelKey),
);
const submitButtonLabel = computed(() => {
    if (saving.value) {
        return t('iam.users.actions.saving');
    }

    if (isEditing.value) {
        return t('iam.users.actions.saveChanges');
    }

    return selectedSaveActionLabel.value;
});
const isFormReadyToSubmit = computed(() => {
    const email = String(values.email ?? '')
        .trim()
        .toLowerCase();
    const fullName = String(values.full_name ?? '').trim();
    const username = String(values.username ?? '').trim();

    if (saving.value) {
        return false;
    }

    if (fullName === '' || email === '' || !EMAIL_REGEX.test(email)) {
        return false;
    }

    if (shouldShowUsernameField.value && username === '') {
        return false;
    }

    if (isCheckingEmail.value || isEmailConflict.value) {
        return false;
    }

    if (
        shouldShowUsernameField.value &&
        (isCheckingUsername.value || isUsernameConflict.value)
    ) {
        return false;
    }

    return true;
});

const isUserActiveChecked = computed(() => isActiveSelection.value);

function resetFormState(): void {
    editingUserId.value = null;
    resetVeeForm({
        values: {
            username: '',
            full_name: '',
            email: '',
            party_id: null,
            profile_id: '',
            skill_ids: [],
            is_active: true,
            assign_direct_skills: false,
        },
    });
    editingUserEmail.value = '';
    editingUsername.value = '';
    isActiveSelection.value = true;
    clearEmailCheck();
    clearNameCheck();
    clearUsernameCheck();
    clearLinkCandidateSelection();
    emailCandidates.value = [];
    nameCandidates.value = [];
    saveIntent.value = 'close';
    selectedSaveAction.value = 'close';
    formErrorMessage.value = null;
}

function openCreateDialog(): void {
    resetFormState();
    isFormOpen.value = true;
}

function handleFormOpenChange(isOpen: boolean): void {
    isFormOpen.value = isOpen;

    if (!isOpen) {
        resetFormState();
    }
}

watch(
    () => values.email,
    () => {
        clearEmailCheck();

        if (!applyingCandidate.value && shouldShowLinkCandidates.value) {
            clearLinkCandidateSelection();
        }
    },
);

watch(
    () => values.username,
    () => {
        clearUsernameCheck();
    },
);
watch(
    () => values.full_name,
    () => {
        clearNameCheck();

        if (!applyingCandidate.value && shouldShowLinkCandidates.value) {
            clearLinkCandidateSelection();
        }
    },
);
watch(isProfileComboboxOpen, (isOpen) => {
    if (!isOpen) {
        profileSearch.value = '';
    }
});

function clearEmailCheck(): void {
    emailCheck.status = 'idle';
    emailCheck.message = '';
    emailCheck.lastEmail = '';
}

function clearNameCheck(): void {
    nameCheck.status = 'idle';
    nameCheck.message = '';
    nameCheck.lastName = '';
}

function clearLinkCandidateSelection(): void {
    selectedLinkCandidate.value = null;
    setFieldValue('party_id', null);
}

function clearUsernameCheck(): void {
    usernameCheck.status = 'idle';
    usernameCheck.message = '';
    usernameCheck.lastUsername = '';
}

async function checkEmailConflict(): Promise<void> {
    const email = values.email.trim().toLowerCase();

    if (!EMAIL_REGEX.test(email)) {
        clearEmailCheck();
        emailCandidates.value = [];
        return;
    }

    if (email && email === emailCheck.lastEmail) {
        return;
    }

    if (isEditing.value && editingUserEmail.value === email) {
        clearEmailCheck();
        emailCheck.lastEmail = email;
        emailCandidates.value = [];
        return;
    }

    emailCheck.status = 'checking';
    emailCheck.lastEmail = email;

    try {
        const params = new URLSearchParams();
        params.set('email', email);

        if (isEditing.value && editingUserId.value !== null) {
            params.set('exclude_user_id', String(editingUserId.value));
        }

        const response = await webApiFetch(
            `/api/v1/users/link-candidates?${params.toString()}`,
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            emailCheck.status = 'idle';
            return;
        }

        const payload = await response.json();
        const existingUser = payload.data?.existing_user ?? null;
        emailCandidates.value = (payload.data?.candidates ??
            []) as LinkCandidate[];

        if (existingUser !== null) {
            emailCheck.status = 'conflict';
            emailCheck.message = t('iam.users.validation.valueInUse');
            clearLinkCandidateSelection();
            return;
        }

        emailCheck.status = 'idle';
        emailCheck.message = '';
    } catch {
        emailCheck.status = 'idle';
    }
}

async function handleEmailBlur(): Promise<void> {
    await validateField('email');
    await checkEmailConflict();
}

async function checkNameCandidates(): Promise<void> {
    const name = values.full_name.trim();

    if (!shouldShowLinkCandidates.value) {
        clearNameCheck();
        nameCandidates.value = [];
        return;
    }

    if (name.length < 2) {
        clearNameCheck();
        nameCandidates.value = [];
        return;
    }

    if (name === nameCheck.lastName) {
        return;
    }

    nameCheck.status = 'checking';
    nameCheck.lastName = name;

    try {
        const response = await webApiFetch(
            `/api/v1/users/link-candidates?name=${encodeURIComponent(name)}`,
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            nameCheck.status = 'idle';
            return;
        }

        const payload = await response.json();
        nameCandidates.value = (payload.data?.candidates ??
            []) as LinkCandidate[];
        nameCheck.status = 'idle';
    } catch {
        nameCheck.status = 'idle';
    }
}

async function handleNameBlur(): Promise<void> {
    await validateField('full_name');
    await checkNameCandidates();
}

function candidateTypesLabel(candidate: LinkCandidate): string {
    if (candidate.entity_types.length > 0) {
        return candidate.entity_types.join(` ${t('iam.users.candidate.and')} `);
    }

    return candidate.party_type;
}

function applyLinkCandidate(candidate: LinkCandidate): void {
    applyingCandidate.value = true;
    selectedLinkCandidate.value = candidate;
    setFieldValue('party_id', candidate.party_id);
    setFieldValue('full_name', candidate.display_name);
    setFieldValue('is_active', candidate.is_active);

    if (candidate.primary_email) {
        setFieldValue('email', candidate.primary_email);
    }

    applyingCandidate.value = false;
}

async function checkUsernameConflict(): Promise<void> {
    const username = values.username.trim();

    if (!shouldShowUsernameField.value) {
        clearUsernameCheck();
        return;
    }

    if (!username) {
        clearUsernameCheck();
        return;
    }

    if (username === usernameCheck.lastUsername) {
        return;
    }

    if (isEditing.value && editingUsername.value === username) {
        clearUsernameCheck();
        usernameCheck.lastUsername = username;
        return;
    }

    usernameCheck.status = 'checking';
    usernameCheck.lastUsername = username;

    try {
        const response = await webApiFetch(
            `/api/v1/users?username=${encodeURIComponent(username)}&per_page=1`,
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            usernameCheck.status = 'idle';
            return;
        }

        const payload = await response.json();
        const total =
            payload.meta?.total ?? payload.total ?? payload.data?.length ?? 0;

        if (total > 0) {
            usernameCheck.status = 'conflict';
            usernameCheck.message = t('iam.users.validation.valueInUse');
        } else {
            usernameCheck.status = 'idle';
            usernameCheck.message = '';
        }
    } catch {
        usernameCheck.status = 'idle';
    }
}

async function handleUsernameBlur(): Promise<void> {
    await validateField('username');
    await checkUsernameConflict();
}

function paginationLabel(label: string): string {
    const normalized = label
        .replace(/&laquo;|&raquo;|&lsaquo;|&rsaquo;/g, '')
        .trim();
    const lower = normalized.toLowerCase();
    const previousLabel = t('common.previous');
    const nextLabel = t('common.next');

    if (
        lower.includes('previous') ||
        lower.includes(previousLabel.toLowerCase())
    ) {
        return previousLabel;
    }

    if (lower.includes('next') || lower.includes(nextLabel.toLowerCase())) {
        return nextLabel;
    }

    return normalized;
}

async function fetchOptions(): Promise<void> {
    const [profilesResponse, skillsResponse] = await Promise.all([
        webApiFetch('/api/v1/profiles', {
            headers: { Accept: 'application/json' },
        }),
        webApiFetch('/api/v1/skills', {
            headers: { Accept: 'application/json' },
        }),
    ]);

    if (profilesResponse.ok) {
        const payload = await profilesResponse.json();
        profiles.value = payload.data ?? [];
    }

    if (skillsResponse.ok) {
        const payload = await skillsResponse.json();
        skills.value = payload.data ?? [];
    }
}

async function fetchUsers(page = currentPage.value): Promise<void> {
    loading.value = true;
    actionErrorMessages.value = [];

    try {
        const params = new URLSearchParams();

        if (search.value.trim() !== '') {
            params.set('search', search.value.trim());
        }

        params.set('per_page', String(perPage.value));
        params.set('page', String(page));

        const response = await webApiFetch(
            `/api/v1/users?${params.toString()}`,
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            error(t('iam.users.errors.load'));
            return;
        }

        const payload: Paginator<UserRow> = await response.json();

        users.value = payload.data ?? [];

        if (payload.meta) {
            usersMeta.value = {
                ...usersMeta.value,
                ...payload.meta,
                links: payload.meta.links ?? payload.links ?? [],
            };
            currentPage.value = payload.meta.current_page ?? page;
            perPage.value = payload.meta.per_page ?? perPage.value;
        } else {
            usersMeta.value = {
                ...usersMeta.value,
                current_page: page,
                links: payload.links ?? [],
                per_page: perPage.value,
                total: payload.data?.length ?? 0,
            };
            currentPage.value = page;
        }
    } catch {
        error(t('iam.users.errors.connection'));
    } finally {
        loading.value = false;
    }
}

function applySearch(): void {
    void fetchUsers(1);
}

function handlePerPageUpdate(value: number): void {
    perPage.value = value;
    applySearch();
}

function clearSearch(): void {
    search.value = '';
    void fetchUsers(1);
}

function toggleSkill(id: number): void {
    if (values.skill_ids.includes(id)) {
        setFieldValue(
            'skill_ids',
            values.skill_ids.filter((skillId) => skillId !== id),
        );
        return;
    }

    setFieldValue('skill_ids', [...values.skill_ids, id]);
}

function updateActiveSelection(checked: boolean): void {
    isActiveSelection.value = Boolean(checked);
    setFieldValue('is_active', isActiveSelection.value);
}

function selectProfile(value: string): void {
    selectedProfileValue.value = value;
    isProfileComboboxOpen.value = false;
}

function editUser(user: UserRow): void {
    editingUserEmail.value = user.email.toLowerCase();
    editingUsername.value = user.username;
    clearEmailCheck();
    clearNameCheck();
    clearUsernameCheck();
    clearLinkCandidateSelection();
    emailCandidates.value = [];
    nameCandidates.value = [];
    editingUserId.value = user.id;
    setValues({
        username: user.username,
        full_name: user.full_name ?? '',
        email: user.email,
        party_id: null,
        profile_id: user.profile ? String(user.profile.id) : '',
        skill_ids: user.skills.map((skill) => skill.id),
        is_active: Boolean(user.is_active),
        assign_direct_skills: user.skills.length > 0,
    });
    isActiveSelection.value = Boolean(user.is_active);
    setFieldValue('is_active', isActiveSelection.value);
    isFormOpen.value = true;
}

function selectSaveAction(action: 'close' | 'configure'): void {
    selectedSaveAction.value = action;
}

async function submitFromPrimaryAction(): Promise<void> {
    saveIntent.value = selectedSaveAction.value;
    await saveUser();
}

const saveUser = handleSubmit(async (values) => {
    saving.value = true;
    formErrorMessage.value = null;
    emailCheck.lastEmail = '';

    if (shouldShowUsernameField.value) {
        usernameCheck.lastUsername = '';
    }

    if (shouldShowUsernameField.value) {
        await checkUsernameConflict();
    }
    await checkEmailConflict();

    if (shouldShowUsernameField.value && usernameCheck.status === 'conflict') {
        formErrorMessage.value = usernameCheck.message;
        saving.value = false;
        return;
    }

    if (emailCheck.status === 'conflict') {
        formErrorMessage.value = emailCheck.message;
        saving.value = false;
        return;
    }

    try {
        const payload: Record<string, unknown> = {
            full_name: values.full_name.trim() || null,
            email: values.email,
            profile_id: values.profile_id ? Number(values.profile_id) : null,
            skill_ids: values.assign_direct_skills ? values.skill_ids : [],
            is_active: values.is_active,
        };

        if (!isEditing.value) {
            payload.party_id = values.party_id;
        }

        if (shouldShowUsernameField.value) {
            payload.username = values.username;
        }

        const endpoint = isEditing.value
            ? `/api/v1/users/${editingUserId.value}`
            : '/api/v1/users';

        const method = isEditing.value ? 'PATCH' : 'POST';

        const response = await webApiFetch(endpoint, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (response.status === 419) {
            formErrorMessage.value = t('iam.users.errors.sessionExpired');
            error(t('iam.users.errors.sessionExpired'));
            return;
        }

        if (response.status === 422) {
            const data = await response.json();
            const payloadErrors = data?.errors ?? {};

            if (
                Array.isArray(payloadErrors.username) &&
                payloadErrors.username[0]
            ) {
                setFieldError('username', payloadErrors.username[0]);
            }

            if (
                Array.isArray(payloadErrors.full_name) &&
                payloadErrors.full_name[0]
            ) {
                setFieldError('full_name', payloadErrors.full_name[0]);
            }

            if (Array.isArray(payloadErrors.email) && payloadErrors.email[0]) {
                setFieldError('email', payloadErrors.email[0]);
            }

            formErrorMessage.value =
                data.message ?? t('iam.users.errors.validation');
            return;
        }

        if (!response.ok) {
            error(t('iam.users.errors.save'));
            return;
        }

        let responsePayload: { data?: { id?: number } } | null = null;
        try {
            responsePayload = (await response.json()) as {
                data?: { id?: number };
            };
        } catch {
            responsePayload = null;
        }
        const savedUserId = isEditing.value
            ? editingUserId.value
            : (responsePayload?.data?.id ?? null);

        if (saveIntent.value === 'configure' && savedUserId !== null) {
            isFormOpen.value = false;
            resetFormState();
            try {
                router.visit(userCapabilities(savedUserId).url);
            } catch {
                window.location.href = `/iam/users/${savedUserId}/capabilities`;
            }
            return;
        }

        success(
            isEditing.value
                ? t('iam.users.toast.updated')
                : t('iam.users.toast.created'),
        );

        isFormOpen.value = false;
        resetFormState();
        await fetchUsers(currentPage.value);
    } catch {
        error(t('iam.users.errors.saveConnection'));
    } finally {
        saving.value = false;
    }
});

async function toggleStatus(user: UserRow): Promise<void> {
    togglingUserId.value = user.id;

    try {
        const response = await webApiFetch(`/api/v1/users/${user.id}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ is_active: !user.is_active }),
        });

        if (response.status === 419) {
            actionErrorMessages.value = [t('iam.users.errors.sessionExpired')];
            error(t('iam.users.errors.sessionExpired'));
            return;
        }

        if (!response.ok) {
            actionErrorMessages.value = [t('iam.users.errors.statusUpdate')];
            error(t('iam.users.errors.statusUpdate'));
            return;
        }

        success(t('iam.users.toast.statusUpdated'));
        await fetchUsers(currentPage.value);
    } catch {
        actionErrorMessages.value = [t('iam.users.errors.statusConnection')];
        error(t('iam.users.errors.statusConnection'));
    } finally {
        togglingUserId.value = null;
    }
}

function visitPage(link: PaginationLink): void {
    if (!link.url) {
        return;
    }

    const url = new URL(link.url, window.location.origin);
    const page = Number(url.searchParams.get('page') ?? '1');

    void fetchUsers(Number.isNaN(page) ? 1 : page);
}

onMounted(async () => {
    await Promise.all([fetchOptions(), fetchUsers(1)]);
});
</script>

<template>
    <Head :title="t('iam.users.headTitle')" />
    <Toast :toast="toast" />

    <Dialog :open="isFormOpen" @update:open="handleFormOpenChange">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{
                        isEditing
                            ? t('iam.users.form.editTitle')
                            : t('iam.users.form.createTitle')
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('iam.users.form.description') }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="saveUser">
                <FormField
                    v-if="shouldShowUsernameField"
                    v-slot="{ field, errors }"
                    name="username"
                >
                    <FormItem>
                        <FormLabel
                            :class="
                                (errors.length || isUsernameConflict) &&
                                'text-destructive'
                            "
                        >
                            {{ t('iam.users.form.fields.username') }}
                        </FormLabel>
                        <FormControl>
                            <Input
                                id="username"
                                v-bind="field"
                                :aria-invalid="
                                    errors.length || isUsernameConflict
                                "
                                :class="[
                                    'bg-background',
                                    (errors.length || isUsernameConflict) &&
                                        'border-destructive focus-visible:ring-destructive',
                                ]"
                                @blur="handleUsernameBlur"
                            />
                        </FormControl>
                        <div class="min-h-5">
                            <FormMessage class="text-destructive" />
                            <p
                                v-if="
                                    !validationErrors.username &&
                                    isUsernameConflict
                                "
                                class="text-sm text-destructive"
                            >
                                {{ usernameCheck.message }}
                            </p>
                            <p
                                v-else-if="
                                    !validationErrors.username &&
                                    isCheckingUsername
                                "
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('iam.users.form.checkingAvailability') }}
                            </p>
                        </div>
                    </FormItem>
                </FormField>

                <FormField v-slot="{ field, errors }" name="full_name">
                    <FormItem>
                        <FormLabel :class="errors.length && 'text-destructive'">
                            Nombre
                        </FormLabel>
                        <FormControl>
                            <Input
                                id="full_name"
                                v-bind="field"
                                :aria-invalid="!!errors.length"
                                :class="[
                                    'bg-background',
                                    errors.length &&
                                        'border-destructive focus-visible:ring-destructive',
                                ]"
                                @blur="handleNameBlur"
                            />
                        </FormControl>
                        <div class="min-h-5">
                            <FormMessage class="text-destructive" />
                            <p
                                v-if="
                                    shouldShowLinkCandidates &&
                                    !validationErrors.full_name &&
                                    nameCheck.status === 'checking'
                                "
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('iam.users.form.checkingMatches') }}
                            </p>
                        </div>
                        <div
                            v-if="
                                shouldShowLinkCandidates &&
                                nameCandidates.length > 0
                            "
                            class="mt-2 grid gap-2 rounded-md border p-2"
                        >
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                {{ t('iam.users.form.nameMatches') }}
                            </p>
                            <div
                                v-for="candidate in nameCandidates"
                                :key="`name-candidate-${candidate.party_id}`"
                                class="flex items-center justify-between gap-3 rounded-md border px-3 py-2"
                            >
                                <div class="grid gap-0.5">
                                    <p class="text-sm font-medium">
                                        {{ candidate.display_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            candidate.primary_email ??
                                            t('iam.users.form.noEmail')
                                        }}
                                        ·
                                        {{ candidateTypesLabel(candidate) }}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="applyLinkCandidate(candidate)"
                                >
                                    {{ t('iam.users.form.link') }}
                                </Button>
                            </div>
                        </div>
                    </FormItem>
                </FormField>

                <FormField v-slot="{ field, errors }" name="email">
                    <FormItem>
                        <FormLabel
                            :class="
                                (errors.length || isEmailConflict) &&
                                'text-destructive'
                            "
                        >
                            {{ t('iam.users.form.fields.email') }}
                        </FormLabel>
                        <FormControl>
                            <Input
                                id="email"
                                type="email"
                                v-bind="field"
                                :aria-invalid="errors.length || isEmailConflict"
                                :class="[
                                    'bg-background',
                                    (errors.length || isEmailConflict) &&
                                        'border-destructive focus-visible:ring-destructive',
                                ]"
                                @blur="handleEmailBlur"
                            />
                        </FormControl>
                        <div class="min-h-5">
                            <FormMessage class="text-destructive" />
                            <p
                                v-if="
                                    !validationErrors.email &&
                                    emailCheck.status === 'conflict'
                                "
                                class="text-sm text-destructive"
                            >
                                {{ emailCheck.message }}
                            </p>
                            <p
                                v-else-if="
                                    !validationErrors.email && isCheckingEmail
                                "
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('iam.users.form.checkingAvailability') }}
                            </p>
                        </div>
                        <div
                            v-if="
                                shouldShowLinkCandidates &&
                                emailCandidates.length > 0
                            "
                            class="mt-2 grid gap-2 rounded-md border p-2"
                        >
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                {{ t('iam.users.form.emailMatches') }}
                            </p>
                            <div
                                v-for="candidate in emailCandidates"
                                :key="`email-candidate-${candidate.party_id}`"
                                class="flex items-center justify-between gap-3 rounded-md border px-3 py-2"
                            >
                                <div class="grid gap-0.5">
                                    <p class="text-sm font-medium">
                                        {{ candidate.display_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            candidate.primary_email ??
                                            t('iam.users.form.noEmail')
                                        }}
                                        ·
                                        {{ candidateTypesLabel(candidate) }}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="applyLinkCandidate(candidate)"
                                >
                                    {{ t('iam.users.form.link') }}
                                </Button>
                            </div>
                        </div>
                    </FormItem>
                </FormField>

                <div
                    v-if="shouldShowLinkCandidates && selectedLinkCandidate"
                    class="rounded-md border bg-muted/30 px-3 py-2"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="grid gap-0.5">
                            <p class="text-sm font-medium">
                                {{ t('iam.users.form.linkedTo') }}
                                {{ selectedLinkCandidate.display_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    selectedLinkCandidate.primary_email ??
                                    t('iam.users.form.noEmail')
                                }}
                                ·
                                {{ candidateTypesLabel(selectedLinkCandidate) }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="clearLinkCandidateSelection"
                        >
                            {{ t('iam.users.form.unlink') }}
                        </Button>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="profile">
                        {{ t('iam.users.form.fields.profile') }}
                    </Label>
                    <Popover v-model:open="isProfileComboboxOpen">
                        <PopoverTrigger as-child>
                            <Button
                                id="profile"
                                type="button"
                                variant="outline"
                                class="h-10 w-full justify-between px-3 font-normal"
                            >
                                <span
                                    class="truncate text-left"
                                    :class="
                                        selectedProfileValue === 'none'
                                            ? 'text-muted-foreground'
                                            : 'text-foreground'
                                    "
                                >
                                    {{ selectedProfileLabel }}
                                </span>
                                <ChevronDown
                                    class="ml-2 size-4 shrink-0 opacity-60"
                                />
                            </Button>
                        </PopoverTrigger>

                        <PopoverContent
                            align="start"
                            class="w-[var(--reka-popover-trigger-width)] p-2"
                        >
                            <Input
                                v-model="profileSearch"
                                :placeholder="t('iam.users.profile.search')"
                                class="mb-2"
                            />
                            <div class="grid max-h-64 gap-1 overflow-auto">
                                <button
                                    type="button"
                                    class="rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    @click="selectProfile('none')"
                                >
                                    {{ t('iam.users.profile.none') }}
                                </button>
                                <button
                                    v-for="profile in filteredProfiles"
                                    :key="profile.id"
                                    type="button"
                                    class="rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    @click="selectProfile(String(profile.id))"
                                >
                                    {{ profile.name }}
                                </button>
                                <div
                                    v-if="filteredProfiles.length === 0"
                                    class="p-2 text-sm text-muted-foreground"
                                >
                                    {{ t('iam.users.profile.empty') }}
                                </div>
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center gap-3 py-1">
                        <Checkbox
                            id="isUserActive"
                            :checked="isUserActiveChecked"
                            @update:checked="updateActiveSelection"
                        />
                        <Label
                            for="isUserActive"
                            class="cursor-pointer font-normal"
                        >
                            {{ t('iam.users.form.active') }}
                        </Label>
                    </div>
                </div>
            </form>

            <div v-if="showDirectSkillsSection" class="mt-2 grid gap-2">
                <Label>{{ t('iam.users.form.directSkills') }}</Label>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="skill in skills"
                        :key="skill.id"
                        class="flex items-center gap-2 py-1 text-sm"
                    >
                        <Checkbox
                            :id="`skill-${skill.id}`"
                            :checked="values.skill_ids.includes(skill.id)"
                            @update:checked="toggleSkill(skill.id)"
                        />
                        <Label
                            :for="`skill-${skill.id}`"
                            class="cursor-pointer font-normal"
                        >
                            {{ skill.name }}
                        </Label>
                    </div>
                </div>
            </div>

            <p
                v-if="formErrorMessage"
                class="rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive"
            >
                {{ formErrorMessage }}
            </p>

            <div class="mt-2 flex items-center justify-end gap-2">
                <Button
                    variant="outline"
                    type="button"
                    @click="isFormOpen = false"
                >
                    {{ t('iam.users.actions.cancel') }}
                </Button>
                <Button
                    v-if="isEditing"
                    type="button"
                    class="flex items-center justify-center gap-2"
                    :disabled="!isFormReadyToSubmit"
                    @click="submitFromPrimaryAction"
                >
                    <span class="text-sm font-medium">
                        {{ submitButtonLabel }}
                    </span>
                </Button>
                <ButtonGroup v-else>
                    <Button
                        type="button"
                        class="flex items-center justify-center gap-2"
                        :disabled="!isFormReadyToSubmit"
                        @click="submitFromPrimaryAction"
                    >
                        <component
                            :is="selectedSaveActionDetails.icon"
                            class="size-4"
                        />
                        <span class="text-sm font-medium">
                            {{ submitButtonLabel }}
                        </span>
                    </Button>
                    <ButtonGroupSeparator />
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                size="icon"
                                class="border-l border-l-background/20"
                                :disabled="!isFormReadyToSubmit"
                                :aria-label="
                                    t('iam.users.actions.selectSaveAction')
                                "
                            >
                                <ChevronDown class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuGroup>
                                <DropdownMenuItem
                                    v-for="action in SAVE_ACTION_KEYS"
                                    :key="action"
                                    class="flex items-center gap-2"
                                    @click="selectSaveAction(action)"
                                >
                                    <span
                                        class="w-4 text-left text-muted-foreground"
                                        aria-hidden="true"
                                    >
                                        {{
                                            selectedSaveAction === action
                                                ? '✔'
                                                : ''
                                        }}
                                    </span>
                                    <component
                                        :is="SAVE_ACTION_OPTIONS[action].icon"
                                        class="size-4 text-muted-foreground"
                                    />
                                    {{
                                        t(SAVE_ACTION_OPTIONS[action].labelKey)
                                    }}
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </ButtonGroup>
            </div>
        </DialogContent>
    </Dialog>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <AppDataTableShell
            :title="t('iam.users.title')"
            :description="t('iam.users.description')"
            :search="search"
            :search-label="t('iam.users.search.label')"
            :search-placeholder="t('iam.users.search.placeholder')"
            :per-page="perPage"
            :per-page-label="t('iam.users.pagination.perPage')"
            @update:per-page="handlePerPageUpdate"
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-3">
                    <Button variant="outline" type="button" disabled>
                        {{ t('iam.users.actions.export') }}
                    </Button>
                    <Button variant="outline" type="button" disabled>
                        {{ t('iam.users.actions.import') }}
                    </Button>
                    <Button type="button" @click="openCreateDialog">{{
                        t('iam.users.actions.create')
                    }}</Button>
                </div>
            </template>

            <template #search>
                <div class="flex flex-wrap items-end gap-2">
                    <div class="w-full sm:max-w-md">
                        <Label for="searchUser" class="sr-only">
                            {{ t('iam.users.search.label') }}
                        </Label>
                        <Input
                            id="searchUser"
                            v-model="search"
                            :placeholder="t('iam.users.search.placeholder')"
                            @keyup.enter="applySearch"
                        />
                    </div>
                    <Button
                        variant="secondary"
                        size="sm"
                        type="button"
                        @click="applySearch"
                    >
                        {{ t('iam.users.search.submit') }}
                    </Button>
                    <Button
                        v-if="search"
                        variant="ghost"
                        size="sm"
                        type="button"
                        @click="clearSearch"
                    >
                        {{ t('iam.users.search.clear') }}
                    </Button>
                </div>
            </template>

            <template #summary>
                <span class="text-sm text-muted-foreground">
                    {{ t('iam.users.pagination.total') }}:
                    <Badge variant="secondary">{{ usersMeta.total }}</Badge>
                </span>
            </template>

            <template #filters>
                <div v-if="actionErrorMessages.length">
                    <p class="text-sm text-destructive">
                        {{ actionErrorMessages[0] }}
                    </p>
                </div>
            </template>

            <template #cards>
                <Card v-for="user in users" :key="user.id" class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold">
                                {{ user.full_name || user.name }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                        <AppRowActions>
                            <AppActionIconButton
                                :action="Actions.edit"
                                :label-key="'iam.users.actions.edit'"
                                @click="editUser(user)"
                            />
                            <AppActionIconButton
                                :action="Actions.permissions"
                                :label-key="'iam.users.actions.assignCapabilities'"
                                as="link"
                                :href="userCapabilities(user.id).url"
                            />
                            <AppActionIconButton
                                :action="
                                    user.is_active
                                        ? Actions.disable
                                        : Actions.enable
                                "
                                :label-key="
                                    user.is_active
                                        ? 'iam.users.actions.deactivate'
                                        : 'iam.users.actions.activate'
                                "
                                :disabled="togglingUserId === user.id"
                                @click="toggleStatus(user)"
                            />
                            <AppActionIconButton
                                :action="Actions.more"
                                :label-key="'iam.users.table.actions'"
                                :tooltip="false"
                                disabled
                            />
                        </AppRowActions>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            {{ t('iam.users.table.profile') }}
                        </span>
                        <span class="font-medium">
                            {{
                                user.profile?.label ?? t('common.notAvailable')
                            }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            {{ t('iam.users.table.status') }}
                        </span>
                        <Badge
                            class="border-transparent"
                            :class="
                                user.is_active
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-rose-100 text-rose-700'
                            "
                        >
                            {{
                                user.is_active
                                    ? t('iam.users.status.active')
                                    : t('iam.users.status.inactive')
                            }}
                        </Badge>
                    </div>
                </Card>

                <Card v-if="!users.length" class="p-6">
                    <p class="text-center text-sm text-muted-foreground">
                        {{ t('iam.users.empty') }}
                    </p>
                </Card>
            </template>

            <div class="mt-6">
                <div
                    v-if="loading"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    {{ t('iam.users.loading') }}
                </div>
                <AppDataTable
                    v-else
                    :rows="users"
                    :columns="columns"
                    row-key="id"
                >
                    <template v-slot:[slotNames.fullName]="{ row }">
                        <span class="text-muted-foreground">
                            {{
                                row.full_name ?? t('iam.users.table.noFullName')
                            }}
                        </span>
                    </template>

                    <template v-slot:[slotNames.email]="{ value }">
                        <span class="text-muted-foreground">{{ value }}</span>
                    </template>

                    <template v-slot:[slotNames.profile]="{ row }">
                        {{ row.profile?.name ?? t('iam.users.profile.none') }}
                    </template>

                    <template v-slot:[slotNames.status]="{ row }">
                        <Badge
                            class="border-transparent"
                            :class="
                                row.is_active
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-rose-100 text-rose-700'
                            "
                        >
                            {{
                                row.is_active
                                    ? t('iam.users.status.active')
                                    : t('iam.users.status.inactive')
                            }}
                        </Badge>
                    </template>

                    <template v-slot:[slotNames.actions]="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <AppActionIconButton
                                :action="Actions.edit"
                                :label-key="'iam.users.actions.edit'"
                                variant="ghost"
                                type="button"
                                @click="editUser(row)"
                            />
                            <AppActionIconButton
                                :action="Actions.permissions"
                                :label-key="'iam.users.actions.assignCapabilities'"
                                as="link"
                                :href="userCapabilities(row.id).url"
                            />
                            <AppActionIconButton
                                :action="
                                    row.is_active
                                        ? Actions.disable
                                        : Actions.enable
                                "
                                :label-key="
                                    row.is_active
                                        ? 'iam.users.actions.deactivate'
                                        : 'iam.users.actions.activate'
                                "
                                variant="ghost"
                                :disabled="togglingUserId === row.id"
                                @click="toggleStatus(row)"
                            />
                            <AppActionIconButton
                                :action="Actions.more"
                                :label-key="'iam.users.table.actions'"
                                variant="ghost"
                                :tooltip="false"
                                disabled
                            />
                        </div>
                    </template>

                    <template v-slot:[slotNames.empty]>
                        {{ t('iam.users.empty') }}
                        <button
                            v-if="search"
                            type="button"
                            class="ml-1 underline"
                            @click="clearSearch"
                        >
                            {{ t('iam.users.search.clear') }}
                        </button>
                    </template>
                </AppDataTable>
            </div>

            <template #pagination>
                <div
                    v-if="paginationLinks.length"
                    class="flex flex-wrap items-center gap-2"
                >
                    <template
                        v-for="(link, index) in paginationLinks"
                        :key="index"
                    >
                        <Button
                            v-if="link.url === null"
                            variant="outline"
                            size="sm"
                            disabled
                        >
                            {{ paginationLabel(link.label) }}
                        </Button>
                        <Button
                            v-else
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            type="button"
                            @click="visitPage(link)"
                        >
                            {{ paginationLabel(link.label) }}
                        </Button>
                    </template>
                </div>
            </template>
        </AppDataTableShell>
    </div>
</template>
