<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import NudoImg from '/resources/images/nudo.webp';
</script>

<template>
    <Head title="Mi camino Budista" />

    <AppLayout title="Mi camino Budista">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mi camino Budista
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="flex flex-col items-center text-center px-6 py-16 sm:py-24">
                        <img
                            :src="NudoImg"
                            alt="Nudo eterno"
                            class="w-64 h-64 sm:w-80 sm:h-80 object-contain mb-8"
                        />

                        <h1 class="text-3xl sm:text-4xl font-semibold text-gray-800 mb-3">
                            En construcción
                        </h1>

                        <p class="text-gray-600 text-lg max-w-md">
                            Estamos preparando esta sección para acompañarte
                            en tu camino budista. Pronto estará disponible.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
