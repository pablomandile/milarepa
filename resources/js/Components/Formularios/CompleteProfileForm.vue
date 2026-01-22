<script>
    export default {
        name: 'CompleteProfileForm'
    }
</script>

<script setup>
import { computed, watch } from 'vue';
import FormSection from '@/Components/FormSection.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import SectionTitle from '@/Components/SectionTitle.vue'


const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    updating: {
        type: Boolean,
        required: true,
        ult: false
    },
    membresias: {
        type: Array,
        default: () => []
    },
    paises: {
        type: Array,
        default: () => []
    },
    provincias: {
        type: Array,
        default: () => []
    },
    municipios: {
        type: Array,
        default: () => []
    },
    barrios: {
        type: Array,
        default: () => []
    },
    sexos: {
        type: Array,
        default: () => []
    }

})

const provinciasFiltradas = computed(() => {
    if (!props.form.pais_id) return [];
    return props.provincias.filter(p => p.pais_id == props.form.pais_id);
});

const municipiosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id);
    if (!provId || isNaN(provId)) return [];

    const filtrados = props.municipios.filter((m) => {
        const mpid = parseInt(m.provincia_id);
        return mpid === provId;
    });
    
    console.log('Provincia ID:', provId, 'Municipios encontrados:', filtrados.length);
    return filtrados;
});

const barriosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id);
    if (provId !== 24) return [];

    return props.barrios.filter((b) => {
        const bpid = parseInt(b.provincia_id);
        return bpid === 24;
    });
});

watch(() => props.form.pais_id, () => {
    if (!provinciasFiltradas.value.find(p => p.id == props.form.provincia_id)) {
        props.form.provincia_id = '';
        props.form.municipio_id = '';
    }
});

watch(() => props.form.provincia_id, () => {
    if (!municipiosFiltrados.value.find(m => m.id == props.form.municipio_id)) {
        props.form.municipio_id = '';
    }
    if (parseInt(props.form.provincia_id) !== 24) {
        props.form.barrio_id = '';
    }
});

defineEmits(['submit'])

</script>

