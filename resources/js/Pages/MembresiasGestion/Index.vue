<template>
    <AppLayout title="Gestionar Membresías - Usuarios">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-users-cog mr-2 text-indigo-600"></i>
                Gestión de Membresías de Usuarios
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Mensaje de éxito -->
                <div v-if="$page.props.flash.success" class="mb-4 p-4 rounded-lg bg-green-100 text-green-800 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ $page.props.flash.success }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Tabla de Usuarios -->
                        <div v-if="usuarios.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-300">
                                <thead class="bg-indigo-600 text-white">
                                    <tr>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Usuario</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Email</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Membresía Actual</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Fecha de Inscripción</th>
                                        <th class="border border-gray-300 px-6 py-3 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="usuario in usuarios.data" :key="usuario.id" class="hover:bg-gray-50">
                                        <td class="border border-gray-300 px-6 py-4">
                                            <p class="font-semibold text-gray-800">{{ usuario.name }}</p>
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            {{ usuario.email }}
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            <span v-if="tieneMembresiaReal(usuario)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-crown mr-1"></i>
                                                {{ usuario.membresia.nombre }}
                                            </span>
                                            <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                                Sin membresía
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            <span v-if="tieneMembresiaReal(usuario) && usuario.membresia_inscripcion_fecha" class="text-sm text-gray-700">
                                                {{ formatearFecha(usuario.membresia_inscripcion_fecha) }}
                                            </span>
                                            <span v-else class="text-sm text-gray-500">-</span>
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button 
                                                    @click="abrirModalAsignar(usuario)"
                                                    class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition text-sm"
                                                    title="Asignar membresía"
                                                >
                                                    <i class="fas fa-plus-circle mr-1"></i>
                                                    Asignar
                                                </button>
                                                <button 
                                                    v-if="tieneMembresiaReal(usuario)"
                                                    @click="eliminarMembresia(usuario)"
                                                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition text-sm"
                                                    title="Eliminar membresía"
                                                >
                                                    <i class="fas fa-trash mr-1"></i>
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mensaje si no hay usuarios -->
                        <div v-else class="text-center py-12">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 text-lg">No hay usuarios registrados</p>
                        </div>

                        <!-- Paginación -->
                        <div v-if="usuarios.links.length > 3" class="mt-6 flex justify-center gap-2">
                            <Link 
                                v-for="link in usuarios.links" 
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-4 py-2 rounded transition',
                                    link.active 
                                        ? 'bg-indigo-600 text-white' 
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para asignar membresía -->
        <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Asignar Membresía a {{ usuarioSeleccionado?.name }}
                </h3>

                <form @submit.prevent="asignarMembresia">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar Membresía
                        </label>
                        <select 
                            v-model="formMembresiaId"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        >
                            <option value="">-- Selecciona una membresía --</option>
                            <option v-for="membresia in membresias" :key="membresia.id" :value="membresia.id">
                                {{ membresia.nombre }} - {{ membresia.entidad.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            type="submit"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                        >
                            <i class="fas fa-check mr-2"></i>
                            Asignar
                        </button>
                        <button 
                            type="button"
                            @click="cerrarModal"
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-medium"
                        >
                            <i class="fas fa-times mr-2"></i>
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

defineProps({
    usuarios: Object,
    membresias: Array
});

const mostrarModal = ref(false);
const usuarioSeleccionado = ref(null);
const formMembresiaId = ref('');

const tieneMembresiaReal = (usuario) => {
    return usuario.membresia && usuario.membresia.id !== 1;
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

const abrirModalAsignar = (usuario) => {
    usuarioSeleccionado.value = usuario;
    formMembresiaId.value = '';
    mostrarModal.value = true;
};

const cerrarModal = () => {
    mostrarModal.value = false;
    usuarioSeleccionado.value = null;
    formMembresiaId.value = '';
};

const asignarMembresia = () => {
    if (!formMembresiaId.value) {
        Swal.fire('Error', 'Debes seleccionar una membresía', 'error');
        return;
    }

    const form = useForm({
        membresia_id: formMembresiaId.value
    });

    form.put(route('membresias.asignar', usuarioSeleccionado.value.id), {
        onSuccess: () => {
            cerrarModal();
        }
    });
};

const eliminarMembresia = (usuario) => {
    Swal.fire({
        title: '¿Está seguro?',
        text: `Se eliminará la membresía de ${usuario.name}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = useForm({});
            form.delete(route('membresias.eliminar', usuario.id));
        }
    });
};
</script>
