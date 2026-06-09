/* ============================================================
   utils.js — Funciones compartidas
   Sistema de Asistencia QR
   ============================================================ */
(function (global) {
  'use strict';

  /* ---------- Toasts ---------- */
  function ensureToastStack() {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    return stack;
  }

  /**
   * Muestra un toast no intrusivo.
   * @param {string} message
   * @param {'success'|'error'|'info'} type
   * @param {object} [opts] { title, duration }
   */
  function toast(message, type = 'info', opts = {}) {
    const stack = ensureToastStack();
    const icons = {
      success: 'fa-circle-check',
      error: 'fa-circle-xmark',
      info: 'fa-circle-info',
    };
    const titles = { success: 'Listo', error: 'Error', info: 'Aviso' };

    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `
      <i class="toast-icon fa-solid ${icons[type] || icons.info}"></i>
      <div class="toast-body">
        <div class="toast-title">${opts.title || titles[type] || ''}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" aria-label="Cerrar">&times;</button>`;

    const remove = () => {
      el.classList.add('toast-out');
      el.addEventListener('animationend', () => el.remove(), { once: true });
    };
    el.querySelector('.toast-close').addEventListener('click', remove);
    stack.appendChild(el);
    if (opts.duration !== 0) setTimeout(remove, opts.duration || 4000);
    return el;
  }

  /* ---------- Loader global ---------- */
  function showLoader() {
    if (document.querySelector('.loader-overlay')) return;
    const o = document.createElement('div');
    o.className = 'loader-overlay';
    o.innerHTML = '<div class="spinner spinner-lg"></div>';
    document.body.appendChild(o);
  }
  function hideLoader() {
    const o = document.querySelector('.loader-overlay');
    if (o) o.remove();
  }

  /* ---------- Fetch helper ---------- */
  /**
   * Wrapper de fetch con JSON, manejo de errores y loader opcional.
   * @returns {Promise<any>}
   */
  async function api(url, options = {}) {
    const { loader = false, ...rest } = options;
    if (loader) showLoader();
    try {
      const res = await fetch(url, {
        headers: { 'Content-Type': 'application/json', ...(rest.headers || {}) },
        ...rest,
      });
      const ct = res.headers.get('content-type') || '';
      const data = ct.includes('application/json') ? await res.json() : await res.text();
      if (!res.ok) {
        const msg = (data && data.message) || `Error ${res.status}`;
        throw new Error(msg);
      }
      return data;
    } finally {
      if (loader) hideLoader();
    }
  }

  /* ---------- Helpers varios ---------- */
  function qs(sel, ctx = document) { return ctx.querySelector(sel); }
  function qsa(sel, ctx = document) { return Array.from(ctx.querySelectorAll(sel)); }

  function formatTime(date = new Date()) {
    return date.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
  }
  function formatDate(date = new Date()) {
    return date.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  /* ---------- Sidebar móvil (hamburger) ---------- */
  function initSidebar() {
    const toggle = qs('[data-sidebar-toggle]');
    const sidebar = qs('.sidebar');
    const backdrop = qs('[data-sidebar-backdrop]');
    if (!toggle || !sidebar) return;
    const open = () => { sidebar.classList.add('is-open'); backdrop && backdrop.classList.add('is-visible'); };
    const close = () => { sidebar.classList.remove('is-open'); backdrop && backdrop.classList.remove('is-visible'); };
    toggle.addEventListener('click', () =>
      sidebar.classList.contains('is-open') ? close() : open());
    backdrop && backdrop.addEventListener('click', close);
    qsa('.sidebar a').forEach((a) => a.addEventListener('click', close));
  }

  document.addEventListener('DOMContentLoaded', initSidebar);

  /* ---------- Export público ---------- */
  global.App = {
    toast, showLoader, hideLoader, api,
    qs, qsa, formatTime, formatDate, initSidebar,
  };
})(window);
