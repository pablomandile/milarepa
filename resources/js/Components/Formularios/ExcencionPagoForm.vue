<script>
    export default {
        name: 'ExcencionPagoForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Dropdown from 'primevue/dropdown';
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    updating: {
        type: Boolean,
        required: true,
        default: false
    },
    usuarios: {
        type: Array,
        default: () => []
    },
    actividades: {
        type: Array,
        default: () => []
    }
})

defineEmits(['submit'])

const usuariosOptions = computed(() => {
    return props.usuarios.map((u) => ({
        id: u.id,
        label: u.email ? `${u.name} (${u.email})` : u.name
    }));
});

const actividadesOptions = computed(() => {
    return props.actividades.map((a) => {
        const fecha = a.fecha_inicio
            ? new Date(a.fecha_inicio).toLocaleDateString('es-AR')
            : null;
        return {
            id: a.id,
            label: fecha ? `${a.nombre} (${fecha})` : a.nombre
        };
    });
});
</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Excención de Pago' : 'Nueva Excención de Pago' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando la excención seleccionada.' : 'Agregando una nueva excención de pago.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6 mb-2">
                <InputLabel for="user_id" value="Usuario" :required="true"/>
                <Dropdown
                    id="user_id"
                    v-model="form.user_id"
                    :options="usuariosOptions"
                    optionLabel="label"
                    optionValue="id"
                    placeholder="Seleccione un usuario"
                    class="w-full mt-1 border border-gray-300"
                    filter
                    filterPlaceholder="Buscar por nombre"
                    showClear
                />
                <InputError :message="$page.props.errors.user_id" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-2">
                <InputLabel for="actividad_id" value="Actividad" :required="true"/>
                <Dropdown
                    id="actividad_id"
                    v-model="form.actividad_id"
                    :options="actividadesOptions"
                    optionLabel="label"
                    optionValue="id"
                    placeholder="Seleccione una actividad"
                    class="w-full mt-1 border border-gray-300"
                    filter
                    filterPlaceholder="Buscar por actividad"
                    showClear
                />
                <InputError :message="$page.props.errors.actividad_id" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-2">
                <InputLabel for="importe" value="Importe" :required="true"/>
                <TextInput
                    id="importe"
                    v-model="form.importe"
                    type="number"
                    step="0.01"
                    min="0"
                    autocomplete="importe"
                    class="mt-1 block w-full"
                />
                <InputError :message="$page.props.errors.importe" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
