import {ElevatedSessionForm} from '@/modules/elevated-session/elevated-session-form';
import {ControllerElement} from '@/common/web-components';

/**
 * `<craft-elevated-session-form>` — boots an {@link ElevatedSessionForm} around
 * the `<form>` it wraps, so PHP/Twig can emit the element instead of a manual
 * `new Craft.ElevatedSessionForm(...)` boot.
 *
 * The optional `inputs` attribute is a JSON array of selectors to watch (resolved
 * within the form); with none, submitting always requires an elevated session.
 *
 * ```html
 * <craft-elevated-session-form inputs='["[name=\"admin\"]"]'>
 *   <form id="main-form">…</form>
 * </craft-elevated-session-form>
 * ```
 */
export default class CraftElevatedSessionForm extends ControllerElement<ElevatedSessionForm> {
  protected readonly rootSelector = 'form';

  protected create(root: HTMLElement): ElevatedSessionForm {
    return new ElevatedSessionForm(root, this.inputSelectors());
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
