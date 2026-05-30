<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import CaracolaUrl from '/resources/images/caracola.webp';

defineProps({
    frase: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AppLayout title="Panel de usuario">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Panel de usuario</h1>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-8 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 place-items-center">
                        <Link
                            :href="route('grid-actividades.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-calendar-days text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Actividades del Mes</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('inscripciones.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-clipboard-check text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Mis inscripciones</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('membresias.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-id-card text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Membresías (Tarjetas Kadampa)</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('centroayuda.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-life-ring text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Centro de ayuda</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('acercade.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-circle-info text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Acerca de</div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Frase de Dharma del día (random por refresh) -->
                <div v-if="frase" class="flex items-center gap-5 sm:gap-6 px-6 mt-10 mb-12 max-w-3xl mx-auto">
                    <img
                        :src="CaracolaUrl"
                        alt="Caracola"
                        class="h-32 sm:h-40 w-auto flex-shrink-0 object-contain opacity-80 dark:opacity-100"
                    />
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-lg sm:text-xl italic text-gray-700 dark:text-gray-200 leading-relaxed">
                            &ldquo;{{ frase.cita_textual }}&rdquo;
                        </p>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            — {{ frase.libro }}
                        </p>
                        <p class="mt-1 text-sm italic font-medium text-gray-600 dark:text-gray-300">
                            Gueshe Kelsang Gyatso
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
