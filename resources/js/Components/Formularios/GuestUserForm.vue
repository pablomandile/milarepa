<script>
export default {
    name: 'GuestUserForm',
};
</script>

<script setup>
import { computed } from 'vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import TextInput from '../TextInput.vue';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import InputMask from 'primevue/inputmask';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    paises: {
        type: Array,
        default: () => [],
    },
    provincias: {
        type: Array,
        default: () => [],
    },
    municipios: {
        type: Array,
        default: () => [],
    },
    barrios: {
        type: Array,
        default: () => [],
    },
});

const provinciasFiltradas = computed(() => {
    if (!props.form.pais_id) return [];
    return props.provincias.filter((p) => p.pais_id == props.form.pais_id);
});

const municipiosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id);
    if (!provId || isNaN(provId)) return [];
    return props.municipios.filter((m) => parseInt(m.provincia_id) === provId);
});

const barriosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id);
    if (provId !== 24) return [];
    return props.barrios.filter((b) => parseInt(b.provincia_id) === 24);
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-2">
        <div class="col-span-1">
            <InputLabel for="guest_name" value="Nombre Completo" :required="true" class="mt-2" />
            <TextInput id="guest_name" v-model="form.name" type="text" class="mt-1 block w-full" />
            <InputError :message="errors.name?.[0]" class="mt-2" />
        </div>
        <div class="col-span-1">
            <InputLabel for="guest_email" value="Correo Electrónico" :required="true" class="mt-2" />
            <TextInput id="guest_email" v-model="form.email" type="email" class="mt-1 block w-full" />
            <InputError :message="errors.email?.[0]" class="mt-2" />
        </div>
        <div class="col-span-1" />

        <div class="col-span-1">
            <InputLabel for="guest_telefono" value="Teléfono" :required="false" class="mt-2" />
            <InputMask
                id="guest_telefono"
                v-model="form.telefono"
                mask="+999 99 9999 9999"
                placeholder="+549 11 1234 5678"
                slotChar="_"
                :unmask="true"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
            <InputError :message="errors.telefono?.[0]" class="mt-2" />
        </div>
        <div class="col-span-1">
            <InputLabel for="guest_whatsapp" value="WhatsApp" :required="false" />
            <InputMask
                id="guest_whatsapp"
                v-model="form.whatsapp"
                mask="+999 99 9999 9999"
                placeholder="+549 11 1234 5678"
                slotChar="_"
                :unmask="true"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
            <InputError :message="errors.whatsapp?.[0]" class="mt-2" />
        </div>
        <div class="col-span-1" />

        <div class="col-span-1">
            <InputLabel for="guest_pais_id" value="País de residencia" :required="true" />
            <Dropdown
                id="guest_pais_id"
                v-model="form.pais_id"
                :options="paises"
                optionLabel="nombre"
                optionValue="id"
                placeholder="Seleccione un país"
                class="w-full mt-1 border border-gray-300"
            />
            <InputError :message="errors.pais_id?.[0]" class="mt-2" />
        </div>
        <div class="col-span-1">
            <InputLabel for="guest_provincia_id" value="Provincia" :required="true" />
            <Dropdown
                id="guest_provincia_id"
                v-model="form.provincia_id"
                :options="provinciasFiltradas"
                optionLabel="nombre"
                optionValue="id"
                placeholder="Seleccione una provincia"
                class="w-full mt-1 border border-gray-300"
            />
            <InputError :message="errors.provincia_id?.[0]" class="mt-2" />
        </div>
        <div class="col-span-1" />

        <div v-if="municipiosFiltrados.length" class="col-span-1">
            <InputLabel for="guest_municipio_id" value="Municipio" :required="false" />
            <Dropdown
                id="guest_municipio_id"
                v-model="form.municipio_id"
                :options="municipiosFiltrados"
                optionLabel="nombre"
                optionValue="id"
                placeholder="Seleccione un municipio"
                class="w-full mt-1 border border-gray-300"
            />
            <InputError :message="errors.municipio_id?.[0]" class="mt-2" />
        </div>
        <div v-if="barriosFiltrados.length" class="col-span-1">
            <InputLabel for="guest_barrio_id" value="Barrio" :required="false" />
            <Dropdown
                id="guest_barrio_id"
                v-model="form.barrio_id"
                :options="barriosFiltrados"
                optionLabel="nombre"
                optionValue="id"
                placeholder="Seleccione un barrio"
                class="w-full mt-1 border border-gray-300"
            />
            <InputError :message="errors.barrio_id?.[0]" class="mt-2" />
        </div>
        <div v-else class="col-span-1" />

        <div class="col-span-3">
            <InputLabel for="guest_direccion" value="Ingrese su dirección" :required="false" />
            <TextInput id="guest_direccion" v-model="form.direccion" type="text" class="mt-1 block w-full" />
            <InputError :message="errors.direccion?.[0]" class="mt-2" />
        </div>

        <div class="col-span-3 space-y-3 px-2">
            <div class="flex items-center">
                <InputSwitch v-model="form.msgxmail" class="mr-3" />
                <label class="block text-sm text-indigo-400">
                    ¿Recibir novedades por correo electrónico?
                </label>
            </div>
            <div class="flex items-center">
                <InputSwitch v-model="form.msgxwapp" class="mr-3" />
                <label class="block text-sm text-indigo-400">
                    ¿Desea recibir información de las Actividades por Whatsapp?
                </label>
            </div>
            <div class="flex items-center">
                <InputSwitch v-model="form.accesibilidad" class="mr-3" />
                <label class="block text-sm text-indigo-400">
                    ¿Tienes necesidades de Accesibilidad?
                </label>
            </div>
        </div>

        <div v-if="form.accesibilidad" class="col-span-3">
            <InputLabel for="guest_accesibilidad_desc" value="Describa su impedimento" :required="false" />
            <TextInput id="guest_accesibilidad_desc" v-model="form.accesibilidad_desc" type="text" class="mt-1 block w-full" />
            <InputError :message="errors.accesibilidad_desc?.[0]" class="mt-2" />
        </div>

        <div class="col-span-3">
            <div class="flex items-center">
                <InputSwitch v-model="form.registrar_datos" class="mr-3" />
                <label class="block text-sm text-indigo-400">
                    Quiero registrar mis datos
                </label>
            </div>
        </div>
    </div>
</template>
