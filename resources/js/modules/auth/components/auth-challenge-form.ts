import {css, html, LitElement} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {actionClient, t} from '@craftcms/ui';
import {registerCraftGlobals} from '@/common/craft-global';

/** @internal Module-private registry of PHP METHOD handle → element class. */
const _registry = new Map<string, typeof CraftAuthChallengeForm>();

/**
 * Abstract base class for Craft CMS 2FA challenge method forms.
 *
 * ## Extending this class (plugin authors)
 *
 * 1. Declare a static `METHOD` property matching your PHP auth method handle.
 * 2. Implement the abstract `endpoint` getter returning the Craft action path to POST to.
 * 3. Override `renderInput()` to return your `<craft-input>` markup.
 * 4. Call `CraftAuthChallengeForm.register('your-tag-name', YourClass)` instead
 *    of `customElements.define`. This registers the element AND marks your method
 *    as native so Craft's login challenge renders it directly.
 *
 * @example
 * ```typescript
 * import {html} from 'lit';
 *
 * const {AuthChallengeForm} = window.Craft;
 *
 * class MyPluginForm extends AuthChallengeForm {
 *   static METHOD = 'my-method';
 *
 *   protected override get endpoint() {
 *     return 'my-plugin/auth/verify';
 *   }
 *
 *   override renderInput() {
 *     return html`
 *       <craft-input
 *         label="Code"
 *         name="code"
 *       ></craft-input>
 *     `;
 *   }
 * }
 *
 * AuthChallengeForm.register('my-plugin-form', MyPluginForm);
 * ```
 *
 * @fires login-verified - Verification succeeded; bubbles to `craft-login-challenge`. Detail: `{ returnUrl: string }`
 * @fires login-failed   - Verification failed; bubbles to `craft-login-challenge`.   Detail: `{ message: string }`
 */
export abstract class CraftAuthChallengeForm extends LitElement {
  static override styles = [
    css`
      :host {
        display: block;
        width: 100%;
      }

      .spinner-overlay {
        display: grid;
        place-items: center;
      }

      .login-form__fields {
        display: flex;
        gap: var(--c-spacing-md);
        align-items: end;
      }

      .login-form__actions {
        margin-block-start: var(--c-spacing-lg);
      }

      .login-form__error {
        margin-block-start: var(--c-spacing-md);
      }

      .alternative-login-methods {
        margin-block-start: var(--c-spacing-lg);
      }

      hr {
        margin-block: var(--c-spacing-lg);
        border: none;
        border-block-end: 1px solid var(--c-color-border-quiet);
      }
    `,
  ];

  /**
   * The PHP auth method handle for this form.
   * Must match the value returned by your PHP method's `handle()`.
   *
   * @example `'totp'`
   */
  static METHOD: string;

  /** Redirect destination forwarded in the `login-verified` event detail. */
  @property({attribute: 'return-url'}) returnUrl = '';

  /**
   * Current submission state. Available in render methods if needed.
   * - `'idle'`    — waiting for input
   * - `'loading'` — request in flight
   * - `'success'` — request succeeded (clears after 2 s)
   * - `'error'`   — request failed
   */
  @state() protected _state: 'idle' | 'loading' | 'success' | 'error' = 'idle';

  /**
   * The single `craft-input` inside this form's shadow DOM.
   * Used for auto-focus and reading the current value on submit.
   * Subclasses may override with a more specific query if needed.
   */
  @query('craft-input') protected _input?: HTMLElement & {value: string};

  // ── Abstract members ───────────────────────────────────────────────────────

  /**
   * The Craft action path to POST `{ code: string }` to.
   * @example `'auth/verify-totp'`
   */
  protected abstract get endpoint(): string;

  // ── Lifecycle ──────────────────────────────────────────────────────────────

  override firstUpdated() {
    this._input?.focus();
  }

  // ── Event handlers ─────────────────────────────────────────────────────────

  protected async _onSubmit(event: Event) {
    event.preventDefault();
    await this._submit(this._input?.value ?? '');
  }

  // ── Core submit logic ──────────────────────────────────────────────────────

  protected async _submit(code: string) {
    if (this._state === 'loading') return;
    this._state = 'loading';

    try {
      await actionClient.post(this.endpoint, {code});

      this.dispatchEvent(
        new CustomEvent('login-verified', {
          bubbles: true,
          composed: true,
          detail: {returnUrl: this.returnUrl},
        })
      );

      this._state = 'success';
      // @TODO hook up to global feedback time setting
      setTimeout(() => {
        this._state = 'idle';
      }, 2000);
    } catch (e: any) {
      this._state = 'error';
      this.dispatchEvent(
        new CustomEvent('login-failed', {
          bubbles: true,
          composed: true,
          detail: {
            message:
              e?.response?.data?.message ?? t('A server error occurred.'),
          },
        })
      );
    }
  }

  // ── Render helpers ─────────────────────────────────────────────────────────

  /**
   * Returns the input markup for this challenge form.
   *
   * Override this in subclasses to provide a `<craft-input>` element configured
   * for your auth method. The base implementation renders an `input` slot so
   * the markup can alternatively be provided declaratively.
   */
  renderInput() {
    return html`<slot name="input">
      <div>
        ${t('Implement the renderInput() method or provide an input slot')}
      </div>
    </slot>`;
  }

  /**
   * Returns the full fields area: the input followed by the submit button.
   *
   * Renders a `fields` slot for full declarative override, and a `submit-button`
   * slot to replace just the button. Override `renderInput()` instead of this
   * method in most cases.
   */
  renderFields() {
    return html`
      <slot name="fields">
        ${this.renderInput()}
        <slot name="submit-button">
          <craft-button
            slot="after"
            type="submit"
            variant="primary"
            ?loading="${this._state === 'loading'}"
          >
            ${t('Verify')}
          </craft-button>
        </slot>
      </slot>
    `;
  }

  override render() {
    return html`
      <form
        class="login-form"
        accept-charset="UTF-8"
        @submit="${this._onSubmit}"
      >
        <div class="login-form__fields">${this.renderFields()}</div>
      </form>
    `;
  }

  // ── Static registry API ────────────────────────────────────────────────────

  /**
   * Register a 2FA challenge form element.
   *
   * Call this instead of `customElements.define()`. It defines the custom
   * element (guarded against double-registration) and records the class so that
   * Craft's login challenge component recognises it as a native method and
   * renders it directly instead of falling back to the legacy JS form handler.
   *
   * @param tagName      - The custom element tag name, e.g. `'craft-totp-form'`.
   * @param elementClass - The class to register.
   */
  static register<
    T extends typeof CraftAuthChallengeForm &
      CustomElementConstructor & {METHOD: string},
  >(tagName: string, elementClass: T): void {
    if (!customElements.get(tagName)) {
      customElements.define(tagName, elementClass);
    }
    _registry.set(elementClass.METHOD, elementClass);
  }

  /**
   * Return `true` if the given PHP method handle has a native web component
   * registered via `CraftAuthChallengeForm.register()`.
   *
   * Used by `login-challenge` to decide between native rendering and the legacy
   * `Craft.createAuthFormHandler()` path.
   */
  static isNative(method: string): boolean {
    return _registry.has(method);
  }
}

// Plugin auth methods extend this class at runtime through the `Craft` global
// (bundling their own copy from an import would give them a separate, useless
// method registry). Assigned here so the global exists in any bundle that can
// render a challenge form.
registerCraftGlobals({AuthChallengeForm: CraftAuthChallengeForm});
