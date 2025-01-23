<script>
    export default {
        name: 'CompleteProfileForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';


defineProps({
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
    localidades: {
        type: Array,
        default: () => []
    },
    sexos: {
        type: Array,
        default: () => []
    }

})

defineEmits(['submit'])

</script>

<template>
    <FormSection @submitted="() => $emit('submit')">
        <template #title>
            {{ updating ? 'Actualizar Perfil' : 'Completar Perfil' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando la Entidad seleccionada' : 'Las Entidades pueden ser Centros, Anexos, lugares de retiro o salas para charlas' }}
        </template>
        <template #form>
            <div class="mt-4 flex items-center col-span-6">
                <InputSwitch
                    v-model="form.msgxmail" 
                    class="mr-3"
                    />
                <label for="msgxmail" class="block text-sm text-indigo-400">
                    ¿Desea recibir información por correo electrónico?
                </label>
                <InputError :message="$page.props.errors.msgxmail" class="ml-2" />
            </div>

            <div class="mt-4 flex items-center col-span-6">
                <InputSwitch
                    v-model="form.accesibilidad" 
                    class="mr-3"
                    />
                <label for="accesibilidad" class="block text-sm text-indigo-400" :required="false" v-tooltip="'¿Posee algún impedimento visual, auditivo o de otro tipo?'">
                    ¿Tienes necesidades de Accesibilidad?
                </label>
                <InputError :message="$page.props.errors.accesibilidad" class="ml-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="accesibilidad_desc" class="text-indigo-400" value="Describa su impedimento" :required="false"/>
                <TextInput id="accesibilidad_desc" v-model="form.accesibilidad_desc" type="text" autocomplete="accesibilidad_desc" class="mt-1 block w-full" v-tooltip="'¿Posee algún impedimento visual, auditivo o de otro tipo?'"/>
                <InputError :message="$page.props.errors.accesibilidad_desc" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="direccion" class="text-indigo-400" v-tooltip="'Ingrese su dirección'" value="Dirección" :required="false"/>
                <TextInput id="direccion" v-tooltip="'Ingrese su dirección'" v-model="form.direccion" type="text" autocomplete="direccion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.direccion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="pais_id" value="País de residencia" :required="true"/>
                <Dropdown
                    id="pais_id"
                    v-model="form.pais_id"
                    :options="paises"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione un país"
                    class="w-full mt-1 md:w-14rem border border-gray-300"
                />
                <InputError :message="$page.props.errors.pais_id" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="localidad_id" value="Localidad" :required="true"/>
                <Dropdown
                    id="localidad_id"
                    v-model="form.localidad_id"
                    :options="localidades"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione una localidad"
                    class="w-full mt-1 md:w-14rem border border-gray-300"
                />
                <InputError :message="$page.props.errors.localidad_id" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="telefono" class="text-indigo-400" value="Teléfono" :required="true"/>
                <TextInput id="telefono" v-model="form.telefono" type="text" autocomplete="telefono" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.telefono" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="whatsapp" class="text-indigo-400" value="Whatsapp" :required="false"/>
                <TextInput id="whatsapp" v-model="form.whatsapp" type="text" autocomplete="whatsapp" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.whatsapp" class="mt-2" />
            </div>
            <div class="mt-4 flex items-center col-span-6">
                <InputSwitch
                    v-model="form.msgxwapp" 
                    class="mr-3"
                    />
                <label for="msgxwapp" class="block text-sm text-indigo-400" :required="false">
                    ¿Desea recibir información de las Actividades por Whatsapp?
                </label>
                <InputError :message="$page.props.errors.msgxwapp" class="ml-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="edad" class="text-indigo-400" value="Edad" :required="false"/>
                <TextInput id="edad" v-model="form.edad" type="number" autocomplete="edad" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.edad" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="sexo_id" value="Sexo" :required="true"/>
                <Dropdown
                    id="sexo_id"
                    v-model="form.sexo_id"
                    :options="sexos"
                    optionLabel="sexo"
                    optionValue="id"
                    placeholder="Seleccione Sexo"
                    class="w-full mt-1 md:w-14rem border border-gray-300"
                />
                <InputError :message="$page.props.errors.sexo_id" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
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
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
