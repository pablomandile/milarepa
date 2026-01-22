<template>
    <AppLayout title="Registrar Membresía">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Membresías Disponibles
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="membresias.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="membresia in membresias" 
                        :key="membresia.id"
                        class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition"
                    >
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                {{ membresia.nombre }}
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">
                                <i class="fas fa-building mr-2"></i>
                                {{ membresia.entidad.nombre }}
                            </p>
                            <p class="text-gray-700 mb-6 text-sm">
                                {{ membresia.descripcion || 'Sin descripción disponible' }}
                            </p>
                            
                            <!-- Precio si existe -->
                            <div v-if="membresia.esquemaPrecioMembresias && membresia.esquemaPrecioMembresias.length > 0" class="mb-6">
                                <p class="text-sm text-gray-600 mb-2">Precio Mensual:</p>
                                <p class="text-2xl font-bold text-indigo-600">
                                    ${{ parseFloat(membresia.esquemaPrecioMembresias[0].precio).toFixed(2) }}
                                </p>
                            </div>

                            <Link 
                                :href="route('registromembresias.create', { membresia_id: membresia.id })"
                                class="w-full block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-center"
                            >
                                <i class="fas fa-pencil-alt mr-2"></i>
                                Inscribirme
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <i class="fas fa-inbox text-5xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 text-lg">No hay membresías disponibles en este momento</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    membresias: Array
});
</script>
