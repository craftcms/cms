import {t} from '@craftcms/ui';
import type {SelectItem, SelectOption} from '@/common/types';

function addBooleanData(
  option: SelectOption,
  {
    trueLabel = t('Enabled'),
    falseLabel = t('Disabled'),
  }: {trueLabel?: string; falseLabel?: string} = {}
) {
  const isEnvVar = option.value.startsWith('$') || option.value.startsWith('@');

  return isEnvVar
    ? {
        ...option,
        data: {
          ...option.data,
          hint: option.data?.boolean === '1' ? trueLabel : falseLabel,
          indicator: option.data?.boolean
            ? {
                variant: option.data?.boolean === '1' ? 'success' : 'empty',
              }
            : null,
        },
      }
    : option;
}

export function transformBooleanOptions(
  options: Array<SelectItem>,
  {
    trueLabel = t('Enabled'),
    falseLabel = t('Disabled'),
  }: {trueLabel?: string; falseLabel?: string} = {}
) {
  return options.map((option) => {
    if (option.type === 'optgroup') {
      return {
        ...option,
        options: option.options.map((option) =>
          addBooleanData(option, {trueLabel, falseLabel})
        ),
      };
    }

    return addBooleanData(option, {trueLabel, falseLabel});
  });
}
