<script>
    export default {
        name: 'PermisosIndex'
    }
</script>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Swal from 'sweetalert2';

const props = defineProps({
    roles: Object,
    permissions: Array,
});

const page = usePage();
const expandedRoles = ref(new Set());

// Mostrar toast si hay mensaje flash
onMounted(() => {
    if (page.props.flash?.success) {
        Swal.fire({
            title: '¡Éxito!',
            text: page.props.flash.success,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    }
});

const getPermissionsByRole = (roleId) => {
    const role = props.roles.data.find(r => r.id === roleId);
    if (role && role.permissions) {
        return role.permissions.map(p => p.name).join(', ');
    }
    return 'Sin permisos';
};

const formatRoleName = (roleName) => {
    if (roleName === 'admin') return 'Administrador';
    if (roleName === 'asistant') return 'Asistente';
    return roleName.charAt(0).toUpperCase() + roleName.slice(1);
};

// Agrupar permisos por recurso
const groupPermissionsByResource = (permissions) => {
    const grouped = {};
    
    permissions.forEach(permission => {
        // Separar acción y recurso (ej: "create maestros" -> ["create", "maestros"])
        const parts = permission.name.split(' ');
        const action = parts[0]; // create, update, delete, read
        const resource = parts.slice(1).join(' '); // maestros, tipos_actividad, etc.
        
        if (!grouped[resource]) {
            grouped[resource] = {
                name: resource,
                actions: []
            };
        }
        
        grouped[resource].actions.push(action);
    });
    
    // Convertir a array y ordenar por nombre de recurso
    return Object.values(grouped).sort((a, b) => a.name.localeCompare(b.name));
};

// Formatear nombre del recurso
const formatResourceName = (resource) => {
    return resource
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

const getVisibleResources = (permissions, roleId) => {
    const grouped = groupPermissionsByResource(permissions);
    return isExpanded(roleId) ? grouped : grouped.slice(0, 10);
};

const hasMoreResources = (permissions) => {
    return groupPermissionsByResource(permissions).length > 10;
};

const toggleExpanded = (roleId) => {
    if (expandedRoles.value.has(roleId)) {
        expandedRoles.value.delete(roleId);
    } else {
        expandedRoles.value.add(roleId);
    }
};

const isExpanded = (roleId) => expandedRoles.value.has(roleId);

const getVisiblePermissions = (permissions, roleId) => {
    return isExpanded(roleId) ? permissions : permissions.slice(0, 20);
};

const hasMorePermissions = (permissions) => permissions.length > 20;

</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Roles y Permisos</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Roles y sus Permisos</h2>
                        <p class="text-sm text-gray-600 mt-1">Visualiza los permisos asignados a cada rol del sistema.</p>
                    </div>
                    <div class="mt-6">
                        <DataTable :value="roles.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="name" header="Rol" style="width: 15%">
                                <template #body="slotProps">
                                    <span class="font-semibold text-gray-800">{{ formatRoleName(slotProps.data.name) }}</span>
                                </template>
                            </Column>
                            <Column header="Módulos / Permisos" style="width: 85%">
                                <template #body="slotProps">
                                    <div v-if="slotProps.data.permissions && slotProps.data.permissions.length > 0" class="space-y-2">
                                        <div
                                            v-for="resource in getVisibleResources(slotProps.data.permissions, slotProps.data.id)"
                                            :key="resource.name"
                                            class="flex items-start gap-2"
                                        >
                                            <span class="text-gray-700 min-w-[150px]">
                                                {{ formatResourceName(resource.name) }}:
                                            </span>
                                            <div class="flex flex-wrap gap-1">
                                                <span
                                                    v-for="action in resource.actions"
                                                    :key="action"
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': action === 'create',
                                                        'bg-blue-100 text-blue-800': action === 'read',
                                                        'bg-yellow-100 text-yellow-800': action === 'update',
                                                        'bg-red-100 text-red-800': action === 'delete',
                                                        'bg-purple-100 text-purple-800': action === 'asign'
                                                    }"
                                                >
                                                    {{ action }}
                                                </span>
                                            </div>
                                        </div>
                                        <button
                                            v-if="hasMoreResources(slotProps.data.permissions)"
                                            @click="toggleExpanded(slotProps.data.id)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 cursor-pointer mt-2"
                                        >
                                            {{ isExpanded(slotProps.data.id) ? '- Ver menos' : `+ Ver más (${groupPermissionsByResource(slotProps.data.permissions).length - 10})` }}
                                        </button>
                                    </div>
                                    <span v-else class="text-gray-500 text-sm italic">
                                        Sin permisos asignados
                                    </span>
                                </template>
                            </Column>
                            <Column header="Acciones" style="width: 10%" class="text-center">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center">
                                        <Link
                                            :href="route('roles.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update roles')"
                                            v-tooltip="'Editar rol'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
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
