<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type ImportResult = {
    total_rows: number;
    created: number;
    updated: number;
    failed: number;
    errors: Record<
        number,
        {
            attribute: string;
            messages: string[];
            values: Record<string, unknown>;
        }[]
    >;
};

const isOpen = ref(false);
const isImporting = ref(false);
const importFile = ref<File | null>(null);
const importFileInput = ref<HTMLInputElement | null>(null);
const importFileBlob = ref<Blob | null>(null);
const importFileName = ref('');
const importErrorMessages = ref<string[]>([]);
const importFieldError = ref('');
const importResult = ref<ImportResult | null>(null);

function reset() {
    importFile.value = null;
    importFileBlob.value = null;
    importFileName.value = '';
    importFieldError.value = '';
    importErrorMessages.value = [];
    importResult.value = null;
    if (importFileInput.value) importFileInput.value.value = '';
}

function handleOpenChange(open: boolean) {
    if (!open) reset();
}

function onFileChange(e: Event) {
    importFieldError.value = '';
    importResult.value = null;
    importFileBlob.value = null;

    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    importFile.value = file;

    if (!file) {
        importFileName.value = '';
        return;
    }

    importFileName.value = file.name;

    const valid = ['.xlsx', '.xls', '.csv'].some((ext) =>
        file.name.toLowerCase().endsWith(ext),
    );

    if (!valid) {
        importFieldError.value = 'Formato inválido. Usa XLSX, XLS o CSV.';
        importFile.value = null;
        importFileName.value = '';
    }
}

function onDrop(e: DragEvent) {
    const file = e.dataTransfer?.files?.[0] ?? null;
    if (!file) return;
    importFile.value = file;
    importFileName.value = file.name;
    onFileChange({ target: { files: [file] } } as unknown as Event);
}

async function submit() {
    importErrorMessages.value = [];
    importResult.value = null;
    importFieldError.value = '';

    if (!importFile.value) {
        importFieldError.value = 'Selecciona un archivo válido.';
        return;
    }

    isImporting.value = true;

    try {
        const buf = await importFile.value.arrayBuffer();
        importFileBlob.value = new Blob([buf], {
            type: importFile.value.type || 'application/octet-stream',
        });
        importFileName.value = importFile.value.name;
    } catch {
        importFileBlob.value = null;
    }

    const fd = new FormData();
    fd.append(
        'file',
        importFileBlob.value ?? importFile.value!,
        importFileName.value,
    );

    const xsrf = decodeURIComponent(
        document.cookie
            .split('; ')
            .find((c) => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );

    try {
        const resp = await fetch('/products/import', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            },
            credentials: 'same-origin',
            body: fd,
        });

        const data = await resp.json().catch(() => ({}));

        if (!resp.ok) {
            importErrorMessages.value = [
                data?.error ?? data?.message ?? 'La importación falló.',
            ];
            return;
        }

        importResult.value = data?.result ?? null;
        router.reload({ only: ['products'] });
    } catch {
        importErrorMessages.value = [
            'Error en la importación. Presione Cerrar y vuelva a intentarlo.',
        ];
    } finally {
        isImporting.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="isOpen" @update:open="handleOpenChange">
        <DialogTrigger as-child>
            <Button variant="secondary">Importar XLS</Button>
        </DialogTrigger>
        <DialogContent class="max-h-[80vh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Importar productos por XLS</DialogTitle>
                <DialogDescription>
                    Sube un archivo XLSX, XLS o CSV con los productos.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <AlertError
                    v-if="importErrorMessages.length"
                    :errors="importErrorMessages"
                    title="Error en importación"
                />

                <!-- Drop zone -->
                <div
                    class="mt-3 flex cursor-pointer flex-col items-center justify-center gap-2 rounded border-2 border-dashed p-4 text-center hover:bg-muted/5"
                    @dragover.prevent
                    @dragenter.prevent
                    @drop.prevent="onDrop"
                    @click="importFileInput?.click()"
                >
                    <p class="text-sm text-muted-foreground">
                        Arrastra y suelta el archivo aquí, o haz clic para
                        seleccionar.
                    </p>
                    <p
                        v-if="importFileName"
                        class="text-xs text-muted-foreground"
                    >
                        Archivo seleccionado:
                        <strong>{{ importFileName }}</strong>
                    </p>
                    <p v-if="importFieldError" class="text-xs text-destructive">
                        {{ importFieldError }}
                    </p>
                    <input
                        ref="importFileInput"
                        type="file"
                        class="hidden"
                        accept=".xlsx,.xls,.csv"
                        @change="onFileChange"
                    />
                </div>

                <!-- Template download -->
                <div
                    class="rounded border border-sidebar-border/70 bg-muted/5 p-3"
                >
                    <p class="mb-2 text-sm font-medium text-muted-foreground">
                        Formato correcto
                    </p>
                    <Button as-child variant="ghost" size="sm">
                        <a
                            href="/products/template"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Descargar plantilla de importación
                        </a>
                    </Button>
                </div>

                <p class="text-sm text-muted-foreground">
                    Nota: El archivo debe contener las columnas:
                    <strong>sku</strong>, <strong>nombre</strong>,
                    <strong>descripcion</strong>, <strong>precio</strong>,
                    <strong>moneda</strong>, <strong>estado</strong> y
                    <strong>stock</strong>.
                </p>

                <!-- Result -->
                <div v-if="importResult" class="grid gap-2">
                    <p class="text-sm text-muted-foreground">
                        Resumen: {{ importResult.created }} creados,
                        {{ importResult.updated }} actualizados,
                        {{ importResult.failed }} fallidos. Total:
                        {{ importResult.total_rows }} filas.
                    </p>

                    <div
                        v-if="
                            importResult.errors &&
                            Object.keys(importResult.errors).length
                        "
                        class="grid gap-2"
                    >
                        <h3 class="text-sm font-medium">Errores por fila</h3>
                        <div
                            class="max-h-64 overflow-y-auto rounded border p-2"
                        >
                            <div
                                v-for="row in Object.keys(importResult.errors)
                                    .map(Number)
                                    .sort((a, b) => a - b)"
                                :key="row"
                                class="mb-3"
                            >
                                <p class="text-xs font-semibold">
                                    Fila {{ row }}
                                </p>
                                <ul class="ml-4 list-disc">
                                    <li
                                        v-for="err in importResult.errors[row]"
                                        :key="err.attribute"
                                        class="text-xs"
                                    >
                                        <span class="font-medium"
                                            >{{ err.attribute }}:</span
                                        >
                                        {{ (err.messages || []).join('; ') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="ghost" type="button">Cerrar</Button>
                </DialogClose>
                <Button
                    type="button"
                    :disabled="isImporting || !importFile"
                    @click="submit"
                >
                    {{ isImporting ? 'Importando...' : 'Importar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
