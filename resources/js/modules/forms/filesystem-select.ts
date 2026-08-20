import type {
  ComboboxItem,
  ComboboxOption,
} from '@craftcms/ui/components/combobox/combobox';
import CraftCombobox from '@craftcms/ui/components/combobox/combobox';
import {property} from 'lit/decorators.js';
import {CpScreenSlideout} from '@/modules/slideout/cp-screen-slideout';

export default class CraftFilesystemSelect extends CraftCombobox {
  @property({type: String, attribute: 'create-url'}) createUrl = '';

  private creating = false;
  private listener?: AbortController;

  override connectedCallback(): void {
    super.connectedCallback();
    this.listener?.abort();
    this.listener = new AbortController();
    this.addEventListener('model-value-changed', this.onModelValueChanged, {
      signal: this.listener.signal,
    });
  }

  override disconnectedCallback(): void {
    this.listener?.abort();
    super.disconnectedCallback();
  }

  private onModelValueChanged = (event: Event): void => {
    if (
      (event as CustomEvent).detail?.initialize ||
      this.modelValue !== '__add__' ||
      this.creating
    ) {
      return;
    }

    event.stopImmediatePropagation();

    if (!this.createUrl) {
      throw new Error('Filesystem select create URL is required.');
    }

    this.creating = true;

    queueMicrotask(() => {
      this.modelValue = '';
      this._inputNode.value = '';
    });

    const slideout = new CpScreenSlideout(this.createUrl, {
      onSubmit: ({data}: {data: {name: string; handle: string}}) => {
        const option = {
          label: data.name,
          value: data.handle,
          data: {hint: data.handle},
        } satisfies ComboboxOption;

        this.options = this.options.map((item) =>
          insertBeforeCreateOption(item, option)
        );
        void this.updateComplete.then(() => {
          this.modelValue = option.value;
        });
      },
    });
    slideout.on('close', () => {
      this.creating = false;
    });
  };
}

function insertBeforeCreateOption(
  item: ComboboxItem,
  option: ComboboxOption
): ComboboxItem {
  if (item.type !== 'optgroup') {
    return item;
  }

  const actionIndex = item.options.findIndex(({value}) => value === '__add__');

  return actionIndex === -1
    ? item
    : {
        ...item,
        options: [
          ...item.options.slice(0, actionIndex),
          option,
          ...item.options.slice(actionIndex),
        ],
      };
}

if (!customElements.get('craft-filesystem-select')) {
  customElements.define('craft-filesystem-select', CraftFilesystemSelect);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-filesystem-select': CraftFilesystemSelect;
  }
}
