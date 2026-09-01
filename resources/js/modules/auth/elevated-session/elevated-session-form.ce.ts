import {ElevatedSessionForm} from '@/modules/auth/elevated-session/elevated-session-form';
import {ControllerElement} from '@/common/web-components';

/**
 * `<craft-elevated-session-form>` — boots an {@link ElevatedSessionForm} around
 * the `<form>` it wraps, so PHP/Twig can emit the element instead of a manual
 * `new Craft.ElevatedSessionForm(...)` `{% js %}` block (the same declarative
 * shape as `<craft-listbox>` / `<craft-generated-fields-table>`).
 *
 * The form renders as **light-DOM children** — the controller listens on the real
 * `<form>` submit and calls `requestSubmit()` on it, so a shadow root would hide
 * it. The optional `inputs` attribute is a JSON array of selectors to watch
 * (resolved within the form); with none, submitting always requires an elevated
 * session — mirroring the constructor's `inputTargets` argument.
 *
 * ```html
 * <craft-elevated-session-form inputs='["[name=\"admin\"]"]'>
 *   <form id="main-form">…</form>
 * </craft-elevated-session-form>
 * ```
 */
export default class CraftElevatedSessionForm extends ControllerElement<ElevatedSessionForm> {
  protected readonly rootSelector = 'form';

  protected create(form: HTMLElement): ElevatedSessionForm {
    if (!(form instanceof HTMLFormElement)) {
      throw new Error('Elevated session host must contain a form.');
    }
    return new ElevatedSessionForm(form, this.inputSelectors());
  }

  private inputSelectors(): string[] | undefined {
    const raw = this.getAttribute('inputs');
    if (!raw) {
      return undefined;
    }
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : undefined;
    } catch {
      return undefined;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-elevated-session-form': CraftElevatedSessionForm;
  }
}
