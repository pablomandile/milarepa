<template>
<footer class="bg-[#bfc1f1] text-black mt-20 " style="border-top: 6px #64748b solid">
    <div class="p-4 max-w-7xl mx-auto">
      <div
        class="flex flex-col gap-4 sm:items-center sm:justify-between sm:flex-row">
        <div>
            <h4 class="text-lg font-bold text-white m-0">
                Nuestras redes
            </h4>
        </div>
        <div>
          <ul
            class="flex flex-col justify-end gap-4 sm:flex-row p-0">
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                href="https://www.facebook.com/EducacionBA">
                  <Facebook class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden">Facebook</span>
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                href="https://twitter.com/EducacionBA">
                <Twitter class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden">Twitter</span>
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                href="https://www.instagram.com/educacionbA/">
                <Instagram class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden"
                  >Instagram</span
                >
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                href="https://www.youtube.com/@educacionBA/">
                <Youtube class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden">Youtube</span>
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                href="https://api.whatsapp.com/send/?phone=5491150500147&amp;text=Hola+%EF%BF%BD&amp;app_absent=0">
                <Wpp class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden">WhatsApp</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <hr class="my-5 sm:my-5 border-slate-400" />
      <div
        class="flex flex-wrap items-center lg:gap-6 lg:justify-between lg:flex-nowrap">
        <div class="grow">
            <LogoMilarepa class="w-[100px] h-[100px] lg:block hidden"/>
        </div>
        <div
          class="flex flex-col justify-between w-full lg:w-auto lg:justify-start lg:flex-row lg:divide-x lg:gap-0">
          <div class="lg:pr-4">
            <p
              class="font-semibold text-white lg:text-end whitespace-nowrap">
              Sistema de inscripciones
            </p>
            <p class="font-thin text-slate-200 lg:text-end">
              {{'entidad_principal'}}
            </p>
          </div>
          <div class="lg:px-4">
            <p class="font-semibold text-white lg:text-end">
                Nueva Tradición Kadampa - <br />
                Unión Internacional de Budismo Kadampa
            </p>
            <p class="font-thin text-slate-200 lg:text-end">
                Budismo Kadampa Moderno
            </p>
          </div>
          <div class="lg:ps-4">
            <p class="font-semibold text-white lg:text-end">
                "Llegará el día en que harás mi trabajo".
            </p>
            <p class="font-thin text-slate-200 lg:text-end">
                Venerable Gueshe Kelsang Gyatso Rimpoché
            </p>
          </div>
        </div>
      </div>
      <hr class="my-5 sm:my-5 border-slate-400" />
      <div class="flex flex-col gap-4">
        <div class="flex justify-center items-center">
            <p class="text-white">
              Todos los derechos reservados © Milarepa
              <span>{{ currentYear }}</span>
            </p>
        </div>
      </div>
    </div>
</footer>
</template>
<script setup>
import LogoMilarepa from "./Icons/LogoMilarepa.vue";
import Facebook from "./Icons/Facebook.vue";
import Wpp from "./Icons/Wpp.vue";
import Twitter from "./Icons/Twitter.vue";
import Youtube from "./Icons/Youtube.vue";
import Instagram from "./Icons/Instagram.vue";
</script>
<script>
export default {
    data() {
        return {
            currentYear: null,
        };
    },
    mounted() {
        this.currentYear = new Date().getFullYear();
    },
};
</script>