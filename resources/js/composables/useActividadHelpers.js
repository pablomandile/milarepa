import { unref } from 'vue';

export function formatPrice(price) {
    if (!price || isNaN(price)) return '0,00';
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(price);
}

export function formatFechaLarga(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '-';
    const meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
    ];
    return `${d.getDate()} de ${meses[d.getMonth()]} ${d.getFullYear()}`;
}

export function formatHora(value) {
    if (!value) return '';
    try {
        return new Date(value).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) + ' hs.';
    } catch (e) {
        return '';
    }
}

function obtenerFechaFinActividad(actividad) {
    const valor = actividad?.fecha_fin ?? actividad?.fechaFin ?? actividad?.fecha_hasta ?? null;
    if (!valor) return null;
    if (typeof valor === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(valor)) {
        const fechaFinDia = new Date(`${valor}T23:59:59`);
        return Number.isNaN(fechaFinDia.getTime()) ? null : fechaFinDia;
    }
    const fecha = new Date(valor);
    return Number.isNaN(fecha.getTime()) ? null : fecha;
}

export function actividadFinalizada(actividad) {
    const fechaFin = obtenerFechaFinActividad(actividad);
    if (!fechaFin) return false;
    return new Date() > fechaFin;
}

export function direccionActividad(actividad) {
    return actividad?.lugar?.direccion || actividad?.entidad?.direccion || '';
}

export function serviciosDisponibles(actividad) {
    return Boolean(
        actividad?.hospedajes?.length ||
        actividad?.comidas?.length ||
        actividad?.transportes?.length ||
        actividad?.grabacion ||
        actividad?.grabacion_id
    );
}

function getFechaLimiteDescuento(actividad) {
    if (!actividad?.pagoAmticipado) return null;
    const fecha = new Date(actividad.pagoAmticipado);
    return Number.isNaN(fecha.getTime()) ? null : fecha;
}

function tieneDescuentoAnticipado(actividad) {
    return !!actividad?.esquema_descuento && !!getFechaLimiteDescuento(actividad);
}

export function descuentoVigente(actividad) {
    if (!tieneDescuentoAnticipado(actividad)) return false;
    const limite = getFechaLimiteDescuento(actividad);
    return limite ? new Date() <= limite : false;
}

export function formatoFechaLimite(actividad) {
    const limite = getFechaLimiteDescuento(actividad);
    if (!limite) return '-';
    return limite.toLocaleDateString('es-AR');
}

// Fallback cuando una fila no trae su moneda cargada (legacy sin moneda_id).
export const SIMBOLO_PRINCIPAL = '$';

function normalizarNombre(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();
}

/**
 * Líneas de precio del esquema para una membresía (o, si no hay, para "sin
 * membresía"): UNA POR MONEDA y con la principal primero.
 *
 * Un esquema puede tener la misma membresía cargada en varias monedas. Antes se
 * devolvía la primera línea que matcheaba sin mirar la moneda, así que una
 * actividad con precio en dólares podía mostrarse como si fueran pesos.
 */
function preciosDesdeEsquema(esquema, membresiaId = null) {
    const membresias = esquema?.membresias || [];
    if (!Array.isArray(membresias) || membresias.length === 0) return [];

    let lineas = membresiaId
        ? membresias.filter((epm) => epm.membresia_id === membresiaId)
        : [];

    if (lineas.length === 0) {
        lineas = membresias.filter((epm) => {
            const nombre = normalizarNombre(epm?.membresia?.nombre);
            return nombre === 'sin membresia' || nombre.includes('sin membres');
        });
    }

    const porMoneda = new Map();
    lineas.forEach((epm) => {
        if (epm?.precio === undefined || epm?.precio === null) return;
        // Sin moneda cargada (legacy) se asume la principal.
        const clave = epm.moneda_id ?? 0;
        if (porMoneda.has(clave)) return;
        porMoneda.set(clave, {
            precio: Number(epm.precio),
            simbolo: epm?.moneda?.simbolo || SIMBOLO_PRINCIPAL,
            moneda_id: epm.moneda_id ?? null,
            esPrincipal: epm?.moneda ? Boolean(epm.moneda.es_principal) : true,
        });
    });

    return [...porMoneda.values()].sort((a, b) => Number(b.esPrincipal) - Number(a.esPrincipal));
}

