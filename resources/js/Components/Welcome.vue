<script setup>
defineProps({
    entidad_principal: {
        type: String,
        default: 'Sin entidad principal configurada',
    },
});

import { ref, onMounted } from 'vue';

const logo = ref(null);
import('../../images/lotus-art-logo.svg').then((module) => {
    logo.value = module.default;
});

// Manejo de opacidad
const logoOpacity = ref(1);

onMounted(() => {
  // Intervalos (en ms) para cada parpadeo (comenzando rápido y aumentando)
  const blinkDelays = [500, 200, 100];
  
  let currentBlink = 0;

  const doBlink = () => {
    if (currentBlink >= blinkDelays.length) {
      // Al terminar todos los parpadeos, dejamos opacidad en 1 y no llamamos más
      logoOpacity.value = 1;
      return;
    }
    // Bajar la opacidad
    logoOpacity.value = 0.2;

    // Después de blinkDelays[currentBlink] ms, volvemos a 1
    setTimeout(() => {
      logoOpacity.value = 1;
      currentBlink++;
      // Programar el siguiente parpadeo
      setTimeout(doBlink, blinkDelays[currentBlink]);
    }, blinkDelays[currentBlink]);
  };

  // Iniciar el parpadeo
  doBlink();
});

</script>

<style scoped>
/* Contenedor que alberga las ondas */
.wave-container {
  position: absolute;
  top: 0;
  left: 0;
  /* para ocupar todo el contenedor padre */
  width: 100%;
  height: 100%;
  pointer-events: none; /* para que no interfiera con clicks */
  overflow: hidden;     /* por si las ondas se salen un poco */
  z-index: 0;          /* debajo del logo */
}

/* Cada .wave es un círculo que se expande y desvanece */
.wave {
  position: absolute;
  top: 50%;
  left: 50%;
  /* Partimos de un círculo pequeñito */
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 3px solid rgba(206, 206, 243, 0.973); /* color y transparencia */
  transform: translate(-50%, -50%) scale(0.1);
  opacity: 1;
  animation: ripple 3s infinite;
}

/* Cambia el color, transparencia o grosor del borde si deseas */
.wave1 {
  animation-delay: 0s;
}
.wave2 {
  animation-delay: 0.8s; /* aparecen escalonadas */
}
.wave3 {
  animation-delay: 1.6s;
}

/* Keyframe que define la expansión y desvanecimiento */
@keyframes ripple {
  0% {
    transform: translate(-50%, -50%) scale(0.1);
    opacity: 1;
  }
  70% {
    opacity: 1;
  }
  100% {
    transform: translate(-50%, -50%) scale(2.7); /* hasta qué tamaño expande */
    opacity: 0;
  }
}
</style>

<template>
    <div>
      <div class="flex justify-center mt-20 mb-4">
          <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            SISTEMA DE INSCRIPCIONES
          </h1>
      </div>
      <div class="flex justify-center mt-4 mb-4">
          <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $page.props.entidad_principal?.nombre || entidad_principal }}

          </h1>
      </div>
      <div class="flex justify-center mt-4 mb-4">
        <h1>Powered by MILAREPA</h1>
      </div>



      <!-- Logo parpadeante -->
      <div class="flex justify-center mb-20">
        <!-- Ajustamos un estilo inline con la opacidad bind-eada -->
        <img
          v-if="logo"
          :src="logo"
          alt="Logo"
          class="block h-80 w-auto transition-opacity duration-200"
          :style="{ opacity: logoOpacity }"
        />
      </div>

      <!-- Capa de las ondas -->
      <div class="flex items-center justify-center ">
        <div class="wave-container mt-10">
          <div class="wave wave1"></div>
          <div class="wave wave2"></div>
          <div class="wave wave3"></div>
        </div>
      </div>

      <div class="flex justify-center mt-4 mb-4">
        <h1 class="text-2xl" style="font-family: 'Parisienne', serif; font-weight: 400; font-style: normal;">¡Todos son Bienvenidos!</h1>
      </div>
        <!-- {{ $page.props }} -->
    </div>
</template>
