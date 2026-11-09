/**
 * A step's mapping as a list of what's mapped, read from the step's settings rather than
 * from the server, so it follows unsaved changes.
 */
import {t} from '@craftcms/ui';
import {getAt, hasMapping, isChecked} from '@/modules/import/mapping/paths';
import {TEMPLATE_RULE, type ImportStep} from '@/modules/import/mapping/types';

/** Destination columns' labels, handles and icons, keyed by their path in the map joined with dots. */
export type DestinationLabels = Record<
  string,
  {label: string; handle: string; icon?: string | null}
>;

export interface SummaryEntry {
  /** The destination's path in the map, joined with dots. */
  key: string;
  label: string | null;
  /** The destination's handle, which its map key isn't when the key is a uid. */
  handle: string;
  icon: string | null;
  kind: 'column' | 'template' | 'nested';
  incoming: string;
  /** The template a destination is filled from, when it's filled from one. */
  template: string | null;
  matchKey: boolean;
  /** Null when there's no rule: for a match key, or a container whose columns each have one. */
  whenEmpty: 'skip' | 'erase' | 'default' | null;
  defaultValue: string | null;
  /** A container's mapped destinations. */
  children: SummaryEntry[];
}

type Tree = Record<string, unknown>;

interface Trees {
  map: Tree;
  matchCriteria: Tree;
  clearableItems: Tree;
  defaultValues: Tree;
  templates: Tree;
}

function treesOf(step: ImportStep): Trees {
  // PHP sends empty settings as `[]`, whose `map` would be `Array.prototype.map`
  const settings = Array.isArray(step.settings) ? {} : (step.settings ?? {});

  return {
    map: (settings.map ?? {}) as Tree,
    matchCriteria: (settings.matchCriteria ?? {}) as Tree,
    clearableItems: (settings.clearableItems ?? {}) as Tree,
    defaultValues: (settings.defaultValues ?? {}) as Tree,
    templates: (settings.templates ?? {}) as Tree,
  };
}

function countMapped(tree: unknown): number {
  return tree !== null && typeof tree === 'object'
    ? Object.values(tree).reduce<number>(
        (total, value) => total + countMapped(value),
        0
      )
    : Number(hasMapping(tree));
}

/** Each mapped destination of the step, with any container's own nested beneath it. */
export function summarizeMapping(
  step: ImportStep,
  labels: DestinationLabels
): SummaryEntry[] {
  const trees = treesOf(step);

  /** The destination at `path` and anything nested in it, or null when nothing in it is mapped. */
  function entryAt(
    path: string[],
    value: unknown,
    fallbackLabel: string | null
  ): SummaryEntry | null {
    const key = path.join('.');
    const known = labels[key];
    const defaultValue = getAt(trees.defaultValues, path);
    const hasDefault =
      typeof defaultValue === 'string' || typeof defaultValue === 'number';
    const criterion = getAt(trees.matchCriteria, path);
    // a container's own criterion is a tree of its nested columns' criteria
    const matchKey = typeof criterion !== 'object' && isChecked(criterion);
    const base = {
      key,
      label: known?.label ?? fallbackLabel,
      handle: known?.handle ?? path[path.length - 1]!,
      icon: known?.icon ?? null,
      template: null,
      matchKey,
      // an empty value matches nothing, so a match key has no When empty rule
      whenEmpty: matchKey
        ? null
        : hasDefault
          ? ('default' as const)
          : isChecked(getAt(trees.clearableItems, path))
            ? ('erase' as const)
            : ('skip' as const),
      defaultValue: hasDefault && !matchKey ? String(defaultValue) : null,
      children: [],
    };

    if (value !== null && typeof value === 'object') {
      const count = countMapped(value);

      return count
        ? {
            ...base,
            kind: 'nested',
            incoming: t(
              '{count, plural, =1{# nested field} other{# nested fields}}',
              {count}
            ),
            whenEmpty: null,
            defaultValue: null,
            children: childEntries(path, value as Tree),
          }
        : null;
    }

    if (!hasMapping(value)) {
      return null;
    }

    if (value === TEMPLATE_RULE) {
      const template = getAt(trees.templates, path);

      return {
        ...base,
        kind: 'template',
        // shown only while the template itself is still empty
        incoming: t('Template'),
        template: typeof template === 'string' ? template : null,
      };
    }

    return {...base, kind: 'column', incoming: String(value)};
  }

  /** A container's mapped destinations: per entry type, its attributes and, under `fields`, its custom fields. */
  function childEntries(path: string[], container: Tree): SummaryEntry[] {
    return Object.entries(container).flatMap(([provider, node]) => {
      // reserved keys, such as `__keep__`, aren't destinations
      if (
        provider.startsWith('__') ||
        node === null ||
        typeof node !== 'object'
      ) {
        return [];
      }

      return Object.entries(node as Tree).flatMap(([key, value]) =>
        key === 'fields' && value !== null && typeof value === 'object'
          ? Object.entries(value as Tree).flatMap(
              ([field, fieldValue]) =>
                entryAt(
                  [...path, provider, 'fields', field],
                  fieldValue,
                  `${provider} › ${field}`
                ) ?? []
            )
          : (entryAt([...path, provider, key], value, `${provider} › ${key}`) ??
            [])
      );
    });
  }

  return Object.entries(trees.map).flatMap(
    ([destination, value]) => entryAt([destination], value, null) ?? []
  );
}
