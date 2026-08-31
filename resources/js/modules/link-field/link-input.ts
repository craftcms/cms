import {
  Base,
  type GarnishBaseSettings,
  type GarnishEvent,
} from '@craftcms/garnish';
import {
  createTextInput as buildTextInput,
  createCopyTextPrompt,
} from '@craftcms/ui/factory';
import {resolveElement, type ElementArg} from '@/common/utils/dom';
import {jq} from '@/common/utils/jquery';
import punycode from 'punycode/';
import type {LinkField} from './link-field';

declare const Craft: any;

interface LinkInputSettings extends GarnishBaseSettings {
  prefixes: string[] | null;
  pattern: string | null;
  inputName?: string;
  inputAttributes?: Record<
    string,
    string | number | boolean | null | undefined
  >;
  textInputAttributes?: Record<
    string,
    string | number | boolean | null | undefined
  >;
}

/**
 * LinkInput — a port of `Craft.LinkInput` onto `@craftcms/garnish` `Base`. The
 * inner control for a text-based link type (URL/email/tel …): it toggles
 * between a text input (for editing) and a chip (for a committed value),
 * validating the value against the type's pattern and prefixes.
 *
 * jQuery-free, but carries a few documented seams: the chip's action menu is
 * built through `Craft.addActionsToChip` (the smart `craft-action-menu` helper,
 * still in the cp bundle — same seam component-select uses), and the paired
 * {@link LinkField} instance is read via the legacy `.data('linkField')` cache.
 * The text input and copy prompt come from `@craftcms/ui/factory`. Booted from
 * `BaseTextLinkType.php`.
 */
export class LinkInput extends Base<LinkInputSettings> {
  static defaults: LinkInputSettings = {
    prefixes: null,
    pattern: null,
    textInputAttributes: {},
  };

  container: HTMLElement | null = null;

  #field: LinkField | null = null;
  #chip: HTMLElement | null = null;
  #textInput: HTMLInputElement | null = null;
  #hiddenInput: HTMLInputElement | null = null;

  constructor(container?: ElementArg, settings?: Partial<LinkInputSettings>) {
    super();
    if (new.target === LinkInput) {
      this.init(container ?? null, settings);
    }
  }

  init(container: ElementArg, settings?: Partial<LinkInputSettings>): void {
    this.container = resolveElement(container);
    if (!this.container) {
      return;
    }
    this.setSettings(settings, LinkInput.defaults);

    const $ = jq();
    // Legacy self-reference + read the paired LinkField, both via `.data`.
    $?.(this.container).data('linkInput', this);
    this.#field =
      $?.(this.container)
        .closest('[data-link-field]')
        .parent()
        .data('linkField') ?? null;

    this.#chip = this.container.querySelector<HTMLElement>(':scope > .chip');
    this.#textInput =
      this.container.querySelector<HTMLInputElement>(':scope > .text');
    this.#hiddenInput = this.container.querySelector<HTMLInputElement>(
      ':scope > input[type=hidden]'
    );

    if (this.#chip) {
      this.initChip();
    } else {
      this.initTextInput();
    }

