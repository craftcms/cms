import {toHandle} from '@craftcms/ui/utilities/string';
import type {FormValue, FormValues} from '../types';

export type Autopopulation = {source: string; target: string};

export function useAutopopulation() {
  const edited = new Map<string, Set<string>>();

  function update(
    identity: string,
    row: FormValues,
    key: string,
    value: FormValue,
    relationships: Autopopulation[]
  ): void {
    const previous = row[key];
    row[key] = value;
    const manual = edited.get(identity) ?? new Set<string>();
    edited.set(identity, manual);

    for (const {source, target} of relationships) {
      if (target === key) manual.add(target);
      if (source !== key || manual.has(target) || typeof value !== 'string')
        continue;

      const generated = toHandle(typeof previous === 'string' ? previous : '', {
        allowNonAlphaStart: true,
      });
      if (row[target] && row[target] !== generated) {
        manual.add(target);
        continue;
      }
      row[target] = toHandle(value, {allowNonAlphaStart: true});
    }
  }

  return {update, forget: (identity: string) => edited.delete(identity)};
}
