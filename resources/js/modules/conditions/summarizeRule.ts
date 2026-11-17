import {t} from '@craftcms/ui';
import {valueAt} from '@/modules/ui/runtime';
import type {UiNodePayload, UiPayload, UiValue} from '@/modules/ui/types';

/** One cell of a rule's summary: its comparison, or one of its values. */
export interface RuleSummaryPart {
  kind: 'operator' | 'value';
  text: string;
  /** An icon after the text, such as a sort's direction. */
  icon?: string;
}

type Option = {value?: unknown; label?: string; options?: Option[]};

function flatOptions(options: unknown): Option[] {
  return Array.isArray(options)
    ? (options as Option[]).flatMap((option) =>
        option.options ? flatOptions(option.options) : [option]
      )
    : [];
}

function optionLabels(options: unknown, value: UiValue): string[] {
  const values = (Array.isArray(value) ? value : [value]).filter(
    (item) => item !== null && item !== undefined && item !== ''
  );
  const all = flatOptions(options);

  return values.map(
    (item) =>
      all.find((option) => String(option.value) === String(item))?.label ??
      String(item)
  );
}

function formatDate(value: UiValue): string | null {
  const date =
    value && typeof value === 'object' && !Array.isArray(value)
      ? (value as {date?: string}).date
      : typeof value === 'string'
        ? value
        : null;

  if (!date) return null;
  const parsed = new Date(date);

  return Number.isNaN(parsed.getTime())
    ? date
    : parsed.toLocaleDateString(document.documentElement.lang || undefined, {
        dateStyle: 'medium',
      });
}

/** What a control shows, in words, or null when it shows nothing. */
function describe(
  node: UiNodePayload,
  values: UiPayload['values']
): string | null {
  const control = node.control;
  if (!control) return null;

  const value = valueAt(values, control.path);
  const props = control.props as Record<string, unknown>;
  let text: string | null;

  switch (control.component) {
    case 'craft:choice':
    case 'craft:combobox': {
      const labels = optionLabels(props.options, value);
      text = labels.length ? labels.join(', ') : null;
      break;
    }
    case 'craft:element-select': {
      // Like a relation field's preview: the chosen elements, by name.
      const labels = (
        Array.isArray(props.elements)
          ? (props.elements as Array<{label?: string; title?: string}>)
          : []
      )
        .map((element) => element.label ?? element.title ?? '')
        .filter(Boolean);
      text = labels.length ? labels.join(', ') : null;
      break;
    }
    case 'craft:lightswitch':
      text = value ? t('on') : t('off');
      break;
    case 'craft:date':
    case 'craft:date-time':
      text = formatDate(value);
      break;
    default:
      text =
        typeof value === 'string' || typeof value === 'number'
          ? String(value) || null
          : null;
  }

  // Fields the builder labels visibly ("From", "To") keep their label here.
  const field = node.props as {label?: string; labelSrOnly?: boolean};

  return text && field.label && field.labelSrOnly === false
    ? `${field.label} ${text}`
    : text;
}

/**
 * A rule's summary, read off the same UI the condition builder renders: its
 * comparison when the builder offers one, then each value it shows.
 */
export function summarizeRule(ui: UiPayload): RuleSummaryPart[] {
  const parts: RuleSummaryPart[] = [];

  const walk = (nodes: UiNodePayload[]) => {
    for (const node of nodes) {
      const text = describe(node, ui.values);

      if (text) {
        parts.push({
          kind: node.control?.path.at(-1) === 'operator' ? 'operator' : 'value',
          text,
        });
      }

      if (node.children) walk(node.children);
    }
  };

  walk(ui.nodes);
  pluralizeElementType(ui, parts);

  return parts;
}

function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

/**
 * An element type picked right before the elements themselves (Related To)
 * reads as their plural, the way a relation field names what it holds:
 * “Users Ada Lovelace, craftcms”.
 */
function pluralizeElementType(ui: UiPayload, parts: RuleSummaryPart[]): void {
  const nodes = ui.nodes.filter((node) => node.control);

  nodes.forEach((node, index) => {
    const next = nodes[index + 1];
    if (
      node.control?.path.at(-1) !== 'elementType' ||
      next?.control?.component !== 'craft:element-select'
    ) {
      return;
    }

    const plural = (next.control.props as {elementPluralDisplayName?: string})
      .elementPluralDisplayName;
    const label = optionLabels(
      (node.control.props as {options?: unknown}).options,
      valueAt(ui.values, node.control.path)
    )[0];
    const part = parts.find(
      (candidate) => candidate.kind === 'value' && candidate.text === label
    );

    if (part && plural) part.text = capitalize(plural);
  });
}
