<script>
export default {
    name: 'ProgramaGrabacionesIndex',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
    programaEstudios: {
        type: Array,
        required: true,
    },
});

const page = usePage();

const form = useForm({
    programa_estudio_id: null,
    nombre: '',
    descripcion: '',
    archivo: null,
});

const archivoNombre = ref('');

const opcionesProgramas = computed(() =>
    props.programaEstudios.map((p) => ({
        id: p.id,
        nombre: p.abreviacion ? `${p.nombre} (${p.abreviacion})` : p.nombre,
    }))
);

const totalGrabaciones = computed(() =>
    props.programaEstudios.reduce((sum, p) => sum + (p.programa_grabaciones?.length || 0), 0)
);

function seleccionarArchivo(event) {
    const f = event.target.files?.[0];
    if (!f) {
        form.archivo = null;
        archivoNombre.value = '';
        return;
    }
    form.archivo = f;
    archivoNombre.value = `${f.name} (${formatBytes(f.size)})`;
}

function formatBytes(bytes) {
    if (!bytes && bytes !== 0) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1073741824) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${(bytes / 1073741824).toFixed(2)} GB`;
}

function formatFecha(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function urlGrabacion(g) {
    return `/storage/${g.archivo}`;
}

function submit() {
    form.post(route('programa-grabaciones.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            archivoNombre.value = '';
        },
    });
}

function borrar(grabacion) {
    Swal.fire({
        title: '¿Eliminar grabación?',
        text: `"${grabacion.nombre}" se borrará permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('programa-grabaciones.destroy', grabacion.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'La grabación ha sido eliminada.', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'Hubo un problema al eliminar la grabación.', 'error');
                },
            });
        }
    });
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Grabaciones por Programa de Estudio
            </h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Total: {{ totalGrabaciones }} grabaciones distribuidas en {{ programaEstudios.length }} programas
                    </p>
                    <Link
                        :href="route('programa-estudios.index')"
                        class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                    >
                        Volver
                    </Link>
                </div>

                <div v-if="page.props.flash?.success" class="rounded-md bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                    {{ page.props.flash.success }}
                </div>

                <!-- Form de upload -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Subir nueva grabación
                    </h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Programa de Estudio <span class="text-red-500">*</span>
                            </label>
                            <Dropdown
                                v-model="form.programa_estudio_id"
                                :options="opcionesProgramas"
                                optionLabel="nombre"
                                optionValue="id"
                                placeholder="Seleccioná un programa"
                                class="w-full border border-gray-300 dark:border-gray-600"
                            />
                            <p v-if="form.errors.programa_estudio_id" class="mt-1 text-sm text-red-600">{{ form.errors.programa_estudio_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nombre de la grabación <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.nombre"
                                type="text"
                                placeholder="Ej: Clase 1 - Introducción"
                                class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Descripción (opcional)
                            </label>
                            <textarea
                                v-model="form.descripcion"
                                rows="3"
                                placeholder="Notas, contenido, fecha de grabación, etc."
                                class="w-full rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">{{ form.errors.descripcion }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Archivo MP3 <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-3 flex-wrap">
                                <label class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2">
                                    <i class="pi pi-upload"></i>
                                    Elegir archivo
                                    <input
                                        type="file"
                                        accept=".mp3,audio/mpeg"
                                        class="hidden"
                                        @change="seleccionarArchivo"
                                    />
                                </label>
                                <span v-if="archivoNombre" class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ archivoNombre }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Tamaño máximo: 300 MB. Solo archivos MP3.
                            </p>
                            <p v-if="form.errors.archivo" class="mt-1 text-sm text-red-600">{{ form.errors.archivo }}</p>
                        </div>

                        <!-- Progress bar -->
                        <div v-if="form.processing && form.progress" class="space-y-1">
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                <span>Subiendo...</span>
                                <span>{{ form.progress.percentage }}%</span>
                            </div>
                            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded">
                                <div class="h-2 bg-indigo-600 rounded transition-all" :style="{ width: `${form.progress.percentage}%` }"></div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium py-2 px-6 rounded inline-flex items-center gap-2"
                            >
                                <i class="pi pi-cloud-upload"></i>
                                {{ form.processing ? 'Subiendo...' : 'Subir grabación' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Listado por programa -->
                <div class="space-y-4">
                    <div
                        v-for="programa in programaEstudios"
                        :key="programa.id"
                        class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
                    >
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-3">
                            <h3 class="text-white font-semibold flex items-center gap-2">
                                <i class="pi pi-book"></i>
                                {{ programa.nombre }}
                                <span v-if="programa.abreviacion" class="px-2 py-0.5 text-xs font-mono bg-white/20 rounded">{{ programa.abreviacion }}</span>
                                <span class="ml-auto text-xs font-normal opacity-80">
                                    {{ programa.programa_grabaciones?.length || 0 }} grabación(es)
                                </span>
                            </h3>
                        </div>

                        <div class="p-4">
                            <p v-if="!programa.programa_grabaciones || programa.programa_grabaciones.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">
                                Sin grabaciones cargadas todavía.
                            </p>
                            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li
                                    v-for="g in programa.programa_grabaciones"
                                    :key="g.id"
                                    class="py-3 flex flex-col md:flex-row md:items-center gap-3"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ g.nombre }}</p>
                                        <p v-if="g.descripcion" class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 whitespace-pre-wrap">{{ g.descripcion }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                            {{ formatBytes(g.size_bytes) }} · {{ formatFecha(g.created_at) }}
                                        </p>
                                    </div>
                                    <audio :src="urlGrabacion(g)" controls class="w-full md:w-72"></audio>
                                    <div class="flex gap-2">
                                        <a
                                            :href="urlGrabacion(g)"
                                            target="_blank"
                                            rel="noopener"
                                            v-tooltip="'Abrir en nueva pestaña'"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300"
                                        >
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <button
                                            v-if="$page.props.user.permissions.includes('delete programa-grabaciones')"
                                            type="button"
                                            @click="borrar(g)"
                                            v-tooltip="'Borrar grabación'"
                                            style="background: none; border: none; cursor: pointer;"
                                        >
                                            <i class="fas fa-trash text-red-500 hover:text-red-700"></i>
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
