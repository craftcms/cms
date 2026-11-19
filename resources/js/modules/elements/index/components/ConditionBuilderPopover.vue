<script setup lang="ts">
  import {appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
  import {useHttp, usePage} from '@inertiajs/vue3';
  import {
    computed,
    inject,
    onMounted,
    ref,
    shallowRef,
    useTemplateRef,
  } from 'vue';
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
   * a draft and hands it back on Apply. It opens as it mounts, and emits
   * `close` once it has closed.
   */
  const props = withDefaults(
    defineProps<{
      /** What it hangs from: a button, or something holding one. */
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

  const popover = useTemplateRef<HTMLElement & {hide(): Promise<void>}>(
    'popover'
  );

  /**
   * Placed against the whole anchor, such as a chip, but opened from the
   * button in it, which focus goes back to.
   */
  const reference = computed(() => ({
    getBoundingClientRect: () => props.anchor.getBoundingClientRect(),
    contextElement: props.anchor.matches('button, craft-button')
      ? props.anchor
      : (props.anchor.querySelector<HTMLElement>('button') ?? props.anchor),
  }));
  const width = computed(
    () => `min(${props.scope === 'all' ? '40rem' : '34rem'}, 100vw - 32px)`
  );

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

  /** Closes it, handing focus back to the anchor's button. */
  function hide(): void {
    void popover.value?.hide();
  }

  async function submit(): Promise<void> {
    if (!draft.value || !valid.value) return;
    if (!(await editor.value?.validate())) return;
    emit('apply', draft.value);
    hide();
  }

  function clear(): void {
    emit('clear');
    hide();
  }

  defineExpose({hide});
</script>

<template>
  <!-- The anchor opens and closes it, and says whether it's expanded. Pickers
    a rule opens, like the element selector, add to the draft. -->
  <craft-popover
    ref="popover"
    class="condition-popover"
    open
    manual
    without-invoker-aria
    keep-open-behind-modals
    :placement="align === 'end' ? 'bottom-end' : 'bottom-start'"
    .anchor="reference"
    :style="{
      '--popover-min-inline-size': width,
      '--popover-max-inline-size': width,
    }"
    @craft-after-hide.self="emit('close')"
  >
    <!-- Its own form, so submitting it never submits the index's around it. -->
    <form slot="content" :aria-label="label" @submit.prevent.stop="submit">
      <craft-pane
        :padding="scope === 'rule' ? 'md' : 'lg'"
        class="condition-popover__pane"
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
          @click="hide"
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
          @clear="clear"
          @cancel="hide"
        >
          <slot name="footer-start" />
        </PaneActions>
      </craft-pane>
    </form>
  </craft-popover>
</template>

<style scoped lang="scss">
  // The popover draws the border. Set on the part, as the custom property
  // would carry into the groups' own panes.
  .condition-popover__pane {
    display: block;
  }

  .condition-popover__pane::part(base) {
    border: 0;
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
