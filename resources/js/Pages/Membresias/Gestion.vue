<script>
    export default {
        name: 'MembresiasGestion'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router, usePage } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Dropdown from 'primevue/dropdown';

    const props = defineProps({
        membresias: {
            type: Array,
            required: true
        },
        precioGrupos: {
            type: Array,
            default: () => []
        }
    });

    const page = usePage();

    const grupoSeleccionado = ref(null);
    const aplicandoGrupo = ref(false);

    const puedeAplicarGrupo = computed(() => {
        const permisos = page.props.user?.permissions || [];
        return permisos.includes('update precio-grupos');
    });

    const grupoOptions = computed(() => {
        return (props.precioGrupos || []).map((g) => ({
            id: g.id,
            label: g.fecha_desde
                ? `${g.nombre} (desde ${new Date(g.fecha_desde).toLocaleDateString('es-AR')})`
                : g.nombre,
        }));
    });

    const grupoActualNombre = computed(() => {
        const found = (props.precioGrupos || []).find((g) => g.id === grupoSeleccionado.value);
        return found?.nombre || '';
    });

    const aplicarGrupo = () => {
        if (!grupoSeleccionado.value) {
            Swal.fire('Seleccioná un grupo', 'Elegí un grupo de precios del listado antes de aplicar.', 'info');
            return;
        }
        Swal.fire({
            title: '¿Aplicar precios?',
            html: `Se actualizará el <strong>valor</strong> de todas las membresías con los precios del grupo <strong>${grupoActualNombre.value}</strong>.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, aplicar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (!result.isConfirmed) return;
            aplicandoGrupo.value = true;
            router.post(route('precio-grupos.aplicar', grupoSeleccionado.value), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('¡Listo!', 'Precios actualizados.', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'No se pudieron aplicar los precios.', 'error');
                },
                onFinish: () => {
                    aplicandoGrupo.value = false;
                }
            });
        });
    };

    const deleteMembresia = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('membresias.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Membresía ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Membresía.", "error");
                },
            });
            }
        });
    };

    const formatArs = (valor) => {
        if (valor === null || valor === undefined) return '$ 0,00';
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 2
        }).format(valor);
    };

    const imagenMembresiaSrc = (membresia) => {
        const ruta = membresia?.imagen?.ruta || '';
        return ruta ? `/storage/${ruta}` : '';
    };

    const expandedRows = ref({});

</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Gestión de Membresías</h1>
        </template>
        <div class="py-12">
            <div class="w-full p-4 sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-fullo">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create membresias')">
                        <Link :href="route('membresias.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" >
                            NUEVA MEMBRESÍA
                        </Link>
                    </div>
                    <div
                        v-if="puedeAplicarGrupo && grupoOptions.length > 0"
                        class="mt-4 flex flex-col md:flex-row md:items-end gap-3 p-4 rounded-md bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600"
                    >
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tomar precios de
                            </label>
                            <Dropdown
                                v-model="grupoSeleccionado"
                                :options="grupoOptions"
                                optionLabel="label"
                                optionValue="id"
                                placeholder="Elegí un grupo de precios"
                                class="w-full md:w-96 border border-gray-300 dark:border-gray-600"
                                showClear
                            />
                        </div>
                        <div>
                            <button
                                @click="aplicarGrupo"
                                :disabled="!grupoSeleccionado || aplicandoGrupo"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                                {{ aplicandoGrupo ? 'Aplicando…' : 'Cambiar' }}
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 md:ml-2 md:self-center">
                            Se actualiza el valor de cada membresía con el precio del grupo seleccionado.
                        </p>
                    </div>
                    <div class="mt-4">
                        <DataTable
                            v-model:expandedRows="expandedRows"
                            :value="membresias"
                            dataKey="id"
                            stripedRows
                            paginator
                            :rows="5"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 60rem"
                        >
                            <Column expander style="width: 3rem" />
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="abreviacion" header="Abrev.">
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.abreviacion" class="inline-block px-2 py-1 text-xs font-mono font-semibold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 dark:text-indigo-300 rounded">
                                        {{ slotProps.data.abreviacion }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </template>
                            </Column>
                            <Column header="Imagen">
                                <template #body="slotProps">
                                    <img
                                        v-if="imagenMembresiaSrc(slotProps.data)"
                                        :src="imagenMembresiaSrc(slotProps.data)"
                                        :alt="`Imagen de ${slotProps.data.nombre}`"
                                        class="h-12 w-20 object-contain rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
                                    />
                                    <span v-else class="text-xs text-gray-500">Sin imagen</span>
                                </template>
                            </Column>
                            <Column field="descripcion" header="Descripción"></Column>
                            <Column field="entidad.nombre" header="Entidad"></Column>
                            <Column header="Botón de Pago">
                                <template #body="slotProps">
                                    {{ slotProps.data.boton_pago?.nombre || 'Sin botón' }}
                                </template>
                            </Column>
                            <Column field="valor" header="Valor">
                                <template #body="slotProps">
                                    {{ formatArs(slotProps.data.valor) }}
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('membresias.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update membresias')"
                                            v-tooltip="'Editar membresía'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteMembresia(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete membresias')"
                                            v-tooltip="'Borrar membresía'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>
                            <template #expansion="slotProps">
                                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-md">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">Info</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                        {{ slotProps.data.info || 'Sin info cargada.' }}
                                    </p>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
