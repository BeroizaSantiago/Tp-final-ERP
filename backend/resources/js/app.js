import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';
import qz from 'qz-tray';
/*
  Add custom scripts here
*/
import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);

/* Impresión directa en comandera mediante el puente local QZ Tray. */
let thermalPrinterConnection = null;
let detectedThermalPrinter = null;

function withThermalTimeout(operation, milliseconds, message) {
  let timer;
  return Promise.race([
    operation,
    new Promise((_, reject) => {
      timer = window.setTimeout(() => reject(new Error(message)), milliseconds);
    }),
  ]).finally(() => window.clearTimeout(timer));
}

async function connectThermalPrinter() {
  if (!qz.websocket.isActive()) {
    thermalPrinterConnection ??= qz.websocket.connect({ retries: 0 })
      .finally(() => { thermalPrinterConnection = null; });
    await withThermalTimeout(
      thermalPrinterConnection,
      5000,
      'QZ Tray no respondió dentro de los 5 segundos.'
    );
  }

  if (!detectedThermalPrinter) {
    const configuredName = String(window.ERP_PRINTING?.printerName ?? '').trim();
    if (configuredName) {
      detectedThermalPrinter = await qz.printers.find(configuredName);
    } else {
      const printers = await qz.printers.find();
      detectedThermalPrinter = printers.find(name =>
        /(thermal|receipt|ticket|comandera|58mm|80mm|xprinter|epson\s*tm|bematech|elgin|pos[-_\s])/i.test(name)
      );

      if (!detectedThermalPrinter) {
        const systemDefault = await qz.printers.getDefault();
        const virtualPrinter = /(pdf|xps|onenote|fax)/i.test(systemDefault ?? '');
        if (!virtualPrinter) detectedThermalPrinter = systemDefault;
      }
    }
  }

  if (!detectedThermalPrinter) {
    throw new Error('No se encontró una comandera. Configurá THERMAL_PRINTER_NAME con el nombre de la impresora instalada.');
  }
  return detectedThermalPrinter;
}

