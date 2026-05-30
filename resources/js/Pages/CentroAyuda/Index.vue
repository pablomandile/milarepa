<script>
    export default {
        name: 'CentroAyudaIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router, usePage } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Tag from 'primevue/tag';
    import { computed, ref } from 'vue';
    import Dialog from 'primevue/dialog';
    import Button from 'primevue/button';
    import Dropdown from 'primevue/dropdown';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';
    

    const page = usePage();
    const isAsistant = computed(() => {
        const roles = (page.props.user?.roles || []).map((role) => String(role).toLowerCase());
        return roles.includes('asistant');
    });

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

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const ticketsArray = computed(() => Array.isArray(tickets) ? tickets : Object.values(tickets || {}));

    const ticketsFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        const lista = ticketsArray.value;
        if (!term) return lista;
        return lista.filter((t) => {
            const campos = [
                t.asunto,
                t.user?.name,
                t.responsable?.name,
                t.estado_ticket?.estado,
            ];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });

    const severityColors = {
        warning: 'bg-amber-100 text-amber-800',
        secondary: 'bg-slate-200 text-slate-700',
        success: 'bg-green-100 text-green-800',
    };

    const getEstadoBadgeClass = (estado) => {
        return severityColors[getSeverity(estado)] ?? 'bg-slate-200 text-slate-700';
    };

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

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Centro de Ayuda</h1>
        </template>
        <div class="py-5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="isAsistant" class="mb-4">
                    <Link
                        :href="route('asistant.panel')"
                        class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
                    >
                        Volver al panel
                    </Link>
                </div>
                <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-4xl mx-auto rounded-lg">
                    <div class="flex items-start gap-3">
                        <i class="pi pi-question-circle text-2xl sm:text-3xl text-indigo-600 mt-1 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-base sm:text-xl text-gray-800 dark:text-gray-100">Consulta la documentación para encontrar la solución a tu consulta</p>
                            <p class="text-base sm:text-xl text-gray-800 dark:text-gray-100 mt-1">o abre un <span class="text-indigo-500"><strong>Ticket</strong></span> para ser atendido a la brevedad por alguien de nuestro equipo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-4xl mx-auto mb-10 rounded-lg">
                    <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight mb-6">Listado de tickets activos</h1>
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create tickets')">
                        <Link :href="route('centroayuda.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded mb-4" >
                            NUEVO TICKET
                        </Link>
                    </div>

                    <!-- Buscador móvil -->
                    <div v-if="ticketsArray.length > 0" class="sm:hidden mb-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="ticketsFiltradosMobile.length > 0" class="space-y-4 sm:hidden">
                        <div
                            v-for="ticket in ticketsFiltradosMobile"
                            :key="ticket.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="pi pi-ticket text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ ticket.asunto }}</p>
                                    </div>
                                    <span
                                        v-if="ticket.estado_ticket"
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold flex-shrink-0"
                                        :class="getEstadoBadgeClass(ticket.estado_ticket.estado)"
                                    >
                                        {{ ticket.estado_ticket.estado }}
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Usuario</span>
                                        <span class="text-right">{{ ticket.user?.name || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Responsable</span>
                                        <span class="text-right">{{ ticket.responsable?.name || 'No Asignado' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        v-if="$page.props.user.permissions.includes('read tickets')"
                                        @click="verTicket(parseInt(ticket.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-100 text-indigo-700 px-3 text-xs font-semibold hover:bg-indigo-200 transition"
                                        title="Ver ticket"
                                    >
                                        <i class="fas fa-eye"></i>
                                        <span>Ver</span>
                                    </button>
                                    <button
                                        v-if="$page.props.user.permissions.includes('asign tickets') && ticket.estado_ticket && ticket.estado_ticket.id !== 3"
                                        @click="asignarTicket(parseInt(ticket.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-emerald-500 text-white px-3 text-xs font-semibold hover:bg-emerald-600 transition"
                                        title="Asignar ticket"
                                    >
                                        <i class="fas fa-user-plus"></i>
                                        <span>Asignar</span>
                                    </button>
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update tickets') && ticket.estado_ticket && ticket.estado_ticket.id !== 3"
                                        :href="route('centroayuda.edit', parseInt(ticket.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar ticket"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete tickets')"
                                        @click="deleteTicket(parseInt(ticket.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar ticket"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="ticketsArray.length > 0" class="sm:hidden text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>
                    <div v-else class="sm:hidden text-center py-10">
                        <i class="pi pi-inbox text-5xl text-gray-300 dark:text-gray-700 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">No hay tickets activos.</p>
                    </div>

                    <!-- Tabla desktop -->
                    <div class="hidden sm:block">
                        <DataTable
                            :value="tickets"
                            v-model:filters="filters"
                            :globalFilterFields="['asunto', 'user.name', 'responsable.name', 'estado_ticket.estado']"
                            stripedRows
                            paginator
                            :rows="5"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 50rem"
                            class="mb-4"
                        >
                            <template #header>
                                <div class="flex justify-end">
                                    <IconField iconPosition="right">
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                    </IconField>
                                </div>
                            </template>
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
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            @click="verTicket(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read tickets')"
                                            v-tooltip="'Ver ticket'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </button>
                                        <button
                                            @click="asignarTicket(parseInt(slotProps.data.id))"
                                            v-if="
                                                $page.props.user.permissions.includes('asign tickets') && 
                                                slotProps.data.estado_ticket &&
                                                slotProps.data.estado_ticket.id !== 3"
                                            v-tooltip="'Asignar ticket'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-user-plus" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </button>
                                        <Link
                                            :href="route('centroayuda.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update tickets') &&
                                            slotProps.data.estado_ticket &&
                                                slotProps.data.estado_ticket.id !== 3"
                                            v-tooltip="'Editar ticket'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteTicket(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete tickets')"
                                            v-tooltip="'Borrar ticket'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
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
    :header="'Detalle del ticket'" 
    :style="{ width: '50rem' }" 
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
>
    <template v-if="ticketSeleccionado">
        <div class="flex flex-col items-center justify-center w-2/3 mx-auto bg-indigo-100 text-black border rounded-md mb-4 h-52">
            <hr class="w-full border-gray-300 dark:border-gray-600 mb-2" />
            <i class="pi pi-exclamation-triangle" style="font-size: 2.5rem"></i>
            <strong class="mb-1">{{ ticketSeleccionado.asunto }}</strong>
            <hr class="w-full border-gray-300 dark:border-gray-600 mb-2" />
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
        class="w-full mb-10 border-solid border-2 border-gray-300 dark:border-gray-600 shadow-sm bg-gray-100 dark:bg-gray-900"
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
