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

export function handleCreateOption(
  event: CustomEvent,
  combobox: CraftCombobox,
  options: ComboboxItem[],
  previousValue: string | string[],
  saved: (options: ComboboxItem[], value: string | string[]) => void
): boolean {
  const value = combobox.modelValue;
  const triggerValue = Array.isArray(value)
    ? value.find((item) => createOption(options, item))
    : value;
  const trigger = createOption(options, triggerValue ?? '');
  if (!trigger || event.detail?.changeSource === 'input') {
    return false;
  }

  event.stopImmediatePropagation();
  queueMicrotask(() => {
    combobox.modelValue = previousValue;
    combobox.value = '';
    combobox.opened = false;
    const input = combobox.querySelector('input');
    if (input && !Array.isArray(previousValue)) {
      const selected = options
        .flatMap((item) => (item.type === 'optgroup' ? item.options : [item]))
        .find((item) => item.value === previousValue);
      input.value = selected?.label ?? previousValue;
    }
  });

  const {url, resultKey, labelField, valueField} = trigger.data.create;
  void openSlideout(url, {
    opener: combobox,
    onSaved: ({data}) => {
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
      saved(
        insertBefore(options, trigger, option),
        Array.isArray(previousValue)
          ? [...previousValue, option.value]
          : option.value
      );
    },
  });

  return true;
}