async function printableHtml(url, width) {
  const response = await nativeFetch(url, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
  if (!response.ok) throw new Error(`No se pudo preparar el ticket (HTTP ${response.status}).`);

  const documentHtml = new DOMParser().parseFromString(await response.text(), 'text/html');
  documentHtml.querySelectorAll('script, .no-print, .print-btn').forEach(element => element.remove());
  const base = documentHtml.createElement('base');
  base.href = url;
  documentHtml.head.prepend(base);
  const html = `<!DOCTYPE html>${documentHtml.documentElement.outerHTML}`;
  const frame = document.createElement('iframe');
  frame.setAttribute('aria-hidden', 'true');
  frame.style.cssText = `position:fixed;left:-9999px;top:0;width:${width}mm;height:1px;border:0;visibility:hidden`;
  document.body.appendChild(frame);
  frame.srcdoc = html;

  await new Promise(resolve => {
    frame.onload = () => window.setTimeout(resolve, 100);
    window.setTimeout(resolve, 1000);
  });

  const heightPx = Math.max(
    frame.contentDocument?.documentElement?.scrollHeight ?? 0,
    frame.contentDocument?.body?.scrollHeight ?? 0
  );
  frame.remove();

  return {
    html,
    height: Math.min(1000, Math.max(30, Math.ceil(heightPx * 25.4 / 96) + 4)),
  };
}

async function printThermalUrl(url, jobName = 'Ticket ERP') {
  try {
    const printer = await connectThermalPrinter();
    const width = [58, 80].includes(Number(window.ERP_PRINTING?.paperWidthMm))
      ? Number(window.ERP_PRINTING.paperWidthMm)
      : 80;
    const printable = await printableHtml(url, width);
    const config = qz.configs.create(printer, {
      units: 'mm',
      margins: 0,
      colorType: 'grayscale',
      scaleContent: true,
      jobName,
      size: { width, height: printable.height },
    });
    const data = [{ type: 'pixel', format: 'html', flavor: 'plain', data: printable.html }];
    await withThermalTimeout(
      qz.print(config, data),
      10000,
      'La comandera no confirmó el trabajo dentro de los 10 segundos.'
    );
    return { printer };
  } catch (error) {
    const detail = String(error?.message ?? error ?? 'QZ Tray no está disponible.');
    throw new Error(`No se pudo imprimir directamente en la comandera. Verificá que QZ Tray esté instalado y abierto. ${detail}`);
  }
}

window.erpThermalPrinter = {
  printUrl: printThermalUrl,
  detect: connectThermalPrinter,
};

/* Selector moderno compartido para fechas y fechas con hora. */
function initializeDatePickers(root = document) {
  const fields = [];
  if (root instanceof HTMLInputElement && root.matches('input[type="date"], input[type="datetime-local"]')) fields.push(root);
  if (root.querySelectorAll) fields.push(...root.querySelectorAll('input[type="date"], input[type="datetime-local"]'));

  fields.forEach(input => {
    if (input.dataset.erpDatepicker === 'true' || input.dataset.nativeDatepicker === 'true') return;

    const withTime = input.type === 'datetime-local';
    input.dataset.erpDatepicker = 'true';
    flatpickr(input, {
      locale: Spanish,
      altInput: true,
      altFormat: withTime ? 'd/m/Y H:i' : 'd/m/Y',
      dateFormat: withTime ? 'Y-m-d\\TH:i' : 'Y-m-d',
      enableTime: withTime,
      time_24hr: true,
      minuteIncrement: 1,
      allowInput: true,
      disableMobile: true,
      static: true,
      minDate: input.min || null,
      maxDate: input.max || null,
      clickOpens: !input.readOnly && !input.disabled,
      altInputClass: `${input.className} erp-date-input`,
      onReady: (_, __, instance) => instance.calendarContainer.classList.add('erp-date-calendar'),
    });
  });
}

document.addEventListener('DOMContentLoaded', () => initializeDatePickers());
new MutationObserver(mutations => mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
  if (node instanceof HTMLElement) initializeDatePickers(node);
}))).observe(document.documentElement, { childList: true, subtree: true });

/* Tema visual: claro, oscuro o el configurado en el sistema operativo. */
const colorThemeKey = 'erp.color-theme';
const systemColorTheme = window.matchMedia('(prefers-color-scheme: dark)');

function resolvedColorTheme(preference) {
  return preference === 'dark' || (preference === 'system' && systemColorTheme.matches) ? 'dark' : 'light';
}

function applyColorTheme(preference = localStorage.getItem(colorThemeKey) || 'system') {
  const allowed = ['light', 'dark', 'system'];
  const selected = allowed.includes(preference) ? preference : 'system';
  const resolved = resolvedColorTheme(selected);
  const root = document.documentElement;

  root.dataset.bsTheme = resolved;
  root.dataset.colorTheme = selected;
  root.style.colorScheme = resolved;

  const toggle = document.querySelector('[data-theme-toggle]');
  if (toggle) {
    const action = resolved === 'dark' ? 'Desactivar' : 'Activar';
    toggle.setAttribute('aria-label', `${action} modo oscuro`);
    toggle.setAttribute('title', `${action} modo oscuro`);
    toggle.setAttribute('aria-pressed', String(resolved === 'dark'));
  }

  window.dispatchEvent(new CustomEvent('erp:theme-changed', { detail: { preference: selected, theme: resolved } }));
}

window.erpSetColorTheme = preference => {
  localStorage.setItem(colorThemeKey, preference);
  applyColorTheme(preference);
};

systemColorTheme.addEventListener?.('change', () => {
  if ((localStorage.getItem(colorThemeKey) || 'system') === 'system') applyColorTheme('system');
});

/*
 * Indicadores de carga compartidos.
 * Las peticiones breves no muestran la capa para evitar parpadeos.
 */
const loaderDelay = 320;
let loaderOperations = 0;
let loaderTimer = null;

