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
    import { ref } from 'vue';
    import Dialog from 'primevue/dialog';
    import Button from 'primevue/button';
    import Dropdown from 'primevue/dropdown';
    

    const { tickets, usuariosResponsables } = defineProps({
        tickets: {
            type: Object,
            required: true
        },
        usuariosResponsables: { 
            type: Array,
            default: () => [] 
        },
    });

    const visibleAssign = ref(false);
    const ticketParaAsignar = ref(null);
    const usuarioSeleccionado = ref('');
    const visible = ref(false);
    const ticketSeleccionado = ref(null);

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

    const verTicket = (id) => {
        const ticket = tickets.find((tick) => tick.id === id);
        if (ticket) {
            ticketSeleccionado.value = ticket;
            visible.value = true;
        }
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

    function formatDateDMY(dateString) {
        if (!dateString) return '';

        // Crear un objeto Date a partir de la cadena
        const date = new Date(dateString);
        // Manejar casos donde dateString sea inválido
        if (isNaN(date.getTime())) return '';

        // Extraer día, mes y año
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${day}/${month}/${year}`;
    }

    function asignarTicket(ticketId) {
        const ticket = tickets.find((tick) => tick.id === ticketId);
        if (ticket) {
            ticketParaAsignar.value = ticket;
            usuarioSeleccionado.value = '';
            visibleAssign.value = true;
        }
    }

    function enviarAsignacion() {
    if (!ticketParaAsignar.value || !usuarioSeleccionado.value) {
        return;
    }
    // Ejemplo: hacemos un patch o post con Inertia
    router.put(route('tickets.asignar', ticketParaAsignar.value.id), {
        responsable_id: usuarioSeleccionado.value,
    }, {
        onSuccess: () => {
            Swal.fire("¡Asignado!", "Se asignó el responsable con éxito", "success");
            visibleAssign.value = false;
        },
        onError: () => {
            Swal.fire("Error", "No se pudo asignar el responsable", "error");
        }
    });
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
                    <p><i class="pi pi-question-circle text-3xl"><span class="text-xl ml-2">Consulta la documentación para encontrar la solución a tu consulta</span></i></p>
                    <p class="text-xl ml-10">o abre un <span class="text-indigo-500"><strong>Ticket</strong></span> para ser atendido a la brevedad por alguien de nuestro equipo.</p>
                </div>
            </div>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto mb-10">
                    <h1 class="font-semibold text-xl text-gray-800 leading-tight mb-6">Listado de tickets activos</h1>
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create tickets')">
                        <Link :href="route('centroayuda.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded mb-4" > 
                            NUEVO TICKET
                        </Link>
                    </div>
                    <div>
                        <DataTable :value="tickets" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem" class="mb-4">
                            <Column field="asunto" header="Asunto" sortable ></Column>
                            <Column field="user.name" header="Usuario" sortable >
                                <template #body="slotProps">
                                    {{ slotProps.data.user ? slotProps.data.user.name : '-' }}
                                </template>
                            </Column>
                            <Column field="responsable.name" header="Responsable" sortable >
                                <template #body="slotProps">
                                    {{ slotProps.data.responsable ? slotProps.data.responsable.name : 'No Asignado' }}
                                </template>
                            </Column>
                            <Column field="estado_ticket.id" header="Estado" class="flex justify-center" sortable >
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
                                    <div class="flex space-x-2">
                                        <a
                                            @click.prevent="verTicket(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read tickets')"
                                            v-tooltip="'Ver ticket'">
                                            <i class="pi pi-eye cursor-pointer text-indigo-400 mr-1"></i>
                                        </a>
                                        <a
                                            @click.prevent="asignarTicket(parseInt(slotProps.data.id))"
                                            v-if="
                                                $page.props.user.permissions.includes('asign tickets') && 
                                                slotProps.data.estado_ticket &&
                                                slotProps.data.estado_ticket.id !== 3"
                                            v-tooltip="'Asignar ticket'"
                                            >
                                            <i class="pi pi-user-plus cursor-pointer text-indigo-400 mr-1"></i>
                                        </a>
                                        <Link
                                            :href="route('centroayuda.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update tickets') &&
                                            slotProps.data.estado_ticket &&
                                                slotProps.data.estado_ticket.id !== 3"
                                            v-tooltip="'Editar ticket'">
                                            <i class="pi pi-pencil text-indigo-400 mr-1"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteTicket(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete tickets')"
                                            v-tooltip="'Borrar ticket'">
                                            <i class="pi pi-trash cursor-pointer text-red-300"></i>
                                        </a>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <!-- Dialog para mostrar detalles -->
    <Dialog 
    v-model:visible="visible" 
    maximizable 
    modal 
    :header="ticketSeleccionado ? `${ticketSeleccionado.asunto}` : 'Detalles...'" 
    :style="{ width: '50rem' }" 
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
>
    <template v-if="ticketSeleccionado">
        <div class="flex flex-col items-center justify-center w-2/3 mx-auto bg-indigo-100 text-black border rounded-md mb-4 h-52">
            <hr class="w-full border-gray-300 mb-2" />
            <strong class="mb-1">Detalle</strong>
            <hr class="w-full border-gray-300 mb-2" />
            <p 
                class="text-center mt-3"
                v-if="ticketSeleccionado.descripcion && ticketSeleccionado.descripcion.trim() !== ''"
            >
                {{ ticketSeleccionado.descripcion }}
            </p>
        </div>
        <div class="flex flex-col items-center justify-center w-2/3 mx-auto mb-4">
            <p v-if="ticketSeleccionado.responsable && ticketSeleccionado.responsable.name.trim() !== ''">
                <i class="pi pi-user mr-1"></i> Responsable asignado: <strong>{{ ticketSeleccionado.responsable.name }}</strong>
            </p>
            <template v-else>
                <i class="pi pi-user mr-1"></i> Sin responsable asignado
            </template>
            <p v-if="ticketSeleccionado && ticketSeleccionado.fecha_apertura.trim() !== '' && ticketSeleccionado.estado_ticket.id !== 3">
                <i class="pi pi-calendar mr-1"></i> Abierto desde: <strong>{{ formatDateDMY(ticketSeleccionado.fecha_apertura) }}</strong>
            </p>
        </div>
    </template>
        <p v-else>Cargando datos...</p>
    </Dialog>
    <!-- Diálogo para asignar ticket a un responsable -->
    <Dialog
    v-model:visible="visibleAssign"
    header="Asignar Ticket"
    :style="{ width: '30rem' }"
    modal
    >
    <template v-if="ticketParaAsignar">
        <p class="mb-4">
        <strong>Ticket:</strong> {{ ticketParaAsignar.asunto }}
        </p>

        <!-- Dropdown de usuarios disponibles -->
        <Dropdown
        v-model="usuarioSeleccionado"
        :options="usuariosResponsables"
        optionLabel="name"
        optionValue="id"
        placeholder="Seleccione responsable"
        class="w-full mb-10 border-solid border-2 border-gray-300 shadow-sm bg-gray-100"
        />

        <div class="flex justify-end space-x-2">
            <Button label="Cancelar" severity="secondary" raised 
                class="px-2 py-2 bg-gray-400 text-white mr-3" 
                @click="visibleAssign = false" />
            <Button label="Asignar" severity="success" raised 
                class="px-2 py-2 bg-green-800 text-white" 
                @click="enviarAsignacion" />
        </div>
    </template>
    <p v-else>Cargando...</p>
    </Dialog>
</template>