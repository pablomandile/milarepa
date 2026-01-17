<script>
    export default {
        name: 'RegistroMembresiasCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import RegistroMembresiaForm from '@/Components/Formularios/RegistroMembresiaForm.vue'
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        membresiaSeleccionada: {
            type: Object,
            default: null
        }
    });

    const form = useForm({
        user_id: '',
        membresia_id: props.membresiaSeleccionada?.id || ''
    })
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Registrarte en una Membresia</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Información de la membresía seleccionada -->
                    <div v-if="membresiaSeleccionada" class="p-6 bg-blue-50 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Membresía Seleccionada</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600"><strong>Nombre:</strong> {{ membresiaSeleccionada.nombre }}</p>
                                <p class="text-sm text-gray-600"><strong>Entidad:</strong> {{ membresiaSeleccionada.entidad?.nombre }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600"><strong>Descripción:</strong> {{ membresiaSeleccionada.descripcion }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('membresias.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <RegistroMembresiaForm 
                            :updating="false"
                            :entidades="entidades"
                            :form="form" @submit="form.post(route('registromembresias.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>