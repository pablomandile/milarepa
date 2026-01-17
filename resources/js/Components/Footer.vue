<template>
<footer class="footer-bg text-black w-full border-t-4 border-slate-500">
    <div class="p-4 w-full">
      <div
        class="flex flex-col gap-4 sm:items-center sm:justify-center sm:flex-row">
        <div>
          <ul
            class="flex flex-col justify-end gap-4 sm:flex-row p-0">
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                :href="$page.props.entidad_principal.facebook_uri"
                target="_blank">
                  <Facebook class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden">Facebook</span>
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                :href="$page.props.entidad_principal.twitter_uri"
                target="_blank">
                <i class="pi pi-twitter text-white text-2xl"></i>
                <span class="font-bold text-white sm:hidden">Twitter</span>
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a  
                class="flex items-center gap-2"
                :href="$page.props.entidad_principal.instagram_uri"
                target="_blank">
                <Instagram class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden"
                  >Instagram</span
                >
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                :href="$page.props.entidad_principal.youtube_uri"
                target="_blank">
                <Youtube class="w-[32px] md:w-[40px] h-auto"/>
                <span class="font-bold text-white sm:hidden">Youtube</span>
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                :href="`https://api.whatsapp.com/send/?phone=${$page.props.entidad_principal.whatsapp}&text=Hola&app_absent=0`" target="_blank">
                <Wpp class="w-[32px] md:w-[40px] h-auto"/> 
                <span class="font-bold text-white sm:hidden">WhatsApp</span>
              </a>
            </li>
            <li class="flex items-center gap-3">
              <a
                class="flex items-center gap-2"
                :href="$page.props.entidad_principal.spotify_uri"
                target="_blank">
                <Spotify class="w-[32px] md:w-[30px] h-auto"/>
                <span class="font-bold text-white sm:hidden">Spotify</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <hr class="my-5 sm:my-5 border-white" />
      <div
        class="flex flex-wrap items-center lg:gap-6 lg:justify-between lg:flex-nowrap">
        <div class="grow">
            <LogoMilarepa class="w-[100px] h-[100px] lg:block hidden"/>
        </div>
        <div
          class="flex flex-col justify-between w-full lg:w-auto lg:justify-start lg:flex-row lg:divide-x lg:gap-0">
          <div class="lg:pr-6 ">
            <p
              class="font-semibold text-white lg:text-end whitespace-nowrap">
              Sistema de inscripciones
            </p>
            <p class="text-slate-800 lg:text-end">
              <!-- {{entidad_principal}} -->
              {{ $page.props.entidad_principal?.nombre || entidad_principal }}
            </p>
          </div>
          <div class="lg:px-6">
            <p class="font-semibold text-white lg:text-end">
              <a href="https://kadampa.org/" target="_blank" rel="noopener noreferrer">
                Nueva Tradición Kadampa - <br />
                Unión Internacional de Budismo Kadampa </a>
            </p>
            <p class="text-slate-800 lg:text-end">
                Budismo Kadampa Moderno
            </p>
          </div>
          <div class="lg:ps-4">
            <p class="font-parisienne text-2xl text-white lg:text-center">
                "Llegará el día en que harás mi trabajo".
            </p>
            <p class="text-slate-800 lg:text-end">
                Venerable Gueshe Kelsang Gyatso 
            </p>
            <div class="flex justify-center">
              <img :src="Ornament" alt="Ornament" width="120" class="mt-2"/>
            </div>
          </div>
        </div>
      </div>
      <hr class="my-5 sm:my-5 border-white" />
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
import Youtube from "./Icons/Youtube.vue";
import Instagram from "./Icons/Instagram.vue";
import Spotify from "./Icons/Spotify.vue";
import Ornament from "../../images/ornament.svg";
import 'primeicons/primeicons.css';


defineProps({
    entidad_principal: {
        type: String,
        default: "Sin entidad principal configurada",
    },
});

const currentYear = new Date().getFullYear();
</script>