import {describe, expect, it} from 'vitest';
import {legacySettingsIslandValues} from './legacy-settings';

describe('legacySettingsIslandValues', () => {
  it('returns no values when the host has no legacy settings island', () => {
    expect(legacySettingsIslandValues(document.createElement('div'))).toBe(
      undefined
    );
  });

  it('returns no values while a legacy settings island has no controls', () => {
    const host = document.createElement('div');
    host.innerHTML = '<craft-legacy-settings-island />';

    expect(legacySettingsIslandValues(host)).toBe(undefined);
  });

  it('projects the live island inputs into native nested values', () => {
    const host = document.createElement('div');
    host.innerHTML = `
      <craft-legacy-settings-island></craft-legacy-settings-island>
      <input name="types[legacy-plugin-field][placeholder]" value="Live value">
      <input name="types[legacy-plugin-field][sources][]" value="section:news">
      <input name="types[legacy-plugin-field][sources][]" value="section:events">
    `;

    expect(legacySettingsIslandValues(host)).toEqual({
      types: {
        'legacy-plugin-field': {
          placeholder: 'Live value',
          sources: ['section:news', 'section:events'],
        },
      },
    });
  });
});
