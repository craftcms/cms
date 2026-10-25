import {html, nothing, type LitElement, type PropertyValues} from 'lit';
// Registers <craft-callout> and <craft-visually-hidden> for the notices.
import '../components/callout/callout.js';
import '../components/visually-hidden/visually-hidden.js';
import {HasSlotController} from '@src/utilities/slot';
import {t} from '@src/utilities/translate';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

type NoticeKind = 'tip' | 'warning';

/** The Lion `FormControlMixin` members the mixin uses. */
interface NoticeHost extends LitElement {
  addToAriaDescribedBy(
    element: HTMLElement,
    config?: {idPrefix?: string; reorder?: boolean}
  ): void;
}

/**
 * Renders `tip` and `warning` slot content as the CP's field notices —
 * an info callout with a lightbulb for tips, a warning callout for warnings —
 * between the control and its feedback, and adds them to the control's
 * accessible description.
 *
 * Shared by `craft-field` and every labeled control (through `HasLabel`), so
 * a tip looks the same whether it's on a wrapped field or a standalone input.
 * Pair it with `baseFieldStyles`, which styles `.field-notice`.
 *
 * ```html
 * <craft-input label="Name">
 *   <span slot="tip">Type <kbd>$</kbd> to choose an environment variable.</span>
 * </craft-input>
 * ```
 */
export const FieldNotices = <T extends Constructor<NoticeHost>>(Base: T) => {
  class FieldNoticesElement extends Base {
    #hasNoticeSlot = new HasSlotController(this, 'tip', 'warning');

    protected override updated(changedProperties: PropertyValues) {
      super.updated(changedProperties);

      for (const kind of ['tip', 'warning'] as const) {
        const node = this.querySelector<HTMLElement>(
          `:scope > [slot="${kind}"]`
        );
        if (node) {
          // Already-registered nodes are ignored.
          this.addToAriaDescribedBy(node, {idPrefix: kind, reorder: false});
        }
      }
    }

    // Lion declares `_feedbackTemplate()` on `FormControlMixin`; notices go
    // just ahead of it, so subclasses that rearrange group two keep them.
    protected _feedbackTemplate() {
      return html`${this._noticeTemplate('tip')}${this._noticeTemplate(
        'warning'
      )}${
        // @ts-expect-error The base is typed without Lion's protected members.
        super._feedbackTemplate()
      }`;
    }

    /**
     * A tip or warning notice, mirroring `forms/field-notice.blade.php`, with
     * a visually hidden prefix. Skipped when nothing is slotted.
     */
    protected _noticeTemplate(kind: NoticeKind) {
      if (!this.#hasNoticeSlot.test(kind)) {
        return nothing;
      }

      const isTip = kind === 'tip';

      return html`
        <craft-callout
          class="field-notice"
          variant=${isTip ? 'info' : 'warning'}
          appearance="plain"
          padding="none"
        >
          <craft-visually-hidden>
            ${isTip ? t('Tip:') : t('Warning:')}
          </craft-visually-hidden>
          <slot name=${kind}></slot>
        </craft-callout>
      `;
    }
  }

  return FieldNoticesElement as T;
};
