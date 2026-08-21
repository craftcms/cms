import type CraftInputElement from '@craftcms/ui/components/input/input';
import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';

describe('CraftInput', () => {
    let app: ReturnType<typeof createApp> | undefined;
    let container: HTMLElement | undefined;

    afterEach(() => {
        app?.unmount();
        container?.remove();
    });

    it('connects optional text-expander triggers to its native input', async () => {
        container = document.createElement('div');
        document.body.append(container);
        app = createApp({
            render: () =>
                h(CraftInput, {
                    modelValue: '',
                    textExpanderTriggers: [
                        {
                            trigger: '$',
                            boundary: 'start',
                            options: [
                                {label: '$SYSTEM_NAME', value: '$SYSTEM_NAME'},
                            ],
                        },
                    ],
                }),
        });
        app.mount(container);
        await nextTick();

        const input =
            container.querySelector<CraftInputElement>('craft-input')!;
        await input.updateComplete;
        const nativeInput = input.querySelector('input')!;
        const expander = container.querySelector('craft-text-expander')!;

        expect(expander.for).toBe(nativeInput.id);
        expect(expander.triggers[0]?.boundary).toBe('start');
    });
});
