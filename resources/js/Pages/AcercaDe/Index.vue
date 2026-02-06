<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const isAsistant = computed(() => {
    const roles = (page.props.user?.roles || []).map((role) => String(role).toLowerCase());
    return roles.includes('asistant');
});

const props = defineProps({
    version: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <AppLayout title="Acerca de">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="isAsistant" class="mb-4">
                    <Link
                        :href="route('asistant.panel')"
                        class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
                    >
                        Volver al panel
                    </Link>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Header -->
                    <div class="p-6 bg-gradient-to-r from-indigo-500 to-purple-600">
                        <h1 class="text-3xl font-bold text-white flex items-center">
                            <i class="fas fa-info-circle mr-3"></i>
                            Acerca de
                        </h1>
                    </div>

                    <!-- Content -->
                    <div class="p-8">
                        <div class="max-w-3xl mx-auto">
                            <!-- Version Card -->
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 shadow-lg rounded-lg p-8 border-l-4 border-indigo-500 mb-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                                            <i class="fas fa-code-branch text-2xl"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Versión del Sistema</h3>
                                        <p class="text-2xl font-bold text-indigo-600 mb-2">
                                            {{ version.version || 'Sin versión disponible' }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i>
                                            Última actualización: 
                                            <span class="font-semibold">{{ new Date(version.created_at).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' }) || 'Fecha no especificada' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Developer Card -->
                            <div class="bg-gradient-to-br from-purple-50 to-pink-100 shadow-lg rounded-lg p-8 border-l-4 border-purple-500">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-500 text-white">
                                            <i class="fas fa-user-tie text-2xl"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Desarrollado por</h3>
                                        <p class="text-xl font-bold text-purple-600 mb-3">
                                            Pablo Mandile
                                        </p>
                                        <div class="flex items-center text-gray-700">
                                            <i class="fas fa-envelope mr-2 text-purple-500"></i>
                                            <a 
                                                href="mailto:pablo.mandile@gmail.com" 
                                                target="_blank" 
                                                class="text-indigo-600 hover:text-indigo-800 underline font-medium transition duration-150">
                                                pablo.mandile@gmail.com
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Info (Optional) -->
                            <div class="mt-8 text-center">
                                <p class="text-gray-500 text-sm">
                                    <i class="fas fa-heart text-red-500 mr-1"></i>
                                    Desarrollado con amor para Milarepa
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animaciones sutiles */
.shadow-lg {
    transition: all 0.3s ease-in-out;
}

.shadow-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>
