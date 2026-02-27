<script>
    export default {
        name: 'NovedadesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link } from '@inertiajs/vue3';
    import Fieldset from 'primevue/fieldset';

    defineProps({
        novedades: {
            type: Object,
            required: true
        }
    })
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Novedades</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create novedades')">
                        <Link :href="route('novedades.gestion')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            GESTIONAR NOVEDADES
                        </Link>
                    </div>
                    <div class="mt-4">
                        <div class="card">
                            <div v-if="novedades">
                                <Fieldset 
                                v-for="(novedad, index) in novedades"
                                :key="novedad.id"
                                :legend="novedad.nombre"
                                :toggleable="true"
                                :collapsed="index !== 0"
                                >
                                    <p class="m-0">
                                        <i class="fas fa-calendar-alt mr-1" style="color: slateblue"></i>
                                        {{ novedad.fecha }}
                                    </p>
                                    <p class="m-0">
                                        {{ novedad.descripcion }}
                                    </p>
                                </Fieldset>
                            </div>
                            <p v-else class="text-gray-500">No hay novedades para mostrar.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
