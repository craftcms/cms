<script setup lang="ts">
  import {
    appendBodyHtml,
    appendElementHtml,
    appendHeadHtml,
    type AppendHtmlDisposer,
  } from '@craftcms/cp';
  import {onBeforeUnmount, ref, watch} from 'vue';

  const props = defineProps<{
    fragment?: CraftCms.Cms.View.HtmlFragment | null;
  }>();

  const emit = defineEmits<{
    rendered: [element: HTMLElement];
  }>();

  const container = ref<HTMLElement | null>(null);
  const disposers: AppendHtmlDisposer[] = [];
  let lastKey = '';
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
        disposeAll();

        return;
      }

      if (key === lastKey) {
        return;
      }

      runId++;
      const currentRunId = runId;
      lastKey = key;
      disposeAll();

      if (
        headHtml &&
        !(await remember(appendHeadHtml(headHtml), currentRunId))
      ) {
        return;
      }

      if (
        html &&
        !(await remember(appendElementHtml(html, element), currentRunId))
      ) {
        return;
      }

      if (
        bodyHtml &&
        !(await remember(appendBodyHtml(bodyHtml), currentRunId))
      ) {
        return;
      }

      if (currentRunId === runId) {
        emit('rendered', element);
      }
    },
    {immediate: true}
  );

  onBeforeUnmount(() => {
    runId++;
    disposeAll();
  });
</script>

<template>
  <div v-if="fragment" ref="container"></div>
</template>
