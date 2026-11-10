import {html, LitElement, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';
import {linkRel, linkTarget, newTabCue} from '@src/utilities/external-links';
import styles, {buttonBaseStyles} from './button.styles.js';
import a11yErrorStyles from '@src/styles/a11y-error.styles.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles.js';
import '../spinner/spinner.js';
import '../icon/icon.js';
import '../shortcut/shortcut.js';
import {HasShortcut} from '@src/mixins/HasShortcut';
import {computeAccessibleName} from 'dom-accessibility-api';
import {classMap} from 'lit/directives/class-map.js';
import {Actionable} from '@src/mixins/Actionable';
import {AsyncStates} from '@src/types';

export const ButtonVariant = {
  Primary: 'primary',
  Danger: 'danger',
  DangerPlain: 'danger-plain',
  Solid: 'solid',
  Fill: 'fill',
  Outline: 'outline',
  Dashed: 'dashed',
  Plain: 'plain',
  Link: 'link',
  None: 'none',
} as const;

export type ButtonVariant = (typeof ButtonVariant)[keyof typeof ButtonVariant];

const isActivationKey = (event: KeyboardEvent) =>
  event.key === ' ' || event.key === 'Enter';

/**
 * Hidden native submit buttons, one per form holding a submit `craft-button`,
 * so pressing Enter in a field still submits the form.
 */
const implicitSubmitters = new WeakMap<
  HTMLFormElement,
  {button: HTMLButtonElement; owners: Set<CraftButton>}
>();

function createImplicitSubmitter(activate: () => void): HTMLButtonElement {
  const button = document.createElement('button');
  button.type = 'submit';
  button.tabIndex = -1;
  button.setAttribute('aria-hidden', 'true');
  button.style.cssText =
    'position:absolute;inset-block-start:0;inset-inline-start:0;width:1px;height:1px;padding:0;border:0;overflow:hidden;clip-path:inset(50%);white-space:nowrap;';
  // Its click is an implementation detail of the form, not one to listen for.
  // The default `craft-button` is clicked instead, so its listeners can cancel
  // the submit, as they could a native default button's.
  button.addEventListener('click', (event) => {
    event.stopPropagation();
    event.preventDefault();
    activate();
  });
  return button;
}

/** Whether a disabled fieldset disables the element, as it would a native control. */
function inDisabledFieldset(element: Element): boolean {
  for (
    let fieldset = element.parentElement?.closest('fieldset');
    fieldset;
    fieldset = fieldset.parentElement?.closest('fieldset')
  ) {
    // Controls in a fieldset's first legend stay enabled.
    if (
      fieldset.disabled &&
      !fieldset.querySelector(':scope > legend')?.contains(element)
    ) {
      return true;
    }
  }

  return false;
}

/**
 * Buttons to recheck when a fieldset is enabled or disabled, which no event
 * reports. One observer serves each document or shadow root, since an observer
 * doesn't see into shadow roots.
 */
const fieldsetObservers = new WeakMap<
  Node,
  {observer: MutationObserver; watchers: Set<() => void>}
>();

function watchFieldsets(root: Node, sync: () => void): void {
  let entry = fieldsetObservers.get(root);

  if (!entry) {
    const watchers = new Set<() => void>();
    const observer = new MutationObserver((records) => {
      if (records.some((r) => r.target instanceof HTMLFieldSetElement)) {
        watchers.forEach((watcher) => watcher());
      }
    });
    observer.observe(root, {
      attributes: true,
      attributeFilter: ['disabled'],
      subtree: true,
    });
    entry = {observer, watchers};
    fieldsetObservers.set(root, entry);
  }

  entry.watchers.add(sync);
}

function unwatchFieldsets(root: Node, sync: () => void): void {
  const entry = fieldsetObservers.get(root);

  if (!entry) {
    return;
  }

  entry.watchers.delete(sync);

  if (!entry.watchers.size) {
    entry.observer.disconnect();
    fieldsetObservers.delete(root);
  }
}

/**
 * @summary Interactive element that triggers an action or event.
 * @since 1.0
 *
 * @dependency craft-spinner
 * @dependency craft-shortcut
 *
 * @slot - The button's label.
 * @slot prefix - Content to display before the label (typically an icon).
 * @slot suffix - Content to display after the label (typically an icon).
 *
 * @attr form - The id of the form a `submit` or `reset` button acts on, when
 *   it isn't the one the button is in.
 *
 * @attr shortcut - A keyboard shortcut shown at the end of the button: a key
 *   (`"K"`), or JSON naming its modifiers (`'{"key": "S", "shift": true}'`).
 *   Display only, and hidden from screen readers. Supplied by the
 *   `HasShortcut` mixin.
 *
 * @csspart content - The button's content wrapper.
 * @csspart prefix - The button's prefix slot.
 * @csspart label - The button's label slot.
 * @csspart suffix - The button's suffix slot.
 * @csspart spinner - Spinner that shows when the button is in a loading state.
 * @csspart link - The anchor element rendered when the button has an href.
 *
 * @event craft-toggle - Fired when a `toggle` button is activated. `detail.active`
 *   is the state being asked for. Cancelable — `active` is owned by whoever set
 *   it, and the button never changes it itself.
 */
export default class CraftButton extends HasShortcut(Actionable(LitElement)) {
  static override styles = [
    buttonBaseStyles,
    visuallyHiddenStyles,
    a11yErrorStyles,
    styles,
  ];

  /**
   * What the button does in a form: `submit` and `reset` act on its `form`,
   * like a native button. Defaults to `button`.
   */
  @property({reflect: true}) type: 'button' | 'submit' | 'reset' = 'button';

  /** Disables the button: it can't be clicked, and leaves the tab order. */
  @property({type: Boolean, reflect: true}) disabled = false;

  /** Whether a disabled fieldset around the button disables it. */
  @state() private fieldsetDisabled = false;

  #internals: ElementInternals | undefined = this.attachInternals?.();

  /** Whether `aria-disabled` is the button's own, to take away again. */
  #ownsAriaDisabled = false;

  /**
   * The form the button submits or resets: the one its `form` attribute
   * names, or else the one it's in.
   */
  get form(): HTMLFormElement | null {
    const id = this.getAttribute('form');

    if (id === null) {
      return this.closest('form');
    }

    const root = this.getRootNode() as Document | ShadowRoot;
    const form = root.getElementById?.(id);

    return form instanceof HTMLFormElement ? form : null;
  }

  static override get observedAttributes(): string[] {
    return [...super.observedAttributes, 'form'];
  }

  override attributeChangedCallback(
    name: string,
    old: string | null,
    value: string | null
  ): void {
    super.attributeChangedCallback(name, old, value);

    if (name === 'form') {
      this.#syncImplicitSubmitter();
    }
  }

  /**
   * Disabled by its own `disabled`, or by a disabled fieldset. A link isn't a
   * form control, so a fieldset leaves it alone. The fieldsets are read as
   * they are now, since `fieldsetDisabled` only catches up once the observer
   * reports, and a click can come first.
   */
  get #inactive(): boolean {
    return this.disabled || (!this.href && inDisabledFieldset(this));
  }

  #syncFieldsetDisabled = (): void => {
    this.fieldsetDisabled = inDisabledFieldset(this);
  };

  /**
   * Disables a form's hidden submitter while its first submit `craft-button`
   * is disabled, as a disabled default button blocks implicit submission.
   *
   * Enter clicks the form's first submit button, so the submitter goes right
   * before the first native one that follows that `craft-button`, outside any
   * fieldset that could disable that one alone, or at the end of the form when
   * none follows. It's taken out when a native one comes first instead. It
   * isn't put beside the `craft-button` itself, which may sit in a
   * `craft-button-group` that lays out every child.
   */
  static #updateImplicitSubmitter(
    form: HTMLFormElement,
    entry: {button: HTMLButtonElement; owners: Set<CraftButton>}
  ): void {
    const first = CraftButton.#defaultSubmitter(entry.owners);
    entry.button.disabled = first ? first.#inactive : false;

    if (!first) {
      return;
    }

    const native = [...form.elements].find(
      (element) =>
        element !== entry.button &&
        (element instanceof HTMLButtonElement ||
          element instanceof HTMLInputElement) &&
        element.type === 'submit'
    );

    if (
      native &&
      !(
        first.compareDocumentPosition(native) & Node.DOCUMENT_POSITION_FOLLOWING
      )
    ) {
      entry.button.remove();
      return;
    }

    let anchor: Element | undefined = native;
    for (
      let fieldset = native?.parentElement?.closest('fieldset');
      fieldset && !fieldset.contains(first);
      fieldset = fieldset.parentElement?.closest('fieldset')
    ) {
      anchor = fieldset;
    }

    if (anchor) {
      if (entry.button.nextElementSibling !== anchor) {
        anchor.before(entry.button);
      }
    } else if (form.lastElementChild !== entry.button) {
      form.append(entry.button);
    }

    if (form.contains(entry.button)) {
      entry.button.removeAttribute('form');
    } else {
      entry.button.setAttribute('form', form.id);
    }
  }

  /** A form's first submit `craft-button`, which Enter in a field clicks. */
  static #defaultSubmitter(owners: Set<CraftButton>): CraftButton | undefined {
    const [first] = [...owners].sort((a, b) =>
      a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
    );
    return first;
  }

  constructor() {
    super();
    this.addEventListener('click', this.#onClick, {capture: true});
    this.addEventListener('mousedown', this.#onMouseDown);
    this.addEventListener('keydown', this.#onKeyDown);
    this.addEventListener('keyup', this.#onKeyUp);
  }

  /** Clicks the button, unless it's disabled. */
  override click(): void {
    // A listener's own click() is suppressed by the browser, and must leave
    // the outer click's form action for after every listener has seen it.
    if (this.#inactive || this.#clicking) {
      return;
    }

    this.#clicking = true;
    try {
      super.click();
    } finally {
      this.#clicking = false;
    }

    if (this.#pendingFormAction) {
      this.#runFormAction(this.#pendingFormAction);
    }
  }

  /** Whether `click()` is dispatching, so it can act on the form itself. */
  #clicking = false;

  /** A click whose form action waits for every listener to have seen it. */
  #pendingFormAction?: Event;

  /**
   * Runs ahead of the page's own listeners, so a disabled button that stays
   * reachable by the pointer swallows the click.
   */
  #onClick = (event: Event) => {
    if (this.#inactive) {
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }

    if (this.type === 'button') {
      return;
    }

    // Like a native submit button, acting on the form only once the click is
    // done, so a listener that cancels it stops the submit.
    this.#pendingFormAction = event;

    if (!this.#clicking) {
      setTimeout(() => this.#runFormAction(event));
    }
  };

  #runFormAction(event: Event): void {
    if (this.#pendingFormAction !== event) {
      return;
    }

    this.#pendingFormAction = undefined;

    // A button disabled by then does nothing, as a native one wouldn't.
    if (event.defaultPrevented || this.#inactive) {
      return;
    }

    const form = this.form;

    if (this.type === 'submit') {
      form?.requestSubmit();
    } else if (this.type === 'reset') {
      form?.reset();
    }
  }

  /**
   * Marks the button `active` while it's pressed. A press that starts a
   * native drag, from a reorder handle say, never gets its mouseup, so the
   * end of the drag releases it too. Releasing restores the value from before
   * the press, so a toggle that was on is still on when its click asks to
   * turn it off.
   */
  #onMouseDown = () => {
    const wasActive = this.active;
    this.active = true;

    const release = () => {
      this.active = wasActive;
      document.removeEventListener('mouseup', release, true);
      document.removeEventListener('dragend', release, true);
    };

    document.addEventListener('mouseup', release, true);
    document.addEventListener('dragend', release, true);
  };

  /** Space and Enter press the button; Space mustn't scroll the page. */
  #onKeyDown = (event: KeyboardEvent) => {
    // Keys on the link in link mode are the link's to handle.
    if (event.composedPath()[0] !== this) {
      return;
    }

    if (event.key === ' ') {
      event.preventDefault();
    }

    if (this.active || !isActivationKey(event)) {
      return;
    }

    this.active = true;

    const release = (keyup: KeyboardEvent) => {
      if (isActivationKey(keyup)) {
        this.active = false;
        document.removeEventListener('keyup', release, true);
      }
    };

    document.addEventListener('keyup', release, true);
  };

  #onKeyUp = (event: KeyboardEvent) => {
    if (isActivationKey(event) && event.composedPath()[0] === this) {
      this.click();
    }
  };

  override connectedCallback() {
    super.connectedCallback();
    this._hostLabel = this.getAttribute('aria-label');

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }

    this.syncLinkHostState();
    this.#syncFieldsetDisabled();
    this.#fieldsetRoot = this.getRootNode();
    watchFieldsets(this.#fieldsetRoot, this.#syncFieldsetDisabled);
    this.#syncImplicitSubmitter();
    this.addEventListener('click', this.#handleToggleClick);

    this.#syncContent();
    // A name can arrive after the first render, as when a tooltip labels its
    // invoker once its overlay sets up, so content and naming changes recheck it.
    this.#contentObserver = new MutationObserver(() => {
      this.#syncContent();
      this._hostLabel = this.getAttribute('aria-label');
      if (this.hasUpdated) {
        // After the anchor has rendered the label it may have just taken.
        void this.updateComplete.then(() => this.#checkAccessibleName());
      }
    });
    this.#contentObserver.observe(this, {
      childList: true,
      characterData: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['slot', 'aria-label', 'aria-labelledby', 'title'],
    });

    // Moved while it was still waiting to be shown: disconnecting dropped the
    // observer, so pick the wait back up rather than never judging it.
    if (this.hasUpdated && !this._accessibleName.trim()) {
      this.#checkAccessibleName();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener('click', this.#handleToggleClick);
    this.#contentObserver?.disconnect();
    this.#contentObserver = null;
    this.#stopAwaitingRender();
    this.#releaseImplicitSubmitter();

    if (this.#fieldsetRoot) {
      unwatchFieldsets(this.#fieldsetRoot, this.#syncFieldsetDisabled);
      this.#fieldsetRoot = null;
    }
  }

  /** The document or shadow root watched for fieldsets changing. */
  #fieldsetRoot: Node | null = null;

  /** The form this button registered an implicit submitter with. */
  #submitterForm: HTMLFormElement | null = null;

  #syncImplicitSubmitter(): void {
    const form = this.isConnected && this.type === 'submit' ? this.form : null;

    if (form === this.#submitterForm) {
      return;
    }

    this.#releaseImplicitSubmitter();

    if (!form) {
      return;
    }

    let entry = implicitSubmitters.get(form);

    if (!entry) {
      const owners = new Set<CraftButton>();
      const button = createImplicitSubmitter(() =>
        CraftButton.#defaultSubmitter(owners)?.click()
      );
      entry = {button, owners};
      implicitSubmitters.set(form, entry);
    }

    entry.owners.add(this);
    this.#submitterForm = form;
    CraftButton.#updateImplicitSubmitter(form, entry);
  }

  #releaseImplicitSubmitter(): void {
    const form = this.#submitterForm;

    if (!form) {
      return;
    }

    const entry = implicitSubmitters.get(form);
    this.#submitterForm = null;

    if (!entry) {
      return;
    }

    entry.owners.delete(this);
    CraftButton.#updateImplicitSubmitter(form, entry);

    // Deferred: the form may be mid-removal itself, and a button moved within
    // it registers again before this runs.
    queueMicrotask(() => {
      if (!entry.owners.size && implicitSubmitters.get(form) === entry) {
        entry.button.remove();
        implicitSubmitters.delete(form);
      }
    });
  }

  /**
   * Reports that a toggle was activated, for the owner of `active` to act on.
   *
   * Cancelable, so a consumer can refuse the change; `active` is left alone
   * either way.
   */
  #handleToggleClick = (event: Event) => {
    if (!this.toggle || this.#inactive || this.loading) {
      return;
    }

    this.dispatchEvent(
      new CustomEvent('craft-toggle', {
        bubbles: true,
        composed: true,
        cancelable: true,
        detail: {active: !this.active, sourceEvent: event},
      })
    );
  };

  override willUpdate(changedProperties: Map<string, unknown>) {
    super.willUpdate(changedProperties);

    // The spinner is this button's rendering of the mixin's state. `loading`
    // stays public and settable on its own, for a caller showing one without
    // a declarative action behind it — so this follows transitions only.
    // On the first update `actionState` is "changed" from nothing to idle,
    // and mirroring that would switch off a spinner the caller had asked for.
    if (
      changedProperties.has('actionState') &&
      changedProperties.get('actionState') !== undefined
    ) {
      this.loading = this.actionState === AsyncStates.Loading;
    }
  }

  override updated(changedProperties: Map<string, unknown>) {
    super.updated(changedProperties);
    if (changedProperties.has('href') || changedProperties.has('disabled')) {
      this.syncLinkHostState();
    }

    const inactivityChanged =
      changedProperties.has('disabled') ||
      changedProperties.has('fieldsetDisabled') ||
      changedProperties.has('href');

    if (changedProperties.has('type')) {
      this.#syncImplicitSubmitter();
    } else if (inactivityChanged && this.#submitterForm) {
      const entry = implicitSubmitters.get(this.#submitterForm);
      if (entry) {
        CraftButton.#updateImplicitSubmitter(this.#submitterForm, entry);
      }
    }

    if (inactivityChanged) {
      // Only an `aria-disabled` it set itself is taken away again: an author
      // may mark an enabled button disabled to keep it focusable.
      if (this.#inactive) {
        if (this.getAttribute('aria-disabled') !== 'true') {
          this.setAttribute('aria-disabled', 'true');
          this.#ownsAriaDisabled = true;
        }
      } else if (this.#ownsAriaDisabled) {
        this.removeAttribute('aria-disabled');
        this.#ownsAriaDisabled = false;
      }

      if (this.fieldsetDisabled && !this.href) {
        this.#internals?.states?.add('fieldset-disabled');
      } else {
        this.#internals?.states?.delete('fieldset-disabled');
      }
    }

    if (inactivityChanged || changedProperties.has('focusableWhenDisabled')) {
      if (this.disabled) {
        this.tabIndex = this.focusableWhenDisabled ? 0 : -1;
      } else if (this.#inactive) {
        this.tabIndex = -1;
      } else if (!this.isLink) {
        this.tabIndex = 0;
      }
    }

    // A shortcut shown on the button is announced on it too, unless the
    // author already set their own.
    if (changedProperties.has('shortcut')) {
      const current = this.getAttribute('aria-keyshortcuts');
      const next = this.shortcutAriaKeys;

      if (current === null || current === this.#shortcutAriaKeys) {
        if (next) {
          this.setAttribute('aria-keyshortcuts', next);
        } else {
          this.removeAttribute('aria-keyshortcuts');
        }
        this.#shortcutAriaKeys = next;
      }
    }

    // Only while `toggle` is set: a plain button may carry an `aria-pressed`
    // its owner manages (`craft-button-group` sets one on every child), and
    // overwriting that would be worse than leaving it alone.
    if (
      this.toggle &&
      (changedProperties.has('active') || changedProperties.has('toggle'))
    ) {
      this.setAttribute('aria-pressed', String(this.active));
    }

    if (changedProperties.has('loading')) {
      if (this.loading) {
        this.announceLoading();
      } else if (changedProperties.get('loading')) {
        this.clearLoadingAnnouncement();
      }
    }
  }

  /**
   * "Loading" stays in the live region until loading ends, since the region
   * sits inside the button and some screen readers read it as button content.
   */
  private announceLoading() {
    this.liveRegion.textContent = t('Loading');
  }

  private clearLoadingAnnouncement() {
    this.liveRegion.textContent = '';
  }

  private syncLinkHostState() {
    if (this.isLink) {
      if (!this.linkHostStateApplied) {
        // Capture the caller's intended type before we force "button" for the
        // link, so we can restore it if `href` is later removed.
        this.originalType = this.type;
      }
      this.setAttribute('role', 'presentation');
      // No tabindex at all, rather than -1: a negative tabindex on a shadow
      // host drops its whole shadow tree from sequential focus navigation,
      // so the anchor could never be reached with Tab.
      this.removeAttribute('tabindex');
      this.type = 'button';
      this.linkHostStateApplied = true;
    } else if (this.linkHostStateApplied) {
      this.setAttribute('role', 'button');
      this.type = this.originalType ?? 'button';
      if (!this.disabled) {
        this.tabIndex = 0;
      }
      this.linkHostStateApplied = false;
    }
  }

  /**
   * Reads which slots the light DOM fills, so the icon only gets space beside
   * a label. CSS can't tell: an empty prefix or suffix is still a flex item,
   * and markup whitespace alone still fills the label slot.
   */
  #syncContent(): void {
    const filled = (slot: string | null) =>
      Array.from(this.childNodes).some((node) => {
        if (node instanceof Element) {
          return (node.getAttribute('slot') || null) === slot;
        }

        return (
          slot === null &&
          node.nodeType === Node.TEXT_NODE &&
          !!node.textContent?.trim()
        );
      });

    // Plain text can be trimmed to its capitals; slotted elements (icons,
    // thumbnails) keep their flex layout. Screen-reader-only text is out of flow.
    const textLabel =
      filled(null) &&
      !Array.from(this.children).some(
        (child) => !child.slot && !child.classList.contains('sr-only')
      );

    const content = {
      label: filled(null),
      textLabel,
      prefix: filled('prefix'),
      suffix: filled('suffix'),
    };

    if (
      content.label !== this._content.label ||
      content.textLabel !== this._content.textLabel ||
      content.prefix !== this._content.prefix ||
      content.suffix !== this._content.suffix
    ) {
      this._content = content;
    }
  }

  override async firstUpdated(changedProperties: Map<string, any>) {
    super.firstUpdated(changedProperties);

    await this.updateComplete;

    const childComponents = this.querySelectorAll('craft-icon, craft-spinner');
    await Promise.all(
      Array.from(childComponents).map((child: any) => child.updateComplete)
    );

    this.#checkAccessibleName();
  }

  /**
   * Flags a button with no accessible name. A hidden button has no computable
   * name, so the check waits until it's visible.
   */
  #checkAccessibleName(): void {
    // Removed while the check waited on its icons. Detached, the button is its
    // own root, which can't resolve an `aria-labelledby` ID, and there's no
    // longer anything to flag.
    if (!this.isConnected) {
      return;
    }

    // In link mode the host is role="presentation" (name not computable on
    // it); the real accessible element is the inner anchor.
    const nameTarget = this.isLink
      ? ((this.shadowRoot?.querySelector('a.link') as HTMLElement | null) ??
        this)
      : this;
    this._accessibleName = computeAccessibleName(nameTarget);

    const unnamed = this._accessibleName.trim() === '';

    if (unnamed && !this.#isVisible()) {
      this.#awaitRender();
      return;
    }

    this._hasAccessibilityError = unnamed;
  }

  /**
   * Whether the button can be seen. `visibility: hidden` counts as well as
   * `display: none` -- it's inherited, so it hides the name as surely -- which
   * client rects alone would miss.
   */
  #isVisible(): boolean {
    return typeof this.checkVisibility === 'function'
      ? this.checkVisibility({visibilityProperty: true})
      : this.getClientRects().length > 0;
  }

  #awaitRender(): void {
    if (this.#renderObserver || typeof ResizeObserver === 'undefined') {
      return;
    }

    // Observes size, so a reveal by `visibility` alone isn't rechecked.
    this.#renderObserver = new ResizeObserver(() => {
      if (!this.#isVisible()) {
        return;
      }

      this.#stopAwaitingRender();
      this.#checkAccessibleName();
    });
    this.#renderObserver.observe(this);
  }

  #stopAwaitingRender(): void {
    this.#renderObserver?.disconnect();
    this.#renderObserver = null;
  }

  /**
   * The button's visual style. Defaults to "fill" (neutral fill).
   */
  @property({reflect: true}) variant: ButtonVariant = ButtonVariant.Fill;

  /**
   * Adopt the ambient colorable palette (from a `[data-color]` / colorable
   * ancestor) instead of the neutral palette. Only
   * affects the neutral variants; `primary` and `danger` stay stable.
   */
  @property({reflect: true, type: Boolean}) inherit: boolean = false;

  /**
   * Size of the button. Defaults to "medium".
   *
   * `xsmall` draws below the 24px minimum target size, so it keeps a
   * `--c-size-touch-target-sm` hit area around the smaller visible box.
   */
  @property({reflect: true}) size:
    | 'zero'
    | 'xsmall'
    | 'small'
    | 'medium'
    | 'large' = 'medium';

  /** The value submitted with the form or used for selection in a radio button-group */
  @property({reflect: true}) value: string;

  /** Whether the button is in a selected/active state (e.g. inside a radio button-group) */
  @property({reflect: true, type: Boolean}) active: boolean = false;

  /**
   * Makes the button a toggle: `aria-pressed` follows `active`. The button
   * doesn't change `active` itself; it fires `craft-toggle` for whoever owns it.
   * For an on/off setting, use `craft-switch`.
   */
  @property({type: Boolean, reflect: true}) toggle: boolean = false;

  /** Show a spinner instead of the label */
  @property({reflect: true, type: Boolean}) loading: boolean = false;

  /**
   * Keep the button in the tab order when disabled, and reachable by the
   * pointer, so a tooltip can say why it's disabled.
   */
  @property({
    attribute: 'focusable-when-disabled',
    type: Boolean,
    reflect: true,
  })
  focusableWhenDisabled: boolean = false;

  /**
   * Pulls the button out by the space around its content, so its label or
   * icon lines up with the text beside it. Meant for buttons with no
   * background, like `plain` and `danger-plain`. Present with no value, it applies on every
   * side. Otherwise a space-separated list of `inline`, `block`,
   * `inline-start`, `inline-end`, `block-start` and `block-end`.
   */
  @property({reflect: true}) flush?: string;

  /** Set align-items for the content */
  @property() align: 'start' | 'end' | 'center' = 'center';

  /** Icon to be rendered within the content. */
  @property() icon: string | null = null;

  /** When set, the button renders as a link to this URL. */
  @property({reflect: true}) href: string | null = null;

  /**
   * Anchor target (e.g. "_blank"). Forwarded to the rendered <a>. Unset, a
   * link to another host opens in a new tab.
   */
  @property() target: string | null = null;

  /** Anchor rel. Forwarded to the <a>; "noopener" is added for target="_blank". */
  @property() rel: string | null = null;

  /** Anchor download attribute. Forwarded to the <a>. */
  @property() download: string | null = null;

  /** Position of the icon. Defaults to "prefix" */
  @property({attribute: 'icon-position'}) iconPosition: 'prefix' | 'suffix' =
    'prefix';

  @query('[data-live-region]') protected liveRegion: HTMLElement;

  /**
   * The name the button actually computes to, kept only so a button that ends
   * up nameless can be flagged.
   *
   * Deliberately internal: it records a name, it does not apply one. Exposing
   * it as an attribute invited consumers to "name" a button by setting it,
   * which silenced the warning below while leaving nothing in the DOM for a
   * screen reader. Name an icon-only button with `aria-label` on the host, or
   * with a `label` on the icon it contains.
   */
  @state()
  private _accessibleName: string = '';

  @state()
  private _hasAccessibilityError: boolean = false;

  /**
   * The host's `aria-label`. In link mode the host is presentational, so the
   * inner anchor carries it instead.
   */
  @state()
  private _hostLabel: string | null = null;

  /** Which parts of the content have something in them; see #syncContent. */
  @state()
  private _content = {
    label: false,
    textLabel: false,
    prefix: false,
    suffix: false,
  };

  /** The `aria-keyshortcuts` this button set from its shortcut, if any. */
  #shortcutAriaKeys: string | null = null;

  #contentObserver: MutationObserver | null = null;

  /** Waits for a hidden button to be shown before judging its name. */
  #renderObserver: ResizeObserver | null = null;

  private linkHostStateApplied = false;

  /** The caller's `type` before link mode forced it to "button". */
  private originalType: CraftButton['type'] | null = null;

  private get isLink(): boolean {
    return !!this.href && !this.disabled;
  }

  private get computedTarget(): string | null {
    return linkTarget(this.href, this.target);
  }

  override render() {
    const hasPrefix =
      this._content.prefix || (!!this.icon && this.iconPosition === 'prefix');
    const hasSuffix =
      this._content.suffix || (!!this.icon && this.iconPosition === 'suffix');

    const content = html`
      <div
        class="${classMap({
          'button-content': true,
          'button-content--spaced-prefix': hasPrefix && this._content.label,
          'button-content--spaced-suffix': hasSuffix && this._content.label,
          'button-content--start': this.align === 'start',
          'button-content--end': this.align === 'end',
          'a11y-error': this._hasAccessibilityError,
        })}"
        part="content"
      >
        <slot name="prefix" class="prefix" part="prefix">
          ${this.icon && this.iconPosition === 'prefix'
            ? html`<craft-icon name="${this.icon}"></craft-icon>`
            : nothing}
        </slot>
        <slot
          class="${classMap({
            label: true,
            'label--text': this._content.textLabel,
          })}"
          part="label"
        ></slot>
        <slot name="suffix" class="suffix" part="suffix">
          ${this.icon && this.iconPosition === 'suffix'
            ? html`<craft-icon name="${this.icon}"></craft-icon>`
            : nothing}
        </slot>
        ${this.shortcutKeys
          ? html`<craft-shortcut
              class="shortcut"
              aria-hidden="true"
              ?alt="${this.shortcutKeys.alt}"
              ?shift="${this.shortcutKeys.shift}"
              >${this.shortcutKeys.key}</craft-shortcut
            >`
          : nothing}
      </div>
      ${this.loading
        ? html`<craft-spinner part="spinner"></craft-spinner>`
        : nothing}
      <span class="cp-visually-hidden" role="status" data-live-region></span>
    `;

    if (this.isLink) {
      const target = this.computedTarget;
      const label = this._hostLabel?.trim() || null;
      const cue = !this.target && !!target;

      // A label replaces the anchor's content as its name, cue included, so
      // the cue describes it instead.
      return html`
        <a
          class="link"
          part="link"
          href="${this.href}"
          target="${target ?? nothing}"
          rel="${linkRel(target, this.rel) ?? nothing}"
          download="${this.download ?? nothing}"
          aria-label="${label ?? nothing}"
          aria-describedby="${label && cue ? 'new-tab-cue' : nothing}"
          >${content}${cue
            ? html`<span id="new-tab-cue" class="cp-visually-hidden"
                >${newTabCue()}</span
              >`
            : nothing}</a
        >
      `;
    }

    return content;
  }
}

if (!customElements.get('craft-button')) {
  customElements.define('craft-button', CraftButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-button': CraftButton;
  }
}
