import {css, html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {repeat} from 'lit/directives/repeat.js';
import {t} from '@src/utilities/translate.js';
import type CraftCheckbox from '../checkbox/checkbox.js';
import '../button/button.js';
import '../callout/callout.js';
import '../checkbox/checkbox.js';

export interface PermissionTreeItem {
  key: string;
  label: string;
  info?: string | null;
  warning?: string | null;
  nested?: PermissionTreeItems;
}

export type PermissionTreeItems =
  | Record<string, PermissionTreeItem>
  | PermissionTreeItem[];

export interface PermissionTreeGroup {
  handle: string;
  heading: string;
  permissions: Record<string, PermissionTreeItem>;
  keys: string[];
}

let nextTreeId = 0;

/**
 * @summary A grouped tree of selectable permissions.
 * @fires model-value-changed - Fired when the selected permissions change.
 *
 * `model-value-changed` is Lion's own protocol name, not one this package
 * chose. It is kept because Lion's form system dispatches and listens for it;
 * a Craft-prefixed alias would only add a second name for the same thing.
 */
export default class CraftPermissionTree extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }

    :host([hidden]) {
      display: none;
    }

    .group + .group {
      margin-block-start: var(--c-spacing-lg);
    }

    .heading {
      display: flex;
      align-items: center;
      gap: var(--c-spacing-sm);
    }

    h3 {
      margin: 0;
      font-size: var(--c-font-size-lg);
    }

    ul {
      margin-block: var(--c-spacing-sm);
      padding: 0;
      list-style: none;
    }

    craft-checkbox.indented {
      position: relative;
      --gap-x: calc((var(--permission-level) * 1lh) + var(--c-spacing-md));
    }

    craft-checkbox.indented::before {
      content: '';
      position: absolute;
      inset-block-start: calc(1lh / 2);
      inset-inline-start: calc(
        var(--c-size-control-2xs) + (var(--c-spacing) * 2)
      );
      width: calc(var(--gap-x) - (var(--c-spacing) * 3.5));
      height: 1px;
      background-color: var(--c-color-neutral-border-quiet);
    }
  `;

  /** Permission groups. */
  @property({type: Array}) groups: PermissionTreeGroup[] = [];

  /** Directly selected permission keys. */
  @property({type: Array, attribute: 'model-value'}) modelValue: string[] = [];

  /** Inherited permission keys, displayed as selected and disabled. */
  @property({type: Array, attribute: 'locked-permissions'})
  lockedPermissions: string[] = [];

  /** Native form field name. */
  @property() name = '';

  /** Prevents permission changes. */
  @property({type: Boolean, reflect: true}) disabled = false;

  readonly #treeId = ++nextTreeId;

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (
      changedProperties.has('modelValue') ||
      changedProperties.has('groups') ||
      changedProperties.has('name') ||
      changedProperties.has('disabled')
    ) {
      this.#syncHiddenInputs();
    }
  }

  override render() {
    const locked = this.#lockedPermissions();

    return repeat(
      this.groups,
      (group) => group.handle,
      (group, index) => this.#renderGroup(group, index, locked)
    );
  }

  #renderGroup(group: PermissionTreeGroup, index: number, locked: Set<string>) {
    const headingId = `permission-tree-heading-${this.#treeId}-${index}`;

    return html`
      <section class="group">
        ${group.heading
          ? html`
              <div class="heading">
                <h3 id=${headingId}>${group.heading}</h3>
                <craft-button
                  type="button"
                  size="small"
                  variant="plain"
                  aria-describedby=${headingId}
                  ?disabled=${this.disabled}
                  @click=${() => this.#toggleAll(group.keys, locked)}
                >
                  ${this.#allSelected(group.keys, locked)
                    ? t('Deselect all')
                    : t('Select all')}
                </craft-button>
              </div>
            `
          : nothing}
        ${this.#renderPermissions(group.permissions, 0, this.disabled, locked)}
      </section>
    `;
  }

  #renderPermissions(
    permissions: PermissionTreeItems,
    level: number,
    disabled: boolean,
    locked: Set<string>
  ): ReturnType<typeof repeat> {
    return repeat(
      Object.entries(permissions),
      ([key]) => key,
      ([key, permission]) => {
        const permissionKey = permission.key || key;
        const selected = this.#isSelected(permissionKey, locked);
        const nested = permission.nested ?? {};

        return html`
          <ul>
            <li>
              <craft-checkbox
                class=${level > 0 ? 'indented' : ''}
                style=${`--permission-level: ${level}`}
                .label=${permission.label}
                .choiceValue=${permissionKey}
                .checked=${selected}
                .disabled=${disabled || this.#isLocked(permissionKey, locked)}
                @model-value-changed=${(event: Event) =>
                  this.#setPermissionSelected(
                    event,
                    permissionKey,
                    nested,
                    locked
                  )}
              >
                ${permission.info || permission.warning
                  ? html`
                      <div slot="help-text">
                        ${permission.info ?? nothing}
                        ${permission.warning
                          ? html`
                              <craft-callout
                                variant="warning"
                                appearance="plain"
                                inline
                              >
                                ${permission.warning}
                              </craft-callout>
                            `
                          : nothing}
                      </div>
                    `
                  : nothing}
              </craft-checkbox>

              ${Object.keys(nested).length
                ? this.#renderPermissions(
                    nested,
                    level + 1,
                    disabled || !selected,
                    locked
                  )
                : nothing}
            </li>
          </ul>
        `;
      }
    );
  }

  #lockedPermissions() {
    return new Set(
      this.lockedPermissions.map((permission) => permission.toLowerCase())
    );
  }

  #isLocked(permission: string, locked: Set<string>) {
    return locked.has(permission.toLowerCase());
  }

  #isSelected(permission: string, locked: Set<string>) {
    return (
      this.modelValue.includes(permission) || this.#isLocked(permission, locked)
    );
  }

  #allSelected(permissionKeys: string[], locked: Set<string>) {
    const selectable = permissionKeys.filter(
      (permission) => !this.#isLocked(permission, locked)
    );

    return (
      selectable.length > 0 &&
      selectable.every((permission) => this.modelValue.includes(permission))
    );
  }

  #toggleAll(permissionKeys: string[], locked: Set<string>) {
    const selectable = permissionKeys.filter(
      (permission) => !this.#isLocked(permission, locked)
    );

    if (this.#allSelected(permissionKeys, locked)) {
      const removed = new Set(selectable);
      this.#setModelValue(
        this.modelValue.filter((permission) => !removed.has(permission))
      );
      return;
    }

    this.#setModelValue([...new Set([...this.modelValue, ...selectable])]);
  }

  #setPermissionSelected(
    event: Event,
    permission: string,
    nested: PermissionTreeItems,
    locked: Set<string>
  ) {
    event.stopPropagation();

    if (this.disabled || this.#isLocked(permission, locked)) {
      return;
    }

    const selected = (event.currentTarget as CraftCheckbox).checked;
    const index = this.modelValue.indexOf(permission);

    if (selected && index === -1) {
      this.#setModelValue([...this.modelValue, permission]);
      return;
    }

    if (selected || index === -1) {
      return;
    }

    const removed = new Set([permission, ...this.#nestedKeys(nested)]);
    this.#setModelValue(this.modelValue.filter((value) => !removed.has(value)));
  }

  #nestedKeys(permissions: PermissionTreeItems): string[] {
    return Object.values(permissions).flatMap((permission) => [
      permission.key,
      ...this.#nestedKeys(permission.nested ?? {}),
    ]);
  }

  #setModelValue(modelValue: string[]) {
    this.modelValue = modelValue;
    this.dispatchEvent(
      new CustomEvent('model-value-changed', {
        bubbles: true,
        composed: true,
        detail: {isTriggeredByUser: true},
      })
    );
  }

  #syncHiddenInputs() {
    this.querySelectorAll('[data-permission-tree-input]').forEach((input) =>
      input.remove()
    );

    if (!this.name || this.disabled) {
      return;
    }

    this.#appendHiddenInput(this.name, '');
    const permissionKeys = new Set(this.groups.flatMap((group) => group.keys));

    for (const permission of this.modelValue) {
      if (permissionKeys.has(permission)) {
        this.#appendHiddenInput(`${this.name}[]`, permission);
      }
    }
  }

  #appendHiddenInput(name: string, value: string) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    input.dataset.permissionTreeInput = '';
    this.append(input);
  }
}

if (!customElements.get('craft-permission-tree')) {
  customElements.define('craft-permission-tree', CraftPermissionTree);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-permission-tree': CraftPermissionTree;
  }
}
