import {describe, expect, it} from 'vite-plus/test';
import {formatInputId, namespaceId, namespaceInputName} from './namespace';
import {removeLeft, removeRight, trim} from './strings';

describe('namespacing', () => {
  it.each([
    ['title', 'fields', 'fields[title]'],
    ['entries[0][title]', 'fields', 'fields[entries][0][title]'],
    ['title', undefined, 'title'],
  ])('namespaces the input name %s in %s', (name, namespace, expected) => {
    expect(namespaceInputName(name, namespace)).toBe(expected);
  });

  it.each([
    ['title', 'fields', 'fields-title'],
    ['entries[0][title]', null, 'entries-0-title'],
    ['123abc', null, 'abc'],
    ['fields[a b]', 'settings[x]', 'settings-x-fields-a-b'],
  ])('builds an ID from %s in %s', (id, namespace, expected) => {
    expect(namespaceId(id, namespace)).toBe(expected);
  });

  it('falls back to a random ID when nothing usable is left', () => {
    expect(formatInputId('123')).toMatch(/^[A-Za-z0-9]{10}$/);
  });
});

describe('trimming', () => {
  it('trims the given characters off both ends', () => {
    expect(trim('\n  pasted\r\n', ' \n\r')).toBe('pasted');
  });

  it('removes a prefix case-insensitively when asked', () => {
    expect(removeLeft('HTTPS://craftcms.com', 'https://', true)).toBe(
      'craftcms.com'
    );
    expect(removeLeft('HTTPS://craftcms.com', 'https://')).toBe(
      'HTTPS://craftcms.com'
    );
    expect(removeRight('craftcms.com/', '/')).toBe('craftcms.com');
  });
});