function globalLoaderElement() {
  return document.getElementById('erpGlobalLoader');
}

function showGlobalLoader(message = 'Cargando datos...') {
  loaderOperations += 1;
  const loader = globalLoaderElement();
  const text = loader?.querySelector('[data-erp-loader-text]');
  if (text) text.textContent = message;

  if (!loader || loader.classList.contains('is-visible') || loaderTimer) return;
  loaderTimer = window.setTimeout(() => {
    loaderTimer = null;
    if (loaderOperations < 1) return;
    loader.classList.add('is-visible');
    loader.setAttribute('aria-hidden', 'false');
  }, loaderDelay);
}

function hideGlobalLoader() {
  loaderOperations = Math.max(0, loaderOperations - 1);
  if (loaderOperations > 0) return;
  if (loaderTimer) window.clearTimeout(loaderTimer);
  loaderTimer = null;
  const loader = globalLoaderElement();
  loader?.classList.remove('is-visible');
  loader?.setAttribute('aria-hidden', 'true');
}

function setButtonLoading(button, loading = true, label = 'Procesando...') {
  if (!(button instanceof HTMLButtonElement || button instanceof HTMLInputElement)) return;

  if (loading) {
    if (button.dataset.erpLoading === 'true') return;
    button.dataset.erpLoading = 'true';
    button.dataset.erpOriginalDisabled = String(button.disabled);
    button.dataset.erpOriginalHtml = button instanceof HTMLButtonElement ? button.innerHTML : button.value;
    button.disabled = true;
    if (button instanceof HTMLButtonElement) {
      button.innerHTML = `<span class="spinner-border spinner-border-sm erp-button-spinner" aria-hidden="true"></span><span>${label}</span>`;
    } else {
      button.value = label;
    }
    return;
  }

  if (button.dataset.erpLoading !== 'true') return;
  if (button instanceof HTMLButtonElement) button.innerHTML = button.dataset.erpOriginalHtml ?? '';
  else button.value = button.dataset.erpOriginalHtml ?? '';
  button.disabled = button.dataset.erpOriginalDisabled === 'true';
  delete button.dataset.erpLoading;
  delete button.dataset.erpOriginalDisabled;
  delete button.dataset.erpOriginalHtml;
}

window.erpLoading = {
  show: showGlobalLoader,
  hide: hideGlobalLoader,
  button: setButtonLoading,
};

let recentActionButton = null;
let recentActionAt = 0;

document.addEventListener('click', event => {
  const button = event.target.closest('button, input[type="submit"]');
  if (!button || button.dataset.erpLoadingIgnore === 'true' || button.hasAttribute('data-bs-toggle')) return;
  recentActionButton = button;
  recentActionAt = Date.now();
}, true);

const nativeFetch = window.fetch.bind(window);
window.fetch = async (...args) => {
  const requestOptions = args[1] ?? {};
  const { erpSilent = false, ...nativeOptions } = requestOptions;
  const fetchArgs = args.length > 1 ? [args[0], nativeOptions] : args;

  if (erpSilent) {
    return nativeFetch(...fetchArgs);
  }

  const actionButton = Date.now() - recentActionAt < 1000 ? recentActionButton : null;
  const buttonTimer = actionButton
    ? window.setTimeout(() => setButtonLoading(actionButton, true), loaderDelay)
    : null;
  showGlobalLoader();
  try {
    return await nativeFetch(...fetchArgs);
  } finally {
    if (buttonTimer) window.clearTimeout(buttonTimer);
    if (actionButton) setButtonLoading(actionButton, false);
    hideGlobalLoader();
  }
};

document.addEventListener('submit', event => {
  if (event.defaultPrevented) return;
  const submitter = event.submitter ?? event.target.querySelector('button[type="submit"], input[type="submit"]');
  if (submitter?.dataset.erpLoadingIgnore !== 'true') setButtonLoading(submitter, true);
});

