<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
const props = defineProps({
  noAside: {
    type: Boolean,
    default: false
  }
});
</script>

<template>
  <div>
    <!-- Si noAside=true renderizamos solo el form ocupando todo el ancho -->
    <form @submit.prevent="$emit('submitted')" v-if="noAside" class="space-y-6">
      <slot name="form" />
      <div class="flex justify-end">
        <slot name="actions" />
      </div>
    </form>

    <!-- Comportamiento por defecto: layout con columna izquierda para title/description -->
    <form @submit.prevent="$emit('submitted')" v-else class="md:grid md:grid-cols-3 md:gap-6">
      <div class="md:col-span-1">
        <slot name="title" />
        <slot name="description" />
      </div>

      <div class="md:col-span-2">
        <div class="bg-white p-4 shadow sm:rounded-lg">
          <slot name="form" />
          <div class="mt-6">
            <slot name="actions" />
          </div>
        </div>
      </div>
    </form>
  </div>
</template>


