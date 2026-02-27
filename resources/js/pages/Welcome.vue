<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
import { index as availabilityIndex } from '@/routes/availability';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Inicio" />

    <div
        class="min-h-screen bg-slate-50 px-4 py-6 text-slate-900 sm:px-6 lg:px-8"
    >
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-end gap-3"
        >
            <Link
                v-if="$page.props.auth.user"
                :href="dashboard()"
                class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-100"
            >
                Dashboard
            </Link>
            <template v-else>
                <Link
                    :href="login()"
                    class="rounded-md border border-transparent px-4 py-2 text-sm font-medium hover:bg-slate-100"
                >
                    Iniciar sesión
                </Link>
            </template>
        </header>

        <main class="mx-auto mt-10 w-full max-w-5xl">
            <section class="rounded-xl border bg-white p-6 shadow-sm sm:p-10">
                <p
                    class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                >
                    Landing en progreso
                </p>
                <h1
                    class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    Agenda pública de citas
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base"
                >
                    Consulta fechas y franjas disponibles para reservar tu cita.
                    Este acceso está disponible para visitantes sin
                    autenticación.
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <Link
                        :href="availabilityIndex()"
                        class="rounded-md bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-700"
                    >
                        Consultar disponibilidad
                    </Link>
                    <p class="text-sm text-slate-500">
                        Próximamente: flujo completo de reserva desde esta
                        landing.
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>