/*
 * Los lectores de codigo de barras suelen finalizar la lectura enviando Enter.
 * En formularios de mantenimiento ese Enter no debe disparar el submit
 * implicito: solo completa el campo. Nueva Venta usa su propio buscador y
 * conserva el flujo especial de escaneo y autoagregado.
 */
document.addEventListener('keydown', event => {
  if (event.key !== 'Enter' || event.ctrlKey || event.altKey || event.metaKey) return;
  if (!(event.target instanceof HTMLInputElement)) return;

  const fieldIdentifier = `${event.target.name} ${event.target.id}`.toLowerCase();
  const isBarcodeField = fieldIdentifier.includes('bar_code') || fieldIdentifier.includes('barcode');

  if (!isBarcodeField) return;

  event.preventDefault();
  event.stopPropagation();
  event.target.dispatchEvent(new Event('change', { bubbles: true }));
}, true);

const notificationKey = 'erp.pending-notification';

function notificationType(message) {
  const text = String(message ?? '').toLowerCase();

  if (/correctamente|exitos|guardad|cread|actualizad|registrad|eliminad|completad|autorizad/.test(text)) return 'success';
  if (/error|no se pudo|no pudo|fall[oó]|inv[aá]lid|servidor|conexi[oó]n/.test(text)) return 'error';
  if (/debe|deb[eé]s|seleccion|ingres|importe|supera|m[aá]ximo|seguridad|primero/.test(text)) return 'warning';

  return 'info';
}

function toast(message, options = {}) {
  const icon = options.icon ?? notificationType(message);
  const title = options.title ?? ({ success: 'Operación exitosa', error: 'Ocurrió un error', warning: 'Atención', info: 'Información' }[icon]);
  const payload = { message: String(message ?? ''), icon, title, createdAt: Date.now() };

  if (options.persist !== false) sessionStorage.setItem(notificationKey, JSON.stringify(payload));

  const result = Swal.fire({
    toast: true,
    position: 'bottom-end',
    icon,
    title,
    text: payload.message,
    showConfirmButton: false,
    timer: icon === 'error' ? 5000 : 3200,
    timerProgressBar: true,
    customClass: { popup: `erp-toast erp-toast-${icon}` },
    didClose: () => {
      const pending = sessionStorage.getItem(notificationKey);
      if (pending && JSON.parse(pending).createdAt === payload.createdAt) sessionStorage.removeItem(notificationKey);
    },
  });

  return result;
}

window.Swal = Swal;
window.erpAlert = toast;
window.alert = message => { toast(message); };
window.erpConfirm = async (message, options = {}) => {
  if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
  const result = await Swal.fire({
    target: document.body,
    backdrop: 'rgba(24, 21, 39, 0.58)',
    icon: options.icon ?? 'warning',
    title: options.title ?? 'Confirmar acción',
    text: String(message ?? ''),
    confirmButtonText: options.confirmButtonText ?? 'Confirmar',
    cancelButtonText: options.cancelButtonText ?? 'Cancelar',
    showCancelButton: true,
    reverseButtons: true,
    focusCancel: true,
    returnFocus: false,
    allowOutsideClick: false,
    stopKeydownPropagation: true,
    heightAuto: false,
    scrollbarPadding: false,
    customClass: { container: 'erp-confirm-container', popup: 'erp-confirm' },
    willOpen: () => document.body.classList.add('erp-confirm-open'),
    didOpen: () => {
      if (document.activeElement instanceof HTMLElement && !document.activeElement.closest('.erp-confirm')) {
        document.activeElement.blur();
      }
    },
    didClose: () => {
      document.body.classList.remove('erp-confirm-open');
      document.documentElement.classList.remove('swal2-shown', 'swal2-height-auto');
      document.body.classList.remove('swal2-shown', 'swal2-height-auto');
    },
  });

  return result.isConfirmed;
};

const pendingNotification = sessionStorage.getItem(notificationKey);
if (pendingNotification) {
  sessionStorage.removeItem(notificationKey);
  try {
    const pending = JSON.parse(pendingNotification);
    if (Date.now() - pending.createdAt < 10000) toast(pending.message, { ...pending, persist: false });
  } catch (_) {
    sessionStorage.removeItem(notificationKey);
  }
}

