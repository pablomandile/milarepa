<script>
    export default {
        name: 'CentroAyudaIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Tag from 'primevue/tag';

    
    defineProps({
        tickets: {
            type: Object,
            required: true
        }
    })

    const deleteTicket = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('centroayuda.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Ticket ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Ticket.", "error");
                },
            });
            }
        });
    };
    
    // Opcional: un pequeño mapeo entre el texto del estado y la severidad del <Tag>
    const severityMap = {
    'Pendiente': 'warning',
    'Asignado': 'secondary',
    'Resuelto': 'success',
    };

    // Función auxiliar que retorna la severidad adecuada
    function getSeverity(estado) {
    // Si el estado no está en severityMap, usamos un valor por defecto
    return severityMap[estado] ?? 'secondary';
    }

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Centro de Ayuda</h1>
        </template>
        <div class="py-5">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto">
                    <p><i class="pi pi-question-circle text-3xl"><span class="text-2xl ml-2">Consulta la documentación para encontrar la solución a tu consulta</span></i></p>
                    <p class="text-xl ml-10">o abre un <span class="text-indigo-500"><strong>Ticket</strong></span> para ser atendido a la brevedad por alguien de nuestro equipo.</p>
                </div>
            </div>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto mb-10">
                    <h1 class="font-semibold text-xl text-gray-800 leading-tight mb-6">Listado de tickets activos</h1>
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create tickets')">
                        <Link :href="route('centroayuda.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded mb-2" > 
                            NUEVO TICKET
                        </Link>
                    </div>
                    <div>
                        <DataTable :value="tickets" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem" class="mb-4">
                            <Column field="descripcion" header="Descripción"></Column>
                            <Column header="Usuario">
                                <template #body="slotProps">
                                    {{ slotProps.data.user ? slotProps.data.user.name : '-' }}
                                </template>
                            </Column>
                            <Column header="Responsable">
                                <template #body="slotProps">
                                    {{ slotProps.data.responsable ? slotProps.data.responsable.name : 'No Asignado' }}
                                </template>
                            </Column>
                            <Column header="Estado" class="flex justify-center">
                                <template #body="slotProps">
                                    <template v-if="slotProps.data.estado_ticket">
                                    <!-- El label (value) es el texto real de la columna "estado" -->
                                        <div class="flex justify-center">
                                            <Tag
                                            :value="slotProps.data.estado_ticket.estado"
                                            :severity="getSeverity(slotProps.data.estado_ticket.estado)"
                                            />
                                        </div>
                                    </template>
                                    <template v-else>
                                    <span>-</span>
                                    </template>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <Link
                                            :href="route('centroayuda.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update tickets')">
                                            <i class="pi pi-pencil text-indigo-400 mr-2"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteTicket(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete tickets')">
                                            <i class="pi pi-trash cursor-pointer text-red-300"></i>
                                        </a>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                    {{ $page.props }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>