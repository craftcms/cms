<script setup lang="ts">
  /**
   * The slideout CP shell: a header strip, a scrolling body with an optional
   * details sidebar, and a save/cancel footer.
   *
   * Implements the same `ScreenSlots`/`ScreenProps` contract as `PageScreen`,
   * so a page component renders in either without knowing which it's in.
   * Regions a slideout has no room for (the context menu, secondary nav, the
   * global footer) still get outlets — hidden — so a page written for a full page
   * doesn't lose teleported content here.
   */
  import {
    DETAILS_PANEL_ID,
    ScreenPanelsKey,
    createScreenPanels,
  } from '@/common/composables/screenPanels';
  import ScreenDetailsLayout from '@/common/components/ScreenDetailsLayout.vue';
  import ScreenPanel from '@/common/components/ScreenPanel.vue';
  import {t} from '@craftcms/ui/utilities/translate';
  import {ButtonVariant} from '@craftcms/ui';
  import {
    computed,
    onBeforeUnmount,
    provide,
    ref,
    useId,
    useTemplateRef,
  } from 'vue';
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
  import type {FormSaveOptions} from '@/common/types';
  import type {ScreenProps, ScreenSlots} from './types';
  import {useScreenRegions} from './useScreenRegions';
  import CpContainer from '@/common/components/CpContainer.vue';
  import ContentFooter from './page/ContentFooter.vue';
  import {DEFAULT_FORM_ACTIONS} from './formActionItems';
  import {useHtmlScreenSubmit} from './slideout/useHtmlScreenSubmit';
  import {useSlideoutDirtyCheck} from './slideout/useSlideoutDirtyCheck';
  import {useSlideoutSaveHandlers} from './slideout/useSlideoutSaveHandlers';

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
    showReadOnlyNotice?: boolean;
    submitButtonLabel?: string | null;
    /** The response's `primaryAction()` button, rendered server-side. */
    primaryAction?: string | null;
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
  const editUrl = computed(
    () => props.value.editUrl || chrome.value.screen?.editUrl || null
  );
  const editLinkId = `slideout-edit-link-${useId()}`;
  const readOnly = computed(
    () => !props.value.handlesReadOnly && Boolean(chrome.value.readOnly)
  );
  const form = computed(() => props.value.form ?? null);

  // A slideout has panels of its own; the page's stay on the page.
  const panels = createScreenPanels();
  provide(ScreenPanelsKey, panels);
  const panelIdPrefix = `slideout-details-${useId()}`;

  // Slot presence isn't reactive, so the template calls these on each render.
  const regions = useScreenRegions(slots, registry, panels);

  // The Save menu's entries. "Save and continue editing" keeps the panel open:
  // in a slideout that's what `redirect: false` means.
  function saveWithOptions(options?: FormSaveOptions): void {
    emit('save', options);
    store?.save(options);
  }

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
  const bodyLayout =
    useTemplateRef<InstanceType<typeof ScreenDetailsLayout>>('bodyLayout');
  const toolbarEl = useTemplateRef<HTMLElement>('toolbarEl');
  const tabsEl = useTemplateRef<HTMLElement>('tabsEl');

  /** Errors for a screen with no Vue form to hang them off. */
  const screenErrors = ref<Record<string, string> | null>(null);
  const saveHandlers = useSlideoutSaveHandlers({
    slideout,
    errors: screenErrors,
  });

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
      content: () => bodyLayout.value?.content ?? null,
      details: () => bodyLayout.value?.details ?? null,
      toolbar: toolbarEl,
      tabs: tabsEl,
    },
    onSaved: saveHandlers.onElementSaved,
    onDraftSaved: saveHandlers.onElementDraftSaved,
    onError: saveHandlers.onElementSaveError,
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

  useSlideoutDirtyCheck({
    slideout,
    formEl,
    form,
    autosaves: elementEditor.autosaves,
  });

  const htmlScreen = useHtmlScreenSubmit({
    form: formEl,
    action: screenAction,
    namespace: () => chrome.value.screen?.namespace,
    containerId: () => slideout?.instance.containerId,
    errors: screenErrors,
    onSaved: saveHandlers.closeAfterSave,
  });

  function save(event: Event) {
    if (props.value.saveDisabled) {
      return;
    }

    if (elementEditor.active.value) {
      void elementEditor.submit(event);

      return;
    }

    if (!form.value && screenAction.value) {
      void htmlScreen.submit();

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
          <!-- Nested in a preview's editor, the panel is a view to go back
            from, as the command palette's are. -->
          <div class="flex gap-lg items-center min-w-0">
            <craft-button
              v-if="slideout?.coversPreviewEditor"
              type="button"
              size="small"
              icon="arrow-left"
              flush="inline-start"
              :variant="ButtonVariant.Plain"
              @click="close"
            >
              {{ t('Back') }}
            </craft-button>
            <div class="slideout-screen__heading flex gap-md items-center">
              <h2 class="slideout-screen__title">{{ title }}</h2>
              <LayoutSlotOutlet name="content-toolbar-meta">
                <slot name="content-toolbar-meta"></slot>
              </LayoutSlotOutlet>
            </div>
          </div>

          <div class="slideout-screen__actions flex gap-sm items-center">
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

            <template v-if="editUrl">
              <craft-button
                :id="editLinkId"
                icon
                size="small"
                :variant="ButtonVariant.Plain"
                :href="editUrl"
                target="_blank"
                rel="noopener"
                class="slideout-screen__edit-link"
              >
                <craft-icon
                  name="arrow-up-right-from-square"
                  :label="t('Open in a new tab')"
                ></craft-icon>
              </craft-button>
              <craft-tooltip :for="editLinkId">
                {{ t('Open in a new tab') }}
              </craft-tooltip>
            </template>

            <craft-button
              v-if="!slideout?.coversPreviewEditor"
              icon
              type="button"
              size="small"
              :variant="ButtonVariant.Plain"
              flush="inline-end"
              @click="close"
              data-slideout-close
            >
              <craft-icon name="xmark-large" :label="t('Close')"></craft-icon>
            </craft-button>
          </div>
        </div>
      </CpContainer>
    </header>

    <CpContainer v-show="regions.has('content-tabs')">
      <div ref="tabsEl" class="slideout-screen__tabs">
        <LayoutSlotOutlet name="content-tabs">
          <slot name="content-tabs"></slot>
        </LayoutSlotOutlet>
      </div>
    </CpContainer>

    <ScreenDetailsLayout
      ref="bodyLayout"
      class="slideout-screen__body"
      :panels="panels"
      :id-prefix="panelIdPrefix"
      :show-details="regions.hasDetails()"
      :has-panels="regions.hasPanels()"
    >
      <div
        v-if="!slideout?.docked"
        v-show="regions.has('content-notices')"
        class="slideout-screen__notices"
        role="status"
      >
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

      <CalloutReadOnly v-if="readOnly && chrome.showReadOnlyNotice !== false" />

      <craft-field-group>
        <slot></slot>
      </craft-field-group>

      <template #details>
        <LayoutSlotOutlet name="content-details">
          <slot name="content-details"></slot>
        </LayoutSlotOutlet>
      </template>
    </ScreenDetailsLayout>
    <ScreenPanel
      v-if="regions.detailsContentIsPanel()"
      :id="DETAILS_PANEL_ID"
      :label="t('Details')"
      icon="circle-info"
    >
      <LayoutSlotOutlet name="content-details">
        <slot name="content-details"></slot>
      </LayoutSlotOutlet>
    </ScreenPanel>

    <!-- Docked beside a preview, the notices sit above the footer, as Live
      Preview's do. -->
    <div
      v-if="slideout?.docked"
      v-show="regions.has('content-notices')"
      class="slideout-screen__notices"
      role="status"
    >
      <LayoutSlotOutlet name="content-notices">
        <slot name="content-notices"></slot>
      </LayoutSlotOutlet>
    </div>

    <footer v-if="!props.hideFooter" class="slideout-screen__footer">
      <ContentFooter
        :read-only="readOnly"
        :form="form"
        :default-form-actions="props.defaultFormActions ?? DEFAULT_FORM_ACTIONS"
        :form-actions="props.formActions"
        :form-additional-actions="props.formAdditionalActions"
        :form-additional-buttons="props.formAdditionalButtons"
        :full-page-form="canSave && !props.saveDisabled"
        :submit-button-label="submitLabel"
        :primary-action-html="chrome.primaryAction"
        :submitting="htmlScreen.submitting.value || elementEditor.saving.value"
        :save-disabled="props.saveDisabled"
        @save="saveWithOptions"
      >
        <template v-for="name in regions.footerSlots()" :key="name" #[name]>
          <slot :name="name"></slot>
        </template>
        <template v-if="!slideout?.workflowActionsOnly" #shell-actions>
          <craft-button type="button" @click="close">
            {{ cancelLabel }}
          </craft-button>
        </template>
      </ContentFooter>
    </footer>

    <!-- Regions a slideout has no place for. Kept as hidden outlets so a page
      written for a full page doesn't drop its teleported content here. -->
    <div hidden>
      <LayoutSlotOutlet name="context-menu" />
      <LayoutSlotOutlet name="title" />
      <LayoutSlotOutlet name="content-toolbar" />
      <LayoutSlotOutlet name="content-sidebar" />
      <LayoutSlotOutlet name="subnav-actions" />
      <LayoutSlotOutlet name="page-header" />
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
    border-block-end: 1px solid var(--c-color-border-quiet);
    min-height: var(--cp-header-height);
    display: grid;
    align-items: center;
  }

  /* A long title truncates, so the header keeps to one line. */
  .slideout-screen__heading {
    min-width: 0;
  }

  .slideout-screen__title {
    overflow: hidden;
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    margin-inline-end: auto;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .slideout-screen__actions {
    --_link-min-width: calc(24px + var(--c-spacing-sm));

    flex-shrink: 0;
  }

  .slideout-screen__toolbar {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm, 0.5rem);
  }

  /* Fills the space between the header and footer. */
  .slideout-screen__body {
    flex: 1;
  }

  .slideout-screen__notices {
    display: grid;
    gap: var(--c-spacing-sm, 0.5rem);
  }

  /* Matches the page shell's `.cp-content__footer`. */
  .slideout-screen__footer {
    display: grid;
    align-content: center;
    border-block-start: 1px solid var(--c-color-border-quiet);
    padding-block: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding);
    min-height: var(--cp-footer-height);
  }
</style>
