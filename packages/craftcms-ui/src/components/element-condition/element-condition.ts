import {css, html, LitElement, nothing, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import {appendBodyHtml, appendHeadHtml} from '../../utilities/dom.js';
import {t} from '../../utilities/translate.js';
import '../spinner/spinner.js';

export type ElementConditionValue = Record<string, unknown> & {
  class: string;
  conditionRules?: Array<Record<string, unknown>>;
};

type RuntimeWindow = Window & {
  Craft?: {
    _actionHeaders?(): Record<string, string>;
    initUiElements?(container: HTMLElement): void;
  };
  htmx?: {process(container: HTMLElement): void};
};

const objectConverter = {
  fromAttribute(value: string | null): Record<string, unknown> {
    return value ? JSON.parse(value) : {};
  },
};

const booleanConverter = {
  fromAttribute(value: string | null): boolean {
    return value !== 'false';
  },
  toAttribute(value: boolean): string {
    return String(value);
  },
};

/**
 * @summary Hosts or remotely renders an element condition builder.
 *
 * @slot - Server-rendered condition builder controls.
 *
 * @event value-changed - Emitted when the serialized condition value changes.
 */
export default class CraftElementCondition extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }

    .error {
      color: var(--c-color-text-error, #cf1124);
    }
  `;

  /** Base input name used by the condition builder. */
  @property({reflect: true}) name: string | null = null;

  /** Condition implementation class rendered by the server. */
  @property({attribute: 'condition-class', reflect: true})
  conditionClass: string | null = null;

  /** Condition-specific builder configuration. */
  @property({attribute: 'builder-config', converter: objectConverter})
  builderConfig: Record<string, unknown> = {};

  /** URL that renders an initially empty condition builder. */
  @property({attribute: 'render-url', reflect: true})
  renderUrl: string | null = null;

  /** Whether condition rules can be reordered. */
  @property({converter: booleanConverter, reflect: true}) sortable = true;

  /** Label shown by the add-rule button. */
  @property({attribute: 'add-rule-label', reflect: true})
  addRuleLabel: string | null = null;

  /** Current serialized condition configuration. */
  @property({attribute: false}) value: ElementConditionValue | null = null;

  /** Prevents condition changes. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  @state() private loading = false;
  @state() private error: string | null = null;

  private abortController?: AbortController;
  private syncQueued = false;
  private lightDomObserver = new MutationObserver(() => {
    this.applyReadOnly();
    this.queueValueSync();
  });
  private disabledControls = new WeakSet<HTMLElement>();

  override connectedCallback(): void {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
    }

    this.addEventListener('click', this.preventReadOnly, true);
    this.addEventListener('input', this.handleBuilderChange);
    this.addEventListener('change', this.handleBuilderChange);
  }

  override disconnectedCallback(): void {
    this.abortController?.abort();
    this.lightDomObserver.disconnect();
    this.removeEventListener('click', this.preventReadOnly, true);
    this.removeEventListener('input', this.handleBuilderChange);
    this.removeEventListener('change', this.handleBuilderChange);
    super.disconnectedCallback();
  }

  protected override firstUpdated(): void {
    if (this.children.length > 0) {
      this.initializeBuilder();

      return;
    }

    void this.renderBuilder();
  }

  protected override updated(changedProperties: PropertyValues<this>): void {
    super.updated(changedProperties);

    if (!changedProperties.has('readOnly')) {
      return;
    }

    if (this.readOnly) {
      this.setAttribute('aria-disabled', 'true');
    } else {
      this.removeAttribute('aria-disabled');
    }

    this.applyReadOnly();
  }

  /** Initializes server-rendered controls already present in the light DOM. */
  initialize(): void {
    this.initializeBuilder();
  }

  private async renderBuilder(): Promise<void> {
    try {
      if (!this.renderUrl || !this.name || !this.conditionClass) {
        throw new Error(
          'Element condition rendering requires renderUrl, name, and conditionClass.'
        );
      }

      this.loading = true;
      this.error = null;
      this.abortController = new AbortController();
      const runtimeWindow = window as RuntimeWindow;
      const response = await fetch(this.renderUrl, {
        method: 'POST',
        headers: {
          Accept: 'text/html',
          'HX-Request': 'true',
          ...runtimeWindow.Craft?._actionHeaders?.(),
        },
        body: this.builderRequest(),
        signal: this.abortController.signal,
      });

      if (!response.ok) {
        throw new Error(
          `Condition builder request failed (${response.status}).`
        );
      }

      const template = document.createElement('template');
      template.innerHTML = await response.text();

      for (const head of template.content.querySelectorAll<HTMLTemplateElement>(
        'template.hx-head-html'
      )) {
        await appendHeadHtml(head.innerHTML);
        head.remove();
      }

      for (const body of template.content.querySelectorAll<HTMLTemplateElement>(
        'template.hx-body-html'
      )) {
        await appendBodyHtml(body.innerHTML);
        body.remove();
      }

      this.replaceChildren(template.content);
      this.initializeBuilder();
    } catch (exception) {
      if (
        exception instanceof DOMException &&
        exception.name === 'AbortError'
      ) {
        return;
      }

      const reason =
        exception instanceof Error
          ? exception.message
          : t('Unknown condition builder error.');

      this.error = t(
        'Element Condition option “conditionRules” could not be rendered for Form output: {reason}',
        {reason}
      );
    } finally {
      this.loading = false;
    }
  }

  private builderRequest(): FormData {
    const request = new FormData();
    const condition = objectValue(this.value);
    const conditionRules = Array.isArray(condition.conditionRules)
      ? condition.conditionRules
      : [];

    request.append(
      'config',
      JSON.stringify({
        ...this.builderConfig,
        class: this.conditionClass,
        id: this.id,
        name: this.name,
        mainTag: 'div',
        sortable: this.sortable,
        forProjectConfig: true,
        addRuleLabel: this.addRuleLabel,
      })
    );
    request.append(
      `${this.name}[class]`,
      String(condition.class ?? this.conditionClass)
    );
    request.append(`${this.name}[config]`, JSON.stringify(this.builderConfig));
    appendFormValue(request, `${this.name}[conditionRules]`, conditionRules);

    return request;
  }

  private initializeBuilder(): void {
    const runtimeWindow = window as RuntimeWindow;

    runtimeWindow.htmx?.process(this);
    runtimeWindow.Craft?.initUiElements?.(this);
    this.applyReadOnly();
    this.lightDomObserver.disconnect();
    this.lightDomObserver.observe(this, {childList: true, subtree: true});
  }

  private handleBuilderChange = (event: Event): void => {
    if (event.target !== this) {
      this.queueValueSync();
    }
  };

  private queueValueSync(): void {
    if (this.readOnly || this.syncQueued) {
      return;
    }

    this.syncQueued = true;
    queueMicrotask(() => {
      this.syncQueued = false;
      this.syncValue();
    });
  }

  private syncValue(): void {
    const hostForm = this.closest('form');

    if (!hostForm) {
      throw new Error(
        'Element Condition components must be rendered within a form.'
      );
    }

    if (!this.name) {
      throw new Error('Element Condition components require a name.');
    }

    const condition = valueAt(
      expandFormData(new FormData(hostForm)),
      htmlNameToPath(this.name)
    );

    if (!isConditionValue(condition)) {
      this.value = null;
    } else {
      if (Array.isArray(condition.conditionRules)) {
        condition.conditionRules = condition.conditionRules.filter(
          (rule): rule is Record<string, unknown> =>
            rule !== null && typeof rule === 'object' && !Array.isArray(rule)
        );
      }

      this.value = condition.conditionRules?.length ? condition : null;
    }

    this.dispatchEvent(
      new CustomEvent('value-changed', {bubbles: true, composed: true})
    );
  }

  private applyReadOnly(): void {
    for (const control of this.querySelectorAll<
      HTMLElement & {disabled: boolean}
    >('button, input, select, textarea, craft-action-menu, craft-button')) {
      if (this.readOnly) {
        if (!control.disabled) {
          this.disabledControls.add(control);
          control.disabled = true;
          control.setAttribute('disabled', '');
        }

        continue;
      }

      if (this.disabledControls.delete(control)) {
        control.disabled = false;
        control.removeAttribute('disabled');
      }
    }
  }

  private preventReadOnly = (event: Event): void => {
    if (!this.readOnly) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
  };

  protected override render() {
    return html`
      ${this.loading ? html`<craft-spinner></craft-spinner>` : nothing}
      ${this.error
        ? html`<p class="error" role="alert">${this.error}</p>`
        : nothing}
      <slot></slot>
    `;
  }
}

function appendFormValue(
  formData: FormData,
  name: string,
  value: unknown
): void {
  if (Array.isArray(value)) {
    value.forEach((item, index) =>
      appendFormValue(formData, `${name}[${index}]`, item)
    );

    return;
  }

  if (value !== null && typeof value === 'object') {
    Object.entries(value).forEach(([key, item]) =>
      appendFormValue(formData, `${name}[${key}]`, item)
    );

    return;
  }

  formData.append(name, value == null ? '' : String(value));
}

function expandFormData(data: FormData): Record<string, unknown> {
  const expanded: Record<string, unknown> = {};

  for (const [postKey, value] of data.entries()) {
    const match = postKey.match(/^([^[]+)(\[.*)?/);

    if (!match?.[1]) {
      continue;
    }

    const keys = match[2]
      ? (match[2].match(/\[[^[\]]*\]/g) ?? []).map((key) => key.slice(1, -1))
      : [];
    keys.unshift(match[1]);
    let parent: Record<string, unknown> | unknown[] = expanded;

    keys.forEach((key, index) => {
      const container = parent as Record<string, unknown>;

      if (index === keys.length - 1) {
        container[key || String((parent as unknown[]).length)] = value;

        return;
      }

      if (typeof container[key] !== 'object' || container[key] === null) {
        const nextKey = keys[index + 1];
        container[key] =
          !nextKey || Number.parseInt(nextKey) === Number(nextKey) ? [] : {};
      }

      parent = container[key] as Record<string, unknown> | unknown[];
    });
  }

  return expanded;
}

function valueAt(value: unknown, path: string): unknown {
  return path.split('.').reduce<unknown>((current, segment) => {
    if (typeof current !== 'object' || current === null) {
      return undefined;
    }

    return (current as Record<string, unknown>)[segment];
  }, value);
}

function htmlNameToPath(name: string): string {
  return name.replaceAll('[', '.').replaceAll(']', '');
}

function objectValue(value: unknown): Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : {};
}

function isConditionValue(value: unknown): value is ElementConditionValue {
  return typeof objectValue(value).class === 'string';
}

if (!customElements.get('craft-element-condition')) {
  customElements.define('craft-element-condition', CraftElementCondition);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-element-condition': CraftElementCondition;
  }
}
