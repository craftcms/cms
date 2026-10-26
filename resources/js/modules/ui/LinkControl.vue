<script setup lang="ts">
  import '@craftcms/ui/components/popover/popover';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, nextTick, onMounted, ref, useId} from 'vue';
  import '../link-field/craft-link-field';
  import type {
    LinkAdvancedField,
    LinkChangeDetail,
    LinkFieldValue,
    LinkTypeConfig,
  } from '../link-field/craft-link-field';
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';

  type LinkControlProps = {
    types: LinkTypeConfig[];
    showLabelField?: boolean;
    advancedFields?: LinkAdvancedField[];
    compact?: boolean;
    registeredTypes?: string[] | null;
  };
  type LinkValue = Pick<LinkFieldValue, 'type' | 'value'> &
    Partial<Omit<LinkFieldValue, 'type' | 'value'>>;
  type ElementSelectStartEvent = CustomEvent<{
    restoreFocusTo?: HTMLElement;
    waitUntil: (promise: Promise<unknown>) => void;
  }>;

  const props = defineProps<{
    control: UiControlPayload<LinkControlProps>;
    value: LinkValue;
    editable: boolean;
    label?: string;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: LinkValue,
      kind: LinkChangeDetail['kind']
    ): void;
  }>();

  const id = useId();
  const buttonId = `${id}-button`;
  const summaryId = `${id}-summary`;
  const root = ref<HTMLElement>();
  const button = ref<HTMLElement>();
  const popoverHost = ref<Element>();
  const popover = ref<HTMLElementTagNameMap['craft-popover']>();
  const linkField = ref<HTMLElement>();
  const draft = ref<LinkValue | null>(null);
  let restoreFocusTo: HTMLElement | null = null;
  let resuming = false;

  const hasValue = computed(() => Boolean(props.value?.value));
  /** A link whose type isn't installed. Its value is kept as it is. */
  const unavailable = computed(
    () =>
      hasValue.value &&
      Boolean(props.control.props.registeredTypes) &&
      !props.control.props.registeredTypes!.includes(props.value.type)
  );
  const buttonText = computed(() =>
    hasValue.value ? t('Edit link') : t('Add link')
  );
  // `defaultLabel` is only for display.
  const formValues = computed(() =>
    Object.entries(props.value ?? {})
      .filter(([key]) => key !== 'defaultLabel')
      .map(([key, value]) => ({
        key,
        value: typeof value === 'boolean' ? (value ? '1' : '') : (value ?? ''),
      }))
  );
  const summaryLabel = computed(
    () => props.value.label || props.value.defaultLabel || props.value.value
  );
  const summaryDetail = computed(() => {
    const type = props.control.props.types.find(
      (linkType) => linkType.id === props.value.type
    );
    const typeLabel = type?.label ?? props.value.type;

    return type?.kind === 'text' && summaryLabel.value !== props.value.value
      ? `${typeLabel}: ${props.value.value}`
      : typeLabel;
  });

  /** Names a button after its cell, since every cell's button reads the same. */
  function buttonName(action: string): string | undefined {
    return props.label
      ? t('{action}, {cell}', {action, cell: props.label})
      : undefined;
  }

  // In the same top-level container as the cell, so a modal layer hides and
  // restores the popover along with it.
  onMounted(() => {
    popoverHost.value = root.value?.closest('body > *') ?? document.body;
  });

  async function remove(): Promise<void> {
    emit('update:value', {type: props.value.type, value: ''}, 'discrete');
    await nextTick();
    button.value?.focus();
  }

  function change(event: Event): void {
    if (!(event instanceof CustomEvent)) {
      throw new TypeError('Expected a link-field custom event.');
    }

    const {kind, value}: LinkChangeDetail = event.detail;
    // `href` is derived, so it isn't part of the field's value.
    const {href: _href, ...linkValue} = value;
    emit('update:value', linkValue, kind);
  }

  function apply(event: Event): void {
    if (!(event instanceof CustomEvent)) {
      throw new TypeError('Expected a link-field custom event.');
    }

    const {href: _href, ...linkValue}: LinkFieldValue = event.detail;
    emit('update:value', linkValue, 'discrete');
    void close();
  }

  async function close(): Promise<void> {
    await popover.value?.hide();
    button.value?.focus();
  }

  function onShow(): void {
    if (!resuming) {
      draft.value = {...props.value};
    }
  }

  async function onAfterShow(): Promise<void> {
    resuming = false;
    await nextTick();

    if (restoreFocusTo?.isConnected) {
      restoreFocusTo.focus();
      restoreFocusTo = null;

      return;
    }

    linkField.value
      ?.querySelector<HTMLElement>('select, input, craft-button')
      ?.focus();
  }

  function suspend(event: Event): void {
    const {detail} = event as ElementSelectStartEvent;
    restoreFocusTo = detail.restoreFocusTo ?? null;
    detail.waitUntil(popover.value?.hide() ?? Promise.resolve());
  }

  function resume(): void {
    resuming = true;
    void popover.value?.show();
  }
