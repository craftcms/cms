import {expect, it} from 'vite-plus/test';

it('registers control dependencies before their custom elements', async () => {
    window.Craft = {} as typeof Craft;

    await import('./element-select-input');
    await import('./field-layout-designer');

    const craft = window.Craft as typeof Craft & {
        ElementThumbLoader: unknown;
        Grid: unknown;
    };

    expect(craft.ElementThumbLoader).toBeTypeOf('function');
    expect(
        () => new (craft.ElementThumbLoader as new () => unknown)()
    ).not.toThrow();
    expect(craft.Grid).toBeTypeOf('function');
});
