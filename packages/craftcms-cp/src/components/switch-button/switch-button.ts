import {LionSwitchButton} from '@lion/ui/switch.js';
import {css} from 'lit';

export default class CraftSwitchButton extends LionSwitchButton {
  static override get styles() {
    return [
      ...super.styles,
      css`
        :host {
          --c-switch-height: var(--c-size-control-sm);
          --c-switch-thumb-offset: 6px;
          --c-switch-thumb-height: calc(
            var(--c-switch-height) - var(--c-switch-thumb-offset)
          );
          display: flex;
          height: var(--c-switch-height);
          width: calc(var(--c-switch-height) * 2);
          margin: -1px;
        }

        :host([size='small']) {
          --c-switch-height: var(--c-size-control-xs);
          --c-switch-thumb-offset: 4px;
        }

        .btn {
          width: 100%;
        }

        .switch-button__track {
          --tw-inset-shadow-color: var(--color-slate-300);
          margin-inline: -1px;
          background-color: var(--c-color-neutral-fill-quiet);
          border-radius: var(--c-radius-full);
          border: 1px solid var(--c-form-control-border);
          box-shadow: var(--c-input-shadow);
        }

        .switch-button__thumb {
          height: var(--c-switch-thumb-height);
          width: auto;
          aspect-ratio: 1;
          border-radius: var(--c-radius-full);
          border: 1px solid var(--c-form-control-border);
          background-color: var(--c-switch-thumb-bg, var(--c-surface-raised));
          inset-block-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-end: auto;
        }

        :host([checked]) .switch-button__track {
          background-color: var(--c-color-success-fill-loud);
        }

        :host([checked]) .switch-button__thumb {
          border: 1px solid var(--c-color-success-border-loud);
          inset-inline-start: auto;
          inset-inline-end: calc(
            (var(--c-switch-height) - var(--c-switch-thumb-height)) / 2
          );
        }

        :host([checked]) .switch-button__thumb:after {
          content: '';
          position: absolute;
          inset-block-start: 3px;
          inset-inline-start: 4px;
          mask-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3C!--! Font Awesome Pro 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2024 Fonticons, Inc.--%3E%3Cpath d='M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7l233.4-233.3c12.5-12.5 32.8-12.5 45.3 0z'/%3E%3C/svg%3E");
          mask-repeat: no-repeat;
          width: calc(var(--c-switch-thumb-height) - 6px);
          aspect-ratio: 1;
          background-color: var(--c-color-success-border-loud);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-switch-button')) {
  customElements.define('craft-switch-button', CraftSwitchButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-switch-button': CraftSwitchButton;
  }
}
