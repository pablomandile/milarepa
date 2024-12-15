<template>
    <div>
        <img :src="logo" alt="Logo Milarepa" class="h-auto w-auto" />
    </div>
</template>

<script>
    import LogoMilarepa from '/resources/images/lotus-art-logo.png';

export default {
    data() {
        return {
            logo: LogoMilarepa, // Asignar el logo a una variable
        };
    },
};
</script>