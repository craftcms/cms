import {expect, it} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import CpLink from './CpLink.vue';

it('renders a linked custom navigation item with its content', async () => {
    const container = document.createElement('div');
    document.body.append(container);
    const app = createApp({
        render: () =>
            h(
                CpLink,
                {
                    as: 'craft-nav-item',
                    href: '/settings/assets',
                    active: true,
                    flush: true,
                    block: true,
                    icon: 'image',
                },
                {
                    default: () => [
                        'Volumes',
                        h('craft-nav-list', {slot: 'subnav'}),
                    ],
                }
            ),
    });

    app.mount(container);
    await nextTick();

    const link = container.querySelector(':scope > craft-nav-item');

    expect(link?.getAttribute('href')).toBe('/settings/assets');
    expect(link?.hasAttribute('active')).toBe(true);
    expect(link?.hasAttribute('flush')).toBe(true);
    expect(link?.hasAttribute('block')).toBe(true);
    expect(link?.getAttribute('icon')).toBe('image');
    expect(link?.textContent).toContain('Volumes');
    expect(link?.querySelector(':scope > craft-nav-list')).not.toBeNull();

    app.unmount();
    container.remove();
});
