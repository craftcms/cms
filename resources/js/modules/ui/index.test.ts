import $ from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';

afterEach(() => {
    vi.unstubAllGlobals();
    vi.resetModules();
});

it('creates text inputs without the legacy Garnish behavior', async () => {
    vi.stubGlobal('$', $);
    vi.stubGlobal('Craft', {ui: {}});

    await import('./index');

    const input = (window.Craft as any).ui.createTextInput({
        name: 'label',
        value: 'Option',
    })[0];

    expect(input).toBeInstanceOf(HTMLInputElement);
    expect(input.name).toBe('label');
    expect(input.value).toBe('Option');
});