    this.addListener(this.container, 'click', (ev: GarnishEvent) => {
      const target = ev.target;
      if (
        this.#chip &&
        target instanceof HTMLElement &&
        !['A', 'BUTTON'].includes(target.nodeName)
      ) {
        this.switchToTextInput();
        this.#textInput?.focus();
      }
    });
  }

  hasPrefix(value: string): boolean {
    value = value.toLowerCase();
    for (const prefix of this.settings!.prefixes ?? []) {
      if (Craft.startsWith(value, prefix, true)) {
        return true;
      }
    }
    return false;
  }

  ensurePrefix(value: string): string {
    const prefixes = this.settings!.prefixes ?? [];
    if (prefixes.length && !this.hasPrefix(value)) {
      return prefixes[0] + value;
    }
    return value;
  }

  removePrefix(value: string): string {
    for (const prefix of this.settings!.prefixes ?? []) {
      value = Craft.removeLeft(value, prefix, true);
    }
    return value;
  }

  removeFirstPrefix(value: string): string {
    const prefixes = this.settings!.prefixes ?? [];
    if (prefixes.length) {
      return Craft.removeLeft(value, prefixes[0], true);
    }
    return value;
  }

  createChip(value: string): void {
    let label = this.removePrefix(value);
    if (label.match(/^[^/]+\/$/)) {
      label = Craft.removeRight(label, '/');
    }

    this.reset();

    const chip = document.createElement('div');
    chip.className = 'chip chromeless';
    const content = document.createElement('div');
    content.className = 'chip-content';
    chip.append(content);

    const link = document.createElement('a');
    link.href = value.replace(/ /g, '+');
    link.rel = 'noopener';
    link.target = '_blank';
    link.className = 'truncate';
    link.textContent = label;
    content.append(link);

    // Empty actions container — `Craft.addActionsToChip` builds a
    // `<craft-action-menu>` inside it.
    const actions = document.createElement('div');
    actions.className = 'chip-actions';
    content.append(actions);

    this.container!.prepend(chip);
    this.#chip = chip;

    this.initChip();
  }

  createTextInput(value: string): void {
    this.reset();
    const input = buildTextInput({
      ...this.settings!.inputAttributes,
      name: this.settings!.inputName,
      value,
    });
    this.#textInput = input;
    this.container!.prepend(input);
    this.initTextInput();
    input.dispatchEvent(new Event('input'));
  }

  switchToTextInput(): void {
    // only remove the first prefix, if set; otherwise the wrong prefix will get added back.
    const value = this.removeFirstPrefix(this.#hiddenInput?.value ?? '');
    this.createTextInput(value);
  }

  initTextInput(): void {
    if (!this.#textInput) {
      return;
    }

    this.addListener(this.#textInput, 'input', () => {
      const value = this.normalize(this.#textInput!.value);
      if (this.#hiddenInput) {
        this.#hiddenInput.value = value;
      }
      this.#field?.updateLabel(this.removePrefix(value));
    });

    this.addListener(this.#textInput, 'blur', () => {
      this.maybeSwitchToChip();
    });

    this.addListener(this.#textInput, 'keydown', (ev: GarnishEvent) => {
      if (
        ev instanceof KeyboardEvent &&
        ev.key === 'Escape' &&
        this.maybeSwitchToChip()
      ) {
        ev.stopPropagation();
        this.#chip?.querySelector('a')?.focus();
      }
    });
  }

  normalize(value: string): string {
    value = Craft.trim(value);
    if (!value) {
      return '';
    }
    const prefixed = this.ensurePrefix(value);
    return this.validate(prefixed) ? prefixed : value;
  }

  validate(value: string): boolean {
    value = punycode.toASCII(value);
    return !!value.match(new RegExp(this.settings!.pattern ?? '', 'i'));
  }

  maybeSwitchToChip(): boolean {
    if (!this.#textInput) {
      return false;
    }

    const value = this.normalize(this.#textInput.value);
    if (value && this.validate(value)) {
      this.createChip(value);
      return true;
    }

    return false;
  }

  initChip(): void {
    if (!this.#chip) {
      return;
    }

    // Descriptors for `Craft.addActionsToChip`: it prefers/builds a
    // `<craft-action-menu>` and auto-groups the `destructive` item, so no manual
    // divider is needed (icons are name strings resolved by craft-action-item).
    const actions = [
      {
        icon: 'share',
        label: Craft.t('app', 'View in a new tab'),
        onActivate: () => {
          const href = this.#chip?.querySelector('a')?.getAttribute('href');
          if (href) {
            window.open(href);
          }
        },
      },
      {
        icon: 'pencil',
        label: Craft.t('app', 'Edit'),
        onActivate: () => {
          this.switchToTextInput();
          this.#textInput?.focus();
        },
      },
      {
        icon: 'link',
        label: Craft.t('app', 'Copy URL'),
        onActivate: () => {
          createCopyTextPrompt({
            label: 'Full URL',
            value: (this.#hiddenInput?.value ?? '').replace(/ /g, '+'),
          });
        },
      },
      {
        icon: 'xmark',
        label: Craft.t('app', 'Remove'),
        destructive: true,
        onActivate: () => {
          this.createTextInput('');
          this.#textInput?.focus();
        },
      },
    ];

    Craft.addActionsToChip(this.#chip, actions);
  }

  reset(): void {
    this.#textInput?.remove();
    this.#chip?.remove();
    this.#textInput = null;
    this.#chip = null;
  }
}
