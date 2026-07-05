<template>
    <AppLayout title="Estado de Inscripciones">
        <Toast position="top-right" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    <i class="fas fa-clipboard-check mr-2 text-indigo-600"></i>
                    Estado de Inscripciones
                </h2>
                <div v-if="canEdit" class="flex items-center gap-2">
                    <Link
                        :href="route('estadoinscripciones.importar')"
                        class="inline-flex items-center gap-2 rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                    >
                        <i class="pi pi-upload"></i>
                        Importar inscripciones
                    </Link>
                    <Link
                        :href="route('estadoinscripciones.importar-multievento')"
                        class="inline-flex items-center gap-2 rounded bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700"
                    >
                        <i class="pi pi-cloud-download"></i>
                        Importar multievento
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="w-full p-0 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-0 sm:p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center px-4 sm:px-0">
                            <select
                                v-model="filtroPeriodo"
                                class="rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-1 text-sm w-full sm:w-56"
                            >
                                <option value="last1">Último mes</option>
                                <option value="all">Mostrar todo</option>
                            </select>
                            <button
                                v-if="canEdit"
                                type="button"
                                class="inline-flex items-center rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                :disabled="isSendingConfirmaciones"
                                @click="abrirDialogoEnvioConfirmaciones"
                            >
                                Envío de Confirmación
                            </button>
                            <button
                                v-if="canEdit"
                                type="button"
                                class="inline-flex items-center rounded bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700 disabled:opacity-60"
                                :disabled="isSendingGrabaciones"
                                @click="abrirDialogoEnvioGrabaciones"
                            >
                                Envío de Grabaciones
                            </button>
                            <button
                                v-if="canEdit"
                                type="button"
                                class="inline-flex items-center gap-1 rounded bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                @click="abrirCrearInscripcion"
                            >
                                <i class="pi pi-plus"></i>
                                Crear inscripción
                            </button>
                        </div>

                        <div v-if="filtradas.length > 0" class="space-y-4 sm:hidden">
                            <div
                                v-for="inscripcion in filtradas"
                                :key="inscripcion.id"
                                class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                            >
                                <div class="space-y-3 p-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ nombreUsuario(inscripcion) }}</span>
                                        <span
                                            class="inline-flex w-fit items-center px-2 py-1 rounded-full text-xs font-medium"
                                            :class="isInvitado(inscripcion) ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                        >
                                            {{ isInvitado(inscripcion) ? 'Invitado' : 'Registrado' }}
                                        </span>
                                    </div>

                                    <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ inscripcion.actividad?.nombre || '-' }}
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ inscripcion.actividad?.entidad?.nombre || '-' }}
                                            </p>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Membresia</span>
                                            <span>{{ inscripcion.membresia || '-' }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Monto</span>
                                            <template v-if="isEditing(inscripcion)">
                                                <input
                                                    v-model.number="editForm.montoapagar"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="w-28 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1 text-sm"
                                                />
                                            </template>
                                            <span v-else class="text-blue-700 font-medium">
                                                ${{ formatearMonto(inscripcion.montoapagar) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Pago</span>
                                            <template v-if="isEditing(inscripcion)">
                                                <select
                                                    v-model="editForm.pago"
                                                    class="rounded border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm"
                                                >
                                                    <option value="Saldado">Saldado</option>
                                                    <option value="Parcial">Parcial</option>
                                                    <option value="Pendiente">Pendiente</option>
                                                </select>
                                            </template>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="badgePagoClass(inscripcion.pago)"
                                            >
                                                {{ inscripcion.pago || '-' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Estado</span>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="badgeEstadoClass(inscripcion.estado)"
                                            >
                                                {{ inscripcion.estado || '-' }}
                                            </span>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="toggleCardExpanded(inscripcion.id)"
                                    >
                                        <span>{{ isCardExpanded(inscripcion.id) ? 'Ocultar detalles' : 'Ver mas detalles' }}</span>
                                        <i
                                            class="pi"
                                            :class="isCardExpanded(inscripcion.id) ? 'pi-chevron-up' : 'pi-chevron-down'"
                                        ></i>
                                    </button>

                                    <div v-if="isCardExpanded(inscripcion.id)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                                        <div class="grid grid-cols-1 gap-3 text-sm text-gray-700 dark:text-gray-300">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</p>
                                                <p>{{ emailUsuario(inscripcion) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pais</p>
                                                <p>{{ paisUsuario(inscripcion) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Provincia</p>
                                                <p>{{ provinciaUsuario(inscripcion) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Municipio/Barrio</p>
                                                <p>{{ municipioBarrioUsuario(inscripcion) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Inscripto</p>
                                                <p>{{ formatearFecha(inscripcion.created_at) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Modalidad</p>
                                                <p>{{ modalidadInscripcion(inscripcion) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha de pago</p>
                                                <p>{{ formatearSoloFecha(inscripcion.fecha_pago) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Referencia de pago</p>
                                                <p>{{ inscripcion.referencia_pago || '-' }}</p>
                                            </div>
                                            <div v-if="inscripcion.montoActividad !== null && inscripcion.montoActividad !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Actividad</p>
                                                <p class="text-blue-700 font-medium">${{ formatearMonto(inscripcion.montoActividad) }}</p>
                                            </div>
                                            <div v-if="inscripcion.montoTransporte !== null && inscripcion.montoTransporte !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Transporte</p>
                                                <p class="text-blue-700 font-medium">${{ formatearMonto(inscripcion.montoTransporte) }}</p>
                                            </div>
                                            <div v-if="inscripcion.montoComidas !== null && inscripcion.montoComidas !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Comidas</p>
                                                <p class="text-blue-700 font-medium">${{ formatearMonto(inscripcion.montoComidas) }}</p>
                                            </div>
                                            <div v-if="inscripcion.montoGrabacion !== null && inscripcion.montoGrabacion !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Grabacion</p>
                                                <p class="text-blue-700 font-medium">${{ formatearMonto(inscripcion.montoGrabacion) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auditor</p>
                                                <p>{{ inscripcion.auditor_user?.name || '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auditado</p>
                                                <p>{{ formatearFecha(inscripcion.auditoria_fecha) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Observaciones</p>
                                                <p class="whitespace-pre-line">{{ inscripcion.observaciones || '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Envio Registro</p>
                                                <p>{{ envioEstado(inscripcion, 'envioRegistro') }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Envio Confirmacion</p>
                                                <p>{{ envioEstado(inscripcion, 'envioConfirmacion') }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Envio Grabacion</p>
                                                <p>{{ envioEstado(inscripcion, 'envioGrabacion') }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Comprobante</p>
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        v-if="canEdit"
                                                        type="button"
                                                        @click="openComprobanteModal(inscripcion)"
                                                        class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sky-700"
                                                        title="Subir comprobante"
                                                        aria-label="Subir comprobante"
                                                    >
                                                        <i class="pi pi-upload"></i>
                                                        <span class="text-xs font-semibold">Subir comprobante</span>
                                                    </button>
                                                    <template v-else>
                                                        <button
                                                            v-if="urlComprobante(inscripcion)"
                                                            type="button"
                                                            @click="abrirComprobante(inscripcion)"
                                                            class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-indigo-600"
                                                            title="Ver comprobante"
                                                            aria-label="Ver comprobante"
                                                        >
                                                            <i class="fas fa-file-alt"></i>
                                                            <span class="text-xs font-semibold">Ver comprobante</span>
                                                        </button>
                                                        <span
                                                            v-if="comprobantesExtras(inscripcion) > 0"
                                                            class="text-xs font-semibold text-gray-500"
                                                        >
                                                            +{{ comprobantesExtras(inscripcion) }}
                                                        </span>
                                                        <span v-else-if="!urlComprobante(inscripcion)" class="text-xs text-gray-400">Sin comprobante</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                    <div v-if="canEdit" class="flex flex-wrap justify-center gap-2">
                                        <template v-if="isEditing(inscripcion)">
                                            <button
                                                class="inline-flex items-center justify-center gap-2 rounded bg-green-600 px-3 py-2 text-xs text-white hover:bg-green-700 disabled:opacity-60"
                                                :disabled="isSaving"
                                                @click="guardarEdicion(inscripcion)"
                                                aria-label="Guardar"
                                                title="Guardar"
                                            >
                                                <span>Guardar</span>
                                            </button>
                                            <button
                                                class="inline-flex items-center justify-center gap-2 rounded bg-gray-200 dark:bg-gray-700 px-3 py-2 text-xs text-gray-700 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600"
                                                @click="cancelarEdicion"
                                                aria-label="Cancelar"
                                                title="Cancelar"
                                            >
                                                <span>Cancelar</span>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button
                                                class="inline-flex items-center justify-center gap-2 rounded bg-indigo-50 px-3 py-2 text-xs text-indigo-700 hover:bg-indigo-100"
                                                @click="iniciarEdicion(inscripcion)"
                                                aria-label="Editar"
                                                title="Editar"
                                            >
                                                <i class="pi pi-file-edit"></i>
                                                <span>Editar</span>
                                            </button>
                                            <button
                                                class="inline-flex items-center justify-center gap-2 rounded bg-emerald-50 px-3 py-2 text-xs text-emerald-700 hover:bg-emerald-100"
                                                @click="abrirConfirmarSaldado(inscripcion)"
                                                aria-label="Saldado"
                                                title="Saldado"
                                            >
                                                <i class="pi pi-check-circle"></i>
                                                <span>Saldado</span>
                                            </button>
                                            <button
                                                class="inline-flex items-center justify-center gap-2 rounded bg-red-50 px-3 py-2 text-xs text-red-700 hover:bg-red-100"
                                                @click="abrirConfirmarBorrar(inscripcion)"
                                                aria-label="Eliminar"
                                                title="Eliminar inscripción"
                                            >
                                                <i class="pi pi-trash"></i>
                                                <span>Eliminar</span>
                                            </button>
                                        </template>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">Sin permisos</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="filtradas.length > 0" class="hidden overflow-x-auto sm:block">
                            <DataTable
                                :value="filtradas"
                                dataKey="id"
                                v-model:expandedRows="expandedRows"
                                v-model:filters="filters"
                                filterDisplay="row"
                                :globalFilterFields="['_nombre', 'actividad.nombre', 'membresia', 'pago', 'estado']"
                                responsiveLayout="scroll"
                                paginator
                                :rows="20"
                                :rowsPerPageOptions="[10, 20, 50, 100]"
                                removableSort
                                class="p-datatable-sm"
                            >
                                <template #header>
                                    <div class="flex justify-end">
                                        <span class="relative w-full sm:w-72">
                                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                            <input
                                                v-model="filters['global'].value"
                                                type="text"
                                                placeholder="Buscar (nombre, actividad, membresía, pago)..."
                                                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 pl-9 pr-3 py-1 text-sm"
                                            />
                                        </span>
                                    </div>
                                </template>
                                <template #empty>
                                    <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No hay inscripciones que coincidan con los filtros.
                                    </div>
                                </template>

                                <Column expander style="width: 3rem" />

                                <Column header="Fecha" field="created_at" sortable :showFilterMenu="false" style="width: 8rem">
                                    <template #body="{ data }">
                                        <span class="text-sm whitespace-nowrap">{{ formatearSoloFecha(data.created_at) }}</span>
                                    </template>
                                </Column>

                                <Column header="Nombre" field="_nombre" sortable :showFilterMenu="false">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ nombreUsuario(data) }}</span>
                                            <span
                                                class="inline-flex w-fit items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="isInvitado(data) ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                            >
                                                {{ isInvitado(data) ? 'Invitado' : 'Registrado' }}
                                            </span>
                                        </div>
                                    </template>
                                    <template #filter="{ filterModel, filterCallback }">
                                        <input
                                            v-model="filterModel.value"
                                            type="text"
                                            placeholder="Nombre"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1 text-sm"
                                            @input="filterCallback()"
                                        />
                                    </template>
                                </Column>

                                <Column header="Actividad" field="actividad.nombre" filterField="actividad.nombre" sortable :showFilterMenu="false">
                                    <template #body="{ data }">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ data.actividad?.nombre || '-' }}
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ data.actividad?.entidad?.nombre || '-' }}
                                            </p>
                                        </div>
                                    </template>
                                    <template #filter="{ filterModel, filterCallback }">
                                        <Dropdown
                                            v-model="filterModel.value"
                                            :options="actividadNombres"
                                            placeholder="Actividad"
                                            class="p-column-filter w-full"
                                            :showClear="true"
                                            @change="filterCallback()"
                                        />
                                    </template>
                                </Column>

                                <Column v-if="cols.membresia" header="Membresía" field="membresia" sortable :showFilterMenu="false">
                                    <template #body="{ data }">
                                        <span class="text-sm">{{ data.membresia || '-' }}</span>
                                    </template>
                                    <template #filter="{ filterModel, filterCallback }">
                                        <Dropdown
                                            v-model="filterModel.value"
                                            :options="membresiaOptions"
                                            placeholder="Membresía"
                                            class="p-column-filter w-full"
                                            :showClear="true"
                                            @change="filterCallback()"
                                        />
                                    </template>
                                </Column>

                                <Column header="Monto">
                                    <template #body="{ data }">
                                        <input
                                            v-if="isEditing(data)"
                                            v-model.number="editForm.montoapagar"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-28 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1 text-sm"
                                        />
                                        <span v-else class="text-sm">
                                            <span class="text-blue-700 font-medium">${{ formatearMonto(data.montoapagar) }}</span>
                                        </span>
                                    </template>
                                </Column>

                                <Column header="Pago" field="pago" class="text-center" :showFilterMenu="false">
                                    <template #body="{ data }">
                                        <select
                                            v-if="isEditing(data)"
                                            v-model="editForm.pago"
                                            class="rounded border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm"
                                        >
                                            <option value="Saldado">Saldado</option>
                                            <option value="Parcial">Parcial</option>
                                            <option value="Pendiente">Pendiente</option>
                                        </select>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                            :class="badgePagoClass(data.pago)"
                                        >
                                            {{ data.pago || '-' }}
                                        </span>
                                    </template>
                                    <template #filter="{ filterModel, filterCallback }">
                                        <Dropdown
                                            v-model="filterModel.value"
                                            :options="pagoOptions"
                                            placeholder="Pago"
                                            class="p-column-filter w-full"
                                            :showClear="true"
                                            @change="filterCallback()"
                                        />
                                    </template>
                                </Column>

                                <Column header="Comprobante" class="text-center">
                                    <template #body="{ data }">
                                        <button
                                            v-if="canEdit"
                                            type="button"
                                            @click="openComprobanteModal(data)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200"
                                            title="Subir comprobante"
                                        >
                                            <i class="pi pi-upload"></i>
                                        </button>
                                        <template v-else>
                                            <button
                                                v-if="urlComprobante(data)"
                                                type="button"
                                                @click="abrirComprobante(data)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200"
                                                title="Ver comprobante"
                                            >
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <span
                                                v-if="comprobantesExtras(data) > 0"
                                                class="text-xs font-semibold text-gray-500"
                                            >
                                                +{{ comprobantesExtras(data) }}
                                            </span>
                                            <span v-else-if="!urlComprobante(data)" class="text-xs text-gray-400">Sin comprobante</span>
                                        </template>
                                    </template>
                                </Column>

                                <Column v-if="cols.estado" header="Estado" field="estado" sortable :showFilterMenu="false">
                                    <template #body="{ data }">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                            :class="badgeEstadoClass(data.estado)"
                                        >
                                            {{ data.estado || '-' }}
                                        </span>
                                    </template>
                                    <template #filter="{ filterModel, filterCallback }">
                                        <Dropdown
                                            v-model="filterModel.value"
                                            :options="opcionesFiltro.estado"
                                            placeholder="Estado"
                                            class="p-column-filter w-full"
                                            :showClear="true"
                                            @change="filterCallback()"
                                        />
                                    </template>
                                </Column>

                                <Column v-if="cols.envioConfirmacion" header="Envío Confirmación" field="envioConfirmacion" :showFilterMenu="false">
                                    <template #body="{ data }">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                            :class="badgeEnvioClass(envioEstado(data, 'envioConfirmacion'))"
                                        >
                                            {{ envioEstado(data, 'envioConfirmacion') }}
                                        </span>
                                    </template>
                                    <template #filter="{ filterModel, filterCallback }">
                                        <Dropdown
                                            v-model="filterModel.value"
                                            :options="opcionesFiltro.envioConfirmacion"
                                            placeholder="Estado"
                                            class="p-column-filter w-full"
                                            :showClear="true"
                                            @change="filterCallback()"
                                        />
                                    </template>
                                </Column>

                                <Column header="Acciones" class="text-center">
                                    <template #body="{ data }">
                                        <div v-if="canEdit" class="flex justify-center gap-2">
                                            
                                            <template v-if="isEditing(data)">
                                                <button
                                                    class="inline-flex items-center justify-center rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700 disabled:opacity-60"
                                                    :disabled="isSaving"
                                                    @click="guardarEdicion(data)"
                                                    aria-label="Guardar"
                                                    title="Guardar"
                                                >
                                                    ✓
                                                </button>
                                                <button
                                                    class="inline-flex items-center justify-center rounded bg-gray-200 dark:bg-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600"
                                                    @click="cancelarEdicion"
                                                    aria-label="Cancelar"
                                                    title="Cancelar"
                                                >
                                                    ✕
                                                </button>
                                            </template>
                                            <button
                                                v-else
                                                class="inline-flex items-center justify-center rounded bg-indigo-50 px-2 py-1 text-xs text-indigo-700 hover:bg-indigo-100"
                                                @click="iniciarEdicion(data)"
                                                aria-label="Editar"
                                                title="Editar"
                                            >
                                                <i class="pi pi-file-edit"></i>
                                            </button>
                                            <button
                                                v-if="!isEditing(data)"
                                                class="inline-flex items-center justify-center rounded bg-emerald-50 px-2 py-1 text-xs text-emerald-700 hover:bg-emerald-100"
                                                @click="abrirConfirmarSaldado(data)"
                                                aria-label="Saldado"
                                                title="Saldado"
                                            >
                                                <i class="pi pi-check-circle"></i>
                                            </button>
                                            <button
                                                v-if="!isEditing(data)"
                                                class="inline-flex items-center justify-center rounded bg-red-50 px-2 py-1 text-xs text-red-700 hover:bg-red-100"
                                                @click="abrirConfirmarBorrar(data)"
                                                aria-label="Eliminar"
                                                title="Eliminar inscripción"
                                            >
                                                <i class="pi pi-trash"></i>
                                            </button>
                                        </div>
                                        <span v-else class="text-xs text-gray-400">Sin permisos</span>
                                    </template>
                                </Column>

                                <template #expansion="{ data }">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-md p-4">
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
                                            <div v-if="!cols.membresia">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Membresía</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ data.membresia || '-' }}</p>
                                            </div>
                                            <div v-if="!cols.estado">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</p>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="badgeEstadoClass(data.estado)"
                                                >
                                                    {{ data.estado || '-' }}
                                                </span>
                                            </div>
                                            <div v-if="!cols.envioConfirmacion">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Envío Confirmación</p>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="badgeEnvioClass(envioEstado(data, 'envioConfirmacion'))"
                                                >
                                                    {{ envioEstado(data, 'envioConfirmacion') }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha de pago</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ formatearSoloFecha(data.fecha_pago) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Referencia de pago</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ data.referencia_pago || '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ emailUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">País</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ paisUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Provincia</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ provinciaUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Municipio/Barrio</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ municipioBarrioUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Inscripto</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ formatearFecha(data.created_at) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Modalidad</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ modalidadInscripcion(data) }}</p>
                                            </div>
                                            <div v-if="data.montoActividad !== null && data.montoActividad !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Actividad</p>
                                                <p class="text-sm text-blue-700 font-medium">${{ formatearMonto(data.montoActividad) }}</p>
                                            </div>
                                            <div v-if="data.montoTransporte !== null && data.montoTransporte !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Transporte</p>
                                                <p class="text-sm text-blue-700 font-medium">${{ formatearMonto(data.montoTransporte) }}</p>
                                            </div>
                                            <div v-if="data.montoComidas !== null && data.montoComidas !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Comidas</p>
                                                <p class="text-sm text-blue-700 font-medium">${{ formatearMonto(data.montoComidas) }}</p>
                                            </div>
                                            <div v-if="data.montoGrabacion !== null && data.montoGrabacion !== undefined">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Monto Grabación</p>
                                                <p class="text-sm text-blue-700 font-medium">${{ formatearMonto(data.montoGrabacion) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auditor</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ data.auditor_user?.name || '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auditado</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ formatearFecha(data.auditoria_fecha) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Envío Registro</p>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="badgeEnvioClass(envioEstado(data, 'envioRegistro'))"
                                                >
                                                    {{ envioEstado(data, 'envioRegistro') }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Envío Grabación</p>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="badgeEnvioClass(envioEstado(data, 'envioGrabacion'))"
                                                >
                                                    {{ envioEstado(data, 'envioGrabacion') }}
                                                </span>
                                            </div>
                                            <div class="md:col-span-3 lg:col-span-6">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Observaciones</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line">{{ data.observaciones || '-' }}</p>
                                            </div>
                                        </div>

                                        <div v-if="data.invitados?.length" class="mt-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                                                Invitados ({{ data.invitados.length }})
                                            </p>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-xs uppercase text-gray-500">
                                                            <th class="py-1 pr-3">Nombre</th>
                                                            <th class="py-1 pr-3">Modalidad</th>
                                                            <th class="py-1 pr-3">Servicios</th>
                                                            <th class="py-1 pr-3 text-right">Monto</th>
                                                            <th class="py-1 pr-3">Asistencia</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="invitado in data.invitados"
                                                            :key="invitado.id"
                                                            class="border-t border-gray-200 dark:border-gray-700"
                                                        >
                                                            <td class="py-1 pr-3 text-gray-800 dark:text-gray-100">{{ invitado.nombre }} {{ invitado.apellido }}</td>
                                                            <td class="py-1 pr-3 text-gray-600 dark:text-gray-300">{{ invitado.online ? 'Online' : 'Presencial' }}</td>
                                                            <td class="py-1 pr-3 text-gray-600 dark:text-gray-300">{{ serviciosInvitado(invitado) }}</td>
                                                            <td class="py-1 pr-3 text-right text-blue-700 font-medium">${{ formatearMonto(invitado.montoapagar) }}</td>
                                                            <td class="py-1 pr-3">
                                                                <select
                                                                    :value="invitado.asistencia || 'Pendiente'"
                                                                    @change="marcarAsistenciaInvitado(invitado, $event.target.value)"
                                                                    class="text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-2 py-1"
                                                                >
                                                                    <option value="Pendiente">Pendiente</option>
                                                                    <option value="Presente">Presente</option>
                                                                    <option value="Ausente">Ausente</option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>
                        </div>

                        <div v-else class="text-center py-12">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400 text-lg">No hay inscripciones activas</p>
                            <p class="text-gray-500 text-sm mt-2">
                                Las inscripciones aparecerán cuando los usuarios se registren en actividades
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="comprobanteModal" modal header="Comprobante" :style="{ width: '700px' }">
            <div class="max-h-[70vh] overflow-y-auto space-y-4">
                <div
                    v-for="(item, index) in comprobantesParaVer"
                    :key="`${item.url}-${index}`"
                    class="rounded border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800"
                >
                    <p v-if="item.descripcion" class="text-xs text-gray-500 mb-2">
                        {{ item.descripcion }}
                    </p>
                    <iframe v-if="item.isPdf" :src="item.url" class="w-full h-[60vh] rounded"></iframe>
                    <img v-else :src="item.url" class="w-full rounded" alt="Comprobante" />
                </div>
            </div>
        </Dialog>

        <Dialog
            v-model:visible="confirmSaldadoVisible"
            modal
            header="Confirmar saldado"
            :style="{ width: '420px' }"
        >
            <p class="text-sm text-gray-700 dark:text-gray-300">
                ¿Confirmas marcar la inscripción como saldada y dejar el monto en $0?
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-100 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                        @click="confirmSaldadoVisible = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
                        @click="confirmarSaldado"
                    >
                        Confirmar
                    </button>
                </div>
            </template>
        </Dialog>

        <Dialog
            v-model:visible="confirmDeleteVisible"
            modal
            header="Eliminar inscripción"
            :style="{ width: '440px' }"
        >
            <p class="text-sm text-gray-700 dark:text-gray-300">
                ¿Seguro que querés eliminar la inscripción de
                <span class="font-semibold">{{ inscripcionParaBorrar ? nombreUsuario(inscripcionParaBorrar) : '' }}</span>
                <template v-if="inscripcionParaBorrar?.actividad?.nombre">
                    a <span class="font-semibold">{{ inscripcionParaBorrar.actividad.nombre }}</span>
                </template>?
            </p>
            <p class="mt-2 text-xs text-red-600">
                Esta acción no se puede deshacer. También se eliminan sus comprobantes asociados.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-100 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                        :disabled="isDeleting"
                        @click="confirmDeleteVisible = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-60"
                        :disabled="isDeleting"
                        @click="confirmarBorrar"
                    >
                        {{ isDeleting ? 'Eliminando...' : 'Eliminar' }}
                    </button>
                </div>
            </template>
        </Dialog>

        <Dialog
            v-model:visible="comprobanteModalVisible"
            modal
            header="Subir comprobante"
            :style="{ width: '450px' }"
        >
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                Subí un comprobante (PDF, JPG o PNG).
            </p>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="estado_inscripciones_comprobante_descripcion">
                    Descripción (opcional)
                </label>
                <input
                    id="estado_inscripciones_comprobante_descripcion"
                    v-model="comprobanteDescripcion"
                    type="text"
                    class="block w-full rounded border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-300"
                    placeholder="Ej: Transferencia febrero"
                />
            </div>
            <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                @change="onComprobanteChange"
                class="block w-full text-sm text-gray-700 dark:text-gray-300"
            />
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-100 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                        @click="comprobanteModalVisible = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 bg-sky-600 text-white rounded hover:bg-sky-700"
                        :disabled="!comprobanteFile"
                        @click="subirComprobante"
                    >
                        Subir
                    </button>
                </div>
            </template>
        </Dialog>

        <Dialog
            v-model:visible="confirmEnviosVisible"
            modal
            header="Confirmar envío"
            :style="{ width: '520px' }"
        >
            <p class="text-sm text-gray-700 dark:text-gray-300">
                Se enviarán {{ totalConfirmacionesPendientes }} confirmaciones de inscripción por correo electrónico.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-100 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                        :disabled="isSendingConfirmaciones"
                        @click="confirmEnviosVisible = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-60"
                        :disabled="isSendingConfirmaciones || totalConfirmacionesPendientes <= 0"
                        @click="enviarConfirmaciones"
                    >
                        Confirmar envío
                    </button>
                </div>
            </template>
        </Dialog>

        <Dialog
            v-model:visible="confirmEnviosGrabacionesVisible"
            modal
            header="Confirmar envío"
            :style="{ width: '520px' }"
        >
            <p class="text-sm text-gray-700 dark:text-gray-300">
                Se enviarán {{ totalGrabacionesPendientes }} notificaciones de grabación por correo electrónico.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-100 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                        :disabled="isSendingGrabaciones"
                        @click="confirmEnviosGrabacionesVisible = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 disabled:opacity-60"
                        :disabled="isSendingGrabaciones || totalGrabacionesPendientes <= 0"
                        @click="enviarGrabaciones"
                    >
                        Confirmar envío
                    </button>
                </div>
            </template>
        </Dialog>

        <!-- Dialog: Crear inscripción (admin inscribe en nombre de otra persona) -->
        <Dialog
            v-model:visible="crearDialog"
            modal
            header="Crear inscripción"
            :style="{ width: '820px' }"
            :breakpoints="{ '768px': '95vw' }"
        >
            <div class="space-y-5 pt-1">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Actividad</label>
                    <Dropdown
                        v-model="crearActividadId"
                        :options="actividades"
                        optionLabel="nombre"
                        optionValue="id"
                        :filter="true"
                        placeholder="Seleccioná una actividad"
                        class="w-full border border-gray-300 dark:border-gray-600"
                    />
                    <p v-if="crearErrors.actividad_id" class="mt-1 text-xs text-red-500">{{ crearErrors.actividad_id[0] }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Participante</label>
                    <div class="flex flex-wrap items-center gap-6">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <RadioButton v-model="crearModo" inputId="crear_modo_existente" value="existente" />
                            Participante existente
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <RadioButton v-model="crearModo" inputId="crear_modo_nuevo" value="nuevo" />
                            Participante nuevo
                        </label>
                    </div>
                </div>

                <div v-if="crearModo === 'existente'">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Buscar usuario</label>
                    <AutoComplete
                        v-model="crearUsuarioSeleccionado"
                        :suggestions="crearUsuariosSugeridos"
                        optionLabel="label"
                        placeholder="Escribí nombre o email…"
                        :minLength="2"
                        :delay="300"
                        class="w-full"
                        inputClass="w-full"
                        @complete="buscarUsuarios"
                    />
                    <p class="mt-1 text-xs text-gray-400">Escribí al menos 2 caracteres del nombre o email.</p>
                    <p v-if="crearErrors.user_id" class="mt-1 text-xs text-red-500">{{ crearErrors.user_id[0] }}</p>
                </div>

                <div v-else>
                    <GuestUserForm
                        :form="crearGuest"
                        :errors="crearErrors"
                        :paises="paises"
                        :provincias="provincias"
                        :municipios="municipios"
                        :barrios="barrios"
                        :mostrar-registrar-datos="true"
                    />
                </div>
            </div>
            <template #footer>
                <button
                    type="button"
                    class="rounded px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100"
                    @click="crearDialog = false"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                    :disabled="crearSubmitting"
                    @click="confirmarCrearInscripcion"
                >
                    {{ crearSubmitting ? 'Preparando…' : 'Continuar al pago' }}
                </button>
            </template>
        </Dialog>

        <!-- Dialog de edición completa (servicios + invitados, recálculo automático) -->
        <Dialog
            v-model:visible="editDialog"
            modal
            header="Editar inscripción"
            :style="{ width: '760px' }"
            :breakpoints="{ '768px': '95vw' }"
        >
            <div v-if="editLoading" class="py-10 text-center text-gray-500">
                Cargando datos…
            </div>
            <div v-else-if="editActividad" class="space-y-5 pt-1">
                <!-- Titular -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Titular</p>
                    <div class="flex flex-wrap items-center gap-4 mb-3">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600 dark:text-gray-300">Estado de pago</label>
                            <select
                                v-model="editTitular.pago"
                                class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-2 py-1 text-sm"
                            >
                                <option value="Saldado">Saldado</option>
                                <option value="Parcial">Parcial</option>
                                <option value="Pendiente">Pendiente</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <Checkbox v-model="editTitular.online" binary inputId="edit_titular_online" />
                            Cursa online
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Precio actividad: <span class="font-semibold">{{ formatMoneyEdit(editTitular.montoActividad) }}</span> (no se modifica)</p>
                    <ServiciosActividadSelector
                        :actividad="editActividad"
                        v-model:grabacion="editTitular.grabacion"
                        v-model:comidas="editTitular.comidas"
                        v-model:transportes="editTitular.transportes"
                        v-model:hospedajes="editTitular.hospedajes"
                        :resolver-precio="resolverPrecioEdit"
                        :format-money="formatMoneyEdit"
                        id-prefix="edit_titular"
                    />
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">Subtotal titular: <span class="font-semibold">{{ formatMoneyEdit(subtotalTitularEdit) }}</span></p>
                </div>

                <!-- Invitados -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Invitados ({{ editInvitados.length }}/{{ MAX_INVITADOS }})</p>
                        <button
                            type="button"
                            class="px-3 py-1 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700 disabled:bg-gray-300"
                            :disabled="editInvitados.length >= MAX_INVITADOS"
                            @click="agregarInvitadoEdit"
                        >
                            Agregar invitado
                        </button>
                    </div>

                    <div v-if="editInvitados.length" class="space-y-4">
                        <div
                            v-for="(inv, idx) in editInvitados"
                            :key="`edit-inv-${idx}`"
                            class="rounded-md border border-gray-200 dark:border-gray-700 p-3"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Invitado {{ idx + 1 }}</span>
                                <button type="button" class="text-red-600 text-sm hover:underline" @click="eliminarInvitadoEdit(idx)">Eliminar</button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-2">
                                <input v-model="inv.nombre" type="text" placeholder="Nombre *" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-2 py-1 text-sm" />
                                <input v-model="inv.apellido" type="text" placeholder="Apellido *" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-2 py-1 text-sm" />
                                <input v-model="inv.telefono" type="text" placeholder="Teléfono" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-2 py-1 text-sm" />
                            </div>
                            <label v-if="editModalidadAbierta" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 mb-2">
                                <Checkbox v-model="inv.online" binary :inputId="`edit_inv_online_${idx}`" />
                                Cursa online
                            </label>
                            <ServiciosActividadSelector
                                :actividad="editActividad"
                                v-model:grabacion="inv.grabacion"
                                v-model:comidas="inv.comidas"
                                v-model:transportes="inv.transportes"
                                v-model:hospedajes="inv.hospedajes"
                                :resolver-precio="resolverPrecioEdit"
                                :format-money="formatMoneyEdit"
                                :id-prefix="`edit_inv_${idx}`"
                            />
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">Subtotal: <span class="font-semibold">{{ formatMoneyEdit(subtotalInvitadoEdit(inv)) }}</span></p>
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400">Sin invitados.</p>
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
                    <p class="text-base text-gray-800 dark:text-gray-100">Total a pagar: <span class="font-bold text-green-700">{{ formatMoneyEdit(totalEdit) }}</span></p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button class="px-4 py-2 bg-gray-500 text-white rounded" @click="cancelarEdicion">Cancelar</button>
                    <button
                        class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-60"
                        :disabled="isSaving || editLoading"
                        @click="guardarEdicionCompleta"
                    >
                        {{ isSaving ? 'Guardando…' : 'Guardar' }}
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from 'primevue/api';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import AutoComplete from 'primevue/autocomplete';
import ServiciosActividadSelector from '@/Components/Actividades/ServiciosActividadSelector.vue';
import GuestUserForm from '@/Components/Formularios/GuestUserForm.vue';

const props = defineProps({
    inscripciones: Array,
    actividades: { type: Array, default: () => [] },
    paises: { type: Array, default: () => [] },
    provincias: { type: Array, default: () => [] },
    municipios: { type: Array, default: () => [] },
    barrios: { type: Array, default: () => [] },
});

const toast = useToast();
const page = usePage();
const filtroPeriodo = ref('last1');
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    _nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'actividad.nombre': { value: null, matchMode: FilterMatchMode.EQUALS },
    membresia: { value: null, matchMode: FilterMatchMode.EQUALS },
    pago: { value: null, matchMode: FilterMatchMode.EQUALS },
    estado: { value: null, matchMode: FilterMatchMode.EQUALS },
    envioConfirmacion: { value: null, matchMode: FilterMatchMode.EQUALS },
});

// Visibilidad de columnas según el ancho de la ventana: a medida que se achica,
// se ocultan más columnas (las menos críticas primero) y pasan al expandable row.
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1920);
const onResize = () => { windowWidth.value = window.innerWidth; };
onMounted(() => window.addEventListener('resize', onResize));
onUnmounted(() => window.removeEventListener('resize', onResize));

const cols = computed(() => {
    const w = windowWidth.value;
    // Umbrales pensados para que en pantallas de 1366px (innerWidth ~1349 al
    // descontar la scrollbar) NO haya scroll horizontal: a ese ancho se muestran
    // las columnas núcleo + Membresía, y Estado/Envío Confirmación se colapsan a
    // la fila expandible. Se vuelven a mostrar en pantallas más anchas.
    return {
        membresia: w >= 1080,
        estado: w >= 1500,
        envioConfirmacion: w >= 1500,
    };
});
const expandedRows = ref([]);
const expandedCardIds = ref([]);
const editRowId = ref(null);
const editForm = ref({
    montoapagar: 0,
    pago: 'Pendiente'
});
const isSaving = ref(false);
const comprobanteModalVisible = ref(false);
const inscripcionParaComprobante = ref(null);
const comprobanteFile = ref(null);
const comprobanteDescripcion = ref('');

const canEdit = computed(() => {
    const roles = (page.props.user?.roles || []).map((role) => String(role).toLowerCase());
    return roles.includes('admin') || roles.includes('editor');
});

// ----- Dialog: Crear inscripción (admin inscribe en nombre de otra persona) -----
const crearDialog = ref(false);
const crearSubmitting = ref(false);
const crearErrors = ref({});
const crearActividadId = ref(null);
const crearModo = ref('existente');
const crearUsuarioSeleccionado = ref(null);
const crearUsuariosSugeridos = ref([]);

const guestFormVacio = () => ({
    name: '', email: '', telefono: '', whatsapp: '',
    pais_id: null, provincia_id: null, municipio_id: null, barrio_id: null,
    direccion: '', msgxmail: false, msgxwapp: false,
    accesibilidad: false, accesibilidad_desc: '', info_tarjetas_kadampa: false,
    registrar_datos: false,
});
const crearGuest = ref(guestFormVacio());

const abrirCrearInscripcion = () => {
    if (!canEdit.value) return;
    crearActividadId.value = null;
    crearModo.value = 'existente';
    crearUsuarioSeleccionado.value = null;
    crearUsuariosSugeridos.value = [];
    crearGuest.value = guestFormVacio();
    crearErrors.value = {};
    crearDialog.value = true;
};

const buscarUsuarios = async (event) => {
    const q = String(event?.query || '').trim();
    if (q.length < 2) {
        crearUsuariosSugeridos.value = [];
        return;
    }
    try {
        const { data } = await axios.get(route('estadoinscripciones.buscar-usuarios'), { params: { q } });
        crearUsuariosSugeridos.value = (data.usuarios || []).map((u) => ({ ...u, label: `${u.name} — ${u.email}` }));
    } catch (e) {
        crearUsuariosSugeridos.value = [];
    }
};

const confirmarCrearInscripcion = async () => {
    if (crearSubmitting.value) return;
    crearErrors.value = {};

    if (!crearActividadId.value) {
        crearErrors.value = { actividad_id: ['Seleccioná una actividad.'] };
        return;
    }

    const payload = { actividad_id: crearActividadId.value, modo: crearModo.value };
    if (crearModo.value === 'existente') {
        const uid = crearUsuarioSeleccionado.value?.id;
        if (!uid) {
            crearErrors.value = { user_id: ['Seleccioná un usuario.'] };
            return;
        }
        payload.user_id = uid;
    } else {
        payload.guest = { ...crearGuest.value };
    }

    crearSubmitting.value = true;
    try {
        const { data } = await axios.post(route('estadoinscripciones.crear-prepare'), payload);
        if (data.ok) {
            router.visit(route('grid-actividades.pago', crearActividadId.value));
        }
    } catch (error) {
        if (error.response?.status === 422) {
            crearErrors.value = error.response.data.errors || {};
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo preparar la inscripción.', life: 4000 });
        }
    } finally {
        crearSubmitting.value = false;
    }
};

const nombreUsuario = (inscripcion) => {
    if (isInvitado(inscripcion) && inscripcion.guest_user?.name) {
        return inscripcion.guest_user.name;
    }
    return inscripcion.user?.name || inscripcion.guest_user?.name || 'Sin datos';
};

const emailUsuario = (inscripcion) => {
    if (isInvitado(inscripcion) && inscripcion.guest_user?.email) {
        return inscripcion.guest_user.email;
    }
    return inscripcion.user?.email || inscripcion.guest_user?.email || '-';
};

const isInvitado = (inscripcion) => {
    return inscripcion.user?.name === 'Invitado';
};

// La edición pasó a un dialog completo; ya no hay edición inline en la fila.
const isEditing = () => false;

// ----- Dialog de edición completa (servicios + invitados, recálculo automático) -----
const MAX_INVITADOS = 10;
const editDialog = ref(false);
const editLoading = ref(false);
const editInscripcionId = ref(null);
const editActividad = ref(null);
const editModalidadAbierta = ref(false);
const editTitular = ref({
    online: false,
    pago: 'Pendiente',
    montoActividad: 0,
    precioGeneral: 0,
    grabacion: false,
    comidas: [],
    transportes: [],
    hospedajes: [],
});
const editInvitados = ref([]);

const nuevoInvitadoEdit = () => ({
    nombre: '',
    apellido: '',
    telefono: '',
    online: false,
    grabacion: false,
    comidas: [],
    transportes: [],
    hospedajes: [],
});

// Funciones inyectadas a ServiciosActividadSelector (precio simple, moneda única).
const resolverPrecioEdit = (item, campo = 'precio') => ({
    precio: Number(item?.[campo] ?? 0) || 0,
    simbolo: '$',
});
const formatMoneyEdit = (valor) => `$${formatearMonto(valor)}`;

const sumarServiciosEdit = (sel) => {
    const a = editActividad.value;
    if (!a) return 0;
    let total = 0;
    if (sel.grabacion && a.grabacion) total += Number(a.grabacion.valor || 0);
    (a.comidas || []).forEach((c) => { if (sel.comidas.includes(c.id)) total += Number(c.precio || 0); });
    (a.transportes || []).forEach((t) => { if (sel.transportes.includes(t.id)) total += Number(t.precio || 0); });
    (a.hospedajes || []).forEach((h) => { if (sel.hospedajes.includes(h.id)) total += Number(h.precio || 0); });
    return total;
};
const subtotalTitularEdit = computed(() => Number(editTitular.value.montoActividad || 0) + sumarServiciosEdit(editTitular.value));
const subtotalInvitadoEdit = (inv) => Number(editTitular.value.precioGeneral || 0) + sumarServiciosEdit(inv);
const totalEdit = computed(() => subtotalTitularEdit.value + editInvitados.value.reduce((acc, inv) => acc + subtotalInvitadoEdit(inv), 0));

const agregarInvitadoEdit = () => {
    if (editInvitados.value.length >= MAX_INVITADOS) {
        toast.add({ severity: 'warn', summary: 'Invitados', detail: `Máximo ${MAX_INVITADOS} invitados.`, life: 4000 });
        return;
    }
    editInvitados.value.push(nuevoInvitadoEdit());
};
const eliminarInvitadoEdit = (index) => {
    editInvitados.value.splice(index, 1);
};

const guardarEdicionCompleta = async () => {
    if (!canEdit.value || isSaving.value) return;
    // Validación mínima de invitados.
    for (const inv of editInvitados.value) {
        if (!String(inv.nombre).trim() || !String(inv.apellido).trim()) {
            toast.add({ severity: 'warn', summary: 'Invitados', detail: 'Cada invitado necesita nombre y apellido.', life: 4000 });
            return;
        }
    }
    isSaving.value = true;
    try {
        const payload = {
            pago: editTitular.value.pago,
            online: editTitular.value.online,
            incluye_grabacion: editTitular.value.grabacion,
            comidas_ids: editTitular.value.comidas,
            transportes_ids: editTitular.value.transportes,
            hospedajes_ids: editTitular.value.hospedajes,
            invitados: editInvitados.value.map((inv) => ({
                nombre: inv.nombre.trim(),
                apellido: inv.apellido.trim(),
                telefono: (inv.telefono || '').trim() || null,
                online: editModalidadAbierta.value ? !!inv.online : false,
                incluye_grabacion: !!inv.grabacion,
                comidas_ids: inv.comidas,
                transportes_ids: inv.transportes,
                hospedajes_ids: inv.hospedajes,
            })),
        };
        await axios.put(route('estadoinscripciones.update', editInscripcionId.value), payload);
        editDialog.value = false;
        toast.add({ severity: 'success', summary: 'Inscripción', detail: 'Cambios guardados.', life: 3000 });
        router.reload({ only: ['inscripciones'] });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'No se pudo guardar la edición.', life: 5000 });
    } finally {
        isSaving.value = false;
    }
};

const isCardExpanded = (id) => expandedCardIds.value.includes(id);

const toggleCardExpanded = (id) => {
    const idx = expandedCardIds.value.indexOf(id);
    if (idx === -1) {
        expandedCardIds.value.push(id);
    } else {
        expandedCardIds.value.splice(idx, 1);
    }
};

// Abre el dialog de edición completa y carga los datos (servicios + invitados).
const iniciarEdicion = async (inscripcion) => {
    if (!canEdit.value) return;
    editInscripcionId.value = inscripcion.id;
    editLoading.value = true;
    editActividad.value = null;
    editInvitados.value = [];
    editDialog.value = true;
    try {
        const { data } = await axios.get(route('estadoinscripciones.editar-data', { estadoinscripcion: inscripcion.id }));
        editActividad.value = data.actividad;
        editModalidadAbierta.value = !!data.modalidad_abierta;
        editTitular.value = {
            online: !!data.inscripcion.online,
            pago: data.inscripcion.pago || 'Pendiente',
            montoActividad: Number(data.inscripcion.montoActividad || 0),
            precioGeneral: Number(data.inscripcion.precioGeneral || 0),
            grabacion: !!data.inscripcion.incluye_grabacion,
            comidas: [...(data.inscripcion.comidas_ids || [])],
            transportes: [...(data.inscripcion.transportes_ids || [])],
            hospedajes: [...(data.inscripcion.hospedajes_ids || [])],
        };
        editInvitados.value = (data.invitados || []).map((inv) => ({
            nombre: inv.nombre,
            apellido: inv.apellido,
            telefono: inv.telefono || '',
            online: !!inv.online,
            grabacion: !!inv.incluye_grabacion,
            comidas: [...(inv.comidas_ids || [])],
            transportes: [...(inv.transportes_ids || [])],
            hospedajes: [...(inv.hospedajes_ids || [])],
        }));
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los datos de edición.', life: 4000 });
        editDialog.value = false;
    } finally {
        editLoading.value = false;
    }
};

const cancelarEdicion = () => {
    editDialog.value = false;
};

// Cambio rápido del estado de pago (botón "marcar saldado"); no toca servicios.
const guardarEdicion = async (inscripcion) => {
    if (!canEdit.value || isSaving.value) return;
    isSaving.value = true;
    try {
        const { data } = await axios.patch(route('estadoinscripciones.pago', { estadoinscripcion: inscripcion.id }), {
            pago: editForm.value.pago,
        });
        inscripcion.pago = editForm.value.pago;
        if (data?.estado) inscripcion.estado = data.estado;
        editRowId.value = null;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el pago.', life: 4000 });
    } finally {
        isSaving.value = false;
    }
};

const serviciosInvitado = (invitado) => {
    const items = [];
    if (invitado.incluye_grabacion) items.push('Grabación');
    (invitado.comidas || []).forEach((c) => items.push(c.nombre));
    (invitado.transportes || []).forEach((t) => items.push(t.descripcion || 'Transporte'));
    (invitado.hospedajes || []).forEach((h) => items.push(h.nombre));
    return items.length ? items.join(', ') : 'Sin servicios';
};

const marcarAsistenciaInvitado = async (invitado, asistencia) => {
    const anterior = invitado.asistencia;
    try {
        await axios.patch(route('invitados.asistencia', { invitado: invitado.id }), { asistencia });
        invitado.asistencia = asistencia;
        toast.add({ severity: 'success', summary: 'Asistencia', detail: `${invitado.nombre}: ${asistencia}`, life: 3000 });
    } catch (error) {
        invitado.asistencia = anterior;
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la asistencia.', life: 4000 });
    }
};

const openComprobanteModal = (inscripcion) => {
    inscripcionParaComprobante.value = inscripcion;
    comprobanteFile.value = null;
    comprobanteDescripcion.value = '';
    comprobanteModalVisible.value = true;
};

const onComprobanteChange = (event) => {
    const files = event.target.files;
    comprobanteFile.value = files && files[0] ? files[0] : null;
};

const subirComprobante = () => {
    if (!inscripcionParaComprobante.value || !comprobanteFile.value) return;

    const formData = new FormData();
    formData.append('comprobante', comprobanteFile.value);
    if (comprobanteDescripcion.value) {
        formData.append('descripcion', comprobanteDescripcion.value);
    }

    const inscripcionId = inscripcionParaComprobante.value.id;

    router.post(
        route('inscripciones.comprobante', { inscripcion: inscripcionId }),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                comprobanteModalVisible.value = false;
                inscripcionParaComprobante.value = null;
                comprobanteFile.value = null;
                comprobanteDescripcion.value = '';
            },
        }
    );
};

const abrirConfirmarSaldado = (inscripcion) => {
    if (!canEdit.value || isSaving.value) return;
    inscripcionParaSaldar.value = inscripcion;
    confirmSaldadoVisible.value = true;
};

const confirmarSaldado = () => {
    const inscripcion = inscripcionParaSaldar.value;
    if (!inscripcion || !canEdit.value || isSaving.value) return;
    editForm.value = {
        montoapagar: 0,
        pago: 'Saldado',
    };
    confirmSaldadoVisible.value = false;
    inscripcionParaSaldar.value = null;
    guardarEdicion(inscripcion);
};

const abrirConfirmarBorrar = (inscripcion) => {
    if (!canEdit.value || isDeleting.value) return;
    inscripcionParaBorrar.value = inscripcion;
    confirmDeleteVisible.value = true;
};

const confirmarBorrar = () => {
    const inscripcion = inscripcionParaBorrar.value;
    if (!inscripcion || !canEdit.value || isDeleting.value) return;
    isDeleting.value = true;
    router.delete(route('estadoinscripciones.destroy', { estadoinscripcion: inscripcion.id }), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Inscripción eliminada',
                detail: 'La inscripción fue eliminada correctamente.',
                life: 4000,
            });
            confirmDeleteVisible.value = false;
            inscripcionParaBorrar.value = null;
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo eliminar la inscripción.',
                life: 5000,
            });
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const paisUsuario = (inscripcion) => {
    if (isInvitado(inscripcion)) {
        return inscripcion.guest_user?.pais?.nombre || '-';
    }
    return inscripcion.user?.pais?.nombre || inscripcion.guest_user?.pais?.nombre || '-';
};

const provinciaUsuario = (inscripcion) => {
    if (isInvitado(inscripcion)) {
        return inscripcion.guest_user?.provincia?.nombre || '-';
    }
    return inscripcion.user?.provincia?.nombre || inscripcion.guest_user?.provincia?.nombre || '-';
};

const municipioBarrioUsuario = (inscripcion) => {
    if (isInvitado(inscripcion)) {
        return (
            inscripcion.guest_user?.municipio?.nombre ||
            inscripcion.guest_user?.barrio?.nombre ||
            '-'
        );
    }
    return (
        inscripcion.user?.municipio?.nombre ||
        inscripcion.user?.barrio?.nombre ||
        inscripcion.guest_user?.municipio?.nombre ||
        inscripcion.guest_user?.barrio?.nombre ||
        '-'
    );
};

const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleString('es-AR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// fecha_pago es un cast 'date'; se formatea desde el string YYYY-MM-DD para evitar
// el corrimiento de día por zona horaria que produce new Date(...) sobre medianoche UTC.
const formatearSoloFecha = (fecha) => {
    if (!fecha) return '-';
    const [y, m, d] = String(fecha).slice(0, 10).split('-');
    if (!y || !m || !d) return '-';
    return `${d}/${m}/${y}`;
};

const modalidadInscripcion = (inscripcion) => {
    if (inscripcion?.online === true) return 'Online';
    if (inscripcion?.online === false) return 'Presencial';
    return '-';
};

const formatearMonto = (monto) => {
    const valor = Number(monto);
    if (Number.isNaN(valor)) return '0,00';
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(valor);
};

const badgePagoClass = (pago) => {
    if (pago === 'Saldado') return 'bg-green-100 text-green-800';
    if (pago === 'Parcial') return 'bg-yellow-100 text-yellow-800';
    if (pago === 'Pendiente') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-700';
};

const badgeEstadoClass = (estado) => {
    if (estado === 'Confirmada') return 'bg-green-100 text-green-800';
    if (estado === 'Registrada') return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-700';
};

const envioEstado = (inscripcion, campo) => {
    if (!inscripcion) return '-';
    if (campo === 'envioGrabacion') {
        return inscripcion.envioGrabacion || '-';
    }
    return inscripcion[campo] || '-';
};

const badgeEnvioClass = (estado) => {
    if (estado === 'Enviada') return 'bg-green-100 text-green-800';
    if (estado === 'Pendiente') return 'bg-yellow-100 text-yellow-800';
    if (estado === 'No aplica') return 'bg-gray-100 text-gray-700';
    return 'bg-slate-100 text-slate-700';
};

const urlComprobante = (inscripcion) => {
    const raw = inscripcion?.comprobantes?.[0]?.ruta || inscripcion?.comprobante_url || inscripcion?.comprobante;
    if (!raw) return null;
    // Si ya es URL absoluta, usarla; de lo contrario, asumir ruta en storage
    if (/^https?:\/\//i.test(raw)) return raw;
    return `/storage/${raw}`;
};

const comprobantesExtras = (inscripcion) => {
    const count = inscripcion?.comprobantes?.length || 0;
    return count > 1 ? count - 1 : 0;
};

const comprobanteModal = ref(false);
const comprobantesParaVer = ref([]);
const confirmSaldadoVisible = ref(false);
const inscripcionParaSaldar = ref(null);
const confirmDeleteVisible = ref(false);
const inscripcionParaBorrar = ref(null);
const isDeleting = ref(false);
const confirmEnviosVisible = ref(false);
const totalConfirmacionesPendientes = ref(0);
const isSendingConfirmaciones = ref(false);
const confirmEnviosGrabacionesVisible = ref(false);
const totalGrabacionesPendientes = ref(0);
const isSendingGrabaciones = ref(false);

const normalizarComprobante = (ruta, descripcion) => {
    if (!ruta) return null;
    const url = /^https?:\/\//i.test(ruta) ? ruta : `/storage/${ruta}`;
    return {
        url,
        descripcion: descripcion || '',
        isPdf: url.toLowerCase().includes('.pdf'),
    };
};

const abrirComprobante = (inscripcion) => {
    if (!inscripcion) return;
    const items = [];
    if (Array.isArray(inscripcion.comprobantes) && inscripcion.comprobantes.length) {
        inscripcion.comprobantes.forEach((comprobante) => {
            const item = normalizarComprobante(comprobante.ruta, comprobante.descripcion);
            if (item) items.push(item);
        });
    } else {
        const raw = inscripcion?.comprobante_url || inscripcion?.comprobante;
        const item = normalizarComprobante(raw, '');
        if (item) items.push(item);
    }
    if (!items.length) return;
    comprobantesParaVer.value = items;
    comprobanteModal.value = true;
};

const abrirDialogoEnvioConfirmaciones = async () => {
    if (!canEdit.value || isSendingConfirmaciones.value) return;
    try {
        const { data } = await axios.get(route('estadoinscripciones.confirmaciones.count'));
        totalConfirmacionesPendientes.value = Number(data?.total || 0);
        if (totalConfirmacionesPendientes.value <= 0) {
            toast.add({
                severity: 'info',
                summary: 'Envío de confirmación',
                detail: 'No hay inscripciones pendientes de confirmación para enviar.',
                life: 4500,
            });
            return;
        }
        confirmEnviosVisible.value = true;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Envío de confirmación',
            detail: 'No se pudo calcular el total de confirmaciones.',
            life: 5000,
        });
    }
};

const enviarConfirmaciones = async () => {
    if (!canEdit.value || isSendingConfirmaciones.value) return;
    isSendingConfirmaciones.value = true;
    try {
        const { data } = await axios.post(route('estadoinscripciones.confirmaciones.enviar'));
        confirmEnviosVisible.value = false;
        const enviadas = Number(data?.enviadas || 0);
        const errores = Number(data?.errores || 0);
        const sinDestino = Number(data?.sin_destino || 0);
        toast.add({
            severity: errores > 0 ? 'warn' : 'success',
            summary: 'Envío de confirmación',
            detail: `Envío finalizado. Enviadas: ${enviadas}. Errores: ${errores}. Sin destino: ${sinDestino}.`,
            life: 6500,
        });
        router.reload({ only: ['inscripciones'] });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Envío de confirmación',
            detail: 'Ocurrió un error al enviar las confirmaciones.',
            life: 5000,
        });
    } finally {
        isSendingConfirmaciones.value = false;
    }
};

const abrirDialogoEnvioGrabaciones = async () => {
    if (!canEdit.value || isSendingGrabaciones.value) return;
    try {
        const { data } = await axios.get(route('estadoinscripciones.grabaciones.count'));
        totalGrabacionesPendientes.value = Number(data?.total || 0);
        if (totalGrabacionesPendientes.value <= 0) {
            toast.add({
                severity: 'info',
                summary: 'Envío de grabaciones',
                detail: 'No hay inscripciones pendientes de envío de grabaciones.',
                life: 4500,
            });
            return;
        }
        confirmEnviosGrabacionesVisible.value = true;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Envío de grabaciones',
            detail: 'No se pudo calcular el total de envíos de grabaciones.',
            life: 5000,
        });
    }
};

const enviarGrabaciones = async () => {
    if (!canEdit.value || isSendingGrabaciones.value) return;
    isSendingGrabaciones.value = true;
    try {
        const { data } = await axios.post(route('estadoinscripciones.grabaciones.enviar'));
        confirmEnviosGrabacionesVisible.value = false;
        const enviadas = Number(data?.enviadas || 0);
        const errores = Number(data?.errores || 0);
        const sinDestino = Number(data?.sin_destino || 0);
        toast.add({
            severity: errores > 0 ? 'warn' : 'success',
            summary: 'Envío de grabaciones',
            detail: `Envío finalizado. Enviadas: ${enviadas}. Errores: ${errores}. Sin destino: ${sinDestino}.`,
            life: 6500,
        });
        router.reload({ only: ['inscripciones'] });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Envío de grabaciones',
            detail: 'Ocurrió un error al enviar las grabaciones.',
            life: 5000,
        });
    } finally {
        isSendingGrabaciones.value = false;
    }
};

// Campo derivado para ordenar la columna "Nombre" (el valor mostrado es computado
// desde user/guest_user). Se estampa sobre la fila original — sin clonar — para no
// romper la edición inline, que muta el objeto de la inscripción directamente.
watch(
    () => props.inscripciones,
    (lista) => {
        (lista || []).forEach((i) => {
            i._nombre = nombreUsuario(i);
        });
    },
    { immediate: true }
);

// El período pre-filtra el dataset; los filtros de columna (Nombre, Actividad,
// Membresía, Pago) y la búsqueda global los maneja PrimeVue sobre `filtradas`.
const filtradas = computed(() => {
    const data = props.inscripciones || [];
    if (filtroPeriodo.value === 'all') return data;

    // Período: filtra por la fecha de la ACTIVIDAD (no por la fecha de inscripción),
    // así las inscripciones importadas (con created_at viejo) se ven si su actividad es reciente.
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    return data.filter((inscripcion) => {
        const fechaActividad = inscripcion.actividad?.fecha_inicio;
        if (!fechaActividad) return false;
        return new Date(fechaActividad) >= start;
    });
});

// Opciones de los dropdowns de filtro, derivadas del dataset visible (post-período).
const actividadNombres = computed(() => {
    const set = new Set();
    filtradas.value.forEach((i) => {
        if (i.actividad?.nombre) set.add(i.actividad.nombre);
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'es'));
});

const pagoOptions = computed(() => {
    const set = new Set();
    filtradas.value.forEach((i) => {
        if (i.pago) set.add(i.pago);
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'es'));
});

const membresiaOptions = computed(() => {
    const set = new Set();
    filtradas.value.forEach((i) => {
        if (i.membresia) set.add(i.membresia);
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'es'));
});

// Opciones para los dropdowns de Estado y los tres campos de Envío,
// derivadas del dataset visible (solo los valores realmente presentes).
const opcionesFiltro = computed(() => {
    const campos = ['estado', 'envioConfirmacion'];
    const sets = Object.fromEntries(campos.map((c) => [c, new Set()]));
    filtradas.value.forEach((i) => {
        campos.forEach((c) => {
            if (i[c]) sets[c].add(i[c]);
        });
    });
    return Object.fromEntries(
        campos.map((c) => [c, Array.from(sets[c]).sort((a, b) => a.localeCompare(b, 'es'))])
    );
});
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>
