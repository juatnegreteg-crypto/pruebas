<script setup lang="ts">
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';

type ImportResult = {
    created: number;
    updated: number;
    failed: number;
    total_rows: number;
};

type ImportErrorItem = {
    attribute: string;
    messages: string[];
    values: Record<string, unknown>;
};

type ImportErrors = Record<number, ImportErrorItem[]>;

type ImportProcessedPayload = {
    queued: boolean;
    result: ImportResult | null;
    status: number;
    raw: Record<string, unknown>;
};

type QueuedImportStatus = 'queued' | 'processing' | 'completed' | 'failed';

type Props = {
    importEndpoint: string;
    templateEndpoint?: string | null;
    statusEndpointTemplate?: string | null;
    statusEndpointIdPlaceholder?: string;
    icon?: Component | null;
    iconClass?: string;
    title?: string;
    description?: string;
    fileLabel?: string;
    acceptedExtensions?: string[];
    acceptedMimeTypes?: string[];
    templateHelpText?: string;
    templateButtonText?: string;
    note?: string;
    progressLabel?: string;
    processingText?: string;
    errorTitle?: string;
    importButtonText?: string;
    importingButtonText?: string;
    cancelButtonText?: string;
    closeButtonText?: string;
    resultTitleSuccess?: string;
    resultTitleWithErrors?: string;
    resultLabels?: Partial<{
        total: string;
        created: string;
        updated: string;
        failed: string;
    }>;
    attributeLabels?: Record<string, string>;
    requiredFileMessage?: string;
    invalidFileMessage?: string;
    queuedStatusText?: string;
    processingStatusText?: string;
    completedStatusText?: string;
    queuePollingIntervalMs?: number;
    queuePollingMaxAttempts?: number;
};

const props = withDefaults(defineProps<Props>(), {
    templateEndpoint: null,
    statusEndpointTemplate: null,
    statusEndpointIdPlaceholder: '{id}',
    icon: null,
    iconClass: 'h-6 w-6 text-muted-foreground',
    title: 'Importar archivo',
    description:
        'Arrastre un archivo o selecciónelo desde su dispositivo para importarlo.',
    fileLabel: 'Archivo',
    acceptedExtensions: () => ['xls', 'xlsx', 'csv'],
    acceptedMimeTypes: () => [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'application/csv',
    ],
    templateHelpText: '¿Necesita un formato base?',
    templateButtonText: 'Descargar plantilla',
    note: '',
    progressLabel: 'Progreso',
    processingText: 'Procesando archivo...',
    errorTitle: 'Error al procesar el archivo',
    importButtonText: 'Importar',
    importingButtonText: 'Importando...',
    cancelButtonText: 'Cancelar',
    closeButtonText: 'Cerrar',
    resultTitleSuccess: 'Importación completada',
    resultTitleWithErrors: 'Importación completada con errores',
    resultLabels: () => ({
        total: 'Total procesados',
        created: 'Creados',
        updated: 'Actualizados',
        failed: 'Fallidos',
    }),
    attributeLabels: () => ({}),
    requiredFileMessage: 'Por favor seleccione un archivo',
    invalidFileMessage: '',
    queuedStatusText: 'Archivo recibido. En espera para procesamiento.',
    processingStatusText: 'Importación en proceso.',
    completedStatusText: 'Importación finalizada exitosamente.',
    queuePollingIntervalMs: 2000,
    queuePollingMaxAttempts: 180,
});

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{
    processed: [payload: ImportProcessedPayload];
}>();

const isImporting = ref(false);
const importProgress = ref(0);
const selectedFile = ref<File | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDraggingFile = ref(false);
const importError = ref<string | null>(null);
const importErrors = ref<ImportErrors>({});
const importResult = ref<ImportResult | null>(null);
const isPollingQueuedImport = ref(false);
const queuedImportStatus = ref<QueuedImportStatus | null>(null);
const queuedImportMessage = ref<string | null>(null);
let queuePollingToken = 0;

const isBusy = computed(
    () => isImporting.value || isPollingQueuedImport.value,
);

const acceptedExtensionsNormalized = computed(() =>
    props.acceptedExtensions
        .map((extension) => extension.toLowerCase().replace('.', ''))
        .filter((extension) => extension.length > 0),
);

const acceptAttribute = computed(() => {
    const extensions = acceptedExtensionsNormalized.value.map(
        (extension) => `.${extension}`,
    );

    return [...extensions, ...props.acceptedMimeTypes].join(',');
});

