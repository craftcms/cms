import {describe, expect, test} from 'vitest';
import {toEnvVar, toHandle} from './string';

describe('toHandle', () => {
  describe('camel', () => {
    test.for([
      ['foo', 'FOO'],
      ['fooBar', 'FOO BAR'],
      ['fooBar', 'Fo’o Bar'],
      ['fooBarBaz', 'Foo Ba’r   Baz'],
      ['fooBar', '0 Foo Bar'],
      ['fooBar', 'Foo!Bar'],
      ['fooBar', 'Foo,Bar'],
      ['fooBar', 'Foo/Bar'],
      ['fooBar', 'Foo\\Bar'],
    ])('%s', ([expected, name]: Array<string>) => {
      expect(toHandle(name as string)).toBe(expected as string);
    });
  });

  describe('pascal', () => {
    test.for([
      ['Foo', 'FOO'],
      ['FooBar', 'FOO BAR'],
      ['FooBar', 'Fo’o Bar'],
      ['FooBarBaz', 'Foo Ba’r   Baz'],
      ['FooBar', '0 Foo Bar'],
      ['FooBar', 'Foo!Bar'],
      ['FooBar', 'Foo,Bar'],
      ['FooBar', 'Foo/Bar'],
      ['FooBar', 'Foo\\Bar'],
    ])('%s', ([expected, name]: Array<string>) => {
      expect(toHandle(name as string, {handleCasing: 'pascal'})).toBe(
        expected as string
      );
    });
  });

  describe('snake', () => {
    test.for([
      ['foo', 'FOO'],
      ['foo_bar', 'FOO BAR'],
      ['foo_bar', 'Fo’o Bar'],
      ['foo_bar_baz', 'Foo Ba’r   Baz'],
      ['foo_bar', '0 Foo Bar'],
      ['foo_bar', 'Foo!Bar'],
      ['foo_bar', 'Foo,Bar'],
      ['foo_bar', 'Foo/Bar'],
      ['foo_bar', 'Foo\\Bar'],
    ])('%s', ([expected, name]: Array<string>) => {
      expect(toHandle(name as string, {handleCasing: 'snake'})).toBe(
        expected as string
      );
    });
  });
});

describe('toEnvVar', () => {
  test.for([
    ['$FOO_BAR_URL', 'Foo Bar', {prefix: '$', suffix: '_URL'}],
    ['', '', {prefix: '$', suffix: '_URL'}],
    ['FOO_BAR', 'Foo Bar'],
  ])('%s %s', ([expected, name, options]: Array<any>) => {
    expect(toEnvVar(name as string, options as Record<string, any>)).toBe(
      expected
    );
  });
});
