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

<template>
    <div>
        <div class="flex justify-center">
            <img :src="logo" alt="Logo" class="block h-80 w-auto" />
        </div>

        {{ $page.props }}


    </div>
</template>