const allowedFormatsLabel = computed(() =>
    acceptedExtensionsNormalized.value
        .map((extension) => `.${extension}`)
        .join(', '),
);

const resolvedInvalidFileMessage = computed(() => {
    if (props.invalidFileMessage !== '') {
        return props.invalidFileMessage;
    }

    return `Por favor seleccione un archivo válido (${allowedFormatsLabel.value})`;
});

function formatErrorMessage(attribute: string, messages: string[]): string {
    if (!messages || messages.length === 0) {
        return `Error en ${props.attributeLabels[attribute] || attribute}`;
    }

    return messages[0];
}

function getErrorSummary(): string {
    const errorCount = Object.keys(importErrors.value).length;
    if (errorCount === 0) {
        return '';
    }

    const errorsByType: Record<string, number> = {};

    Object.values(importErrors.value).forEach((rowErrors) => {
        rowErrors.forEach((error) => {
            const label =
                props.attributeLabels[error.attribute] || error.attribute;
            errorsByType[label] = (errorsByType[label] || 0) + 1;
        });
    });

    return Object.entries(errorsByType)
        .map(
            ([label, count]) =>
                `${label}: ${count} error${count > 1 ? 'es' : ''}`,
        )
        .join(', ');
}

function parseImportResult(data: Record<string, unknown>): ImportResult {
    const resultData =
        data.result && typeof data.result === 'object'
            ? (data.result as Record<string, unknown>)
            : {};

    return {
        created: Number(resultData.created ?? 0),
        updated: Number(resultData.updated ?? 0),
        failed: Number(resultData.failed ?? 0),
        total_rows: Number(resultData.total_rows ?? 0),
    };
}

function parseImportErrors(data: Record<string, unknown>): ImportErrors {
    const resultData =
        data.result && typeof data.result === 'object'
            ? (data.result as Record<string, unknown>)
            : {};

    if (resultData.errors && typeof resultData.errors === 'object') {
        return resultData.errors as ImportErrors;
    }

    return {};
}

function resolveStatusEndpoint(importId: number): string | null {
    if (!props.statusEndpointTemplate) {
        return null;
    }

    const placeholderCandidates = [
        props.statusEndpointIdPlaceholder,
        '{id}',
        ':id',
    ].filter((placeholder, index, list) => list.indexOf(placeholder) === index);

    for (const placeholder of placeholderCandidates) {
        if (props.statusEndpointTemplate.includes(placeholder)) {
            return props.statusEndpointTemplate.replace(
                placeholder,
                String(importId),
            );
        }
    }

    return null;
}

function defaultQueueMessage(status: QueuedImportStatus): string {
    if (status === 'queued') {
        return props.queuedStatusText;
    }

    if (status === 'processing') {
        return props.processingStatusText;
    }

    return props.completedStatusText;
}

async function wait(ms: number): Promise<void> {
    await new Promise((resolve) => setTimeout(resolve, ms));
}

