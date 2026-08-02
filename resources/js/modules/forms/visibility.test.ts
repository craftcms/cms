import {describe, expect, it} from 'vite-plus/test';
import {evaluateVisibilityCondition} from './visibility';

type Condition = CraftCms.Cms.Cp.Forms.Data.VisibilityConditionData;

function evaluate(condition: Condition, value: unknown): boolean {
  return evaluateVisibilityCondition(
    condition,
    {settings: {source: value}},
    'settings'
  );
}

describe('Visibility Conditions', () => {
  it.each([
    [
      'equals uses strict equality',
      {name: 'source', operator: 'equals', value: 1},
      1,
      true,
    ],
    [
      'equals normalizes numeric form values',
      {name: 'source', operator: 'equals', value: 1},
      '1',
      true,
    ],
    [
      'equals normalizes checked form values',
      {name: 'source', operator: 'equals', value: true},
      '1',
      true,
    ],
    [
      'equals normalizes empty nullable form values',
      {name: 'source', operator: 'equals', value: null},
      '',
      true,
    ],
    [
      'equals compares arrays strictly by value',
      {name: 'source', operator: 'equals', value: [1, '2']},
      [1, '2'],
      true,
    ],
    [
      'equals rejects incompatible arrays',
      {name: 'source', operator: 'equals', value: [1]},
      {0: 1},
      false,
    ],
    [
      'notEquals compares compatible values',
      {name: 'source', operator: 'notEquals', value: 1},
      2,
      true,
    ],
    [
      'notEquals rejects incompatible values',
      {name: 'source', operator: 'notEquals', value: 1},
      'not a number',
      false,
    ],
    [
      'lessThan orders numbers',
      {name: 'source', operator: 'lessThan', value: 2},
      1,
      true,
    ],
    [
      'lessThan normalizes numeric form values',
      {name: 'source', operator: 'lessThan', value: 2},
      '1',
      true,
    ],
    [
      'lessThanOrEqual includes equality',
      {name: 'source', operator: 'lessThanOrEqual', value: 2},
      2,
      true,
    ],
    [
      'lessThanOrEqual rejects arrays',
      {name: 'source', operator: 'lessThanOrEqual', value: 2},
      [2],
      false,
    ],
    [
      'greaterThan orders numbers',
      {name: 'source', operator: 'greaterThan', value: 1},
      2,
      true,
    ],
    [
      'greaterThan normalizes numeric form values',
      {name: 'source', operator: 'greaterThan', value: 1},
      '2',
      true,
    ],
    [
      'greaterThanOrEqual includes equality',
      {name: 'source', operator: 'greaterThanOrEqual', value: 2},
      2,
      true,
    ],
    [
      'greaterThanOrEqual rejects arrays',
      {name: 'source', operator: 'greaterThanOrEqual', value: 2},
      [2],
      false,
    ],
    [
      'beginsWith ignores case',
      {name: 'source', operator: 'beginsWith', value: 'NE'},
      'News',
      true,
    ],
    [
      'beginsWith rejects arrays',
      {name: 'source', operator: 'beginsWith', value: 'ne'},
      ['news'],
      false,
    ],
    [
      'endsWith ignores case',
      {name: 'source', operator: 'endsWith', value: 'WS'},
      'News',
      true,
    ],
    [
      'endsWith rejects arrays',
      {name: 'source', operator: 'endsWith', value: 'ws'},
      ['news'],
      false,
    ],
    [
      'contains finds text without case',
      {name: 'source', operator: 'contains', value: 'EW'},
      'News',
      true,
    ],
    [
      'contains rejects objects',
      {name: 'source', operator: 'contains', value: 'news'},
      {value: 'news'},
      false,
    ],
    [
      'contains finds strict array members',
      {name: 'source', operator: 'contains', value: 1},
      [1, '2'],
      true,
    ],
    [
      'contains normalizes numeric form values',
      {name: 'source', operator: 'contains', value: 1},
      ['1'],
      true,
    ],
    [
      'in finds a scalar in the expected list',
      {name: 'source', operator: 'in', value: ['news', 1]},
      'news',
      true,
    ],
    [
      'in finds any array member in the expected list',
      {name: 'source', operator: 'in', value: ['news', 1]},
      ['other', '1'],
      true,
    ],
    [
      'in rejects objects',
      {name: 'source', operator: 'in', value: ['news']},
      {value: 'news'},
      false,
    ],
    [
      'notIn negates compatible membership',
      {name: 'source', operator: 'notIn', value: ['news']},
      'other',
      true,
    ],
    [
      'notIn rejects objects',
      {name: 'source', operator: 'notIn', value: ['news']},
      {value: 'other'},
      false,
    ],
    ['empty matches null', {name: 'source', operator: 'empty'}, null, true],
    [
      'empty matches an empty string',
      {name: 'source', operator: 'empty'},
      '',
      true,
    ],
    [
      'empty matches an empty array',
      {name: 'source', operator: 'empty'},
      [],
      true,
    ],
    [
      'empty does not match zero',
      {name: 'source', operator: 'empty'},
      0,
      false,
    ],
    [
      'empty does not match false',
      {name: 'source', operator: 'empty'},
      false,
      false,
    ],
    ['notEmpty matches zero', {name: 'source', operator: 'notEmpty'}, 0, true],
    [
      'notEmpty rejects objects',
      {name: 'source', operator: 'notEmpty'},
      {},
      false,
    ],
    [
      'notEmpty rejects missing values',
      {name: 'missing', operator: 'notEmpty'},
      'ignored',
      false,
    ],
  ] as const)('%s', (_name, condition, value, expected) => {
    expect(evaluate(condition as Condition, value)).toBe(expected);
  });

  it.each([
    [
      'equals',
      {name: 'source', operator: 'equals', value: 1},
      ['not a number', true, null, [], {}, undefined],
    ],
    [
      'notEquals',
      {name: 'source', operator: 'notEquals', value: 1},
      ['not a number', true, null, [], {}, undefined],
    ],
    [
      'lessThan',
      {name: 'source', operator: 'lessThan', value: 1},
      ['', 'not a number', true, null, [], {}, undefined, Number.NaN],
    ],
    [
      'lessThanOrEqual',
      {name: 'source', operator: 'lessThanOrEqual', value: 1},
      ['', 'not a number', true, null, [], {}, undefined, Number.NaN],
    ],
    [
      'greaterThan',
      {name: 'source', operator: 'greaterThan', value: 1},
      ['', 'not a number', true, null, [], {}, undefined, Number.NaN],
    ],
    [
      'greaterThanOrEqual',
      {name: 'source', operator: 'greaterThanOrEqual', value: 1},
      ['', 'not a number', true, null, [], {}, undefined, Number.NaN],
    ],
    [
      'beginsWith',
      {name: 'source', operator: 'beginsWith', value: 'a'},
      [1, true, null, [], {}, undefined],
    ],
    [
      'endsWith',
      {name: 'source', operator: 'endsWith', value: 'a'},
      [1, true, null, [], {}, undefined],
    ],
    [
      'contains',
      {name: 'source', operator: 'contains', value: 'a'},
      [1, true, null, {}, undefined, [{}]],
    ],
    [
      'in',
      {name: 'source', operator: 'in', value: ['a']},
      [{}, undefined, [{}]],
    ],
    [
      'notIn',
      {name: 'source', operator: 'notIn', value: ['a']},
      [{}, undefined, [{}]],
    ],
    ['empty', {name: 'source', operator: 'empty'}, [{}, undefined, [{}]]],
    ['notEmpty', {name: 'source', operator: 'notEmpty'}, [{}, undefined, [{}]]],
  ] as const)(
    '%s rejects every incompatible value shape',
    (_name, condition, values) => {
      for (const value of values) {
        expect(evaluate(condition as Condition, value)).toBe(false);
      }
    }
  );

  it('evaluates explicit nested all and any groups', () => {
    const condition: Condition = {
      all: [
        {name: 'first', operator: 'equals', value: true},
        {
          any: [
            {name: 'second', operator: 'equals', value: 'yes'},
            {name: 'third', operator: 'equals', value: 'yes'},
          ],
        },
      ],
    };

    expect(
      evaluateVisibilityCondition(
        condition,
        {
          settings: {first: true, second: 'no', third: 'yes'},
        },
        'settings'
      )
    ).toBe(true);
  });
});
