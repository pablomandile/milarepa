<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
defineProps({
    entidad_principal: {
        type: String,
        default: 'Sin entidad principal configurada',
    },
});
</script>

<template>
    <AppLayout title="Dashboard" :entidad_principal="entidad_principal">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Inicio
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <Welcome :entidad_principal=entidad_principal />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