async function trackQueuedImport(
    importId: number,
    initialMessage: string | null,
): Promise<void> {
    const statusEndpoint = resolveStatusEndpoint(importId);
    if (!statusEndpoint) {
        queuedImportStatus.value = null;
        queuedImportMessage.value = null;
        setDialogOpen(false);
        return;
    }

    const pollingToken = ++queuePollingToken;
    isPollingQueuedImport.value = true;
    queuedImportStatus.value = 'queued';
    queuedImportMessage.value = initialMessage || defaultQueueMessage('queued');
    importProgress.value = 100;

    for (let attempt = 0; attempt < props.queuePollingMaxAttempts; attempt++) {
        if (pollingToken !== queuePollingToken || !open.value) {
            return;
        }

        if (attempt > 0) {
            await wait(props.queuePollingIntervalMs);
        }

        if (pollingToken !== queuePollingToken || !open.value) {
            return;
        }

        try {
            const response = await fetch(statusEndpoint, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });
            const data = (await response.json().catch(() => ({}))) as Record<
                string,
                unknown
            >;

            if (!response.ok) {
                const message =
                    typeof data.message === 'string'
                        ? data.message
                        : 'No fue posible consultar el estado de la importación.';
                importError.value = message;
                queuedImportStatus.value = 'failed';
                queuedImportMessage.value = null;
                isPollingQueuedImport.value = false;
                return;
            }

            const status =
                typeof data.status === 'string'
                    ? (data.status as QueuedImportStatus)
                    : null;
            const message =
                typeof data.message === 'string' ? data.message : null;

            if (status === 'queued' || status === 'processing') {
                queuedImportStatus.value = status;
                queuedImportMessage.value = message || defaultQueueMessage(status);
                continue;
            }

            if (status === 'failed') {
                const error =
                    typeof data.error === 'string' && data.error !== ''
                        ? data.error
                        : message || 'La importación finalizó con errores.';
                importError.value = error;
                queuedImportStatus.value = 'failed';
                queuedImportMessage.value = null;
                isPollingQueuedImport.value = false;
                return;
            }

            if (status === 'completed') {
                const parsedResult = parseImportResult(data);

                importResult.value = parsedResult;
                importErrors.value = parseImportErrors(data);
                queuedImportStatus.value = 'completed';
                queuedImportMessage.value =
                    message || defaultQueueMessage('completed');
                isPollingQueuedImport.value = false;

                emit('processed', {
                    queued: false,
                    result: parsedResult,
                    status: response.status,
                    raw: data,
                });

                return;
            }
        } catch (error: unknown) {
            if (error instanceof Error && error.message !== '') {
                importError.value = error.message;
            } else {
                importError.value =
                    'No fue posible consultar el estado de la importación.';
            }

            queuedImportStatus.value = 'failed';
            queuedImportMessage.value = null;
            isPollingQueuedImport.value = false;

            return;
        }
    }

    queuedImportStatus.value = 'processing';
    queuedImportMessage.value =
        'La importación continúa en segundo plano. Puede cerrar esta ventana y revisar el listado luego.';
    isPollingQueuedImport.value = false;
}

function resetImportState(): void {
    queuePollingToken += 1;
    selectedFile.value = null;
    isDraggingFile.value = false;
    importProgress.value = 0;
    importError.value = null;
    importErrors.value = {};
    importResult.value = null;
    isPollingQueuedImport.value = false;
    queuedImportStatus.value = null;
    queuedImportMessage.value = null;

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function setDialogOpen(value: boolean): void {
    open.value = value;

    if (!value) {
        resetImportState();
    }
}

function setSelectedFile(file: File | null): void {
    if (!file) {
        selectedFile.value = null;
        return;
    }

    const isValidMime =
        props.acceptedMimeTypes.length === 0 ||
        props.acceptedMimeTypes.includes(file.type);

    const fileName = file.name.toLowerCase();
    const isValidExtension = acceptedExtensionsNormalized.value.some(
        (extension) =>
            fileName.endsWith(`.${extension}`) ||
            fileName.endsWith(`.${extension.toUpperCase()}`),
    );

    if (!isValidMime && !isValidExtension) {
        importError.value = resolvedInvalidFileMessage.value;
        selectedFile.value = null;

        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }

        return;
    }

    selectedFile.value = file;
    importError.value = null;
}

function onFileSelected(event: Event): void {
    const target = event.target as HTMLInputElement;
    setSelectedFile(target.files?.[0] ?? null);
}

function openFilePicker(): void {
    if (!isBusy.value) {
        fileInputRef.value?.click();
    }
}

function onFileDragOver(): void {
    if (!isBusy.value) {
        isDraggingFile.value = true;
    }
}

function onFileDragLeave(): void {
    isDraggingFile.value = false;
}

function onFileDrop(event: DragEvent): void {
    event.preventDefault();
    isDraggingFile.value = false;

    if (isBusy.value) {
        return;
    }

    setSelectedFile(event.dataTransfer?.files?.[0] ?? null);
}

