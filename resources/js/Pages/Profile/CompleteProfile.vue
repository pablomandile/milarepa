<script>
    export default {
        name: 'CompleteProfile'
    }
</script>

<script setup>
    import { computed } from 'vue';
    import { Link, useForm, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import CompleteProfileForm from '@/Components/Formularios/CompleteProfileForm.vue'
    import ScrollTop from 'primevue/scrolltop';
    import Swal from 'sweetalert2';

    const props = defineProps({
        user: {
            type: Object,
            default: () => ({}),
        },
        membresias: {
            type: Array,
            default: () => [],
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
        sexos: {
            type: Array,
            default: () => [],
        },
        programa_estudios: {
            type: Array,
            default: () => [],
        },
        updating: {
            type: Boolean,
            default: false,
        },
        target_user_id: {
            type: [Number, String],
            default: null,
        },
    });

  
    const page = usePage();
    const isAsistant = computed(() => {
        const roles = (page.props.user?.roles || []).map((role) => String(role).toLowerCase());
        return roles.includes('asistant');
    });

    const canSkipProfile = computed(() => {
        const hasPais = !!form.pais_id;
        const hasProvincia = !!form.provincia_id;
        const hasTelefono = !!String(form.telefono || '').trim();
        const hasSexo = !!form.sexo_id;
        return hasPais && hasProvincia && hasTelefono && hasSexo;
    });
    const form = useForm({
        accesibilidad: false,
        accesibilidad_desc: '',
        direccion: '',
        pais_id: '',
        provincia_id: '',
        municipio_id: '',
        barrio_id: '',
        telefono: '',
        whatsapp: '',
        fecha_nacimiento: null,
        sexo_id: '',
        membresia_id: '',
        programa_estudio_id: '',
        es_maestro: false,
        es_coordinador: false,
        msgxmail: false,
        msgxwapp: false,
        perfil_completo: false

    });

    function toBoolean(value) {
        return value === true || value === 1 || value === '1';
    }

    function parseISODateToLocal(dateStr) {
        if (!dateStr) return null;
        const isoPart = String(dateStr).split('T')[0];
        const [y, m, d] = isoPart.split('-').map((n) => parseInt(n, 10));
        if (Number.isNaN(y) || Number.isNaN(m) || Number.isNaN(d)) return null;
        return new Date(y, m - 1, d);
    }

    function toYYYYMMDDLocal(date) {
        if (!(date instanceof Date)) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    if (props.updating && props.user) {
        form.accesibilidad = toBoolean(props.user.accesibilidad);
        form.accesibilidad_desc = props.user.accesibilidad_desc ?? '';
        form.direccion = props.user.direccion ?? '';
        form.pais_id = props.user.pais_id ?? '';
        form.provincia_id = props.user.provincia_id ?? '';
        form.municipio_id = props.user.municipio_id ?? '';
        form.barrio_id = props.user.barrio_id ?? '';
        form.telefono = props.user.telefono ?? '';
        form.whatsapp = props.user.whatsapp ?? '';
        form.fecha_nacimiento = parseISODateToLocal(props.user.fecha_nacimiento) ?? null;
        form.sexo_id = props.user.sexo_id ?? '';
        form.membresia_id = props.user.membresia_id ?? '';
        form.programa_estudio_id = props.user.programa_estudio_id ?? '';
        form.es_maestro = toBoolean(props.user.es_maestro);
        form.es_coordinador = toBoolean(props.user.es_coordinador);
        form.msgxmail = toBoolean(props.user.msgxmail);
        form.msgxwapp = toBoolean(props.user.msgxwapp);
    }

    function submit() {
        // Formatear fecha_nacimiento al formato YYYY-MM-DD si existe
        if (form.fecha_nacimiento) {
            if (form.fecha_nacimiento instanceof Date) {
                form.fecha_nacimiento = toYYYYMMDDLocal(form.fecha_nacimiento);
            } else if (typeof form.fecha_nacimiento === 'string') {
                // Try to parse ISO string safely to local and format
                const dt = parseISODateToLocal(form.fecha_nacimiento);
                form.fecha_nacimiento = dt ? toYYYYMMDDLocal(dt) : form.fecha_nacimiento;
            }
        }

        // Map 'Ninguno' sentinel to id 1 before submit
        if (form.programa_estudio_id === '__NONE__' || form.programa_estudio_id === '' || form.programa_estudio_id === null) {
            form.programa_estudio_id = 1;
        }
        
        if (props.updating) {
            // Si estamos en modo edición
            const updateRoute = props.target_user_id
                ? route('usuarios.profile.complete.update', props.target_user_id)
                : route('profile.complete.update');

            form.put(updateRoute, {
            onSuccess: () => {
                // Opcional: algo tras éxito
            },
            onError: (errors) => {
                const firstError = Object.values(errors || {})[0];
                const detail = Array.isArray(firstError) ? firstError[0] : firstError;
                Swal.fire('Error', detail || 'Revisa los campos obligatorios.', 'error');
            },
            });
        } else {
            // Si estamos en modo "crear" o "completar" inicial
            form.post(route('profile.complete.store'), {
            onSuccess: () => {
                // Opcional
            },
            onError: (errors) => {
                const firstError = Object.values(errors || {})[0];
                const detail = Array.isArray(firstError) ? firstError[0] : firstError;
                Swal.fire('Error', detail || 'Revisa los campos obligatorios.', 'error');
            },
            });
        }
    }

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar datos a tu Perfil</h1>
        </template>
        <div class="py-12">
            <div class="w-full md:w-[70%] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="px-0 py-6 md:p-6 bg-white border-b border-gray-200">
                            <CompleteProfileForm 
                            :updating="updating"
                            :membresias="membresias"
                            :paises="paises"
                            :provincias="provincias"
                            :municipios="municipios"
                            :barrios="barrios"
                            :sexos="sexos"
                            :programa-estudios="programa_estudios"
                            :form="form" 
                            @submit="submit"
                        />
                            <div class="mt-6 flex justify-end">
                                <Link
                                    :href="canSkipProfile ? (isAsistant ? route('asistant.panel') : route('dashboard')) : '#'"
                                    class="inline-flex items-center px-4 py-2 rounded-md text-sm text-white"
                                    :class="canSkipProfile ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 cursor-not-allowed pointer-events-none'"
                                >
                                    Completar más tarde
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ScrollTop
            class="bg-slate-300	"
            :threshold="100"
            icon="pi pi-angle-up"
            :pt="{
                root: 'w-2rem h-2rem border-round-sm',
                icon: {
                    class: 'text-indigo-500'
                }
            }"
        />         
    </AppLayout>

</template>
