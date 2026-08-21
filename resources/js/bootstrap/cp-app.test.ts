import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h} from 'vue';
import {cpComponentRegistry} from './components';
import {installCpApp} from './cp-app';

afterEach(() => {
    document.body.innerHTML = '';
    delete (window as any).Craft;
    vi.restoreAllMocks();
});

it('installs and uninstalls shared CP app behavior', () => {
    const root = document.createElement('div');
    document.body.appendChild(root);
    (window as any).Craft = {};
    const app = createApp({render: () => h('div')});
    const uninstall = vi.spyOn(cpComponentRegistry, 'uninstall');

    installCpApp(app);

    expect(app.config.compilerOptions.isCustomElement?.('craft-button')).toBe(
        true
    );
    expect(app.component('craft:hidden-field')).toBeDefined();

    app.mount(root);
    app.unmount();

    expect(uninstall).toHaveBeenCalledWith(app);
});