</script>

<template>
  <div v-if="control.props.compact" ref="root" class="link-control--compact">
    <p v-if="hasValue" :id="summaryId" class="link-control__summary">
      <span class="link-control__label">{{ summaryLabel }}</span>
      {{ ' ' }}
      <span class="link-control__detail">{{
        unavailable
          ? t('This link type is no longer available.')
          : summaryDetail
      }}</span>
    </p>
    <div class="link-control__actions">
      <craft-button
        :id="buttonId"
        ref="button"
        type="button"
        size="small"
        variant="fill"
        .disabled="!editable || unavailable"
        :aria-label="buttonName(buttonText)"
        :aria-describedby="hasValue ? summaryId : undefined"
      >
        {{ buttonText }}
      </craft-button>
      <craft-button
        v-if="hasValue && editable"
        type="button"
        size="small"
        variant="plain"
        :aria-label="buttonName(t('Remove link'))"
        @click="remove"
      >
        {{ t('Remove link') }}
      </craft-button>
    </div>
    <template v-if="editable">
      <input
        v-for="field in formValues"
        :key="field.key"
        type="hidden"
        :name="`${inputName(control.path)}[${field.key}]`"
        :value="field.value"
      />
    </template>
    <!-- Outside the table cell, so the cell's input styles and click-to-focus don't reach the editor. -->
    <Teleport v-if="popoverHost" :to="popoverHost">
      <craft-popover
        ref="popover"
        class="craft-link-popover"
        :for="buttonId"
        :label="t('Link')"
        placement="bottom-start"
        @craft-show="onShow"
        @craft-after-show="onAfterShow"
      >
        <div slot="content" class="p-2">
          <craft-link-field
            ref="linkField"
            :types="control.props.types"
            .modelValue="draft ?? value"
            .showLabelField="control.props.showLabelField"
            .advancedFields="control.props.advancedFields"
            .disabled="!editable"
            @apply="apply"
            @cancel="close"
            @element-select-start="suspend"
            @element-select-end="resume"
          />
        </div>
      </craft-popover>
    </Teleport>
  </div>
  <div v-else>
    <craft-link-field
      live
      :types="control.props.types"
      .modelValue="value"
      :name="editable ? inputName(control.path) : ''"
      .showLabelField="control.props.showLabelField"
      .advancedFields="control.props.advancedFields"
      :disabled="!editable"
      @link-change="change"
    />
  </div>
</template>

<style scoped>
  .link-control--compact {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--c-spacing-xs);
  }

  .link-control__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xs);
  }

  .link-control__summary {
    display: flex;
    flex-direction: column;
    align-self: stretch;
    /* Keeps long URLs from widening table columns past their width settings */
    contain: inline-size;
    margin: 0;
  }

  .link-control__label,
  .link-control__detail {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .link-control__detail {
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }
</style>
