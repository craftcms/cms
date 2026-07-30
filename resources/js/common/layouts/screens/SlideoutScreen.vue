<script setup lang="ts">
  /**
   * The slideout CP shell: a header strip, a scrolling body with an optional
   * details sidebar, and a save/cancel footer.
   *
   * Implements the same `ScreenSlots`/`ScreenProps` contract as `PageScreen`,
   * so a page component renders in either without knowing which it's in.
   * Regions a slideout has no room for (breadcrumbs, secondary nav, the global
   * footer) still get outlets — hidden — so a page written for a full page
   * doesn't lose teleported content here.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {ButtonVariant} from '@craftcms/ui';
  import {computed, provide} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import PassthroughScreen from './PassthroughScreen.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
  import {
    ScreenShellKey,
    useScreenPropsStore,
  } from '@/common/composables/screen';
  import {useSlideout} from '@/common/slideouts/useSlideout';
  import type {FormSaveOptions} from '@/common/types';
  import type {ScreenProps, ScreenSlots} from './types';

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const ownProps = withDefaults(defineProps<ScreenProps>(), {
    form: null,
  });

  const slots = defineSlots<ScreenSlots>();

  const registry = provideLayoutSlotRegistry();

  // A page rendering `<AppLayout>` inline inside this shell renders
  // transparently instead of stacking a second one.
  provide(ScreenShellKey, PassthroughScreen);

  const slideout = useSlideout();
  const store = useScreenPropsStore();

  // Options pushed through `useAppLayout()` land in the panel's store; props
  // passed to an inline `<AppLayout>` arrive directly. Either can configure
  // this shell, so merge them with the direct props winning.
  const props = computed<ScreenProps>(() => ({
    ...(store?.props as ScreenProps | undefined),
    ...Object.fromEntries(
      Object.entries(ownProps).filter(([, value]) => value !== undefined)
    ),
  }));

  const page = usePage<{
    title?: string;
    readOnly?: boolean;
    submitButtonLabel?: string | null;
    screen?: {editUrl?: string | null};
  }>();

  const title = computed(() => props.value.title?.trim() || page.props.title);
  const editUrl = computed(() => page.props.screen?.editUrl ?? null);
  const readOnly = computed(() => Boolean(page.props.readOnly));
  const form = computed(() => props.value.form ?? null);

  const hasToolbar = computed(
    () => Boolean(slots.toolbar) || registry.has('toolbar')
  );
  const hasTabs = computed(() => Boolean(slots.tabs) || registry.has('tabs'));
  const hasContentNotice = computed(
    () => Boolean(slots['content-notice']) || registry.has('content-notice')
  );
  const hasDetails = computed(
    () => Boolean(slots.details) || registry.has('details')
  );

  const submitLabel = computed(() => page.props.submitButtonLabel || t('Save'));

  function save() {
    const options: FormSaveOptions = {redirect: false};

    emit('save', options);
    // Reaches pages that configured this shell from below — via
    // `useAppLayout({onSave})` or an inline `<AppLayout @save>`.
    store?.save(options);
  }

  function close() {
    slideout?.close();
  }

  useAppendHtml();
</script>

<template>
  <form class="slideout-screen" method="post" @submit.prevent="save">
    <header class="slideout-screen__header">
      <h2 class="slideout-screen__title">{{ title }}</h2>

      <div v-show="hasToolbar" class="slideout-screen__toolbar">
        <LayoutSlotOutlet name="toolbar">
          <slot name="toolbar"></slot>
        </LayoutSlotOutlet>
      </div>

      <LayoutSlotOutlet name="actions">
        <slot name="actions"></slot>
      </LayoutSlotOutlet>

      <a
        v-if="editUrl"
        :href="editUrl"
        target="_blank"
        rel="noopener"
        class="slideout-screen__edit-link"
      >
        <craft-icon name="external-link" :label="t('Open in a new tab')" />
      </a>

      <craft-button
        icon
        type="button"
        :variant="ButtonVariant.Plain"
        @click="close"
        data-slideout-close
      >
        <craft-icon name="xmark" :label="t('Close')"></craft-icon>
      </craft-button>
    </header>

    <div v-show="hasTabs" class="slideout-screen__tabs">
      <LayoutSlotOutlet name="tabs">
        <slot name="tabs"></slot>
      </LayoutSlotOutlet>
    </div>

    <div class="slideout-screen__body">
      <div class="slideout-screen__content">
        <LayoutSlotOutlet name="error-summary">
          <slot name="error-summary">
            <ErrorSummary
              v-if="form && form.hasErrors"
              :errors="form.errors"
            />
          </slot>
        </LayoutSlotOutlet>

        <div v-show="hasContentNotice" role="status">
          <LayoutSlotOutlet name="content-notice">
            <slot name="content-notice"></slot>
          </LayoutSlotOutlet>
        </div>

        <CalloutReadOnly v-if="readOnly" />

        <slot></slot>

        <LayoutSlotOutlet name="content-footer">
          <slot name="content-footer"></slot>
        </LayoutSlotOutlet>
      </div>

      <!-- v-show, not v-if: the outlet is a teleport target and must stay in
        the DOM so page content can mount before registration flips hasDetails. -->
      <aside v-show="hasDetails" class="slideout-screen__details">
        <LayoutSlotOutlet name="details">
          <slot name="details"></slot>
        </LayoutSlotOutlet>
      </aside>
    </div>

    <footer class="slideout-screen__footer">
      <LayoutSlotOutlet name="additional-buttons">
        <slot name="additional-buttons"></slot>
      </LayoutSlotOutlet>

      <div class="slideout-screen__footer-actions">
        <craft-button type="button" @click="close">
          {{ t('Cancel') }}
        </craft-button>

        <LayoutSlotOutlet name="submit-button">
          <slot name="submit-button">
            <craft-button
              v-if="form && !readOnly"
              type="submit"
              :variant="ButtonVariant.Primary"
              :loading="form.processing || undefined"
            >
              {{ submitLabel }}
            </craft-button>
          </slot>
        </LayoutSlotOutlet>
      </div>
    </footer>

    <!-- Regions a slideout has no place for. Kept as hidden outlets so a page
      written for a full page doesn't drop its teleported content here. -->
    <div hidden>
      <LayoutSlotOutlet name="breadcrumbs" />
      <LayoutSlotOutlet name="context-menu" />
      <LayoutSlotOutlet name="title" />
      <LayoutSlotOutlet name="title-badge" />
      <LayoutSlotOutlet name="sidebar" />
      <LayoutSlotOutlet name="subnav-actions" />
      <LayoutSlotOutlet name="footer" />
    </div>
  </form>
</template>

<style scoped lang="css">
  .slideout-screen {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
  }

  .slideout-screen__header {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm, 0.5rem);
    padding: var(--c-spacing-md, 1rem);
    border-block-end: 1px solid var(--c-border-quiet, #e5e5e5);
  }

  .slideout-screen__title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-inline-end: auto;
  }

  .slideout-screen__toolbar {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm, 0.5rem);
  }

  .slideout-screen__tabs {
    padding-inline: var(--c-spacing-md, 1rem);
    border-block-end: 1px solid var(--c-border-quiet, #e5e5e5);
  }

  .slideout-screen__body {
    display: flex;
    gap: var(--c-spacing-md, 1rem);
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: var(--c-spacing-md, 1rem);
  }

  .slideout-screen__content {
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    align-content: start;
    flex: 1;
    min-width: 0;
  }

  .slideout-screen__details {
    flex: 0 0 16rem;
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    align-content: start;
  }

  .slideout-screen__footer {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm, 0.5rem);
    padding: var(--c-spacing-md, 1rem);
    border-block-start: 1px solid var(--c-border-quiet, #e5e5e5);
  }

  .slideout-screen__footer-actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm, 0.5rem);
    margin-inline-start: auto;
  }
</style>