/** "$ 50.000,00 · USD 120,00" — cadena vacía si no hay ninguna línea. */
export function formatPrecios(lineas) {
    if (!Array.isArray(lineas) || lineas.length === 0) return '';
    return lineas.map((l) => `${l.simbolo} ${formatPrice(l.precio)}`).join(' · ');
}

export function preciosSinMembresiaNormal(actividad) {
    return preciosDesdeEsquema(actividad?.esquema_precio, null);
}

export function preciosSinMembresiaVigente(actividad) {
    if (descuentoVigente(actividad)) {
        const conDescuento = preciosDesdeEsquema(actividad?.esquema_descuento, null);
        if (conDescuento.length) return conDescuento;
    }
    return preciosSinMembresiaNormal(actividad);
}

export function preciosMembresiaUsuario(actividad, user) {
    const userMemId = user?.membresia?.id || user?.membresia_id;
    if (!userMemId) return [];

    if (descuentoVigente(actividad)) {
        const conDescuento = preciosDesdeEsquema(actividad?.esquema_descuento, userMemId);
        if (conDescuento.length) return conDescuento;
    }

    return preciosDesdeEsquema(actividad?.esquema_precio, userMemId);
}

// Variantes numéricas (moneda principal): las usa lo que necesita comparar
// montos, como el "Incluído" cuando el precio con membresía es 0.
export function precioSinMembresiaNormal(actividad) {
    return preciosSinMembresiaNormal(actividad)[0]?.precio ?? 0;
}

export function precioSinMembresiaVigente(actividad) {
    return preciosSinMembresiaVigente(actividad)[0]?.precio ?? 0;
}

export function precioMembresiaUsuario(actividad, user) {
    return preciosMembresiaUsuario(actividad, user)[0]?.precio ?? 0;
}

export function esInscrito(actividadId, inscripcionesIds) {
    const ids = unref(inscripcionesIds) || [];
    return ids.includes(actividadId);
}

export function actividadSinInscripcionDisponible(actividad, inscripcionesIds) {
    return esInscrito(actividad.id, inscripcionesIds) || actividadFinalizada(actividad);
}

export function textoBotonInscripcion(actividad, inscripcionesIds) {
    if (esInscrito(actividad.id, inscripcionesIds)) return 'Inscripto';
    if (actividadFinalizada(actividad)) return 'Actividad finalizada';
    return 'Inscribirme';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatInlineMarkdown(value) {
    return value
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>');
}

export function renderMarkdown(value) {
    const safeText = escapeHtml(value).replace(/\r\n/g, '\n');
    const lines = safeText.split('\n');
    const html = [];
    let inList = false;

    for (const rawLine of lines) {
        const line = rawLine.trim();

        if (!line) {
            if (inList) {
                html.push('</ul>');
                inList = false;
            }
            continue;
        }

        if (line.startsWith('- ')) {
            if (!inList) {
                html.push('<ul class="list-disc pl-5 my-2 space-y-1">');
                inList = true;
            }
            html.push(`<li>${formatInlineMarkdown(line.slice(2).trim())}</li>`);
            continue;
        }

        if (inList) {
            html.push('</ul>');
            inList = false;
        }

        if (line.startsWith('### ')) {
            html.push(`<h5 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-3">${formatInlineMarkdown(line.slice(4).trim())}</h5>`);
        } else if (line.startsWith('## ')) {
            html.push(`<h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-3">${formatInlineMarkdown(line.slice(3).trim())}</h4>`);
        } else if (line.startsWith('# ')) {
            html.push(`<h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-3">${formatInlineMarkdown(line.slice(2).trim())}</h3>`);
        } else {
            html.push(`<p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mt-2">${formatInlineMarkdown(line)}</p>`);
        }
    }

    if (inList) {
        html.push('</ul>');
    }

    return html.join('');
}

export function nombresMaestrosYCoordinadores(actividad) {
    const maestros = (actividad?.maestros || []).map((m) => m?.nombre || m?.name || '').filter(Boolean);
    const coords = (actividad?.coordinadores || []).map((c) => c?.nombre || c?.name || '').filter(Boolean);
    const todos = [...maestros, ...coords];
    if (todos.length === 0) return '';
    if (todos.length === 1) return todos[0];
    if (todos.length === 2) return `${todos[0]} y ${todos[1]}`;
    return `${todos.slice(0, -1).join(', ')} y ${todos[todos.length - 1]}`;
}
