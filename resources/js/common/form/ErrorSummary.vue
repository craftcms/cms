<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {nextTick, onMounted, ref, useId, watch} from 'vue';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import {fieldId} from '@/modules/ui/runtime';

  const props = withDefaults(
    defineProps<{
      errors: Record<string, string>;
      /**
       * What the screen was trying to do. Settings screens are the default
       * because they were the first caller; an element editor says its own.
       */
      title?: string;
    }>(),
    {title: () => t('Could not save settings')}
  );

  const root = ref<HTMLElement | null>(null);
  const titleId = `error-summary-${useId()}`;

  /** The error keys whose field is on the page, which get a link to it. */
  const linkedKeys = ref(new Set<string>());

  /** An error's key is its control's path, which names the field wrapping it. */
  function targetId(key: string): string {
    return fieldId(key.split('.'));
  }

  function findLinkedKeys(): void {
    linkedKeys.value = new Set(
      Object.keys(props.errors).filter((key) =>
        document.getElementById(targetId(key))
      )
    );
  }

  /**
   * Shows the field an error belongs to, switching to its tab first if it's on
   * a hidden one, and puts focus in it.
   */
  function goToField(event: MouseEvent, key: string): void {
    const field = document.getElementById(targetId(key));

    if (!field) {
      return;
    }

    event.preventDefault();

    const panel = field.closest<HTMLElement>('[role="tabpanel"]');

    if (panel && (panel.hidden || panel.classList.contains('hidden'))) {
      document
        .querySelector<HTMLElement>(
          `[role="tab"][aria-controls="${CSS.escape(panel.id)}"]`
        )
        ?.click();
    }

    // The control rather than the first thing in the field, which can be its
    // actions menu.
    const control = firstFocusableWithin(
      field.querySelector<HTMLElement>(':scope > [slot="input"]') ?? field
    );

    if (control) {
      control.focus();
    } else {
      field.setAttribute('tabindex', '-1');
      field.focus();
    }

    field.scrollIntoView({block: 'center'});
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
        <!-- Deliberately text, not markup. A message can carry HTML the server
           composed — the link to another site's validation errors is one — but
           it can also carry content, and Craft encodes before it lets markup
           through (`Html::encodeInvalidTags()`, then Markdown). Rendering these
           raw would put a field's own value into the DOM as HTML.
           `ElementResponse::errorSummary()` already does that work server-side;
           this component should be fed its output rather than re-deriving it. -->
        <li v-for="(error, key) in errors" :key="key">
          <a
            v-if="linkedKeys.has(String(key))"
            :href="`#${targetId(String(key))}`"
            @click="goToField($event, String(key))"
            >{{ error }}</a
          >
          <template v-else>{{ error }}</template>
        </li>
      </ul>
    </craft-callout>
  </div>
</template>

<style scoped lang="scss">
  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 4px);
  }

  a {
    color: inherit;
    text-decoration: underline;
  }
</style>
