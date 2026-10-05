import {LionSwitchButton} from '@lion/ui/switch.js';
import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {baseFormControlStyles} from '@src/styles/form.styles';

/**
 * @summary The toggle itself — the track and thumb a `craft-switch` renders.
 *
 * It is the switch's internal control rather than something to use directly:
 * on its own it has no label, no hidden input, and nothing posts. Reach for
 * `craft-switch`, which supplies all three and drives this.
 */
export default class CraftSwitchButton extends LionSwitchButton {
  /**
   * Display-only mixed state (thumb centered, `aria-checked="mixed"`).
   * State transitions are managed by `craft-switch`.
   */
  @property({type: Boolean, reflect: true}) indeterminate = false;

  override updated(changedProperties: PropertyValues): void {
    super.updated(changedProperties);
    if (
      changedProperties.has('indeterminate') ||
      changedProperties.has('checked')
    ) {
      this.setAttribute(
        'aria-checked',
        this.checked ? 'true' : this.indeterminate ? 'mixed' : 'false'
      );
    }
  }

  static override get styles() {
    return [
      ...super.styles,
      css`
        :host {
          --c-switch-height: var(--c-size-control-sm);
          --c-switch-thumb-offset: 5px;
          --c-switch-thumb-height: calc(
            var(--c-switch-height) - var(--c-switch-thumb-offset)
          );
          display: flex;
          height: var(--c-switch-height);
          width: calc(var(--c-switch-height) * 1.75);
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
          ${baseFormControlStyles}
          --tw-inset-shadow-color: var(--color-slate-300);
          background-color: var(--c-color-neutral-fill-quiet);
          border-radius: var(--c-radius-full);
          min-height: unset;
        }

        .switch-button__thumb {
          height: var(--c-switch-thumb-height);
          width: auto;
          aspect-ratio: 1;
          border-radius: var(--c-radius-full);
          border: 1px solid var(--c-form-control-border-color);
          background-color: var(--c-switch-thumb-fill, var(--c-surface-raised));
          inset-block-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-start: calc(var(--c-switch-thumb-offset) / 2);
          inset-inline-end: auto;
          box-sizing: border-box;
          background-clip: padding-box;
        }

        :host([indeterminate]:not([checked])) .switch-button__thumb {
          inset-inline-start: calc(50% - (var(--c-switch-thumb-height) / 2));
          inset-inline-end: auto;
        }

        :host([checked]) .switch-button__track {
          border-color: transparent;
          background-color: var(--c-color-accent-fill-loud);
        }

        :host([checked]) .switch-button__thumb {
          border-color: transparent;
          inset-inline-start: auto;
          inset-inline-end: calc(var(--c-switch-thumb-offset) / 2);
        }

        :host([checked]) .switch-button__thumb:after {
          --_checkmark-size: calc(var(--c-switch-thumb-height) / 2);
          --_checkmark-offset: calc((var(--c-switch-thumb-height) - var(--_checkmark-size)) / 2 - 1px);
          content: '';
          position: absolute;
          inset-block-start: var(--_checkmark-offset);
          inset-inline-start: var(--_checkmark-offset);
          mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' version='1.1' viewBox='0 0 255.8 234.9'%3E%3Cpath d='M245.9,4.6c-10.7-7.8-25.7-5.4-33.5,5.3l-119.4,164.2-52.1-52.1c-9.4-9.4-24.6-9.4-33.9,0-9.3,9.4-9.4,24.6,0,33.9l72,72c5,5,11.8,7.5,18.8,7s13.4-4.1,17.5-9.8L251.2,38.1c7.8-10.7,5.4-25.7-5.3-33.5Z'/%3E%3C/svg%3E");
          mask-repeat: no-repeat;
          width: var(--_checkmark-size);
          aspect-ratio: 1;
          background-color: var(--c-color-accent-fill-loud);
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
