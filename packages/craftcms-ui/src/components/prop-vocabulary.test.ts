import {existsSync, readFileSync} from 'node:fs';
import {join} from 'node:path';
import {describe, expect, it} from 'vite-plus/test';
import {sizes} from '@src/constants/size';
import {variants} from '@src/constants/variants';

/**
 * Holds every element to the prop vocabulary in `.ai/rules/craftcms-ui.md`.
 * Reads the built manifest, like `manifest-coverage.test.ts`.
 */

const MANIFEST = join(import.meta.dirname, '../../dist/custom-elements.json');

interface Attribute {
  name: string;
  type?: {text?: string};
}

interface Declaration {
  tagName?: string;
  customElement?: boolean;
  attributes?: Attribute[];
}

const manifest = existsSync(MANIFEST)
  ? (JSON.parse(readFileSync(MANIFEST, 'utf8')) as {
      modules: {declarations?: Declaration[]}[];
    })
  : null;

const elements = (manifest?.modules ?? [])
  .flatMap((module) => module.declarations ?? [])
  .filter((declaration) => declaration.customElement && declaration.tagName);

const attributes = elements.flatMap((element) =>
  (element.attributes ?? []).map((attribute) => ({
    tag: element.tagName!,
    name: attribute.name,
    type: attribute.type?.text ?? '',
  }))
);

const literals = (type: string) =>
  [...type.matchAll(/'([^']*)'/g)].map((match) => match[1] ?? '');

/** Names with a vocabulary word that replaces them. */
const REPLACED: Record<string, string> = {
  small: 'size="small"',
  fill: 'color',
  'bg-color': '<region>-color',
  busy: 'loading, or a name for what is in flight',
  pending: 'indeterminate',
};

/** `variant` means something other than a semantic color on these. */
const VARIANT_EXCEPTIONS = new Set([
  'craft-button',
  'craft-copy-button',
  'craft-badge-indicator',
  'craft-pane',
  'craft-icon',
  'craft-reorder-button',
]);

describe('the prop vocabulary', () => {
  it('reads attributes from the manifest', () => {
    expect(attributes.length).toBeGreaterThan(200);
  });

  it('sizes every element on one scale', () => {
    const offScale = attributes
      .filter((attribute) => attribute.name === 'size')
      .flatMap(({tag, type}) => {
        const values = literals(type);

        if (values.length === 0) {
          return /SizeValue/.test(type) ? [] : [`${tag}: ${type}`];
        }

        return values
          .filter((value) => !(sizes as string[]).includes(value))
          .map((value) => `${tag}: ${value}`);
      });

    expect(offScale).toEqual([]);
  });

  it('keeps variant for semantic color', () => {
    const offVocabulary = attributes
      .filter(
        ({tag, name}) => name === 'variant' && !VARIANT_EXCEPTIONS.has(tag)
      )
      .flatMap(({tag, type}) => {
        if (literals(type).length === 0) {
          return /VariantValue/.test(type) ? [] : [`${tag}: ${type}`];
        }

        return literals(type)
          .filter((value) => !(variants as string[]).includes(value))
          .map((value) => `${tag}: ${value}`);
      });

    expect(offVocabulary).toEqual([]);
  });

  it('names a boolean for what turning it on does', () => {
    const negated = attributes
      .filter(({name}) => name.startsWith('no-'))
      .map(({tag, name}) => `${tag}: ${name}`);

    expect(negated).toEqual([]);
  });

  it('uses the vocabulary word instead of a synonym', () => {
    const replaced = attributes
      .filter(({name}) => name in REPLACED)
      .map(({tag, name}) => `${tag}: ${name} → ${REPLACED[name]}`);

    expect(replaced).toEqual([]);
  });
});
