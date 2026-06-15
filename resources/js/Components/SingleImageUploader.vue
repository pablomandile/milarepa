<template>
    <div class="max-w-sm p-2 bg-white dark:bg-gray-800 border rounded shadow">

      <!-- Botón Elegir Archivo -->
      <div>
        <label
          class="cursor-pointer bg-blue-600 hover:bg-blue-800 text-white font-bold py-1 px-2 rounded text-sm"
        >
          Elegir Imagen
          <input
            type="file"
            accept="image/*"
            class="hidden"
            @change="onFileSelected"
          />
        </label>
      </div>

      <!-- Vista Previa -->
      <div v-if="fileData" class="mt-4 flex items-center">
        <img
          :src="fileData.previewUrl"
          alt="Vista previa"
          class="w-16 h-16 object-cover rounded mr-2 border"
        />
        <div class="text-sm">
          <p class="font-semibold">{{ fileData.name }}</p>
          <p class="text-gray-500">{{ formatFileSize(fileData.size) }}</p>
        </div>
      </div>

      <!-- Quitar selección -->
      <div v-if="fileData" class="flex gap-2 mt-4">
        <button
          type="button"
          class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded"
          @click="cancel"
        >
          Quitar
        </button>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, onUnmounted, watch } from 'vue';

  // PROPS:
  // v-model:file -> el File seleccionado se enlaza al padre.
  //   La imagen NO se sube acá: la subida/proceso ocurre al guardar el formulario.
  // folder -> se conserva por compatibilidad con los usos existentes, pero ya no
  //   se usa acá (la carpeta de destino la decide el backend al guardar).
  const props = defineProps({
    file: {
      type: [Object, null],
      default: null,
    },
    folder: {
      type: String,
      default: 'img/actividades',
    },
  });
  const emits = defineEmits(['update:file']);

  // Estado local: { name, size, file, previewUrl }
  const fileData = ref(null);

  // Limpia el preview actual liberando la object URL.
  function clearPreview() {
    if (fileData.value?.previewUrl) {
      URL.revokeObjectURL(fileData.value.previewUrl);
    }
    fileData.value = null;
  }

  // Si el padre limpia el File (ej. tras un submit exitoso), limpiamos el preview.
  watch(
    () => props.file,
    (nuevo) => {
      if (!nuevo && fileData.value) {
        clearPreview();
      }
    }
  );

  // Al desmontar el componente, liberamos la URL de preview
  onUnmounted(() => {
    clearPreview();
  });

  // Función para formatear el tamaño
  function formatFileSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    else return (bytes / 1048576).toFixed(2) + ' MB';
  }

  // Selección de archivo: guardamos preview local y emitimos el File al padre.
  function onFileSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    clearPreview();
    fileData.value = {
      file,
      name: file.name,
      size: file.size,
      previewUrl: URL.createObjectURL(file),
    };

    emits('update:file', file);
  }

  // Quitar la selección.
  function cancel() {
    clearPreview();
    emits('update:file', null);
  }
  </script>

  <style scoped>
  /* Placeholder para evitar que Vite genere un CSS de 0 bytes que en server da 404 */
  .single-image-uploader-marker { display: contents; }
  </style>
