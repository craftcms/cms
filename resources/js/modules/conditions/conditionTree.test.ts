import {describe, expect, it} from 'vite-plus/test';
import {
  findGroup,
  findRule,
  removeGroup,
  replaceGroup,
  replaceRule,
  rootGroup,
  toggleOperator,
  ungroup,
} from './conditionTree';
import type {ConditionConfig, GroupConfig, RuleConfig} from './types';

const rule = (uid: string): RuleConfig => ({class: 'Title', uid});
const group = (
  operator: 'and' | 'or',
  rules: Array<RuleConfig | GroupConfig>
): GroupConfig => ({operator, rules}) as GroupConfig;
const condition = (
  operator: 'and' | 'or',
  rules: Array<RuleConfig | GroupConfig>
): ConditionConfig => ({
  class: 'EntryCondition',
  conditionRules: group(operator, rules),
});

// a AND (b OR (c AND d))
const nested = () =>
  condition('and', [
    rule('a'),
    group('or', [rule('b'), group('and', [rule('c'), rule('d')])]),
  ]);

describe('rootGroup', () => {
  it('treats a bare rule list as All', () => {
    expect(rootGroup({class: 'C', conditionRules: [rule('a')]})).toEqual({
      operator: 'and',
      rules: [rule('a')],
    });
  });

  it('is empty without a condition', () => {
    expect(rootGroup(null).rules).toEqual([]);
  });
});

describe('findRule and findGroup', () => {
  it('find items at any depth', () => {
    expect(findRule(nested(), 'd')).toEqual(rule('d'));
    expect(findGroup(nested(), [1, 1])?.rules).toEqual([rule('c'), rule('d')]);
  });

  it('find nothing at a path that isn’t a group', () => {
    expect(findGroup(nested(), [0])).toBeUndefined();
    expect(findGroup(nested(), [5])).toBeUndefined();
  });
});

describe('toggleOperator', () => {
  it('flips the top level', () => {
    expect(rootGroup(toggleOperator(nested(), [])).operator).toBe('or');
  });

  it('flips only the nested group at the path', () => {
    const toggled = toggleOperator(nested(), [1, 1]);

    expect(findGroup(toggled, [1, 1])?.operator).toBe('or');
    expect(findGroup(toggled, [1])?.operator).toBe('or');
    expect(rootGroup(toggled).operator).toBe('and');
  });
});

describe('replaceRule', () => {
  it('drops a group left empty by a removal', () => {
    const result = replaceRule(
      condition('and', [rule('a'), group('or', [rule('b')])]),
      'b',
      null
    );

    expect(rootGroup(result).rules).toEqual([rule('a')]);
  });

  it('returns null once nothing is left', () => {
    expect(replaceRule(condition('and', [rule('a')]), 'a', null)).toBeNull();
  });
});

describe('replaceGroup and removeGroup', () => {
  it('replace a nested group in place', () => {
    const result = replaceGroup(nested(), [1, 1], {
      operator: 'or',
      rules: [rule('x')],
    });

    expect(findGroup(result, [1, 1])).toEqual(group('or', [rule('x')]));
    expect(findRule(result, 'c')).toBeUndefined();
  });

  it('remove a group and everything in it', () => {
    const result = removeGroup(nested(), [1]);

    expect(rootGroup(result).rules).toEqual([rule('a')]);
  });

  it('drop a group whose replacement has no rules', () => {
    const result = replaceGroup(nested(), [1, 1], {operator: 'and', rules: []});

    expect(findGroup(result, [1])?.rules).toEqual([rule('b')]);
  });
});

describe('ungroup', () => {
  it('moves a group’s rules into its parent, in its place', () => {
    const result = ungroup(
      condition('and', [
        rule('a'),
        group('or', [rule('b'), rule('c')]),
        rule('d'),
      ]),
      [1]
    );

    expect(rootGroup(result).rules).toEqual([
      rule('a'),
      rule('b'),
      rule('c'),
      rule('d'),
    ]);
  });

  it('works at depth', () => {
    const result = ungroup(nested(), [1, 1]);

    expect(findGroup(result, [1])?.rules).toEqual([
      rule('b'),
      rule('c'),
      rule('d'),
    ]);
  });
});
