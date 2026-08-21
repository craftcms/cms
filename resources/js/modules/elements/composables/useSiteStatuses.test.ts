import {useForm} from '@inertiajs/vue3';
import {createApp, defineComponent, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {useSiteStatuses} from './useSiteStatuses';

describe('useSiteStatuses', () => {
    let app: ReturnType<typeof createApp>;
    let container: HTMLElement;

    afterEach(() => {
        app?.unmount();
        container?.remove();
    });

    function mount(
        initial: Record<string, any>
    ): ReturnType<typeof useForm<Record<string, any>>> {
        let form!: ReturnType<typeof useForm<Record<string, any>>>;

        container = document.createElement('div');
        document.body.append(container);
        app = createApp(
            defineComponent({
                setup() {
                    form = useForm<Record<string, any>>(initial);
                    useSiteStatuses(form);

                    return () => null;
                },
            })
        );
        app.mount(container);

        return form;
    }

    it('applies the global switch to every site', async () => {
        const form = mount({
            enabled: true,
            enabledForSite: {'1': true, '2': true},
        });

        form.enabled = false;
        await nextTick();

        expect(form.enabledForSite).toEqual({'1': false, '2': false});
    });

    it('turns the global switch off when every site is off', async () => {
        const form = mount({
            enabled: true,
            enabledForSite: {'1': true, '2': true},
        });

        form.enabledForSite['1'] = false;
        form.enabledForSite['2'] = false;
        await nextTick();

        expect(form.enabled).toBe(false);
    });

    it('marks the global switch indeterminate when sites disagree', async () => {
        const form = mount({
            enabled: true,
            enabledForSite: {'1': true, '2': true},
        });

        form.enabledForSite['2'] = false;
        await nextTick();

        expect(form.enabled).toBe('-');
    });

    it('turns the global switch on when every site is on', async () => {
        const form = mount({
            enabled: '-',
            enabledForSite: {'1': true, '2': false},
        });

        form.enabledForSite['2'] = true;
        await nextTick();

        expect(form.enabled).toBe(true);
    });

    it('leaves the sites alone while the global switch is indeterminate', async () => {
        const form = mount({
            enabled: true,
            enabledForSite: {'1': true, '2': false},
        });

        form.enabled = '-';
        await nextTick();

        expect(form.enabledForSite).toEqual({'1': true, '2': false});
    });

    it('does nothing without per-site switches', async () => {
        const form = mount({enabled: true});

        form.enabled = false;
        await nextTick();

        expect(form.enabled).toBe(false);
    });
});
