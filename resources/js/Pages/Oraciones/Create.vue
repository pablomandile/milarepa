<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OracionForm from '@/Components/Formularios/OracionForm.vue';
</script>

<template>
    <Head title="Crear Oración" />

    <AppLayout title="Crear Oración">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Crear Oración</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <OracionForm mode="create" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