/*
 * Acciones visuales compartidas del ERP.
 * También normaliza botones creados por JavaScript en grillas dinámicas.
 */
const actionDefinitions = {
  edit: { pattern: /^(editar|edit)\b/, classes: ['btn-sm', 'btn-warning'] },
  view: { pattern: /^(ver|ver detalle|consultar)\b/, classes: ['btn-sm', 'btn-primary'] },
  save: { pattern: /^(guardar|actualizar)\b/, classes: ['btn-success'] },
  delete: { pattern: /^(eliminar|borrar)\b/, classes: ['btn-sm', 'btn-danger'] },
  disable: { pattern: /^(desactivar|inhabilitar|anular)\b/, classes: ['btn-sm', 'btn-danger'] },
  enable: { pattern: /^(activar|habilitar)\b/, classes: ['btn-sm', 'btn-success'] },
  back: { pattern: /^volver\b/, classes: ['btn-secondary'] },
  new: { pattern: /^(nuevo|nueva)\b/, classes: ['btn-primary'] },
};

const buttonVariants = [
  'btn-primary', 'btn-secondary', 'btn-success', 'btn-danger', 'btn-warning',
  'btn-info', 'btn-dark', 'btn-light', 'btn-outline-primary',
  'btn-outline-secondary', 'btn-outline-success', 'btn-outline-danger',
  'btn-outline-warning', 'btn-outline-info', 'btn-outline-dark',
];

function normalizeActionButton(button) {
  // Acciones futuras conservan deliberadamente su variante outline propia.
  if (button.classList.contains('future-action') || button.dataset.erpActionIgnore === 'true') return;

  const text = String(button.textContent ?? '').trim().toLowerCase().replace(/\s+/g, ' ');
  const explicit = button.dataset.erpNormalized ? null : button.dataset.erpAction;
  const entry = explicit
    ? [explicit, actionDefinitions[explicit]]
    : Object.entries(actionDefinitions).find(([, definition]) => definition.pattern.test(text));

  if (!entry?.[1]) return;

  const [action, definition] = entry;
  const currentVariants = buttonVariants.filter(className => button.classList.contains(className));
  const desiredVariants = definition.classes.filter(className => className.startsWith('btn-') && className !== 'btn-sm');
  const desiredSmall = definition.classes.includes('btn-sm');
  if (
    button.dataset.erpAction === action
    && currentVariants.length === desiredVariants.length
    && desiredVariants.every(className => currentVariants.includes(className))
    && button.classList.contains('btn-sm') === desiredSmall
  ) return;

  button.classList.remove(...buttonVariants);
  if (action === 'back' || action === 'new' || action === 'save') button.classList.remove('btn-sm');
  button.classList.add('btn', ...definition.classes, 'erp-action', `erp-action-${action}`);
  button.dataset.erpAction = action;
  button.dataset.erpNormalized = '1';
}

function normalizeActionButtons(root = document) {
  if (root instanceof Element && root.matches('.btn')) normalizeActionButton(root);
  root.querySelectorAll?.('.btn').forEach(normalizeActionButton);
}

window.erpNormalizeActionButtons = normalizeActionButtons;

document.addEventListener('DOMContentLoaded', () => {
  applyColorTheme();
  document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    window.erpSetColorTheme(document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark');
  });
  normalizeActionButtons();
  new MutationObserver(mutations => mutations.forEach(mutation => {
    mutation.addedNodes.forEach(node => {
      if (node instanceof Element) normalizeActionButtons(node);
    });
    if (mutation.type === 'attributes' && mutation.target instanceof Element) normalizeActionButtons(mutation.target);
    if (mutation.type === 'characterData' && mutation.target.parentElement) normalizeActionButtons(mutation.target.parentElement.closest('.btn') ?? mutation.target.parentElement);
  })).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'], characterData: true });
});
