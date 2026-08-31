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
  import {useEventListener} from '@vueuse/core';
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
  } from '@/common/composables/screen';
  import {useSlideout} from '@/common/slideouts/useSlideout';
  import {useElementEditor} from '@/common/slideouts/useElementEditor';
  import {firstMessages} from '@/common/slideouts/errors';
  import type {FormSaveOptions} from '@/common/types';
  import type {ScreenProps, ScreenSlots} from './types';

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
      <h2 class="slideout-screen__title">{{ title }}</h2>

      <!-- Always rendered: `Craft.ElementEditor` hangs its autosave spinner
        and draft status icon here, and a screen with no toolbar still has
        drafts to report on. -->
      <div ref="toolbarEl" class="slideout-screen__toolbar">
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

    <div v-show="hasTabs" ref="tabsEl" class="slideout-screen__tabs">
      <LayoutSlotOutlet name="tabs">
        <slot name="tabs"></slot>
      </LayoutSlotOutlet>
    </div>

    <div class="slideout-screen__body">
      <div ref="contentEl" class="slideout-screen__content">
        <LayoutSlotOutlet name="error-summary">
          <slot name="error-summary">
            <ErrorSummary v-if="form && form.hasErrors" :errors="form.errors" />
            <!-- Server-HTML screens have no Vue form to hang errors off. -->
            <ErrorSummary v-else-if="screenErrors" :errors="screenErrors" />
          </slot>
        </LayoutSlotOutlet>

        <div v-show="hasContentNotice" role="status">
          <LayoutSlotOutlet name="content-notice">
            <slot name="content-notice"></slot>
          </LayoutSlotOutlet>
        </div>

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
          {{ cancelLabel }}
        </craft-button>

        <LayoutSlotOutlet name="submit-button">
          <slot name="submit-button">
            <craft-button
              v-if="canSave && !readOnly"
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
    /* Stacked by default: a fixed-width details column beside the content
       squeezes it below a usable width on a narrow panel. Legacy makes the
       same call at ~700px. */
    flex-direction: column;
    gap: var(--c-spacing-md, 1rem);
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: var(--c-spacing-md, 1rem);
    background-color: var(--c-surface-overlay);

    @container slideout (width >= 44rem) {
      flex-direction: row;
    }
  }

  .slideout-screen__content {
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    align-content: start;
    flex: 1;
    min-width: 0;
  }

  .slideout-screen__details {
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    align-content: start;

    /* Only claim a fixed column once the panel is wide enough to spare it. */
    @container slideout (width >= 44rem) {
      flex: 0 0 16rem;
    }
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
