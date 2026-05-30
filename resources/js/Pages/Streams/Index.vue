<script>
    export default {
        name: 'StreamsIndex'
    }
</script>

<script setup>
    import { computed, ref } from 'vue';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';

    const props = defineProps({
        streams: {
            type: Array,
            required: true
        }
    })

    // Controla qué filas de la tabla principal están expandidas
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

    const streamsFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.streams;
        return props.streams.filter((s) => String(s.nombre ?? '').toLowerCase().includes(term));
    });

    const deleteStream = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('streams.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Stream ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Stream.", "error");
                },
            });
            }
        });
    };

    const resolveLinkUrl = (link) => {
        if (!link) return '';
        const raw = String(link).trim();
        if (!raw) return '';
        if (/^https?:\/\//i.test(raw)) return raw;
        return `https://${raw}`;
    };
    
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Streams</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create streams')">
                        <Link :href="route('streams.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO STREAM
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="streams.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="streamsFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="stream in streamsFiltradosMobile"
                            :key="stream.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-broadcast-tower text-2xl text-indigo-600"></i>
                                    <p class="text-base font-semibold text-gray-800 dark:text-gray-100 flex-1 min-w-0 break-words">{{ stream.nombre }}</p>
                                </div>

                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="toggleCardExpanded(stream.id)"
                                >
                                    <span>{{ isCardExpanded(stream.id) ? 'Ocultar links' : 'Ver links' }} ({{ (stream.links || []).length }})</span>
                                    <i class="pi" :class="isCardExpanded(stream.id) ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                                </button>

                                <div v-if="isCardExpanded(stream.id)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                                    <div v-if="(stream.links || []).length === 0" class="text-sm text-gray-500">Sin links cargados.</div>
                                    <div v-else class="space-y-2">
                                        <div v-for="link in stream.links" :key="link.id" class="flex items-center justify-between gap-2 text-sm">
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-800 dark:text-gray-100 break-words">{{ link.nombre }}</p>
                                                <p class="text-xs text-gray-500 break-all">{{ link.link }}</p>
                                            </div>
                                            <a
                                                v-if="resolveLinkUrl(link.link)"
                                                :href="resolveLinkUrl(link.link)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-indigo-500 flex-shrink-0"
                                                title="Abrir link"
                                            >
                                                <i class="pi pi-link"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update streams')"
                                        :href="route('streams.edit', parseInt(stream.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar stream"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete streams')"
                                        @click="deleteStream(parseInt(stream.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar stream"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="streams.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="streams"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre']"
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
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('streams.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update streams')"
                                            v-tooltip="'Editar stream'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteStream(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete streams')"
                                            v-tooltip="'Borrar stream'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #expansion="{ data }">
                                <DataTable 
                                    :value="data.links"
                                    class="mt-3"
                                    stripedRows
                                    tableStyle="min-width: 50rem"
                                >
                                    <Column field="nombre" header="Nombre"></Column>
                                    <Column field="link" header="Link"></Column>
                                    <Column header="Test">
                                        <template #body="slotProps">
                                            <a
                                                v-if="resolveLinkUrl(slotProps.data.link)"
                                                :href="resolveLinkUrl(slotProps.data.link)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-indigo-500"
                                                v-tooltip="'Abrir link'"
                                            >
                                                <i class="pi pi-link"></i>
                                            </a>
                                            <span v-else class="text-gray-400">-</span>
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
</template>
