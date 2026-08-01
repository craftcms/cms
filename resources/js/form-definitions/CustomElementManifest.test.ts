import {describe, expect, it} from 'vite-plus/test';
import manifest from '@craftcms/ui/custom-elements.json' with {type: 'json'};

type ManifestDeclaration = {
  tagName?: string;
  attributes?: Array<{name: string}>;
};

type CustomElementUsage = {
  tagName: string;
  attributeNames: string[];
  sourcePath: string;
};

const rendererSources = import.meta.glob(
  ['./FormElementRenderer.vue', './renderers/*.vue'],
  {
    eager: true,
    import: 'default',
    query: '?raw',
  }
) as Record<string, string>;

const manifestAttributes = new Map<string, Set<string>>();

for (const module of manifest.modules) {
  for (const declaration of (module.declarations ??
    []) as ManifestDeclaration[]) {
    if (declaration.tagName) {
      manifestAttributes.set(
        declaration.tagName,
        new Set(declaration.attributes?.map(({name}) => name) ?? [])
      );
    }
  }
}

const inheritedControlAttributes = new Set([
  'checked',
  'disabled',
  'label',
  'label-sr-only',
  'max',
  'min',
  'model-value',
  'name',
  'placeholder',
  'readonly',
  'rows',
  'selected',
  'selected-index',
  'step',
  'type',
  'value',
]);

const globalAttributes = new Set([
  'class',
  'id',
  'key',
  'ref',
  'role',
  'slot',
  'style',
  'tabindex',
  'title',
]);

const usages = Object.entries(rendererSources).flatMap(([sourcePath, source]) =>
  customElementUsages(sourcePath, source)
);

describe('Form Definition custom element manifest contract', () => {
  it('declares every custom element used by a core renderer', () => {
    expect(
      usages
        .filter(({tagName}) => !manifestAttributes.has(tagName))
        .map(({sourcePath, tagName}) => `${sourcePath}: <${tagName}>`)
    ).toEqual([]);
  });

  it('declares every component-specific attribute used by a core renderer', () => {
    expect(
      usages.flatMap(({attributeNames, sourcePath, tagName}) =>
        attributeNames
          .filter((attributeName) =>
            isComponentSpecificAttribute(attributeName)
          )
          .filter(
            (attributeName) =>
              !manifestAttributes.get(tagName)?.has(attributeName)
          )
          .map(
            (attributeName) => `${sourcePath}: <${tagName} ${attributeName}>`
          )
      )
    ).toEqual([]);
  });
});

function customElementUsages(
  sourcePath: string,
  source: string
): CustomElementUsage[] {
  return Array.from(
    source.matchAll(/<(craft-[a-z0-9-]+)(\s[^<>]*?)?\/?\s*>/g),
    ([, tagName, rawAttributes]) => ({
      tagName: tagName!,
      attributeNames: attributeNames(rawAttributes ?? ''),
      sourcePath,
    })
  );
}

function attributeNames(rawAttributes: string): string[] {
  const attributesWithoutValues = rawAttributes.replace(
    /=\s*(?:"[^"]*"|'[^']*')/gs,
    ''
  );

  return Array.from(
    attributesWithoutValues.matchAll(/(?:^|\s)([@:.]?[a-z][\w:-]*)/g),
    ([, name]) => {
      const attributeName = name!;

      if (!attributeName.startsWith('.')) {
        return attributeName.replace(/^:/, '');
      }

      return attributeName
        .slice(1)
        .replace(/[A-Z]/g, (character) => `-${character.toLowerCase()}`);
    }
  );
}

function isComponentSpecificAttribute(name: string): boolean {
  return (
    !name.startsWith('@') &&
    !name.startsWith('aria-') &&
    !name.startsWith('data-') &&
    !name.startsWith('v-') &&
    !globalAttributes.has(name) &&
    !inheritedControlAttributes.has(name)
  );
}
