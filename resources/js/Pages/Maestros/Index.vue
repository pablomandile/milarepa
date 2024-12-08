<script>
    export default {
        name: 'MaestrosIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/solid';
    import { Inertia } from '@inertiajs/inertia';
    import Swal from "sweetalert2";
    
    defineProps({
        maestros: {
            type: Object,
            required: true
        }
    })

    const deleteMaestro = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('maestros.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El curso ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el curso.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
                <h1 class="font-semibold text-xl text-gray-800 leading-tight">Maestros</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto-sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-3xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create maestros')">
                        <Link :href="route('maestros.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO MAESTRO
                        </Link>
                    </div>
                
                    <div class="mt-4">
                        <ul role="list" class="divide-y divide-gray-100">
                            <li class="flex justify-between gap-x-6 py-5" v-for="maestro in maestros.data">
                                <div class="flex min-w-0 gap-x-4">
                                    <div class="min-w-0 flex-auto">
                                        <p class="text-md font-semibold leading-6 text-gray-900">{{ maestro.nombre }}</p>
                                    </div>
                                    <div class="min-w-0 flex-auto">
                                        <p class="text-md font-semibold leading-6 text-gray-900">{{ maestro.telefono }}</p>
                                    </div>
                                    <div class="min-w-0 flex-auto">
                                        <p class="text-md font-semibold leading-6 text-gray-900">{{ maestro.email }}</p>
                                    </div>
                                </div>
                                <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                                    <div class="flex space-x-4">
                                        <Link :href="route('maestros.edit', parseInt(maestro.id))" v-if="$page.props.user.permissions.includes('update maestros')">
                                        <PencilSquareIcon class="w-6 h-6 text-indigo-500" />
                                        </Link>
                                        <Link @click="deleteaestro(parseInt(maestro.id))" v-if="$page.props.user.permissions.includes('delete maestros')">
                                        <TrashIcon class="w-6 h-6 text-red-300" />
                                        </Link>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>