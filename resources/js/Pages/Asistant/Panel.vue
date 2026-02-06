<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="Panel de usuario">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Panel de usuario</h1>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 place-items-center">
                        <Link
                            :href="route('grid-actividades.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-calendar-days text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800">Actividades del Mes</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('inscripciones.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-clipboard-check text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800">Mis inscripciones</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('membresias.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-id-card text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800">Membresías (Tarjetas Kadampa)</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('centroayuda.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-life-ring text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800">Centro de ayuda</div>
                            </div>
                        </Link>
                        <Link
                            :href="route('acercade.index')"
                            class="group flex h-56 w-56 items-center justify-center text-center rounded-2xl border border-gray-200 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 transition"
                        >
                            <div class="space-y-3">
                                <i class="fa-solid fa-circle-info text-8xl text-indigo-600"></i>
                                <div class="text-sm font-semibold text-gray-800">Acerca de</div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
