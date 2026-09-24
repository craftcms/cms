import {describe, expect, test} from 'vite-plus/test';

import {defaultTrueBoolean, jsonAttribute} from './converters';

describe('defaultTrueBoolean', () => {
  test('is on when the attribute is absent', () => {
    expect(defaultTrueBoolean.fromAttribute!(null, Boolean)).toBe(true);
  });

  test('is on when the attribute is present but empty', () => {
    expect(defaultTrueBoolean.fromAttribute!('', Boolean)).toBe(true);
  });

  test('is off only for the literal string "false"', () => {
    expect(defaultTrueBoolean.fromAttribute!('false', Boolean)).toBe(false);
    expect(defaultTrueBoolean.fromAttribute!('0', Boolean)).toBe(true);
    expect(defaultTrueBoolean.fromAttribute!('no', Boolean)).toBe(true);
  });

  test('serialises as a string both ways, so it survives a round trip', () => {
    expect(defaultTrueBoolean.toAttribute!(false, Boolean)).toBe('false');
    expect(
      defaultTrueBoolean.fromAttribute!(
        defaultTrueBoolean.toAttribute!(false, Boolean) as string,
        Boolean
      )
    ).toBe(false);
  });
});

describe('jsonAttribute', () => {
  const converter = jsonAttribute<string[]>(() => []);

  test('parses a JSON attribute', () => {
    expect(converter.fromAttribute!('["a","b"]', Array)).toEqual(['a', 'b']);
  });

  test('falls back for an absent attribute', () => {
    expect(converter.fromAttribute!(null, Array)).toEqual([]);
  });

  test('falls back for an empty attribute rather than throwing', () => {
    expect(() => converter.fromAttribute!('', Array)).not.toThrow();
    expect(converter.fromAttribute!('', Array)).toEqual([]);
  });

  test('gives each host its own fallback instance', () => {
    const first = converter.fromAttribute!(null, Array) as string[];
    first.push('mutated');
    expect(converter.fromAttribute!(null, Array)).toEqual([]);
  });

  test('serialises back to JSON', () => {
    expect(converter.toAttribute!(['a'], Array)).toBe('["a"]');
  });
});
