<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  oracionCantada: {
    type: Object,
    required: true,
  },
});

const dayLabels = {
  lunes: 'Lunes',
  martes: 'Martes',
  miercoles: 'Miercoles',
  jueves: 'Jueves',
  viernes: 'Viernes',
  sabado: 'Sabado',
  domingo: 'Domingo',
};

const diasSemanaTexto = computed(() => {
  if (props.oracionCantada.periodicidad !== 'Diaria') return '-';
  const dias = Array.isArray(props.oracionCantada.dias_semana) ? props.oracionCantada.dias_semana : [];
  if (dias.length === 0) return '-';

  const ordered = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']
    .filter((d) => dias.includes(d));

  if (ordered.length === 7) return 'Todos los dias';
  return ordered.map((d) => dayLabels[d] || d).join(', ');
});

const horaTexto = computed(() => {
  if (!props.oracionCantada?.hora) return '-';
  return `${String(props.oracionCantada.hora).slice(0, 5)} hs.`;
});
</script>

<template>
  <AppLayout title="Oracion Cantada">
    <Head :title="oracionCantada.nombre" />

    <div class="min-h-screen bg-slate-50 py-8">
      <div class="mx-auto w-full max-w-4xl px-4 sm:px-6">
        <div class="mb-4">
          <Link
            :href="route('calendario.index')"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
          >
            Volver al calendario
          </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="grid gap-0 md:grid-cols-[260px_1fr]">
            <div class="border-b border-slate-200 bg-slate-100 md:border-b-0 md:border-r">
              <div v-if="oracionCantada.imagen" class="h-full">
                <img
                  :src="oracionCantada.imagen"
                  :alt="oracionCantada.nombre"
                  class="h-full min-h-[220px] w-full object-cover"
                />
              </div>
              <div v-else class="flex min-h-[220px] items-center justify-center text-sm text-slate-500">
                Sin imagen
              </div>
            </div>

            <div class="p-5 sm:p-6">
              <h1 class="text-2xl font-semibold text-slate-900">{{ oracionCantada.nombre }}</h1>

              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                  <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hora</div>
                  <div class="mt-1 text-sm text-slate-900">{{ horaTexto }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                  <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Periodicidad</div>
                  <div class="mt-1 text-sm text-slate-900">{{ oracionCantada.periodicidad }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                  <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ oracionCantada.periodicidad === 'Mensual' ? 'Dia del mes' : 'Dias de la semana' }}
                  </div>
                  <div class="mt-1 text-sm text-slate-900">
                    {{ oracionCantada.periodicidad === 'Mensual' ? (oracionCantada.dia ?? '-') : diasSemanaTexto }}
                  </div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                  <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visible en calendario</div>
                  <div class="mt-1 text-sm" :class="oracionCantada.mostrar_en_calendario ? 'text-emerald-700' : 'text-slate-900'">
                    {{ oracionCantada.mostrar_en_calendario ? 'Si' : 'No' }}
                  </div>
                </div>
              </div>

              <div class="mt-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Descripcion</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                  {{ oracionCantada.descripcion }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
