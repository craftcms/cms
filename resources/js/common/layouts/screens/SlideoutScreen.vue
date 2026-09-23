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
  import {
    computed,
    onBeforeUnmount,
    onMounted,
    provide,
    ref,
    useTemplateRef,
  } from 'vue';
  import {useElementSize, useEventListener} from '@vueuse/core';
  import {useDetailsOverlay} from '@/common/composables/useDetailsOverlay';
  import {router} from '@inertiajs/vue3';
  import {submitScreenForm} from '@/common/slideouts/submitScreenForm';
  import {setSlideoutDirtyCheck} from '@/common/slideouts/store';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import PassthroughScreen from './PassthroughScreen.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
  import {
    ScreenContentReadyKey,
    ScreenShellKey,
    type ScreenPageProps as GenericScreenPageProps,
    useScreenPageProps,
    useScreenPropsStore,
    ScreenDetailsOverlayKey,
  } from '@/common/composables/screen';
  import {useSlideout} from '@/common/slideouts/useSlideout';
  import {useElementEditor} from '@/common/slideouts/useElementEditor';
  import {firstMessages} from '@/common/slideouts/errors';
  import type {FormSaveOptions} from '@/common/types';
  import type {ScreenProps, ScreenSlots} from './types';
  import {useScreenRegions} from './useScreenRegions';
  import CpContainer from '@/common/components/CpContainer.vue';

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  // No `withDefaults` here on purpose: a default turns an unset prop into a
  // defined one, which then wins the merge below and clobbers whatever the
  // page pushed through `useAppLayout()`. Unset must stay `undefined`.
  const ownProps = defineProps<ScreenProps>();

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
    ...store?.props,
    ...Object.fromEntries(
      Object.entries(ownProps).filter(([, value]) => value !== undefined)
    ),
  }));

  // The slideout's own props, not the page behind it — `usePage()` would give
  // us the base page's title and edit URL.
  const pageProps = useScreenPageProps();

  interface ScreenPageProps {
    title?: string;
    readOnly?: boolean;
    submitButtonLabel?: string | null;
    screen?: {
      editUrl?: string | null;
      /** Present on screens that submit server-rendered HTML (`cp/Screen`). */
      action?: string | null;
      namespace?: string | null;
      /** Present only on the element-edit screen. See `useElementEditor()`. */
      elementEditorSettings?: GenericScreenPageProps | null;
    };
  }

  // SAFETY: CP screen responses provide this documented chrome payload.
  const chrome = computed(() => pageProps() as ScreenPageProps);

  const title = computed(() => props.value.title?.trim() || chrome.value.title);
  const editUrl = computed(() => chrome.value.screen?.editUrl ?? null);
  const readOnly = computed(() => Boolean(chrome.value.readOnly));
  const form = computed(() => props.value.form ?? null);

  const regions = useScreenRegions(slots, registry);
  const hasToolbarMeta = computed(() => regions.has('content-toolbar-meta'));
  const hasTabs = computed(() => regions.has('content-tabs'));
  const hasNotices = computed(() => regions.has('content-notices'));
  const hasDetails = computed(() => regions.has('content-details'));

  const submitLabel = computed(
    () =>
      props.value.submitButtonLabel ||
      chrome.value.submitButtonLabel ||
      t('Save')
  );

  /**
   * Screens rendered through `cp/Screen` have no Vue form — just server HTML
   * with real, namespaced inputs — so the shell submits them itself.
   */
  const screenAction = computed(() => chrome.value.screen?.action ?? null);

  const formEl = useTemplateRef<HTMLFormElement>('formEl');
  const contentEl = useTemplateRef<HTMLElement>('contentEl');
  const detailsEl = useTemplateRef<HTMLElement>('detailsEl');
  const toolbarEl = useTemplateRef<HTMLElement>('toolbarEl');
  const tabsEl = useTemplateRef<HTMLElement>('tabsEl');

  // The overlay threshold lives in the stylesheet below; this reads it back.
  const {width: detailsWidth} = useElementSize(detailsEl);
  provide(
    ScreenDetailsOverlayKey,
    useDetailsOverlay(() => detailsEl.value, detailsWidth)
  );

  const submittingHtml = ref(false);
  const screenErrors = ref<Record<string, string> | null>(null);

  /**
   * The element-edit screen brings its own controller: drafts, autosaving and
   * delta submission are `Craft.ElementEditor`'s, not the shell's.
   */
  const elementEditor = useElementEditor({
    settings: () => chrome.value.screen?.elementEditorSettings,
    namespace: () => chrome.value.screen?.namespace,
    slideout,
    regions: {
      form: formEl,
      content: contentEl,
      details: detailsEl,
      toolbar: toolbarEl,
      tabs: tabsEl,
    },
    onSaved: handleElementSaved,
    onDraftSaved: handleElementDraftSaved,
    onError: handleElementSaveError,
  });

  // The editor snapshots the form to detect changes, so it can't be built
  // until the screen's server-rendered HTML is really in the document.
  provide(ScreenContentReadyKey, elementEditor.start);

  onBeforeUnmount(elementEditor.destroy);

  const canSave = computed(
    () =>
      !elementEditor.isStatic.value &&
      (Boolean(form.value) ||
        Boolean(screenAction.value) ||
        elementEditor.active.value)
  );

  /**
   * A revision — or an element already autosaved to a provisional draft — has
   * nothing to cancel; the only thing left to do is leave.
   */
  const cancelLabel = computed(
    () =>
      elementEditor.cancelLabel.value ??
      (elementEditor.isStatic.value ? t('Close') : t('Cancel'))
  );

  const craft = () => window.Craft;

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function handleElementSaved(response: any): void {
    const data = response?.data ?? {};

    if (data.message) {
      craft()?.cp?.displaySuccess(data.message, data.notificationSettings);
    }

    if (data.modelClass && data.modelId) {
      craft()?.refreshComponentInstances?.(data.modelClass, data.modelId);
    }

    // Tell other tabs — and the legacy element index behind this panel — that
    // the element changed, exactly as `ElementEditorSlideout` does.
    if (data.element?.id) {
      craft()?.broadcaster?.postMessage({
        event: 'saveElement',
        id: data.element.id,
      });
    }

    craft()?.Preview?.refresh?.();

    const handled = slideout?.saved({data});

    slideout?.close({force: true});

    if (!handled) {
      router.reload();
    }
  }

  /**
   * An autosaved draft. The panel stays open — this only lets the opener show
   * the provisional changes, the way the legacy index picks up a `saveDraft`
   * broadcast.
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function handleElementDraftSaved(response: any): void {
    slideout?.saved({draft: true, data: response?.data});
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function handleElementSaveError(error: any): void {
    const data = error?.response?.data;

    if (!data) {
      throw error;
    }

    craft()?.cp?.displayError(data.message);

    // The server's own summary gets drawn into the screen HTML, split across
    // its tabs. Only stand in for it when there wasn't one.
    screenErrors.value = data.errorSummary
      ? null
      : data.errors
        ? firstMessages(data.errors)
        : {error: data.message ?? t('Couldn’t save.')};
  }

  /**
   * Whether the panel holds unsaved changes, so the store can prompt before
   * discarding it.
   *
   * A Vue page has Inertia's own precise `isDirty`. A server-rendered screen
   * has no such tracking, and snapshot-diffing its markup isn't reliable —
   * `Craft.initUiElements()` rewrites inputs after load, which would read as
   * user edits. So that path tracks whether the user has interacted at all:
   * more conservative (undoing an edit still counts), but never wrong in the
   * direction that loses work.
   */
  const touched = ref(false);
  useEventListener(formEl, 'input', () => (touched.value = true));
  useEventListener(formEl, 'change', () => (touched.value = true));

  const isDirty = () => {
    // Autosaved drafts persist every edit as it's made, so there's nothing to
    // warn about — and warning anyway would make every element slideout
    // prompt on close.
    if (elementEditor.autosaves.value) {
      return false;
    }

    return form.value ? Boolean(form.value.isDirty) : touched.value;
  };

  onMounted(() => {
    if (slideout) {
      setSlideoutDirtyCheck(slideout.instance.id, isDirty);
    }
  });

  onBeforeUnmount(() => {
    if (slideout) {
      setSlideoutDirtyCheck(slideout.instance.id, null);
    }
  });

  async function saveHtmlScreen(): Promise<void> {
    if (!formEl.value || !screenAction.value) {
      return;
    }

    submittingHtml.value = true;
    screenErrors.value = null;

    const result = await submitScreenForm(formEl.value, {
      action: screenAction.value,
      namespace: chrome.value.screen?.namespace,
      containerId: slideout?.instance.containerId,
    });

    submittingHtml.value = false;

    if (!result.ok) {
      if (result.fatal) {
        throw result.fatal;
      }

      screenErrors.value = result.errors ?? {
        error: result.message ?? t('Couldn’t save.'),
      };

      return;
    }

    const handled = slideout?.saved({data: result.data});

    slideout?.close({force: true});

    if (!handled) {
      // The controller flashes its success message to the session even on the
      // JSON branch, so refreshing the page behind surfaces it and picks up
      // whatever changed.
      router.reload();
    }
  }

  function save(event: Event) {
    if (props.value.saveDisabled) {
      return;
    }

    if (elementEditor.active.value) {
      void elementEditor.submit(event);

      return;
    }

    if (!form.value && screenAction.value) {
      void saveHtmlScreen();

      return;
    }

    // Deliberately no `redirect: false` — in a slideout that flag means "save
    // and continue editing" (the cmd+S path), which keeps the panel open. The
    // Save button should close it. A slideout never follows the redirect
    // either way; `useSettingsSave` drops it from the payload.
    emit('save');
    // Reaches pages that configured this shell from below — via
    // `useAppLayout({onSave})` or an inline `<AppLayout @save>`.
    store?.save();
  }

  function close() {
    slideout?.close();
  }

  useAppendHtml();
