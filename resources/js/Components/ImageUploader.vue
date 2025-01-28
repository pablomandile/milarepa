<template>
    <div class="w-full max-w-sm mx-auto p-4 bg-white rounded shadow">
      <h2 class="text-lg font-semibold mb-4">Subir Imagen</h2>
  
      <!-- Botón para elegir archivo -->
      <div>
        <label
          for="file-input"
          class="cursor-pointer bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
        >
          Elegir Imagen
        </label>
        <input
          id="file-input"
          type="file"
          accept="image/*"
          @change="onFileSelected"
          class="hidden"
        />
      </div>
  
      <!-- Vista previa del archivo seleccionado -->
      <div v-if="selectedFile" class="mt-4">
        <div class="flex items-center">
          <img
            :src="previewUrl"
            alt="Vista Previa"
            class="w-16 h-16 object-cover rounded mr-4"
          />
          <div>
            <p class="text-sm">{{ selectedFile.name }}</p>
            <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
          </div>
        </div>
      </div>
  
      <!-- Botones Subir y Cancelar -->
      <div v-if="selectedFile" class="mt-4 flex space-x-2">
        <button
          @click="uploadImage"
          :disabled="isUploading"
          class="flex-1 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
        >
          {{ isUploading ? 'Subiendo...' : 'Subir' }}
        </button>
        <button
          @click="cancelUpload"
          :disabled="isUploading"
          class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
        >
          Cancelar
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watch, onUnmounted } from 'vue';
  import { useForm } from '@inertiajs/vue3';
  import Swal from 'sweetalert2';
  
  // Referencia para almacenar el objeto de URL para la vista previa
  const previewUrl = ref(null);
  
  // Referencia para el archivo seleccionado
  const selectedFile = ref(null);
  
  // Estado de subida
  const isUploading = ref(false);
  
  // Formulario de Inertia
  const form = useForm({
    imagen: null,
  });
  
  // Función para formatear el tamaño del archivo
  const formatFileSize = (bytes) => {
    if (bytes < 1024) return `${bytes} B`;
    else if (bytes < 1048576) return `${(bytes / 1024).toFixed(2)} KB`;
    else return `${(bytes / 1048576).toFixed(2)} MB`;
  };
  
  // Función para manejar la selección del archivo
  const onFileSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
      selectedFile.value = file;
      form.imagen = file;
      previewUrl.value = URL.createObjectURL(file);
    }
  };
  
  // Función para subir la imagen
  const uploadImage = () => {
    if (!selectedFile.value) return;
  
    isUploading.value = true;
  
    form.post(route('imagenes.store'), {
      onSuccess: () => {
        Swal.fire({
          icon: 'success',
          title: '¡Éxito!',
          text: 'La imagen se ha subido correctamente.',
          timer: 2000,
          showConfirmButton: false,
        });
        resetForm();
      },
      onError: () => {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Hubo un problema al subir la imagen.',
        });
        isUploading.value = false;
      },
    });
  };
  
  // Función para cancelar la subida
  const cancelUpload = () => {
    resetForm();
  };
  
  // Función para reiniciar el formulario y limpiar las selecciones
  const resetForm = () => {
    form.reset();
    selectedFile.value = null;
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value);
      previewUrl.value = null;
    }
    isUploading.value = false;
  };
  
  // Limpiar la URL de vista previa al desmontar el componente
  onUnmounted(() => {
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value);
    }
  });
  </script>
  
  <style scoped>
  /* Puedes añadir estilos personalizados aquí si es necesario */
  </style>
  