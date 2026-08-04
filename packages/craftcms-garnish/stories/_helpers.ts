/**
 * Shared DOM builders for the modal-based stories.
 *
 * The Modal widget positions itself `fixed` and renders its own `.modal-shade`,
 * so these containers are appended to `document.body` (not the story canvas) —
 * exactly as the old playground did.
 */

import {Modal} from '../src/index';

/** Build a styled modal container with the given heading + body HTML. */
export function buildModalContainer(title: string, body: string): HTMLElement {
  const el = document.createElement('div');
  el.className = 'pg-modal';
  el.innerHTML = `
    <h3>${title}</h3>
    <div class="pg-modal-body">${body}</div>
    <div class="pg-modal-actions">
      <button type="button" class="pg-modal-primary" data-modal-close>Close (hide)</button>
    </div>
  `;
  document.body.appendChild(el);
  el.querySelector('[data-modal-close]')!.addEventListener('click', () => {
    // Find the owning Modal via the static registry and hide it.
    Modal.instances.find((m) => m.$container === el)?.hide();
  });
  return el;
}

/**
 * Build a modal container styled to look draggable, optionally with a header
 * handle (for the `dragHandleSelector` demo).
 */
export function buildDragModalContainer(
  title: string,
  body: string,
  withHandle: boolean
): HTMLElement {
  const el = document.createElement('div');
  el.className = 'pg-modal pg-modal--draggable';
  const handleHtml = withHandle
    ? `<div class="pg-modal-drag-handle" data-drag-handle>${title} — drag here</div>`
    : `<h3>${title}</h3>`;
  el.innerHTML = `
    ${handleHtml}
    <div class="pg-modal-body">${body}</div>
    <div class="pg-modal-actions">
      <button type="button" class="pg-modal-primary" data-modal-close>Close (hide)</button>
    </div>
  `;
  document.body.appendChild(el);
  el.querySelector('[data-modal-close]')!.addEventListener('click', () => {
    Modal.instances.find((m) => m.$container === el)?.hide();
  });
  return el;
}
