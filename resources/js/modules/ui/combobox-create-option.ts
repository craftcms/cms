import type CraftCombobox from '@craftcms/ui/components/combobox/combobox';
import type {
  ComboboxItem,
  ComboboxOption,
} from '@craftcms/ui/components/combobox/combobox';
import {openSlideout} from '@/common/slideouts';

type CreateSettings = {
  url: string;
  resultKey: string;
  labelField: string;
  valueField: string;
};

type CreateOption = ComboboxOption & {
  data: {create: CreateSettings};
};

function createOption(
  items: ComboboxItem[],
  value: string
): CreateOption | undefined {
  for (const item of items) {
    const options = item.type === 'optgroup' ? item.options : [item];
    const option = options.find(
      (option) => option.value === value && option.data?.create
    );
    if (option) {
      return option as CreateOption;
    }
  }
}

function insertBefore(
  items: ComboboxItem[],
  trigger: CreateOption,
  created: ComboboxOption
): ComboboxItem[] {
  return items.flatMap<ComboboxItem>((item) => {
    if (item.type === 'optgroup') {
      return [
        {
          ...item,
          options: insertBefore(
            item.options,
            trigger,
            created
          ) as ComboboxOption[],
        },
      ];
    }

    return item.value === trigger.value ? [created, item] : [item];
  });
}

/**
 * Opens the slideout for a "create" option when one was just picked, and puts
 * the previous value back. Call it from the combobox's `input` listener; the
 * value is restored before the combobox commits, so no `change` follows.
 * Typing the option's label doesn't pick it.
 */
export function handleCreateOption(
  event: Event,
  combobox: CraftCombobox,
  options: ComboboxItem[],
  previousValue: string | string[],
  saved: (options: ComboboxItem[], value: string | string[]) => void
): boolean {
  if (
    !(event instanceof InputEvent) ||
    event.inputType !== 'insertReplacementText'
  ) {
    return false;
  }

  const value = combobox.value;
  const triggerValue = Array.isArray(value)
    ? value.find((item) => createOption(options, item))
    : value;
  const trigger = createOption(options, triggerValue ?? '');
  if (!trigger) {
    return false;
  }

  combobox.value = previousValue;
  combobox.opened = false;

  const {url, resultKey, labelField, valueField} = trigger.data.create;
  void openSlideout(url, {
    opener: combobox.querySelector('input') ?? combobox,
    onSaved: ({data, draft}) => {
      if (draft) {
        return;
      }

      const record = (data as Record<string, unknown> | undefined)?.[
        resultKey
      ] as Record<string, unknown> | undefined;
      if (!record) {
        throw new Error(`Combobox create response is missing "${resultKey}".`);
      }

      const label = record[labelField];
      const value = record[valueField];
      if (
        (typeof label !== 'string' && typeof label !== 'number') ||
        (typeof value !== 'string' && typeof value !== 'number')
      ) {
        throw new Error(
          `Combobox create response has invalid fields for "${resultKey}".`
        );
      }

      const option = {
        label: String(label),
        value: String(value),
      };
      const createdOptions = insertBefore(options, trigger, option);
      const selectedValue = Array.isArray(previousValue)
        ? [...new Set([...previousValue, option.value])]
        : option.value;

      combobox.options = createdOptions;
      combobox.value = selectedValue;
      saved(createdOptions, selectedValue);
    },
  });

  return true;
}
