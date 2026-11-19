<script setup lang="ts">
  import {
    appendBodyHtml,
    appendHeadHtml,
    claimEscape,
    isEscapeClaimed,
    t,
  } from '@craftcms/ui';
  import {useHttp, usePage} from '@inertiajs/vue3';
  import {
    computed,
    inject,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    useTemplateRef,
  } from 'vue';
  import {onClickOutside, onKeyStroke, useElementBounding} from '@vueuse/core';
  import ElementIndexController from '@actions/Elements/ElementIndex/ElementIndexController';
  import ConditionBuilder from '@/modules/conditions/ConditionBuilder.vue';
  import type {
    BuilderPayload,
    ConditionConfig,
  } from '@/modules/conditions/types';
  import type {SourceItem} from '@/modules/elements/types/sources';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {elementIndexContextKey} from '@/modules/elements/index/index-context';
  import PaneActions from './PaneActions.vue';

  /**
   * A condition builder hung from a control in the element index, which edits
   * a draft and hands it back on Apply.
   */
  const props = withDefaults(
    defineProps<{
      anchor: HTMLElement;
      /** The condition the builder starts from. */
      condition: ConditionConfig | null;
      /** Names the builder's inputs. */
      builderId: string;
      label: string;
      /** Shows `label` as a heading with a close button. */
      titled?: boolean;
      /** Which edge of the anchor the panel lines up with. */
      align?: 'start' | 'end';
      /**
       * `all` edits a whole condition, `group` one nested group, and `rule` a
       * lone rule, without the group around it.
       */
      scope?: 'all' | 'group' | 'rule';
      clearLabel?: string;
    }>(),
    {align: 'start', scope: 'all'}
  );

  const emit = defineEmits<{
    close: [];
    apply: [draft: ConditionConfig];
    clear: [];
  }>();

  const panel = useTemplateRef<HTMLElement>('panel');
  const {left, right, bottom} = useElementBounding(() => props.anchor);
  const width = computed(() => (props.scope === 'all' ? '40rem' : '34rem'));
  const position = computed(() => {
    const top = bottom.value + 6;
    const edge =
      props.align === 'end'
        ? {right: `max(16px, calc(100vw - ${right.value}px))`}
        : {
            left: `max(16px, min(${left.value}px, calc(100vw - 16px - min(${width.value}, 100vw - 32px))))`,
          };

    return {
      ...edge,
      top: `${top}px`,
      width: `min(${width.value}, calc(100vw - 32px))`,
      '--c-pane-max-block-size': `calc(100vh - ${top + 16}px)`,
    };
  });

  const page = usePage<{
    elementType: string;
    context?: string;
    source: SourceItem;
  }>();
  const context = inject(elementIndexContextKey);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const http = useHttp<
    Record<string, any>,
    {
      builder: BuilderPayload;
      headHtml: string;
      bodyHtml: string;
    }
  >({});

  http.transform(() => ({
    ...context?.value?.extraParams,
    elementType: context?.value?.elementType ?? page.props.elementType,
    context: context?.value?.context ?? page.props.context ?? 'index',
    source: context?.value?.source ?? page.props.source,
    fieldLayouts: context?.value?.fieldLayouts,
    id: props.builderId,
    conditionConfig: props.condition ?? undefined,
  }));

  const {announce} = useAnnouncer();
  const builder = shallowRef<BuilderPayload>();
  const draft = shallowRef<ConditionConfig>();
  const valid = ref(true);
  const editor = ref<InstanceType<typeof ConditionBuilder>>();

  onMounted(() => {
    http.post(ElementIndexController.filterHud().url, {
      onSuccess: async (data) => {
        announce(t('Loading complete'));
        builder.value = data.builder;
        draft.value = data.builder.value;
        if (data.headHtml) await appendHeadHtml(data.headHtml);
        if (data.bodyHtml) await appendBodyHtml(data.bodyHtml);
      },
    });
  });

  async function submit(): Promise<void> {
    if (!draft.value || !valid.value) return;
    if (!(await editor.value?.validate())) return;
    emit('apply', draft.value);
    emit('close');
  }

  // A picker open inside claims the Escape that closes it.
  onKeyStroke('Escape', (event) => {
    if (isEscapeClaimed(event)) return;
    claimEscape(event);
    emit('close');
  });

  // Pickers a rule opens live outside the panel, so only clicks back on the
  // index close it. The anchor toggles it itself.
  onClickOutside(panel, (event) => {
    const target = event.target as Element | null;
    if (target?.closest('.element-index') && !props.anchor.contains(target)) {
      emit('close');
    }
  });

  onBeforeUnmount(() => {
    const focused = document.activeElement;
    if (!props.anchor.isConnected) return;
    if (
      focused &&
      focused !== document.body &&
      !panel.value?.contains(focused)
    ) {
      return;
    }
    const invoker = props.anchor.matches('button, craft-button')
      ? props.anchor
      : props.anchor.querySelector<HTMLElement>('button');
    invoker?.focus();
  });
</script>

<template>
  <Teleport to="body">
    <form
      ref="panel"
      class="condition-popover"
      role="dialog"
      :aria-label="label"
      :style="position"
      @submit.prevent.stop="submit"
    >
      <craft-pane
        appearance="raised"
        :padding="scope === 'rule' ? 'md' : 'lg'"
        class="block w-full"
        :label="titled ? label : undefined"
        heading-level="3"
      >
        <craft-button
          v-if="titled"
          slot="header-actions"
          type="button"
          icon="xmark-large"
          :aria-label="t('Close')"
          variant="plain"
          size="small"
          flush="inline-end"
          @click="emit('close')"
        ></craft-button>
        <craft-spinner v-if="http.processing || !builder"></craft-spinner>
        <!-- The whole condition fills the pane, its own group supplying the padding. -->
        <ConditionBuilder
          v-else
          ref="editor"
          :slot="scope === 'all' ? 'body' : undefined"
          :class="`condition-popover__builder--${scope}`"
          :payload="builder"
          :autofocus="scope !== 'all'"
          @change="draft = $event"
          @valid="valid = $event"
        />
        <PaneActions
          v-if="builder"
          :clear-label="clearLabel"
          apply-type="submit"
          :apply-disabled="!valid"
          @clear="emit('clear')"
          @cancel="emit('close')"
        >
          <slot name="footer-start" />
        </PaneActions>
      </craft-pane>
    </form>
  </Teleport>
</template>

<style scoped lang="scss">
  .condition-popover {
    position: fixed;
    z-index: 50;
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-overlay);
  }

  .condition-popover__builder--all > :deep(div > .condition-group::part(base)) {
    border: 0;
    border-radius: 0;
  }

  // Just the one rule: no group operator, no adding or removing rules here.
  .condition-popover__builder--rule {
    :deep(.condition-group::part(base)) {
      border: 0;
      padding: 0;
    }

    :deep(.condition-group::part(header)),
    :deep(.condition-group__add),
    :deep(.condition-rule__remove) {
      display: none;
    }
  }
</style>
