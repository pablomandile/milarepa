<script setup>
defineProps({
    entidad_principal: {
        type: String,
        default: 'Sin entidad principal configurada',
    },
});

import { ref } from 'vue';
const logo = ref(null);

// Cargar el logo dinámicamente
import('../../images/lotus-art-logo.svg').then((module) => {
    logo.value = module.default;
});
</script>

<template>
    <div>
      <div class="flex justify-center mt-20 mb-4">
          <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            SISTEMA DE INSCRIPCIONES
          </h1>
      </div>
      <div class="flex justify-center mt-4 mb-4">
          <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ entidad_principal }}
          </h1>
      </div>
      <div class="flex justify-center mt-4 mb-4">
        <h1>Powered by MILAREPA</h1>
      </div>
      <div class="flex justify-center mb-20">
          <img :src="logo" alt="Logo" class="block h-80 w-auto" />
      </div>
      <div class="flex justify-center mt-4 mb-4">
        <h1>¡Todos son Bienvenidos!</h1>
      </div>
      <!-- {{ $page.props }} -->
      
      
    </div>
</template>
