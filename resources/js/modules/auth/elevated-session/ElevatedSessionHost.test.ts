import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

const manager = vi.hoisted(() => ({
    state: {
        active: true,
        checking: false,
        loginName: 'admin@example.com',
        alternativeLoginMethods: null,
    },
    cancel: vi.fn(),
    confirm: vi.fn(),
}));

vi.mock('./manager', async () => {
    const {reactive, readonly} = await import('vue');

    return {
        elevatedSessionManager: {
            state: readonly(reactive(manager.state)),
            cancel: manager.cancel,
            confirm: manager.confirm,
        },
    };
});

vi.mock('@craftcms/ui', () => ({
    ConfigService: {
        getInstance: () => ({
            getCpUrl: (path: string) => `https://example.test/admin/${path}`,
            get: (key: string, fallback: unknown) =>
                key === 'useEmailAsUsername' ? true : fallback,
        }),
    },
}));

vi.mock('@/common/components/Modal.vue', () => ({
    default: {
        props: {isActive: Boolean},
        emits: ['close', 'opened'],
        template:
            '<div v-if="isActive"><button class="overlay" @click="$emit(\'close\')">Close</button><slot /></div>',
    },
}));

vi.mock('@/modules/auth/components/login/login-form.js', () => ({}));

// `craft-pane` is a native custom element, so there's nothing to mock: the
// template compiler passes it straight through and its children stay in the
// light DOM where these queries can reach them.

vi.mock('@/common/components/HtmlFragmentRenderer.vue', () => ({
    default: {template: '<div></div>'},
}));

import ElevatedSessionHost from './ElevatedSessionHost.vue';

describe('ElevatedSessionHost', () => {
    let app: ReturnType<typeof createApp>;
    let container: HTMLElement;

    beforeEach(async () => {
        manager.cancel.mockClear();
        manager.confirm.mockClear();
        container = document.createElement('div');
        document.body.append(container);
        app = createApp(ElevatedSessionHost);
        app.mount(container);
        await nextTick();
    });

    afterEach(() => {
        app.unmount();
        container.remove();
    });

    it('renders the dialog surface with its accessible name and description', () => {
        const pane = container.querySelector('craft-pane')!;

        expect(pane).not.toBeNull();
        expect(pane.getAttribute('role')).toBe('dialog');
        expect(pane.getAttribute('aria-modal')).toBe('true');
        expect(pane.getAttribute('aria-labelledby')).toBe(
            'elevated-session-title'
        );
        expect(pane.getAttribute('aria-describedby')).toBe(
            'elevated-session-description'
        );
        expect(pane.querySelector('#elevated-session-title')).not.toBeNull();
        expect(
            pane.querySelector('#elevated-session-description')
        ).not.toBeNull();
    });

    it('renders the server-selected identity and confirms login success', () => {
        const loginForm = container.querySelector('craft-login-form')!;
        const event = new CustomEvent('craft:login:success', {
            bubbles: true,
            cancelable: true,
        });

        loginForm.dispatchEvent(event);

        expect(loginForm.getAttribute('static-email')).toBe(
            'admin@example.com'
        );
        expect(event.defaultPrevented).toBe(true);
        expect(manager.confirm).toHaveBeenCalledOnce();
    });

    it('cancels when the modal requests dismissal', () => {
        container.querySelector<HTMLButtonElement>('.overlay')!.click();

        expect(manager.cancel).toHaveBeenCalledOnce();
    });
});
