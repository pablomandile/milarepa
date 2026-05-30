<script>
    export default {
        name: 'MaestrosIndex'
    }
</script>

<script setup>
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
    import { computed, ref } from 'vue';

    const props = defineProps({
        maestros: {
            type: Array,
            required: true
        }
    })

    const imageDialogVisible = ref(false);
    const selectedImageUrl = ref('');
    const expandedRows = ref([]);
    const expandedCardIds = ref([]);

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const isCardExpanded = (id) => expandedCardIds.value.includes(id);

    const toggleCardExpanded = (id) => {
        const idx = expandedCardIds.value.indexOf(id);
        if (idx === -1) {
            expandedCardIds.value.push(id);
        } else {
            expandedCardIds.value.splice(idx, 1);
        }
    };

    const maestrosFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.maestros;
        return props.maestros.filter((m) => {
            const campos = [m.nombre, m.telefono, m.email];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });

    const openImageDialog = (imageUrl) => {
        if (!imageUrl) return;
        selectedImageUrl.value = imageUrl;
        imageDialogVisible.value = true;
    };

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
                Swal.fire("¡Eliminado!", "El Maestro ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Maestro.", "error");
                },
            });
            }
        });
    };
    
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';

</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Maestr@s</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-5xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create maestros')">
                        <Link :href="route('maestros.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEV@ MAESTR@
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="maestros.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="maestrosFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="maestro in maestrosFiltradosMobile"
                            :key="maestro.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-3">
                                    <button
                                        v-if="maestro.imagen"
                                        type="button"
                                        class="relative inline-flex group cursor-zoom-in"
                                        @click="openImageDialog('/storage/' + maestro.imagen.ruta)"
                                    >
                                        <img
                                            :src="'/storage/' + maestro.imagen.ruta"
                                            alt="Foto maestro"
                                            class="h-14 w-14 rounded-full object-cover border border-gray-200 dark:border-gray-700"
                                        />
                                    </button>
                                    <div v-else class="h-14 w-14 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                                        <i class="fas fa-user text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="text-base font-semibold text-gray-800 dark:text-gray-100 flex-1 min-w-0 break-words">{{ maestro.nombre }}</p>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Teléfono</span>
                                        <span class="text-right">
                                            <a v-if="maestro.telefono" :href="`tel:${maestro.telefono}`" class="text-indigo-600 hover:text-indigo-800">
                                                {{ maestro.telefono }}
                                            </a>
                                            <span v-else>-</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Email</span>
                                        <span class="text-right break-all">
                                            <a v-if="maestro.email" :href="`mailto:${maestro.email}`" class="text-indigo-600 hover:text-indigo-800">
                                                {{ maestro.email }}
                                            </a>
                                            <span v-else>-</span>
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="toggleCardExpanded(maestro.id)"
                                >
                                    <span>{{ isCardExpanded(maestro.id) ? 'Ocultar detalles' : 'Ver más detalles' }}</span>
                                    <i class="pi" :class="isCardExpanded(maestro.id) ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                                </button>

                                <div v-if="isCardExpanded(maestro.id)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Sobre el maestr@</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                        {{ maestro.sobre_maestro || 'Sin descripción cargada.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update maestros')"
                                        :href="route('maestros.edit', parseInt(maestro.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar maestro"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete maestros')"
                                        @click="deleteMaestro(parseInt(maestro.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar maestro"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="maestros.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="maestros"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'telefono', 'email']"
                            v-model:expandedRows="expandedRows"
                            dataKey="id"
                            stripedRows
                            paginator
                            :rows="5"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 58rem"
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
                            <Column expander style="width: 4rem" />
                            <Column header="Foto">
                                <template #body="slotProps">
                                    <div class="flex items-center justify-center">
                                        <button
                                            v-if="slotProps.data.imagen"
                                            type="button"
                                            class="relative inline-flex group cursor-zoom-in"
                                            @click="openImageDialog('/storage/' + slotProps.data.imagen.ruta)"
                                        >
                                            <img
                                                :src="'/storage/' + slotProps.data.imagen.ruta"
                                                alt="Foto maestro"
                                                class="h-12 w-12 rounded object-cover border border-gray-200 dark:border-gray-700"
                                            />
                                            <span class="absolute -top-1 -right-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-white dark:bg-gray-800 text-indigo-600 border border-indigo-200 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                                <i class="pi pi-search-plus" style="font-size: 11px;"></i>
                                            </span>
                                        </button>
                                        <span v-else class="text-sm text-gray-400">Sin foto</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column field="telefono" header="Telefono"></Column>
                            <Column field="email" header="Correo electrónico"></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('maestros.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update maestros')"
                                            v-tooltip="'Editar maestro'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteMaestro(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete maestros')"
                                            v-tooltip="'Borrar maestro'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>
                            <template #expansion="{ data }">
                                <div class="p-4 bg-gray-50 dark:bg-gray-800/50">
                                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Sobre el maestr@</div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                        {{ data.sobre_maestro || 'Sin descripción cargada.' }}
                                    </p>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="imageDialogVisible"
            modal
            header="Foto de Maestro"
            :style="{ width: '720px' }"
        >
            <div class="w-full">
                <img
                    v-if="selectedImageUrl"
                    :src="selectedImageUrl"
                    alt="Foto de Maestro"
                    class="w-full max-h-[70vh] object-contain"
                />
            </div>
        </Dialog>
    </AppLayout>
</template>
