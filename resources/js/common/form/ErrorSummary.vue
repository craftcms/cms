<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {inject, nextTick, onMounted, ref, useId, watch} from 'vue';
  import EmphasizedText from '@/common/components/EmphasizedText.vue';
  import {firstFocusableWithin, revealElement} from '@/common/utils/dom';
  import {fieldId, scopedId, UiFieldIdScope} from '@/modules/ui/runtime';

  const props = withDefaults(
    defineProps<{
      errors: Record<string, string>;
      /**
       * What the screen was trying to do. Settings screens are the default
       * because they were the first caller; an element editor says its own.
       */
      title?: string;
      /**
       * The control paths an error key could belong to, in order. The first
       * one whose field is on the page gets the link. Screens that render a UI
       * payload pass the renderer's own resolution, since a validator key (a
       * field handle, say) can sit deeper in the form than it says.
       */
      errorPaths?: (key: string) => (string[] | null)[];
    }>(),
    {
      title: () => t('Could not save settings'),
      errorPaths: (key: string) => [key.split('.')],
    }
  );

  const root = ref<HTMLElement | null>(null);
  const titleId = `error-summary-${useId()}`;

  const fieldIdScope = inject(UiFieldIdScope, undefined);

  /** The id of the first field on the page that an error's key resolves to. */
  function targetId(key: string): string | null {
    for (const path of props.errorPaths(key)) {
      const id = path && scopedId(fieldIdScope, fieldId(path));
      if (id && document.getElementById(id)) {
        return id;
      }
    }

    return null;
  }

  /** Each linked error's key, with the id of the field it links to. */
  const targets = ref(new Map<string, string>());

  function findLinkedKeys(): void {
    targets.value = new Map(
      Object.keys(props.errors).flatMap((key) => {
        const id = targetId(key);
        return id ? [[key, id] as const] : [];
      })
    );
  }

  /**
   * Shows the field an error belongs to, switching to its tab first if it's on
   * a hidden one, and puts focus in it.
   */
  async function goToField(event: MouseEvent, key: string): Promise<void> {
    const id = targets.value.get(key);
    const field = id ? document.getElementById(id) : null;

    if (!field) {
      return;
    }

    event.preventDefault();
    await revealElement(field, {block: 'center'});

    // The control rather than the first thing in the field, which can be its
    // actions menu.
    const control = firstFocusableWithin(
      field.querySelector<HTMLElement>(':scope > [slot="input"]') ?? field
    );

    if (control) {
      control.focus({preventScroll: true});
    } else {
      field.setAttribute('tabindex', '-1');
      field.focus({preventScroll: true});
    }
  }

  // Mounted once per failed save (the editor clears its errors as it submits),
  // so the failure is announced and in view, wherever the user had scrolled to.
  onMounted(async () => {
    findLinkedKeys();
    await nextTick();
    root.value?.focus();
  });

  watch(
    () => props.errors,
    () => nextTick(findLinkedKeys),
    {deep: true}
  );
</script>

<template>
  <!-- The focus target, as the callout's host has no box of its own to focus. -->
  <div
    ref="root"
    role="group"
    tabindex="-1"
    :aria-labelledby="titleId"
    class="mb-3"
  >
    <craft-callout
      variant="danger"
      rounded="none"
      appearance="fill"
      icon="triangle-exclamation"
    >
      <div :id="titleId" slot="title" class="font-bold">
        {{ title }}
      </div>
      <ul>
        <!-- Deliberately text, not markup: only the `*Name*` emphasis markers
           become elements, as text nodes. A message can carry HTML the server
           composed — the link to another site's validation errors is one — but
           it can also carry content, and Craft encodes before it lets markup
           through (`Html::encodeInvalidTags()`, then Markdown). Rendering these
           raw would put a field's own value into the DOM as HTML.
           `ElementResponse::errorSummary()` already does that work server-side;
           this component should be fed its output rather than re-deriving it. -->
        <li v-for="(error, key) in errors" :key="key">
          <a
            v-if="targets.has(String(key))"
            :href="`#${targets.get(String(key))}`"
            @click="goToField($event, String(key))"
            ><EmphasizedText :text="error"
          /></a>
          <EmphasizedText v-else :text="error" />
        </li>
      </ul>
    </craft-callout>
  </div>
</template>

<style scoped lang="scss">
  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 1px);
    --c-callout-icon-padding-inline: 0 var(--c-spacing-sm);
  }

  a {
    color: inherit;
    text-decoration: underline;
  }

  li :deep(strong) {
    font-weight: 600;
  }
</style>
