<template>
    <div class="max-w-sm p-2 bg-white border rounded shadow">
  
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
  
      <!-- Botones Subir / Cancelar -->
      <div v-if="fileData && !isUploaded" class="flex gap-2 mt-4">
        <button
          class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded disabled:opacity-50"
          :disabled="isUploading"
          @click="uploadFile"
        >
          {{ isUploading ? 'Subiendo...' : 'Subir' }}
        </button>
        <button
          class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded disabled:opacity-50"
          :disabled="isUploading"
          @click="cancel"
        >
          Cancelar
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onUnmounted } from 'vue';
  import { useForm } from '@inertiajs/vue3';
  import { useToast } from 'primevue/usetoast';

  
  // PROPS:
  // v-model:imagenId -> para asignar el ID al padre
  // v-model:imagenId funciona con modelValue + update:modelValue en Vue 3, 
  // pero más simple es un prop/emit manual:
const props = defineProps({
    // Si quieres enlazar la ID con el padre, define:
    imagenId: {
      type: Number,
      default: null
    },
    folder: {
      type: String,
      default: 'img/actividades'
    }
  });
  const emits = defineEmits(['update:imagenId']);
  
  // Estado local
  const fileData = ref(null);     // { name, size, file, previewUrl }
  const isUploading = ref(false);
  const isUploaded = ref(false);

  const toast = useToast();

  // Hook Inertia Form (si deseas usar .post y que devuelva JSON)
  const form = useForm({
    imagen: null
  });
  
  // Al desmontar el componente, liberamos la URL de preview
  onUnmounted(() => {
    if (fileData.value && fileData.value.previewUrl) {
      URL.revokeObjectURL(fileData.value.previewUrl);
    }
  });
  
  // Función para formatear el tamaño
  function formatFileSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    else return (bytes / 1048576).toFixed(2) + ' MB';
  }
  
  // Selección de archivo
  function onFileSelected(e) {
    const file = e.target.files[0];
    if (!file) return;
  
    // Liberar anterior preview si existía
    if (fileData.value?.previewUrl) {
      URL.revokeObjectURL(fileData.value.previewUrl);
    }
    fileData.value = {
      file,
      name: file.name,
      size: file.size,
      previewUrl: URL.createObjectURL(file)
    };
    // Reset states if user re-selects
    isUploading.value = false;
    isUploaded.value = false;
  }
  
  // Subir archivo al backend (Axios + endpoint JSON)
  async function uploadFile() {
    if (!fileData.value) return;
  
    isUploading.value = true;
    form.imagen = fileData.value.file;
  
    // // Realiza la petición a tu ruta de subida
    // // Ejemplo 1: usando form.post con Inertia
    // form.post(route('imagenes.store'), {
    //   onSuccess: (page) => {
    //     isUploading.value = false;
    //     // Asumiendo que el servidor responde con un redirect => 
    //     // no queremos redireccionar en la UI -> pasamos preserveState/preserveScroll
    //     // o preferimos un response JSON. 
    //     //
    //     // O si el server redirige, Inertia recargará la página. 
    //     // MEJOR: Hacer un endpoint que devuelva JSON sin redirigir. 
    //     //
    //     // SOLUCIÓN SUGERIDA: Mejor usar un request manual con axios o fetch 
    //     // si no deseas redirecciones. Ver ejemplo 2 más abajo.
    //   },
    //   onError: () => {
    //     isUploading.value = false;
    //     // Manejo de error
    //     alert('Error al subir la imagen');
    //   },
    //   preserveState: true, 
    //   preserveScroll: true
    // });
  
    // Ejemplo 2 (más recomendado): Usar Axios o fetch
    try {
      let data = new FormData();
      data.append('imagen', fileData.value.file);
      data.append('folder', props.folder);
      const response = await axios.post('/imagenes-json', data);
    
      // Respuesta JSON: { id: 123, path: "storage/img/..." }
      const { id, path } = response.data;
      isUploading.value = false;
      isUploaded.value = true;

      toast.add({
        severity: 'success',
        summary: 'Subida Exitosa',
        detail: 'La imagen se ha subido correctamente.',
        life: 3000,
        });
    
      // Emite al padre la nueva ID
      emits('update:imagenId', id);
    
      // Podrías guardar path si deseas mostrar la preview real 
      // en vez del local previewUrl.
    } catch (error) {
      isUploading.value = false;
      const mensaje =
        error?.response?.data?.errors?.imagen?.[0] ||
        'Hubo un problema al subir la imagen.';
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: mensaje,
        life: 3000,
        });
    }
  }
  
  // Cancelar
  function cancel() {
    // Borrar estado
    if (fileData.value?.previewUrl) {
      URL.revokeObjectURL(fileData.value.previewUrl);
    }
    fileData.value = null;
    form.reset();
    isUploading.value = false;
  }
  </script>
  
  <style scoped>
  /* Ajustes de estilo a gusto */
  </style>
  