<template>
    <FormSection @submitted="() => $emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Perfil' : 'Completar Perfil' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Perfil de usuario' : 'Complete su perfil con la información solicitada' }}
                </template>
            </SectionTitle> 
        </template>
        <template #form>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 -mt-2 -ml-2">
                <!-- País y Provincia en una línea -->
                <div class="col-span-1">
                    <InputLabel for="pais_id" class="mt-4" value="País de residencia" :required="true"/>
                    <Dropdown
                        id="pais_id"
                        v-model="form.pais_id"
                        :options="paises"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccione un país"
                        class="w-full mt-1 border border-gray-300"
                    />
                    <InputError :message="$page.props.errors.pais_id" class="mt-2" />
                </div>
                <div class="col-span-1">
                    <InputLabel for="provincia_id" class="mt-4" value="Provincia" :required="true"/>
                    <Dropdown
                        id="provincia_id"
                        v-model="form.provincia_id"
                        :options="provinciasFiltradas"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccione una provincia"
                        class="w-full mt-1 border border-gray-300"
                    />
                    <InputError :message="$page.props.errors.provincia_id" class="mt-2" />
                </div>
                <div></div>

                <!-- Municipio y Barrio en una línea -->
                <div v-if="municipiosFiltrados.length" class="col-span-1">
                    <InputLabel for="municipio_id" class="mt-4" value="Municipio" :required="false"/>
                    <Dropdown
                        id="municipio_id"
                        v-model="form.municipio_id"
                        :options="municipiosFiltrados"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccione un municipio"
                        class="w-full mt-1 border border-gray-300"
                    />
                    <InputError :message="$page.props.errors.municipio_id" class="mt-2" />
                </div>
                <div v-if="barriosFiltrados.length" class="col-span-1">
                    <InputLabel for="barrio_id" class="mt-4" value="Barrio" :required="false"/>
                    <Dropdown
                        id="barrio_id"
                        v-model="form.barrio_id"
                        :options="barriosFiltrados"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccione un barrio"
                        class="w-full mt-1 border border-gray-300"
                    />
                    <InputError :message="$page.props.errors.barrio_id" class="mt-2" />
                </div>
                <div v-else></div>

                <!-- Dirección, Teléfono y Whatsapp en una línea -->
                <div class="col-span-1">
                    <InputLabel for="direccion" class="text-indigo-400 mt-4" v-tooltip="'Ingrese su dirección'" value="Dirección" :required="false"/>
                    <TextInput id="direccion" v-tooltip="'Ingrese su dirección'" v-model="form.direccion" type="text" autocomplete="direccion" class="mt-1 block w-full" />
                    <InputError :message="$page.props.errors.direccion" class="mt-2" />
                </div>
                <div class="col-span-1">
                    <InputLabel for="telefono" class="text-indigo-400 mt-4" value="Teléfono" :required="true"/>
                    <TextInput id="telefono" v-model="form.telefono" type="text" autocomplete="telefono" class="mt-1 block w-full" />
                    <InputError :message="$page.props.errors.telefono" class="mt-2" />
                </div>
                <div class="col-span-1">
                    <InputLabel for="whatsapp" class="text-indigo-400 mt-4" value="Whatsapp" :required="false"/>
                    <TextInput id="whatsapp" v-model="form.whatsapp" type="text" autocomplete="whatsapp" class="mt-1 block w-full" />
                    <InputError :message="$page.props.errors.whatsapp" class="mt-2" />
                </div>

                <!-- Fecha Nacimiento y Sexo -->
                <div class="col-span-1">
                    <InputLabel for="fecha_nacimiento" class="text-indigo-400 mt-4" value="Fecha de Nacimiento" :required="false"/>
                    <Calendar 
                        id="fecha_nacimiento" 
                        v-model="form.fecha_nacimiento" 
                        dateFormat="dd/mm/yy"
                        placeholder="Seleccione su fecha de nacimiento"
                        :showIcon="true"
                        class="w-full mt-1 border border-gray-300 rounded-md"
                        inputClass="rounded-md border border-gray-300"
                    />
                    <InputError :message="$page.props.errors.fecha_nacimiento" class="mt-2" />
                </div>
                <div class="col-span-1">
                    <InputLabel for="sexo_id" class="mt-4" value="Sexo" :required="true"/>
                    <Dropdown
                        id="sexo_id"
                        v-model="form.sexo_id"
                        :options="sexos"
                        optionLabel="sexo"
                        optionValue="id"
                        placeholder="Seleccione Sexo"
                        class="w-full mt-1 border border-gray-300"
                    />
                    <InputError :message="$page.props.errors.sexo_id" class="mt-2" />
                </div>
                <div></div>

            </div>

            <!-- Switches fuera del grid -->
            <div class="mt-4 space-y-3">
                <div class="flex items-center">
                    <InputSwitch
                        v-model="form.msgxmail" 
                        class="mr-3"
                    />
                    <label for="msgxmail" class="block text-sm text-indigo-400" :required="false" v-tooltip="'¿Desea recibir novedades de las nuevas actividades?'">
                        ¿Recibir novedades por correo electrónico?
                    </label>
                    <InputError :message="$page.props.errors.msgxmail" class="ml-2" />
                </div>            
                <div class="flex items-center">
                    <InputSwitch
                        v-model="form.msgxwapp" 
                        class="pr-6"
                    />
                    <label for="msgxwapp" class="block text-sm text-indigo-400 ml-3" :required="false">
                        ¿Desea recibir información de las Actividades por Whatsapp?
                    </label>
                    <InputError :message="$page.props.errors.msgxwapp" class="mt-2" />
                </div>
                <div class="flex items-center">
                    <InputSwitch
                        v-model="form.accesibilidad" 
                        class="mr-3"
                    />
                    <label for="accesibilidad" class="block text-sm text-indigo-400" :required="false" v-tooltip="'¿Posee algún impedimento visual, auditivo o de otro tipo?'">
                        ¿Tienes necesidades de Accesibilidad?
                    </label>
                    <InputError :message="$page.props.errors.accesibilidad" class="ml-2" />
                </div>
            </div>

            <div v-if="form.accesibilidad" class="mt-6">
                <InputLabel for="accesibilidad_desc" class="text-indigo-400" value="Describa su impedimento" :required="false"/>
                <TextInput
                    id="accesibilidad_desc"
                    v-model="form.accesibilidad_desc"
                    type="text"
                    autocomplete="accesibilidad_desc"
                    class="mt-1 block w-full"
                    v-tooltip="'¿Posee algún impedimento visual, auditivo o de otro tipo?'"
                />
                <InputError :message="$page.props.errors.accesibilidad_desc" class="mt-2" />
            </div>
            <!-- <div class="col-span-6 sm:col-span-6">
                <InputLabel for="membresia_id" value="Membresía" :required="true" v-tooltip="'Si no posee membresía puede elegir Sin Membresía - CMKA'"/>
                <Dropdown
                    id="membresia_id"
                    v-model="form.membresia_id"
                    :options="membresias"
                    optionLabel="label"
                    optionValue="id"
                    placeholder="Seleccione membresia"
                    class="w-full mt-1 md:w-14rem border border-gray-300"
                />
                <InputError :message="$page.props.errors.es_maestro" class="mt-2" />
            </div> -->
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>

