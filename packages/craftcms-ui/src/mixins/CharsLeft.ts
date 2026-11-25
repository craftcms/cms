import {css, html, type LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import '../components/visually-hidden/visually-hidden.js';
import {formatNumber} from '@src/utilities/format';
import {t} from '@src/utilities/translate';
import type {NativeFieldInterface} from './NativeField';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

type CharsLeftBase = Constructor<LitElement & NativeFieldInterface>;

export declare class CharsLeftInterface {
  showCharsLeft: boolean;
}

export const charsLeftStyles = css`
  .input-group__input:has(> .chars-left) {
    position: relative;
  }

  .input-group__input:has(> .chars-left) ::slotted([slot='input']) {
    padding-inline-end: calc(
      var(--c-input-spacing-inline) * 2 + var(--_chars-left-width)
    );
  }

  .chars-left {
    position: absolute;
    inset-inline-end: var(--c-input-spacing-inline);
    inset-block-end: 0;
    display: flex;
    align-items: center;
    block-size: var(--_control-height);
    color: var(--c-form-control-placeholder);
    font-variant-numeric: tabular-nums;
    pointer-events: none;
  }

  .chars-left[data-negative] {
    color: var(--c-color-danger-on-normal);
  }
`;

/**
 * Adds `show-chars-left`, which shows how many characters are left before the
 * control's `maxlength`, at the end of the control. `craft-textarea` keeps
 * space free below its text for it.
 */
export const CharsLeft = <T extends CharsLeftBase>(Base: T) => {
  class CharsLeftElement extends Base {
    /** The character limit, which the component declares. */
    declare maxlength?: number;

    /**
     * Shows how many characters are left before `maxlength`, at the end of the
     * control. Has no effect without a `maxlength`, or while the control is
     * disabled or read-only.
     */
    @property({type: Boolean, reflect: true, attribute: 'show-chars-left'})
    showCharsLeft = false;

    protected override _inputGroupInputTemplate(): unknown {
      const limit = this.maxlength;

      if (
        !this.showCharsLeft ||
        !limit ||
        limit < 0 ||
        this.disabled ||
        this.readOnly
      ) {
        return super._inputGroupInputTemplate();
      }

      const charsLeft = limit - this.value.length;

      return html`
        <div
          class="input-group__input"
          style="--_chars-left-width: ${String(limit).length + 1}ch"
        >
          <slot name="input"></slot>
          <div class="chars-left" role="status" ?data-negative=${charsLeft < 0}>
            <craft-visually-hidden>
              ${t('Characters left: {chars, number}', {chars: charsLeft})}
            </craft-visually-hidden>
            <span aria-hidden="true">${formatNumber(charsLeft)}</span>
          </div>
        </div>
      `;
    }
  }

  return CharsLeftElement as unknown as Constructor<CharsLeftInterface> & T;
};