</script>

<template>
  <!-- The `id` must be the container id: screens that drive themselves from
    JS are handed their settings by `$('#<containerId>').data(…)`, emitted
    server-side against the `X-Craft-Container-Id` this panel sent. -->
  <form
    :id="slideout?.instance.containerId"
    ref="formEl"
    class="slideout-screen"
    method="post"
    @submit.prevent="save"
  >
    <header class="slideout-screen__header">
      <CpContainer>
        <div class="flex items-center gap-sm justify-between">
          <div class="flex gap-md items-center">
            <h2 class="slideout-screen__title">{{ title }}</h2>
            <LayoutSlotOutlet name="content-toolbar-meta">
              <slot name="content-toolbar-meta"></slot>
            </LayoutSlotOutlet>
          </div>

          <div class="flex gap-sm items-center">
            <!-- Always rendered: `Craft.ElementEditor` hangs its autosave spinner
        and draft status icon here, and a screen with no toolbar still has
        drafts to report on. -->
            <div ref="toolbarEl" class="slideout-screen__toolbar">
              <LayoutSlotOutlet name="content-toolbar-actions">
                <slot name="content-toolbar-actions"></slot>
              </LayoutSlotOutlet>
            </div>

            <LayoutSlotOutlet name="content-actions">
              <slot name="content-actions"></slot>
            </LayoutSlotOutlet>

            <a
              v-if="editUrl"
              :href="editUrl"
              target="_blank"
              rel="noopener"
              class="slideout-screen__edit-link"
            >
              <craft-icon
                name="external-link"
                :label="t('Open in a new tab')"
              />
            </a>

            <craft-button
              icon
              type="button"
              size="small"
              :variant="ButtonVariant.Plain"
              flush
              @click="close"
              data-slideout-close
            >
              <craft-icon name="xmark" :label="t('Close')"></craft-icon>
            </craft-button>
          </div>
        </div>
      </CpContainer>
    </header>

    <div v-show="hasTabs" ref="tabsEl" class="slideout-screen__tabs">
      <LayoutSlotOutlet name="content-tabs">
        <slot name="content-tabs"></slot>
      </LayoutSlotOutlet>
    </div>

    <div class="slideout-screen__body">
      <div ref="contentEl" class="slideout-screen__content">
        <div v-show="hasNotices" class="slideout-screen__notices" role="status">
          <LayoutSlotOutlet name="content-notices">
            <slot name="content-notices"></slot>
          </LayoutSlotOutlet>
        </div>

        <LayoutSlotOutlet name="error-summary">
          <slot name="error-summary">
            <ErrorSummary v-if="form && form.hasErrors" :errors="form.errors" />
            <!-- Server-HTML screens have no Vue form to hang errors off. -->
            <ErrorSummary v-else-if="screenErrors" :errors="screenErrors" />
          </slot>
        </LayoutSlotOutlet>

        <CalloutReadOnly v-if="readOnly" />

        <craft-field-group>
          <slot></slot>
        </craft-field-group>

        <LayoutSlotOutlet name="content-footer">
          <slot name="content-footer"></slot>
        </LayoutSlotOutlet>
      </div>

      <!-- v-show, not v-if: the outlet is a teleport target and must stay in
        the DOM so page content can mount before registration flips hasDetails. -->
      <aside
        v-show="hasDetails"
        ref="detailsEl"
        class="slideout-screen__details"
      >
        <LayoutSlotOutlet name="content-details">
          <slot name="content-details"></slot>
        </LayoutSlotOutlet>
      </aside>
    </div>

    <footer class="slideout-screen__footer">
      <LayoutSlotOutlet name="additional-buttons">
        <slot name="additional-buttons"></slot>
      </LayoutSlotOutlet>

      <div class="slideout-screen__footer-actions">
        <craft-button type="button" @click="close">
          {{ cancelLabel }}
        </craft-button>

        <LayoutSlotOutlet name="submit-button">
          <slot name="submit-button">
            <craft-button
              v-if="canSave && !readOnly && !props.saveDisabled"
              type="submit"
              :variant="ButtonVariant.Primary"
              :loading="
                form?.processing ||
                submittingHtml ||
                elementEditor.saving.value ||
                undefined
              "
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
      <LayoutSlotOutlet name="content-toolbar" />
      <LayoutSlotOutlet name="content-sidebar" />
      <LayoutSlotOutlet name="subnav-actions" />
      <LayoutSlotOutlet name="page-footer" />
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
    border-block-end: 1px solid var(--c-color-border-quiet, #e5e5e5);
    min-height: var(--cp-header-height);
    display: grid;
    align-items: center;
  }

  .slideout-screen__title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
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
    /* One layout at every width: the details column collapses to its rail
       rather than dropping below the content. */
    flex-direction: row;
    flex: 1;
    min-height: 0;
    /* The positioning context for the overlaid column, which is why the content
       scrolls and not this — an absolutely positioned child of a scroll
       container scrolls away with it. */
    position: relative;
    overflow: hidden;
    background-color: var(--c-surface-overlay);
  }

  .slideout-screen__notices {
    display: grid;
    gap: var(--c-spacing-sm, 0.5rem);
  }

  .slideout-screen__content {
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    align-content: start;
    flex: 1;
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
  }

  .slideout-screen__details {
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    align-content: stretch;
    align-self: stretch;

    /* Overlaid by default, a panel being narrower than the threshold more often
       than not. Only the tab panels lift out; the strip stays in the flow as
       the rail. The width itself is in the query below. */
    --cp-details-overlay: 1;

    /* The panels anchor to this, above the scrim so the rail stays lit. */
    position: relative;
    z-index: var(--c-layer-sticky);

    &:has(craft-tabs[collapsed]) {
      border-inline-start-color: transparent;
    }
  }

  /* The panels, and only the panels, sit over the content. Flat rather than
     nested in the rule above, where `:deep()` loses its parent selector and
     would reach every craft-tabs in the shell. */
  .slideout-screen__details :deep(craft-tabs::part(panels)) {
    position: absolute;
    inset-block: 0;
    /* The rail's leading edge: at 0 it would open on top of the rail. */
    inset-inline-end: 100%;
    z-index: var(--c-layer-sticky);
    /* `cqi`, not a percentage: percentages resolve against the rail this hangs
       off rather than against the content. */
    inline-size: clamp(calc(300rem / 16), 80cqi, calc(400rem / 16));
    max-inline-size: 80cqi;
    overflow-y: auto;
    background-color: var(--c-surface-overlay);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    box-shadow: var(--c-shadow-overlay);
  }

  /* Wide enough to seat the column in the flow beside the content. */
  @container slideout (width >= 960px) {
    .slideout-screen__details {
      --cp-details-overlay: 0;

      flex: 0 1 calc(350rem / 16);
      min-inline-size: calc(300rem / 16);

      /* Closed, it hands the track back — the floor included, or the rail
         would keep reserving it. */
      &:has(craft-tabs[collapsed]) {
        flex: 0 0 auto;
        min-inline-size: 0;
      }
    }

    /* Room of its own, so the panels stay in the flow. */
    .slideout-screen__details :deep(craft-tabs::part(panels)) {
      position: static;
      inline-size: auto;
      min-inline-size: 0;
      max-inline-size: none;
      overflow: visible;
      border-inline-start: 0;
    }
  }

  .slideout-screen__footer {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm, 0.5rem);
    padding: var(--c-spacing-md, 1rem);
    border-block-start: 1px solid var(--c-border-quiet, #e5e5e5);
    min-height: var(--cp-footer-height);
  }

  .slideout-screen__footer-actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm, 0.5rem);
    margin-inline-start: auto;
  }
</style>
