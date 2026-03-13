<script setup>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import GuestUserForm from '@/Components/Formularios/GuestUserForm.vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  header: {
    type: String,
    default: '',
  },
  width: {
    type: String,
    default: '900px',
  },
  breakpoints: {
    type: Object,
    default: () => ({ '1199px': '90vw', '575px': '95vw' }),
  },
  title: {
    type: String,
    default: 'Elige la forma de Inscribirte',
  },
  mode: {
    type: String,
    default: null,
  },
  highlightAllWhenModeEmpty: {
    type: Boolean,
    default: false,
  },
  loadingNuevo: {
    type: Boolean,
    default: false,
  },
  loadingRegistrado: {
    type: Boolean,
    default: false,
  },
  loadingLogin: {
    type: Boolean,
    default: false,
  },
  disableNuevo: {
    type: Boolean,
    default: false,
  },
  disableRegistrado: {
    type: Boolean,
    default: false,
  },
  disableLogin: {
    type: Boolean,
    default: false,
  },
  cancelLabel: {
    type: String,
    default: 'Cancelar',
  },
  submitNuevoLabel: {
    type: String,
    default: 'Guardar e Inscribirme',
  },
  submitNuevoLoadingLabel: {
    type: String,
    default: 'Enviando...',
  },
  submitRegistradoLabel: {
    type: String,
    default: 'Continuar',
  },
  submitRegistradoLoadingLabel: {
    type: String,
    default: 'Validando...',
  },
  submitLoginLabel: {
    type: String,
    default: 'Iniciar Sesion y Continuar',
  },
  submitLoginLoadingLabel: {
    type: String,
    default: 'Iniciando...',
  },
  guestForm: {
    type: Object,
    default: null,
  },
  guestErrors: {
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
  guestContainerClass: {
    type: String,
    default: 'max-h-[70vh] overflow-y-auto pr-2',
  },
  guestFormExtraProps: {
    type: Object,
    default: () => ({}),
  },
  email: {
    type: String,
    default: '',
  },
  registeredError: {
    type: String,
    default: '',
  },
  registeredContainerClass: {
    type: String,
    default: 'space-y-2',
  },
  registeredInputId: {
    type: String,
    default: 'email-registrado',
  },
  registeredInputClass: {
    type: String,
    default: 'w-full max-w-md rounded-md border border-gray-300 px-3 py-2',
  },
  registeredPlaceholder: {
    type: String,
    default: 'tu@email.com',
  },
  loginEmail: {
    type: String,
    default: '',
  },
  loginPassword: {
    type: String,
    default: '',
  },
  loginError: {
    type: String,
    default: '',
  },
  loginContainerClass: {
    type: String,
    default: 'space-y-2',
  },
  loginEmailId: {
    type: String,
    default: 'login-email',
  },
  loginPasswordId: {
    type: String,
    default: 'login-password',
  },
  loginInputClass: {
    type: String,
    default: 'block w-full max-w-md rounded-md border border-gray-300 px-3 py-2',
  },
  loginEmailPlaceholder: {
    type: String,
    default: 'tu@email.com',
  },
  loginPasswordPlaceholder: {
    type: String,
    default: '••••••••',
  },
});

const emit = defineEmits([
  'update:modelValue',
  'select-mode',
  'cancel',
  'submit-nuevo',
  'submit-registrado',
  'submit-login',
  'update:email',
  'update:loginEmail',
  'update:loginPassword',
]);

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const emailValue = computed({
  get: () => props.email,
  set: (value) => emit('update:email', value),
});

const loginEmailValue = computed({
  get: () => props.loginEmail,
  set: (value) => emit('update:loginEmail', value),
});

const loginPasswordValue = computed({
  get: () => props.loginPassword,
  set: (value) => emit('update:loginPassword', value),
});

function isModeActive(targetMode) {
  return props.mode === targetMode || (props.highlightAllWhenModeEmpty && !props.mode);
}

