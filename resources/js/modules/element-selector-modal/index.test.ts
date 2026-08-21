import {afterEach, expect, it, vi} from 'vite-plus/test';

afterEach(() => {
    vi.unstubAllGlobals();
    vi.resetModules();
});

it('loads without the legacy selector modal registry', async () => {
    vi.stubGlobal('Craft', {});

    await expect(import('./index')).resolves.toBeDefined();
});
