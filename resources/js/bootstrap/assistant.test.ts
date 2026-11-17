import {describe, expect, it} from 'vite-plus/test';
import {createAssistantApi} from './assistant';

describe('createAssistantApi', () => {
  it('refuses a second text expander for a trigger that’s taken', () => {
    const api = createAssistantApi();
    api.registerTextExpander({id: 'test:first', trigger: '#', label: 'First'});

    expect(() =>
      api.registerTextExpander({
        id: 'test:second',
        trigger: '#',
        label: 'Second',
      })
    ).toThrow('trigger already taken by test:first');
  });
});
