import {expect, it} from 'vite-plus/test';
import type {UiNodePayload, UiPayload, UiValues} from '@/modules/ui/types';
import {summarizeRule} from './summarizeRule';

function control(
  path: string[],
  component: string,
  props: Record<string, unknown> = {},
  nodeProps: Record<string, unknown> = {}
): UiNodePayload {
  return {
    type: 'Field',
    component: 'craft:field',
    props: nodeProps,
    control: {path, component, props},
  } as unknown as UiNodePayload;
}

const ui = (nodes: UiNodePayload[], values: UiValues): UiPayload =>
  ({nodes, values}) as unknown as UiPayload;

it('names the comparison, then the chosen options by label', () => {
  const parts = summarizeRule(
    ui(
      [
        control(['operator'], 'craft:choice', {
          options: [{value: 'in', label: 'is one of'}],
        }),
        control(['values'], 'craft:combobox', {
          options: [
            {value: 1, label: 'News'},
            {label: 'More', options: [{value: 2, label: 'Blog'}]},
          ],
        }),
      ],
      {operator: 'in', values: ['1', '2']}
    )
  );

  expect(parts).toEqual([
    {kind: 'operator', text: 'is one of'},
    {kind: 'value', text: 'News, Blog'},
  ]);
});

it('names chosen elements, after their type in the plural', () => {
  const parts = summarizeRule(
    ui(
      [
        control(['elementType'], 'craft:choice', {
          options: [{value: 'Entry', label: 'Entry'}],
        }),
        control(['elementIds'], 'craft:element-select', {
          elements: [{label: 'Home'}, {title: 'About'}],
          elementPluralDisplayName: 'entries',
        }),
      ],
      {elementType: 'Entry', elementIds: [1, 2]}
    )
  );

  expect(parts.map((part) => part.text)).toEqual(['Entries', 'Home, About']);
});

it('keeps a visible field label and skips empty values', () => {
  const parts = summarizeRule(
    ui(
      [
        control(['min'], 'craft:text', {}, {label: 'From', labelSrOnly: false}),
        control(['max'], 'craft:text', {}, {label: 'To', labelSrOnly: false}),
      ],
      {min: '5', max: ''}
    )
  );

  expect(parts).toEqual([{kind: 'value', text: 'From 5'}]);
});
