<script setup lang="ts">
  import {appendElementHtml, serializeFormInputsAsObject} from '@craftcms/ui';
  import {
    computed,
    inject,
    nextTick,
    onMounted,
    shallowRef,
    useTemplateRef,
  } from 'vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';

  import {useIsSlideout} from '@/common/composables/screen';
  import {
    LegacyEditorContextKey,
    type ElementEditor,
    type LegacyEditorPayload,
  } from './element-editor-context';

  const props = defineProps<{editor: ElementEditor; region: string}>();
  const context = inject(LegacyEditorContextKey)!;
  const isSlideout = useIsSlideout();
  const payload = computed(() => props.editor.props as LegacyEditorPayload);
  const html = computed(() =>
    props.region === 'content'
      ? payload.value.editorContentHtml
      : payload.value.editorSidebarHtml
  );
  const root = useTemplateRef<HTMLElement>('root');
  const formTarget = shallowRef<HTMLElement | null>(null);

  async function render(
    fragment: CraftCms.Cms.View.HtmlFragment,
    container: HTMLElement,
    active: () => boolean
  ) {
    formTarget.value = null;
    await nextTick();
    const dispose = await appendElementHtml(fragment.html, container);

    if (active()) {
      formTarget.value = container.querySelector('[data-element-editor-form]');
      await nextTick();
    }

    return dispose;
  }

  function publish(initial = false): void {
    if (!root.value || html.value == null) {
      return;
    }

    const values = serializeFormInputsAsObject(root.value, formTarget.value);

    for (const name of Object.keys(values)) {
      if (
        name
          .split(/[[\]]/)
          .some((part) =>
            ['__proto__', 'prototype', 'constructor'].includes(part)
          )
      ) {
        delete values[name];
      }
    }

    context.publish(props.editor, props.region, values, initial);
  }

  function ready(): void {
    publish(true);
    context.ready(props.editor, props.region);
  }

  onMounted(() => {
    if (html.value == null) {
      context.ready(props.editor, props.region);
    }
  });
</script>

<template>
  <div
    ref="root"
    :id="
      region === 'content' && !isSlideout
        ? payload.editorContainerId
        : undefined
    "
    @input="publish()"
    @change="publish()"
  >
    <slot v-if="html == null" />
    <template v-else>
      <HtmlFragmentRenderer
        :fragment="{html, headHtml: '', bodyHtml: ''}"
        :render="render"
        @ready="ready"
      />
      <Teleport v-if="formTarget" :to="formTarget">
        <slot />
      </Teleport>
    </template>
  </div>
</template>