async function performImport(): Promise<void> {
    if (!selectedFile.value) {
        importError.value = props.requiredFileMessage;
        return;
    }

    isImporting.value = true;
    importError.value = null;
    importErrors.value = {};
    importResult.value = null;
    queuedImportStatus.value = null;
    queuedImportMessage.value = null;
    importProgress.value = 10;

    let progressInterval: ReturnType<typeof setInterval> | null = null;

    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);

        progressInterval = setInterval(() => {
            if (importProgress.value < 90) {
                importProgress.value += 10;
            }
        }, 300);

        const response = await fetch(props.importEndpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
                Accept: 'application/json',
            },
        });

        importProgress.value = 100;

        const data = (await response.json().catch(() => ({}))) as Record<
            string,
            unknown
        >;

        if (response.status === 202 || data.queued === true) {
            emit('processed', {
                queued: true,
                result: null,
                status: response.status,
                raw: data,
            });

            const importId = Number(data.import_id ?? 0);
            const queueMessage =
                typeof data.message === 'string' ? data.message : null;

            if (response.ok && importId > 0 && props.statusEndpointTemplate) {
                void trackQueuedImport(importId, queueMessage);
            } else {
                setDialogOpen(false);
            }

            return;
        }

        if (!response.ok) {
            const detailMessage =
                typeof data.detail === 'string' ? data.detail : null;
            const message =
                typeof data.message === 'string' ? data.message : null;
            const title = typeof data.title === 'string' ? data.title : null;

            importError.value =
                detailMessage ||
                message ||
                title ||
                'Error al procesar el archivo';
            importProgress.value = 0;
            return;
        }

        const parsedResult = parseImportResult(data);

        importResult.value = parsedResult;
        importErrors.value = parseImportErrors(data);

        emit('processed', {
            queued: false,
            result: parsedResult,
            status: response.status,
            raw: data,
        });
    } catch (error: unknown) {
        importProgress.value = 0;

        if (error instanceof Error) {
            importError.value =
                error.message ||
                'Ocurrió un error al importar el archivo. Por favor intente nuevamente.';
            return;
        }

        importError.value =
            'Ocurrió un error al importar el archivo. Por favor intente nuevamente.';
    } finally {
        if (progressInterval) {
            clearInterval(progressInterval);
        }

        isImporting.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="setDialogOpen">
        <DialogContent
            class="grid-rows-[auto_minmax(0,1fr)_auto] max-h-[90vh] overflow-hidden sm:max-h-[85vh]"
        >
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div class="min-h-0 space-y-4 overflow-y-auto py-1 pr-1">
                <div class="space-y-2">
                    <Label for="file-upload">{{ fileLabel }}</Label>
                    <input
                        id="file-upload"
                        ref="fileInputRef"
                        type="file"
                        class="sr-only"
                        :accept="acceptAttribute"
                        @change="onFileSelected"
                        :disabled="isBusy"
                    />
                    <div
                        role="button"
                        tabindex="0"
                        class="rounded-md border border-dashed p-6 transition-colors"
                        :class="[
                            isDraggingFile
                                ? 'border-primary bg-primary/5'
                                : 'border-border bg-muted/20',
                            isBusy
                                ? 'cursor-not-allowed opacity-60'
                                : 'cursor-pointer',
                        ]"
                        @click="openFilePicker"
                        @keydown.enter.prevent="openFilePicker"
                        @keydown.space.prevent="openFilePicker"
                        @dragover.prevent="onFileDragOver"
                        @dragleave.prevent="onFileDragLeave"
                        @drop.prevent="onFileDrop"
                        :aria-disabled="isBusy"
                    >
                        <div class="flex flex-col items-center gap-2">
                            <component
                                v-if="icon"
                                :is="icon"
                                :class="iconClass"
                            />
                            <slot v-else name="icon" />
                            <p class="text-sm font-medium">
                                Arrastre y suelte su archivo aquí
                            </p>
                            <p class="text-xs text-muted-foreground">
                                o haga clic para abrir el explorador de archivos
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Formatos permitidos: {{ allowedFormatsLabel }}
                            </p>
                        </div>
                    </div>
                    <Input
                        v-if="selectedFile"
                        :model-value="selectedFile.name"
                        readonly
                        disabled
                    />
                </div>

                <div
                    v-if="templateEndpoint"
                    class="rounded-md border bg-muted/30 p-3"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ templateHelpText }}
                    </p>
                    <Button
                        as-child
                        variant="outline"
                        class="mt-2 border-[1px] border-black"
                    >
                        <a :href="templateEndpoint">
                            {{ templateButtonText }}
                        </a>
                    </Button>
                </div>

                <div v-if="isImporting" class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{
                            progressLabel
                        }}</span>
                        <span class="font-medium">{{ importProgress }}%</span>
                    </div>
                    <Progress :model-value="importProgress" class="h-2" />
                    <div class="flex items-center justify-center gap-2 py-2">
                        <Spinner class="h-4 w-4" />
                        <span class="text-sm text-muted-foreground">
                            {{ processingText }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="isPollingQueuedImport && queuedImportStatus"
                    class="space-y-2 rounded-md border border-primary/40 bg-primary/5 p-4"
                >
                    <div class="flex items-center gap-2">
                        <Spinner class="h-4 w-4" />
                        <p class="text-sm font-medium">
                            {{
                                queuedImportStatus === 'queued'
                                    ? 'Archivo en cola'
                                    : 'Procesando importación'
                            }}
                        </p>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ queuedImportMessage }}
                    </p>
                </div>

                <div
                    v-if="importError && !isImporting && !isPollingQueuedImport"
                    class="space-y-2 rounded-md border border-destructive/50 bg-destructive/10 p-4"
                >
                    <p class="text-sm font-semibold text-destructive">
                        {{ errorTitle }}
                    </p>
                    <div
                        class="font-mono text-sm whitespace-pre-line text-destructive/90"
                    >
                        {{ importError }}
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Corrija el archivo y vuelva a intentar la importación.
                    </p>
                </div>

                <div v-if="importResult && !isImporting" class="space-y-3">
                    <div
                        :class="[
                            'rounded-md border p-3',
                            importResult.failed > 0
                                ? 'border-yellow-500/50 bg-yellow-500/10'
                                : 'border-green-500/50 bg-green-500/10',
                        ]"
                    >
                        <p
                            :class="[
                                'text-sm font-medium',
                                importResult.failed > 0
                                    ? 'text-yellow-700 dark:text-yellow-400'
                                    : 'text-green-700 dark:text-green-400',
                            ]"
                        >
                            {{
                                importResult.failed > 0
                                    ? resultTitleWithErrors
                                    : resultTitleSuccess
                            }}
                        </p>
                        <div class="mt-2 space-y-1 text-sm text-muted-foreground">
                            <p>
                                {{ resultLabels.total }}:
                                <span class="font-medium">{{
                                    importResult.total_rows
                                }}</span>
                            </p>
                            <p v-if="importResult.created > 0">
                                {{ resultLabels.created }}:
                                <span
                                    class="font-medium text-green-600 dark:text-green-400"
                                >
                                    {{ importResult.created }}
                                </span>
                            </p>
                            <p v-if="importResult.updated > 0">
                                {{ resultLabels.updated }}:
                                <span
                                    class="font-medium text-blue-600 dark:text-blue-400"
                                >
                                    {{ importResult.updated }}
                                </span>
                            </p>
                            <p v-if="importResult.failed > 0">
                                {{ resultLabels.failed }}:
                                <span class="font-medium text-destructive">
                                    {{ importResult.failed }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="Object.keys(importErrors).length > 0"
                        class="space-y-2 rounded-md border border-destructive/50 bg-destructive/10 p-3"
                    >
                        <p class="text-sm font-medium text-destructive">
                            Errores de validación encontrados
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ getErrorSummary() }}
                        </p>

                        <div
                            class="mt-2 max-h-48 space-y-2 overflow-y-auto rounded border border-destructive/30 bg-background/50 p-2"
                        >
                            <div
                                v-for="(errors, row) in importErrors"
                                :key="row"
                                class="text-xs"
                            >
                                <p class="font-semibold text-destructive">
                                    Fila {{ row }}:
                                </p>
                                <ul
                                    class="mt-1 ml-3 list-inside list-disc space-y-0.5"
                                >
                                    <li
                                        v-for="(error, idx) in errors"
                                        :key="idx"
                                        class="text-muted-foreground"
                                    >
                                        {{
                                            formatErrorMessage(
                                                error.attribute,
                                                error.messages,
                                            )
                                        }}
                                        <span
                                            v-if="error.values[error.attribute]"
                                            class="text-destructive"
                                        >
                                            ({{ error.values[error.attribute] }})
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!isBusy && !importResult && note"
                    class="rounded-md bg-muted p-3"
                >
                    <p class="text-sm text-muted-foreground">
                        <strong>Nota:</strong> {{ note }}
                    </p>
                </div>
            </div>

            <DialogFooter class="gap-2 border-t bg-background pt-4">
                <DialogClose as-child>
                    <Button
                        variant="secondary"
                        @click="setDialogOpen(false)"
                        :disabled="isImporting"
                    >
                        {{ importResult ? closeButtonText : cancelButtonText }}
                    </Button>
                </DialogClose>
                <Button
                    v-if="!importResult && !isPollingQueuedImport"
                    @click="performImport"
                    :disabled="!selectedFile || isBusy"
                >
                    <Spinner v-if="isImporting" class="mr-2 h-4 w-4" />
                    {{
                        isImporting
                            ? importingButtonText
                            : importButtonText
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
