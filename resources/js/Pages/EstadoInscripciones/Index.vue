<template>
    <AppLayout title="Estado de Inscripciones">
        <Toast position="top-right" />
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    <i class="fas fa-clipboard-check mr-2 text-indigo-600"></i>
                    Estado de Inscripciones
                </h2>
                <Link
                    v-if="canEdit"
                    :href="route('estadoinscripciones.importar')"
                    class="inline-flex items-center gap-2 rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                >
                    <i class="pi pi-upload"></i>
                    Importar inscripciones
                </Link>
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
                                <option value="last1">Actividades del último mes</option>
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
                                            <span>{{ inscripcion.estado || '-' }}</span>
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
                                                        v-if="isEditing(inscripcion)"
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
                                            v-if="isEditing(data)"
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
                                        <span class="text-sm">{{ data.estado || '-' }}</span>
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
                                                <p class="text-sm text-gray-800 dark:text-gray-100">{{ data.estado || '-' }}</p>
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

const props = defineProps({
    inscripciones: Array
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
    return {
        membresia: w >= 1080,
        estado: w >= 1200,
        envioConfirmacion: w >= 1360,
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

const isEditing = (inscripcion) => editRowId.value === inscripcion.id;

const isCardExpanded = (id) => expandedCardIds.value.includes(id);

const toggleCardExpanded = (id) => {
    const idx = expandedCardIds.value.indexOf(id);
    if (idx === -1) {
        expandedCardIds.value.push(id);
    } else {
        expandedCardIds.value.splice(idx, 1);
    }
};

const iniciarEdicion = (inscripcion) => {
    editRowId.value = inscripcion.id;
    editForm.value = {
        montoapagar: Number(inscripcion.montoapagar || 0),
        pago: inscripcion.pago || 'Pendiente',
    };
};

const cancelarEdicion = () => {
    editRowId.value = null;
};

const guardarEdicion = async (inscripcion) => {
    if (!canEdit.value || isSaving.value) return;
    isSaving.value = true;
    try {
        const { data } = await axios.put(route('estadoinscripciones.update', inscripcion.id), {
            montoapagar: editForm.value.montoapagar,
            pago: editForm.value.pago,
        });
        inscripcion.montoapagar = editForm.value.montoapagar;
        inscripcion.pago = editForm.value.pago;
        // El backend confirma la inscripción al quedar saldada; reflejamos el nuevo estado.
        if (data?.estado) inscripcion.estado = data.estado;
        editRowId.value = null;
    } catch (error) {
        alert('No se pudo guardar la edición.');
    } finally {
        isSaving.value = false;
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
