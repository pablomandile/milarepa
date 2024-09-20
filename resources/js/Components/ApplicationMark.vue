<template>
	<!-- Usa la etiqueta img para mostrar el archivo SVG -->
	<img :src="logo" alt="Logo" class="block h-9 w-auto" />
  </template>
  
  <script>
  export default {
	data() {
	  return {
		logo: null,  // Inicialmente es null
	  };
	},
	async created() {
	  // Carga el logo dinámicamente usando import
	  const logo = await import('../../images/lotus-art-logo.svg');
	  this.logo = logo.default;  // Asegúrate de acceder a "default"
	},
  };
  </script>
  