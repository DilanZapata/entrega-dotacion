// Utilidades compartidas por todo el sistema de dotación.

const MESES_ES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
  'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

function fechaLargaEs(date = new Date()) {
  return `${date.getDate()} de ${MESES_ES[date.getMonth()]} de ${date.getFullYear()}`;
}

async function apiGet(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  if (!res.ok) throw new Error(`Error ${res.status} al consultar ${url}`);
  return res.json();
}

async function apiSend(url, method, body) {
  const res = await fetch(url, {
    method,
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body || {}),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.message || `Error ${res.status}`);
  }
  return data;
}

// Escapa texto antes de insertarlo en HTML (nombres, descripciones cargadas desde archivos, etc.).
function esc(valor) {
  return String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// Fecha local de hoy en formato AAAA-MM-DD.
function hoyISO() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// Normaliza texto para comparar descripciones/tallas sin importar mayúsculas ni espacios.
function normTexto(valor) {
  return String(valor ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
}

// ---- ARL de contratistas ----
const ARL_DIAS_ENTRE_REVISIONES = 30; // la afiliación del contratista se paga mes a mes
const ARL_DIAS_AVISO_VENCIMIENTO = 7;

function diasEntre(desdeISO, hastaISO) {
  return Math.round((new Date(hastaISO + 'T00:00:00') - new Date(desdeISO + 'T00:00:00')) / 86400000);
}

// Devuelve el estado de la ARL de un contratista: { clave, etiqueta, clase, pendiente }.
function estadoArl(emp) {
  const hoy = hoyISO();
  const sinDatos = !emp.arl_nombre && !emp.arl_vigente_hasta && !emp.arl_ultima_revision;
  if (sinDatos) {
    return { clave: 'sin_datos', etiqueta: 'Sin registrar', clase: 'bg-slate-200 text-slate-600', pendiente: true };
  }
  if (emp.arl_vigente_hasta && emp.arl_vigente_hasta < hoy) {
    return { clave: 'vencida', etiqueta: 'Vencida', clase: 'bg-red-100 text-red-700', pendiente: true };
  }
  if (emp.arl_vigente_hasta && diasEntre(hoy, emp.arl_vigente_hasta) <= ARL_DIAS_AVISO_VENCIMIENTO) {
    return { clave: 'por_vencer', etiqueta: 'Por vencer', clase: 'bg-amber-100 text-amber-700', pendiente: true };
  }
  if (!emp.arl_ultima_revision || diasEntre(emp.arl_ultima_revision, hoy) >= ARL_DIAS_ENTRE_REVISIONES) {
    return { clave: 'por_revisar', etiqueta: 'Por revisar', clase: 'bg-amber-100 text-amber-700', pendiente: true };
  }
  return { clave: 'al_dia', etiqueta: 'Al día', clase: 'bg-green-100 text-green-700', pendiente: false };
}

function etiquetaGenero(genero, base) {
  const g = (genero || '').toUpperCase();
  if (g === 'MUJER') return `${base} Dama`;
  if (g === 'HOMBRE') return `${base} Caballero`;
  return base;
}
