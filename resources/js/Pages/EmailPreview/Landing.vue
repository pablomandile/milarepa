<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const inscripcionId = ref('');
const pagoId = ref('');
const grabacionId = ref('');
const email4Id = ref('');
const email5Id = ref('');
const email6Id = ref('');

const verInscripcion = () => {
    if (inscripcionId.value) {
        window.location.href = `/email-preview/inscripcion/${inscripcionId.value}`;
    }
};

const verPago = () => {
    if (pagoId.value) {
        window.location.href = `/email-preview/inscripcion-confirmada/${pagoId.value}`;
    }
};

const verGrabacion = () => {
    if (grabacionId.value) {
        window.location.href = `/email-preview/grabacion/${grabacionId.value}`;
    }
};

const verEmail4 = () => {
    if (email4Id.value) {
        window.location.href = `/email-preview/informacion-membresias/${email4Id.value}`;
    }
};

const verEmail5 = () => {
    if (email5Id.value) {
        window.location.href = `/email-preview/inscripcion-tk-registrada/${email5Id.value}`;
    }
};

const verEmail6 = () => {
    if (email6Id.value) {
        window.location.href = `/email-preview/actividades-online/${email6Id.value}`;
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Previsualización de Emails</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 1: Inscripción Confirmada -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-indigo-600 to-purple-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">📧 Inscripción Registrada</h2>
                            <p class="opacity-90 text-sm">Email de confirmación de registro</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/inscripcion"
                                class="block w-full px-4 py-2 text-center bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID de Inscripción:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="inscripcionId"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600"
                                        @keyup.enter="verInscripcion"
                                    />
                                    <button
                                        @click="verInscripcion"
                                        :disabled="!inscripcionId"
                                        class="px-3 py-2 bg-purple-700 text-white text-sm rounded-lg hover:bg-purple-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Confirmación de Pago -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-green-600 to-emerald-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">💳 Confirmación de Pago</h2>
                            <p class="opacity-90 text-sm">Email de confirmación de pago recibido</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/inscripcion-confirmada"
                                class="block w-full px-4 py-2 text-center bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID de Inscripción:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="pagoId"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-600"
                                        @keyup.enter="verPago"
                                    />
                                    <button
                                        @click="verPago"
                                        :disabled="!pagoId"
                                        class="px-3 py-2 bg-emerald-700 text-white text-sm rounded-lg hover:bg-emerald-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Grabación Disponible -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-orange-600 to-red-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">🎬 Grabación Disponible</h2>
                            <p class="opacity-90 text-sm">Email con link de grabación</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/grabacion"
                                class="block w-full px-4 py-2 text-center bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID de Inscripción:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="grabacionId"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-600"
                                        @keyup.enter="verGrabacion"
                                    />
                                    <button
                                        @click="verGrabacion"
                                        :disabled="!grabacionId"
                                        class="px-3 py-2 bg-red-700 text-white text-sm rounded-lg hover:bg-red-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 4 -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-blue-600 to-cyan-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">💳 Tarjetas Kadampa</h2>
                            <p class="opacity-90 text-sm">Información de membresías</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/informacion-membresias"
                                class="block w-full px-4 py-2 text-center bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="email4Id"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                                        @keyup.enter="verEmail4"
                                    />
                                    <button
                                        @click="verEmail4"
                                        :disabled="!email4Id"
                                        class="px-3 py-2 bg-cyan-700 text-white text-sm rounded-lg hover:bg-cyan-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5 -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-pink-600 to-rose-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">📧 Inscripción TK Registrada</h2>
                            <p class="opacity-90 text-sm">Email de registro de Tarjeta Kadampa</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/inscripcion-tk-registrada"
                                class="block w-full px-4 py-2 text-center bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="email5Id"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-600"
                                        @keyup.enter="verEmail5"
                                    />
                                    <button
                                        @click="verEmail5"
                                        :disabled="!email5Id"
                                        class="px-3 py-2 bg-rose-700 text-white text-sm rounded-lg hover:bg-rose-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6 -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-violet-600 to-purple-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">📧 Actividades Online</h2>
                            <p class="opacity-90 text-sm">Bienvenida + enlace a clases online</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/actividades-online"
                                class="block w-full px-4 py-2 text-center bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="email6Id"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-600"
                                        @keyup.enter="verEmail6"
                                    />
                                    <button
                                        @click="verEmail6"
                                        :disabled="!email6Id"
                                        class="px-3 py-2 bg-purple-700 text-white text-sm rounded-lg hover:bg-purple-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6 -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-teal-600 to-cyan-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">📧 Email 7</h2>
                            <p class="opacity-90 text-sm">Plantilla de email personalizada</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/inscripcion"
                                class="block w-full px-4 py-2 text-center bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="email6Id"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600"
                                        @keyup.enter="verEmail6"
                                    />
                                    <button
                                        @click="verEmail6"
                                        :disabled="!email6Id"
                                        class="px-3 py-2 bg-cyan-700 text-white text-sm rounded-lg hover:bg-cyan-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6 -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-fuchsia-600 to-pink-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">📧 Email 8</h2>
                            <p class="opacity-90 text-sm">Plantilla de email personalizada</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/inscripcion"
                                class="block w-full px-4 py-2 text-center bg-fuchsia-600 text-white rounded-lg hover:bg-fuchsia-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="email6Id"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-fuchsia-600"
                                        @keyup.enter="verEmail6"
                                    />
                                    <button
                                        @click="verEmail6"
                                        :disabled="!email6Id"
                                        class="px-3 py-2 bg-pink-700 text-white text-sm rounded-lg hover:bg-pink-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6 -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-gradient-to-br from-yellow-600 to-amber-700 text-white text-center">
                            <h2 class="text-xl font-bold mb-2">📧 Email 9</h2>
                            <p class="opacity-90 text-sm">Plantilla de email personalizada</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <Link
                                href="/email-preview/inscripcion"
                                class="block w-full px-4 py-2 text-center bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-semibold text-sm"
                            >
                                Ver con Datos de Prueba
                            </Link>

                            <div class="border-t pt-4">
                                <label class="block text-xs font-medium text-gray-700 mb-2">
                                    ID:
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="email6Id"
                                        type="number"
                                        placeholder="Ingresa ID"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"
                                        @keyup.enter="verEmail6"
                                    />
                                    <button
                                        @click="verEmail6"
                                        :disabled="!email6Id"
                                        class="px-3 py-2 bg-amber-700 text-white text-sm rounded-lg hover:bg-amber-800 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

