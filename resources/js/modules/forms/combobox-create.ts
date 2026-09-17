import type {
  ComboboxItem,
  ComboboxOption,
} from '@craftcms/ui/components/combobox/combobox';
import CraftCombobox from '@craftcms/ui/components/combobox/combobox';
import {property} from 'lit/decorators.js';
import {openSlideout} from '@/common/slideouts';

/**
 * Inserts `option` directly before the trigger option (`value === createValue`) in a flat
 * option list. Optgroups aren't supported — every consumer of this control so far (Tax Zone,
 * Tax Category, …) offers a flat list, so grouping isn't worth the added complexity here; add
 * it if a future caller actually needs it.
 */
function insertBeforeCreateOption(
  options: ComboboxItem[],
  createValue: string,
  option: ComboboxOption
): ComboboxItem[] {
  const index = options.findIndex(
    (item) => item.type !== 'optgroup' && item.value === createValue
  );

  return index === -1
    ? [...options, option]
    : [...options.slice(0, index), option, ...options.slice(index)];
}

/**
 * @summary A `craft-combobox` whose "create a new one" option opens the target resource's own
 * create screen in a slideout instead of selecting a value directly, then appends the result as
 * the new selection once saved. See {@link CraftCms\Cms\Form\Controls\ComboboxCreate} (PHP) for
 * the full contract.
 *
 * @since 1.0
 */
export default class CraftComboboxCreate extends CraftCombobox {
  @property({attribute: 'create-url'}) createUrl = '';

  /** The option value that opens the create slideout instead of being selected directly. */
  @property({attribute: 'create-value'}) createValue = '__add__';

  /** The key the created record is nested under in the save response. */
  @property({attribute: 'result-key'}) resultKey = '';

  /** The created record's field used as the new option's label. */
  @property({attribute: 'label-field'}) labelField = 'name';

  /** The created record's field used as the new option's value. */
  @property({attribute: 'value-field'}) valueField = 'id';

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

  /** Whether the trigger option is (still) among the current selection. */
  private isTriggered(): boolean {
    return this.multipleChoice
      ? Array.isArray(this.modelValue) && this.modelValue.includes(this.createValue)
      : this.modelValue === this.createValue;
  }

  private onModelValueChanged = (event: Event): void => {
    if (
      (event as CustomEvent).detail?.initialize ||
      !this.isTriggered() ||
      this.creating
    ) {
      return;
    }

    event.stopImmediatePropagation();

    if (!this.createUrl) {
      throw new Error('Combobox create URL is required.');
    }

    this.creating = true;

    // Reset back to nothing selected while the slideout is open, rather than leaving the
    // trigger option itself "selected" — for multi-select, that just means dropping the
    // trigger's own value back out of an otherwise-untouched selection.
    queueMicrotask(() => {
      if (this.multipleChoice) {
        const values = Array.isArray(this.modelValue) ? this.modelValue : [];
        this.changeValues(values.filter((value) => value !== this.createValue));
        return;
      }

      this.modelValue = '';
      this._inputNode.value = '';
      this._notifyModelValueChanged();
    });

    void openSlideout(this.createUrl, {
      opener: this,
      onSaved: ({data}) => {
        const record = (data as Record<string, unknown> | undefined)?.[
          this.resultKey
        ] as Record<string, unknown> | undefined;

        if (!record) {
          throw new Error(
            `Combobox create response is missing "${this.resultKey}".`
          );
        }

        const option = {
          label: String(record[this.labelField] ?? ''),
          value: String(record[this.valueField] ?? ''),
        } satisfies ComboboxOption;

        this.options = insertBeforeCreateOption(
          this.options,
          this.createValue,
          option
        );

        void this.updateComplete.then(() => {
          if (this.multipleChoice) {
            // Multi-select's chips render straight off `modelValue`/`options` (see
            // `_inputGroupInputTemplate()`/`valueLabel()`), not off a registered
            // `craft-option` element, so there's no DOM lookup needed here the way
            // single-select's textbox sync requires below.
            const values = Array.isArray(this.modelValue) ? this.modelValue : [];
            this.changeValues([...values, option.value]);
            return;
          }

          const selectedOption = Array.from(
            this._listboxNode.querySelectorAll('craft-option')
          ).find((item) => String(item.choiceValue) === String(option.value));

          if (!selectedOption) {
            throw new Error('Created option was not rendered.');
          }

          this.modelValue = option.value;
          this._setTextboxValue(
            this._getTextboxValueFromOption(selectedOption)
          );
          this._notifyModelValueChanged();
        });
      },
    }).finally(() => {
      this.creating = false;
    });
  };
}

if (!customElements.get('craft-combobox-create')) {
  customElements.define('craft-combobox-create', CraftComboboxCreate);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-combobox-create': CraftComboboxCreate;
  }
}
