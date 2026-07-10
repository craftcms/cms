import {Base, getInputPostVal} from '@craftcms/garnish';
import {formElevatedSessions} from './support';
import {
  isFetchingElevatedTimeout,
  requireElevatedSession,
} from './elevated-session';

// `Craft` and `$` (jQuery) remain page globals. This class manages a
// server-rendered `<form>` and its inputs via jQuery, mirroring the seams kept by
// the other legacy ports (`listbox`, `sortable-checkbox-select`).
declare const $: any;

interface WatchedInput {
  $input: any;
  val: string | string[] | null;
}

/**
 * Elevated Session Form — a port of the legacy `Craft.ElevatedSessionForm` onto
 * `@craftcms/garnish` `Base`.
 *
 * Guards a form so that, if any of the watched inputs changed, the native submit
 * is held until the user establishes an elevated session (via
 * {@link requireElevatedSession} → the login modal). Setup lives in {@link init},
 * invoked from the constructor only for the leaf class (`new.target` guard) — the
 * same contract as the other ports.
 *
 * Construct with a form (selector, element, or jQuery collection) and, optionally,
 * a list of input selectors to watch. With no selectors it always requires an
 * elevated session on submit (the legacy "no way to know" behavior).
 *
 * @see elevated-session.ts for the manager-backed promise primitive.
 */
export class ElevatedSessionForm extends Base {
  $form: any = null;
  inputSelectors: string[] = [];
  $inputs: any = null;
  inputs: WatchedInput[] = [];

  constructor(form?: any, inputs?: string | string[]) {
    super();
    if (new.target === ElevatedSessionForm) {
      this.init(form, inputs);
    }
  }

  init(form: any, inputs?: string | string[]): void {
    this.$form = $(form);
    this.inputSelectors = [];
    this.$inputs = $();
    this.inputs = [];

    // Object back-reference + double-instantiation guard.
    const el: Element | undefined = this.$form[0];
    if (el) {
      formElevatedSessions.set(el, this);
    }

    // Only check specific inputs?
    if (typeof inputs !== 'undefined') {
      ($.makeArray(inputs) as unknown[]).forEach((selector) => {
        if (typeof selector === 'string') {
          this.inputSelectors.push(selector);
        }

        $(selector, this.$form).each((_i: number, input: Element) => {
          this.$inputs = this.$inputs.add(input);
          const $input = $(input);
          this.inputs.push({
            $input,
            val: getInputPostVal(input),
          });
        });
      });
    }

    this.addListener(this.$form, 'submit', 'handleFormSubmit');
  }

  handleFormSubmit(ev: any): void {
    // Ignore if we're in the middle of getting the elevated session timeout.
    if (isFetchingElevatedTimeout()) {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      ev.cancel = true;
      return;
    }

    if (!this.inputsChanged()) {
      return;
    }

    // Prevent the form from submitting until the user has an elevated session.
    ev.preventDefault();
    ev.stopImmediatePropagation();
    ev.cancel = true;

    requireElevatedSession()
      .then(() => this.submitForm())
      .catch(() => {
        // Cancelled — leave the form as-is.
      });
  }

  inputsChanged(): boolean {
    if (!this.inputSelectors.length && !this.inputs.length) {
      // No way to know.
      return true;
    }

    // If we have any input selectors, see if there are any new inputs that match.
    for (const selector of this.inputSelectors) {
      const $inputs = $(selector, this.$form);
      for (let i = 0; i < $inputs.length; i++) {
        const input = $inputs[i];
        if (!this.$inputs.is(input)) {
          return true;
        }
      }
    }

    // If we have any inputs, see if their values have changed.
    for (const {$input: initial, val} of this.inputs) {
      // Is this a password input? (Craft swaps the visible input.)
      const $input = initial.data('passwordInput')
        ? initial.data('passwordInput').$currentInput
        : initial;

      if (!valsMatch(getInputPostVal($input[0] ?? $input), val)) {
        return true;
      }
    }

    return false;
  }

  submitForm(): void {
    // Don't let handleFormSubmit() interrupt this time.
    this.disable();
    this.$form.trigger('submit');
    this.enable();
  }
}

function valsMatch(
  a: string | string[] | null,
  b: string | string[] | null
): boolean {
  if (Array.isArray(a) || Array.isArray(b)) {
    return JSON.stringify(a) === JSON.stringify(b);
  }
  return a === b;
}
