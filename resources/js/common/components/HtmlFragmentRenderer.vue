<script setup lang="ts">
    import {
        appendBodyHtml,
        appendElementHtml,
        appendHeadHtml,
        type AppendHtmlDisposer,
    } from '@craftcms/ui';
    import {onBeforeUnmount, ref, watch} from 'vue';

    const props = withDefaults(
        defineProps<{
            fragment?: CraftCms.Cms.View.HtmlFragment | null;
            /** Tag name for the container element. */
            as?: string;
        }>(),
        {
            fragment: null,
            as: 'div',
        }
    );

    const emit = defineEmits<{
        /** The fragment — assets included — is in the document. */
        (e: 'ready', element: HTMLElement): void;
    }>();

    const container = ref<HTMLElement | null>(null);
    const disposers: AppendHtmlDisposer[] = [];
    let lastKey = '';
    let lastElement: HTMLElement | null = null;
    let runId = 0;

    const disposeAll = () => {
        while (disposers.length) {
            disposers.pop()?.();
        }
    };

    const remember = async (
        promise: Promise<AppendHtmlDisposer>,
        currentRunId: number
    ): Promise<boolean> => {
        const dispose = await promise;

        if (currentRunId !== runId) {
            dispose();

            return false;
        }

        disposers.push(dispose);

        return true;
    };

    watch(
        () => ({
            element: container.value,
            html: props.fragment?.html ?? '',
            headHtml: props.fragment?.headHtml ?? '',
            bodyHtml: props.fragment?.bodyHtml ?? '',
        }),
        async ({element, html, headHtml, bodyHtml}) => {
            const key = `${headHtml}\u0000${html}\u0000${bodyHtml}`;

            if (!element || key === '\u0000\u0000') {
                runId++;
                lastKey = '';
                lastElement = null;
                disposeAll();

                return;
            }

            // Element identity matters too: a runtime `as` change swaps in a fresh
            // empty container that needs the same fragment re-appended.
            if (key === lastKey && element === lastElement) {
                return;
            }

            runId++;
            const currentRunId = runId;
            lastKey = key;
            lastElement = element;
            disposeAll();

            if (
                headHtml &&
                !(await remember(appendHeadHtml(headHtml), currentRunId))
            ) {
                return;
            }

            if (
                html &&
                !(await remember(
                    appendElementHtml(html, element),
                    currentRunId
                ))
            ) {
                return;
            }

            if (
                bodyHtml &&
                !(await remember(appendBodyHtml(bodyHtml), currentRunId))
            ) {
                return;
            }

            // Upgrade legacy UI elements (lightswitches, field toggles, menus, …)
            // the same way CpScreenSlideout does after injecting fragment content.
            if (html) {
                (window as any).Craft?.initUiElements?.(element);
            }

            emit('ready', element);
        },
        {immediate: true}
    );

    onBeforeUnmount(() => {
        runId++;
        disposeAll();
    });
</script>

<template>
    <component :is="as" v-if="fragment" ref="container" />
</template>
