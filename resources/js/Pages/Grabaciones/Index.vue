<script>
    export default {
        name: 'GrabacionesIndex'
    }
</script>

<script setup>
    import { computed, ref } from 'vue';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Dialog from 'primevue/dialog';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';

    const props = defineProps({
        grabaciones: {
            type: Array,
            required: true
        }
    })

    // Controla quÃ© filas de la tabla principal estÃ¡n expandidas
    const expandedRows = ref([]);
    const expandedCardIds = ref([]);

    const isCardExpanded = (id) => expandedCardIds.value.includes(id);
    const toggleCardExpanded = (id) => {
        const idx = expandedCardIds.value.indexOf(id);
        if (idx === -1) expandedCardIds.value.push(id);
        else expandedCardIds.value.splice(idx, 1);
    };

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const formatValor = (v) => `$ ${Number(v || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const grabacionesFiltradasMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.grabaciones;
        return props.grabaciones.filter((g) => {
            const campos = [g.nombre, g.boton_pago?.nombre];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });

    const deleteGrabacion = (id) => {
    Swal.fire({
        title: "Â¿EstÃ¡s seguro?",
        text: "Esta acciÃ³n no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "SÃ­, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('grabaciones.destroy', id), {
                onSuccess: () => {
                Swal.fire("Â¡Eliminado!", "La GrabaciÃ³n ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la GrabaciÃ³n.", "error");
                },
            });
            }
        });
    };

    const audioDialogVisible = ref(false);
    const audioLink = ref('');
    const audioNombre = ref('');

    function openAudio(link) {
        audioLink.value = link?.link || '';
        audioNombre.value = link?.nombre || 'GrabaciÃ³n';
        audioDialogVisible.value = true;
    }
    
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Grabaciones</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create grabaciones')">
                        <Link :href="route('grabaciones.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA GRABACIÓN
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="grabaciones.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="grabacionesFiltradasMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="grabacion in grabacionesFiltradasMobile"
                            :key="grabacion.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-microphone text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ grabacion.nombre }}</p>
                                        <p class="text-lg font-bold text-green-700 mt-1">{{ formatValor(grabacion.valor) }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Botón de Pago</span>
                                        <span class="text-right">{{ grabacion.boton_pago?.nombre || 'Sin botón' }}</span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="toggleCardExpanded(grabacion.id)"
                                >
                                    <span>{{ isCardExpanded(grabacion.id) ? 'Ocultar archivos' : 'Ver archivos' }} ({{ (grabacion.linksgrabacion || []).length }})</span>
                                    <i class="pi" :class="isCardExpanded(grabacion.id) ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                                </button>

                                <div v-if="isCardExpanded(grabacion.id)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                                    <div v-if="(grabacion.linksgrabacion || []).length === 0" class="text-sm text-gray-500">Sin archivos cargados.</div>
                                    <div v-else class="space-y-3">
                                        <div v-for="linkLine in grabacion.linksgrabacion" :key="linkLine.id" class="flex items-center justify-between gap-2 text-sm">
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-800 dark:text-gray-100 break-words">{{ linkLine.nombre }}</p>
                                                <p v-if="linkLine.boton_pago?.nombre" class="text-xs text-gray-500">{{ linkLine.boton_pago.nombre }}</p>
                                            </div>
                                            <button
                                                type="button"
                                                class="text-emerald-600 hover:text-emerald-800 flex-shrink-0"
                                                @click="openAudio(linkLine)"
                                                title="Abrir en Drive"
                                            >
                                                <i class="fas fa-external-link-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update grabaciones')"
                                        :href="route('grabaciones.edit', parseInt(grabacion.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar grabación"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update grabaciones')"
                                        :href="route('grabaciones.links', parseInt(grabacion.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-emerald-500 text-white px-3 text-xs font-semibold hover:bg-emerald-600 transition"
                                        title="Agregar links"
                                    >
                                        <i class="fas fa-link"></i>
                                        <span>Links</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete grabaciones')"
                                        @click="deleteGrabacion(parseInt(grabacion.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar grabación"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="grabaciones.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="grabaciones"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'boton_pago.nombre']"
                            stripedRows
                            paginator
                            :rows="10"
                            v-model:expandedRows="expandedRows"
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 50rem"
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
                            <Column expander style="width: 5rem" />
                            <Column field="nombre" header="Nombre"></Column>
                            <Column header="Botón de Pago">
                                <template #body="{ data }">
                                    {{ data.boton_pago?.nombre || 'Sin botón' }}
                                </template>
                            </Column>
                            <Column field="valor" header="Valor">
                                <template #body="{ data }">
                                    $ {{ Number(data.valor || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('grabaciones.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update grabaciones')"
                                            v-tooltip="'Editar grabación'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <Link
                                            :href="route('grabaciones.links', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update grabaciones')"
                                            v-tooltip="'Agregar links'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-link" style="font-size: 18px !important; line-height: 1; color: rgb(16, 185, 129);"></i>
                                        </Link>
                                        <button
                                            @click="deleteGrabacion(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete grabaciones')"
                                            v-tooltip="'Borrar grabación'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #expansion="{ data }">
                                <DataTable 
                                    :value="data.linksgrabacion"
                                    class="mt-3"
                                    stripedRows
                                    tableStyle="min-width: 50rem"
                                >
                                    <Column field="nombre" header="Nombre"></Column>
                            <Column header="Botón de Pago">
                                <template #body="{ data }">
                                    {{ data.boton_pago?.nombre || 'Sin botón' }}
                                </template>
                            </Column>
                                    <Column field="link" header="Link"></Column>
                                    <Column header="Abrir" bodyStyle="text-align:center;">
                                        <template #body="{ data: linkLine }">
                                            <button
                                                type="button"
                                                class="text-emerald-600 hover:text-emerald-800"
                                                @click="openAudio(linkLine)"
                                                title="Abrir en Drive"
                                            >
                                                <i class="fas fa-external-link-alt"></i>
                                            </button>
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <Dialog
        v-model:visible="audioDialogVisible"
        modal
        :header="audioNombre"
        :style="{ width: '520px' }"
    >
        <div class="space-y-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">Abrir archivo en Google Drive.</p>
            <a
                v-if="audioLink"
                :href="audioLink"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
            >
                <i class="fas fa-external-link-alt"></i>
                Abrir en Drive
            </a>
            <p v-else class="text-sm text-gray-500">No hay link disponible.</p>
        </div>
    </Dialog>
</template>