function cancelDialog() {
  visible.value = false;
  emit('cancel');
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="header"
    :style="{ width }"
    :breakpoints="breakpoints"
  >
    <div class="space-y-4">
      <h2 class="text-xl font-semibold text-gray-900">{{ title }}</h2>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <button
          type="button"
          class="px-3 py-2 rounded-md font-medium transition-colors"
          :class="isModeActive('nuevo')
            ? 'bg-indigo-600 text-white hover:bg-indigo-700'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          @click="emit('select-mode', 'nuevo')"
        >
          Soy nuevo
        </button>
        <button
          type="button"
          class="px-3 py-2 rounded-md font-medium transition-colors"
          :class="isModeActive('registrado')
            ? 'bg-indigo-600 text-white hover:bg-indigo-700'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          @click="emit('select-mode', 'registrado')"
        >
          Ya estoy Registrado
        </button>
        <button
          type="button"
          class="px-3 py-2 rounded-md font-medium transition-colors"
          :class="isModeActive('login')
            ? 'bg-indigo-600 text-white hover:bg-indigo-700'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          @click="emit('select-mode', 'login')"
        >
          Iniciar sesion
        </button>
      </div>

      <slot name="afterModeButtons" :mode="mode" />

      <div v-if="mode === 'nuevo'">
        <slot v-if="$slots.nuevo" name="nuevo" />
        <div v-else :class="guestContainerClass">
          <GuestUserForm
            v-if="guestForm"
            :form="guestForm"
            :errors="guestErrors"
            :paises="paises"
            :provincias="provincias"
            :municipios="municipios"
            :barrios="barrios"
            v-bind="guestFormExtraProps"
          />
        </div>
      </div>

      <div v-else-if="mode === 'registrado'">
        <slot v-if="$slots.registrado" name="registrado" />
        <div v-else :class="registeredContainerClass">
          <label class="text-sm font-medium text-gray-700" :for="registeredInputId">Email</label>
          <input
            :id="registeredInputId"
            v-model="emailValue"
            type="email"
            :class="registeredInputClass"
            :placeholder="registeredPlaceholder"
          />
          <p v-if="registeredError" class="text-sm text-red-600">{{ registeredError }}</p>
        </div>
      </div>

      <div v-else-if="mode === 'login'">
        <slot v-if="$slots.login" name="login" />
        <div v-else :class="loginContainerClass">
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700" :for="loginEmailId">Email</label>
            <input
              :id="loginEmailId"
              v-model="loginEmailValue"
              type="email"
              :class="loginInputClass"
              :placeholder="loginEmailPlaceholder"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700" :for="loginPasswordId">Contraseña</label>
            <input
              :id="loginPasswordId"
              v-model="loginPasswordValue"
              type="password"
              :class="loginInputClass"
              :placeholder="loginPasswordPlaceholder"
            />
          </div>
          <p v-if="loginError" class="text-sm text-red-600">{{ loginError }}</p>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <button
          @click="cancelDialog"
          class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-600 transition-colors"
        >
          {{ cancelLabel }}
        </button>

        <button
          v-if="mode === 'nuevo'"
          @click="emit('submit-nuevo')"
          :disabled="disableNuevo || loadingNuevo"
          class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700 transition-colors disabled:opacity-60"
        >
          {{ loadingNuevo ? submitNuevoLoadingLabel : submitNuevoLabel }}
        </button>

        <button
          v-else-if="mode === 'registrado'"
          @click="emit('submit-registrado')"
          :disabled="disableRegistrado || loadingRegistrado"
          class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors disabled:opacity-60"
        >
          {{ loadingRegistrado ? submitRegistradoLoadingLabel : submitRegistradoLabel }}
        </button>

        <button
          v-else-if="mode === 'login'"
          @click="emit('submit-login')"
          :disabled="disableLogin || loadingLogin"
          class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors disabled:opacity-60"
        >
          {{ loadingLogin ? submitLoginLoadingLabel : submitLoginLabel }}
        </button>
      </div>
    </template>
  </Dialog>
</template>
