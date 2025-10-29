import {Cookies} from './cookies.js';
import {beforeEach, describe, expect, test} from 'vitest';

describe('Cookies', () => {
  beforeEach(() => {
    // Clear cookies before each test
    document.cookie.split(';').forEach((cookie) => {
      const eqPos = cookie.indexOf('=');
      const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
      document.cookie = `${name.trim()}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
    });
  });

  type SetCookieArgs = [string, string, object | undefined, string];
  test.for([
    ['test', 'not-test', undefined, 'Craft:test=not-test'],
    [
      'encoded',
      'https://some-value.com',
      undefined,
      'Craft:encoded=https%3A%2F%2Fsome-value.com',
    ],
    [
      'custom-prefixed',
      'not-test',
      {prefix: 'custom-'},
      'custom-:custom-prefixed=not-test',
    ],
    ['max-age', 'test', {maxAge: 600}, 'Craft:max-age=test'],
    ['expired', 'test', {expires: new Date('2023-01-01')}, ''],
    ['domain', 'another-domain', {domain: 'example.com'}, ''],
    ['path', 'another-path', {domain: '/another-path'}, ''],
  ] as SetCookieArgs[])(
    'set(%s, %s, %s)',
    ([name, value, options, expected]) => {
      const cookies = new Cookies(options);
      cookies.set(name, value);
      expect(document.cookie).toBe(expected);
    }
  );

  test('gets a cookie', () => {
    document.cookie = 'Craft:test=test';
    const cookies = new Cookies();
    expect(cookies.get('test')).toBe('test');
  });

  test('removes a cookie', () => {
    const cookies = new Cookies();
    cookies.set('bad-cookie', 'test');
    cookies.remove('bad-cookie');
    expect(document.cookie).not.toContain('Craft:bad-cookie=test');
  });
});
