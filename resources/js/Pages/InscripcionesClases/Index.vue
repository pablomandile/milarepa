<script>
export default {
    name: 'InscripcionesClasesIndex'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref } from 'vue';

const props = defineProps({
    inscripcionesClases: {
        type: Array,
        required: true,
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const getParticipante = (i) => i.user?.name || i.guest_user?.name || i.nombre_snapshot || '';
const getEmail = (i) => i.user?.email || i.guest_user?.email || i.email_snapshot || '';

const inscripcionesFiltradasMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    if (!term) return props.inscripcionesClases;
    return props.inscripcionesClases.filter((i) => {
        const campos = [
            i.clase?.nombre,
            getParticipante(i),
            getEmail(i),
            i.membresia,
            String(i.montoApagar ?? ''),
            String(i.pago ?? ''),
        ];
        return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
    });
});

const deleteInscripcionClase = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.delete(route('inscripciones-clases.destroy', id), {
            onSuccess: () => {
                Swal.fire('¡Eliminado!', 'La inscripción de clase fue eliminada.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'No se pudo eliminar la inscripción de clase.', 'error');
            },
        });
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Inscripciones de Clases</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-[108rem] mx-auto">
                    <div class="flex justify-between">
                        <Link
                            :href="route('inscripciones-clases.create')"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            REGISTRO DE INSCRIPCIÓN
                        </Link>
                    </div>

                    <!-- Buscador móvil -->
                    <div v-if="inscripcionesClases.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="inscripcionesFiltradasMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="inscripcion in inscripcionesFiltradasMobile"
                            :key="inscripcion.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-user-check text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ getParticipante(inscripcion) || '-' }}</p>
                                        <p v-if="inscripcion.clase?.nombre" class="text-sm text-gray-600 dark:text-gray-400">{{ inscripcion.clase.nombre }}</p>
                                    </div>
                                    <span v-if="inscripcion.online" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 flex-shrink-0">
                                        Online
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Email</span>
                                        <span class="text-right break-all">
                                            <a v-if="getEmail(inscripcion)" :href="`mailto:${getEmail(inscripcion)}`" class="text-indigo-600 hover:text-indigo-800">
                                                {{ getEmail(inscripcion) }}
                                            </a>
                                            <span v-else>-</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Membresía</span>
                                        <span class="text-right">{{ inscripcion.membresia || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Monto Total</span>
                                        <span class="text-right font-semibold">{{ inscripcion.montoApagar || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Pago</span>
                                        <span class="text-right">{{ inscripcion.pago || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        :href="route('inscripciones-clases.edit', inscripcion.id)"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        @click="deleteInscripcionClase(inscripcion.id)"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="inscripcionesClases.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="inscripcionesClases"
                            v-model:filters="filters"
                            :globalFilterFields="['clase.nombre', 'user.name', 'user.email', 'guest_user.name', 'guest_user.email', 'nombre_snapshot', 'email_snapshot', 'membresia']"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 20, 50]"
                            tableStyle="min-width: 65rem"
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
                            <Column header="Clase">
                                <template #body="slotProps">
                                    {{ slotProps.data.clase?.nombre || '-' }}
                                </template>
                            </Column>
                            <Column header="Participante">
                                <template #body="slotProps">
                                    {{ slotProps.data.user?.name || slotProps.data.guest_user?.name || slotProps.data.nombre_snapshot || '-' }}
                                </template>
                            </Column>
                            <Column header="Email">
                                <template #body="slotProps">
                                    {{ slotProps.data.user?.email || slotProps.data.guest_user?.email || slotProps.data.email_snapshot || '-' }}
                                </template>
                            </Column>
                            <Column field="membresia" header="Membresía" />
                            <Column field="montoApagar" header="Monto Total" />
                            <Column field="pago" header="Pago" />
                            <Column header="Online">
                                <template #body="slotProps">
                                    <span>{{ slotProps.data.online ? 'Sí' : 'No' }}</span>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('inscripciones-clases.edit', slotProps.data.id)"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteInscripcionClase(slotProps.data.id)"
                                            class="text-red-600 hover:text-red-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1;"></i>
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
</template>
