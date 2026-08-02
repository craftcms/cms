import {serializeFormInputs} from '@craftcms/ui';
import {expandFormData} from '@/common/utils/forms';
import type {FormValues} from './types';

export function legacySettingsIslandValues(
  host: HTMLElement | null
): FormValues | undefined {
  if (!host?.querySelector('craft-legacy-settings-island')) {
    return undefined;
  }

  const formData = new FormData();

  for (const [name, value] of new URLSearchParams(serializeFormInputs(host))) {
    formData.append(name, value);
  }

  const values = expandFormData(formData);

  if (
    typeof values.types !== 'object' ||
    values.types === null ||
    Array.isArray(values.types)
  ) {
    return undefined;
  }

  return values;
}
