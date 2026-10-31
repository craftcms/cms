<script setup lang="ts">
  /**
   * The element editor, for full pages and slideouts alike. It renders no
   * chrome of its own: it configures the surrounding shell through
   * `useAppLayout()` and `LayoutSlot`, so the shell decides where the header,
   * save controls and details column go.
   */
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    provide,
    ref,
    shallowReactive,
    useTemplateRef,
    watch,
    type Component,
  } from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import MetadataDetailsContent from '@/common/components/MetadataDetailsContent.vue';
  import AutosaveMessage from '@/modules/elements/components/AutosaveMessage.vue';
  import ElementActionMenu from '@/modules/elements/components/ElementActionMenu.vue';
  import {createElementActionDispatcher} from '@/modules/elements/composables/useElementActionMenu';
  import ElementActivityAvatars from '@/modules/elements/components/ElementActivityAvatars.vue';
  import ElementViewButtons from '@/modules/elements/components/ElementViewButtons.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useIsSlideout} from '@/common/composables/screen';
  import {useSlideout} from '@/common/slideouts/useSlideout';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {useElementEditor} from '@/modules/elements/composables/useElementEditor';
  import type {UiValues} from '@/modules/ui/types';
  import {isSidebarTab, type SidebarTabOutlets} from '@/modules/ui/sidebarTabs';
  import ElementDetailsPanels from '@/modules/elements/components/ElementDetailsPanels.vue';
  import {elementDetailsPanelRegistry} from '@/bootstrap/element-details-panels';
  import type {FormSaveOptions} from '@/common/types';
  import WorkflowEditLockCallout from '@/modules/workflows/components/WorkflowEditLockCallout.vue';
  import WorkflowDraftsStatus from '@/modules/workflows/components/WorkflowDraftsStatus.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import {NestedOwnerEditorKey} from '@/modules/elements/nested-owner';
  import {
    AssistantEditorKey,
    assistantContainer,
    registerAssistantEditor,
    type AssistantEditor,
  } from '@/modules/ai/assistant/context';
  import LivePreview from '@/modules/elements/live-preview/LivePreview.vue';
  import {useLivePreview} from '@/modules/elements/live-preview/useLivePreview';
  import {getActivePreview} from '@/modules/elements/live-preview/previews';
  import ReleaseCallout from '@/modules/releases/components/ReleaseCallout.vue';
  import ReleasePicker from '@/modules/releases/components/ReleasePicker.vue';
  import {provideReleaseEditorActions} from '@/modules/releases/releaseEditor';
  import type {ReleaseOption} from '@/modules/elements/composables/useElementEditor';
  import {create} from '@actions/ReleasesController';
  import {openSlideout} from '@/common/slideouts';
  import {registerActions} from '@/modules/command-palette/registry';
  import type {CommandPaletteItem} from '@/modules/command-palette/types';
  import {firstFocusableWithin} from '@/common/utils/dom';

  interface CreatedReleaseResponse {
    release?: {
      id: number;
      name: string;
      status: 'draft';
      targetDate?: string | null;
    };
  }

  const contentEl = useTemplateRef<HTMLElement>('content');
  const slideout = useSlideout();

  const props = defineProps<{
    /**
     * Identity attributes merged into every submission — the one per-type
     * piece of the pipeline (e.g. an entry's `entryId`/`sectionId`).
     */
    saveData?: () => UiValues;
    transform?: (data: object) => UiValues;
    formWrapper?: Component;
    showDetails?: boolean;
  }>();

  const editor = useElementEditor({
    saveData: props.saveData,
    transform: props.transform,
    root: () => contentEl.value,
  });

  const {
    activity,
    activityTimelineVersion,
    autosave,
    addToRelease,
    discardDraft,
    errors,
    form,
    uiPayload,
    onMutation,
    onSidebarMutation,
    props: payload,
    renderer,
    refreshUi,
    refreshLayout,
    save,
    sidebarErrors,
    sidebarPayload,
    sidebarRenderer,
    startEditingDraft,
    releaseDraftLocked,
    submitAction,
    updatePayload,
    workflowReviewLocked,
  } = editor;

  provide(NestedOwnerEditorKey, editor.nestedOwnerEditor);

  // The AI assistant reads the form's values, and applies the changes it
  // proposes, through the fields that render each control path.
  const assistantParams = payload.aiAssistant;

  if (assistantParams) {
    const assistantEditor: AssistantEditor = {
      id: crypto.randomUUID(),
      params: () => payload.aiAssistant ?? assistantParams,
      label: () => payload.title,
      scope: () => uiPayload.value?.scope ?? [],
      values: editor.formValues,
      container: () =>
        contentEl.value ? assistantContainer(contentEl.value) : null,
    };

    provide(AssistantEditorKey, assistantEditor);
    onBeforeUnmount(registerAssistantEditor(assistantEditor));
  }

  /**
   * Fields outside of a tab — in a field layout whose tab was never saved,
   * say — need the spacing a tab would otherwise give them.
   */
  const hasUntabbedFields = computed(
    () =>
      uiPayload.value?.nodes.some((node) => node.component !== 'craft:tab') ??
      false
  );

  /**
   * Whether the content starts with a tab strip, which sits flush with its
   * top. In Live Preview, sidebar tabs are in it too.
   */
  const hasContentTabs = computed(
    () =>
      (uiPayload.value?.nodes.filter(
        (node) =>
          node.component === 'craft:tab' &&
          (contentInPreview.value || !isSidebarTab(node))
      ).length ?? 0) > 1
  );

  /** Field layout tabs that render as details panels, rather than tabs. */
  const sidebarTabs = computed(
    () => uiPayload.value?.nodes.filter(isSidebarTab) ?? []
  );
  const sidebarTabOutlets: SidebarTabOutlets = shallowReactive(new Map());
  const sidebarTabOutletRefs = new Map<string, (el: unknown) => void>();

  /**
   * A stable ref callback per tab, so Vue only calls it as the tab's panel
   * mounts and unmounts. A panel can remount (when the details region moves),
   * and the new one may register before the old one lets go.
   */
  function sidebarTabOutletRef(uid: string): (el: unknown) => void {
    let ref = sidebarTabOutletRefs.get(uid);

    if (!ref) {
      ref = (el: unknown) => {
        if (el instanceof HTMLElement) {
          sidebarTabOutlets.set(uid, el);
        } else if (!sidebarTabOutlets.get(uid)?.isConnected) {
          sidebarTabOutlets.delete(uid);
        }
      };
      sidebarTabOutletRefs.set(uid, ref);
    }

    return ref;
  }

  const hasDetails = computed(
    () =>
      Boolean(props.showDetails) ||
      sidebarTabs.value.length > 0 ||
      Boolean(sidebarPayload.value) ||
      Boolean(payload.metadataHtml) ||
      Boolean(payload.activityTimelineUrl) ||
      elementDetailsPanelRegistry.hasVisible(payload)
  );
  const detailsPanels = useTemplateRef<{select: (panelId: string) => void}>(
    'detailsPanels'
  );

  const editorLocked = computed(
    () =>
      workflowReviewLocked.value || payload.readOnly || releaseDraftLocked.value
  );

  const canChooseRelease = computed(
    () =>
      payload.canAddToRelease &&
      (payload.releaseOptions.length > 0 || payload.canCreateRelease)
  );
  // Alternate saves in the Save button's menu, and the buttons grouped with
  // it (Create a draft, …).
  const formActionItems = computed(() =>
    payload.editorActions.menu.map((action) => ({
      label: action.label,
      shortcut: action.shortcut ? {key: 'S', shift: action.shift} : undefined,
      onClick: () => submitAction(action),
    }))
  );

  const dispatchBehavior = createElementActionDispatcher({
    currentEntryTypeId: () => form.typeId ?? null,
    slideout,
  });

  const saveButtons = computed(() =>
    payload.editorActions.buttons.map((action) => ({
      label: action.label,
      variant: action.variant,
      disabled: action.disabled,
      disabledReason: action.disabledReason,
      onClick: () => submitAction(action),
    }))
  );

  // Mirrors the legacy wording: a changed draft names the draft, anything else
  // names the element type.
  const staleMessage = computed(() =>
    t('This {type} has been updated.', {
      type:
        activity.staleType.value === 'element' &&
        payload.draftId !== null &&
        !payload.isProvisionalDraft
          ? t('draft')
          : payload.elementDisplayName,
    })
  );

  // In a panel, the panel's own screen is what's out of date — not the page
  // behind it.
  /** Shows the latest version, which is what the stale notice asks for. */
  function reload(): void {
    if (slideout) {
      void slideout
        .reload()
        .then(() => activity.rebase(payload.updatedTimestamps));
    } else {
      router.reload({
        onSuccess: () => activity.rebase(payload.updatedTimestamps),
      });
    }
  }

  function primaryAction(options?: FormSaveOptions): void {
    if (payload.editorActions.primary.panelId) {
      detailsPanels.value?.select(payload.editorActions.primary.panelId);
      return;
    }

    save(options);
  }

  async function startEditing(): Promise<void> {
    startEditingDraft();
    await nextTick();

    if (contentEl.value) {
      firstFocusableWithin(contentEl.value)?.focus();
    }
  }

  function chooseRelease(release: ReleaseOption): void {
    addToRelease(release);
  }

  function createRelease(opener: HTMLElement | null): void {
    void openSlideout(create().url, {
      opener,
      onSaved: ({data}) => {
        const release = (data as CreatedReleaseResponse | undefined)?.release;

        if (!release) {
          throw new Error(
            'The saved release response did not include a release.'
          );
        }

        chooseRelease({
          id: release.id,
          name: release.name,
          status: release.status,
          targetDate: release.targetDate ?? null,
        });
      },
    });
  }

  provideReleaseEditorActions({
    select: chooseRelease,
    create: createRelease,
  });

  const isSlideout = useIsSlideout();
  const page = usePage<{readOnly?: boolean}>();
  const showElementControls = computed(
    () => !isSlideout && !page.props.readOnly && !payload.release
  );
  const showActionMenu = computed(
    () => (isSlideout || !page.props.readOnly) && !payload.release
  );

  // A slideout opened from within a preview edits beside that preview, so it
  // doesn't open one of its own.
  const inOpenPreview = isSlideout && getActivePreview() !== null;
  const livePreview = useLivePreview(
    () => (inOpenPreview ? null : (payload.preview ?? null)),
    {
      save: () => autosave.save(),
      get hasPendingChanges() {
        return autosave.hasPendingChanges.value;
      },
    }
  );
  // Slideouts get View too, as Craft 5's had it in their action menu, which
  // leaves it out once there are targets.
  const showViewButtons = computed(
    () =>
      (showElementControls.value || (isSlideout && !payload.release)) &&
      payload.previewTargets.length > 0
  );
  const canPreview = computed(
    () =>
      !inOpenPreview &&
      (showElementControls.value || (isSlideout && !payload.release)) &&
      Boolean(payload.preview?.targets.length)
  );
  const livePreviewPane =
    useTemplateRef<InstanceType<typeof LivePreview>>('livePreviewPane');
  const livePreviewTarget = computed(
    () => livePreviewPane.value?.editorTarget ?? null
  );
  /** Whether the editor content is in Live Preview's pane rather than here. */
  const contentInPreview = computed(
    () =>
      Boolean(livePreviewPane.value?.holdsEditor) &&
      Boolean(livePreviewTarget.value)
  );
  // The content's height, kept while it's away so the page doesn't collapse
  // and lose its scroll position. Read before the content moves.
  const awayHeight = ref(0);
  watch(
    contentInPreview,
    (away) => {
      if (away) {
        awayHeight.value = contentEl.value?.offsetHeight ?? 0;
      }
    },
    {flush: 'pre'}
  );
  const previewButton = useTemplateRef<HTMLElement>('previewButton');

  /** Whether the draft is in review, waiting on the user's approval of its current stage. */
  const awaitingUserApproval = computed(
    () =>
      payload.workflow.current?.status === 'pending' &&
      payload.workflow.current.isReviewer &&
      !payload.workflow.current.hasApproved
  );
  const approvalNoticeDismissed = ref(false);
  /** Live Preview's two panes don't fit on a phone, so it isn't offered there. */
  const isPhone = cpBreakpoints.smaller('md');

  watch(autosave.saves, () => {
    livePreview.draftSaved(autosave.lastResponse.value);

    // A newer save is on its way, so this one's page would only be replaced.
    if (!autosave.hasPendingChanges.value) {
      void livePreview.refresh();
    }
  });

  // The screen is only there for the preview.
  onMounted(() => {
    if (payload.standalonePreview && canPreview.value) {
      void livePreview.open();
    }
  });

  /**
   * What the command palette offers on this page: the Save button and its
   * menu, discarding provisional changes, adding the draft to a release, and
   * copying the element's ID, URL and reference tag. Read each time the
   * palette opens; a slideout's editor leaves the palette to the page's.
   */
  function paletteActions(): CommandPaletteItem[] {
    const items: CommandPaletteItem[] = [];
    const description = t('This page');

    if (!editorLocked.value) {
      items.push(
        {
          id: 'editor-save',
          label: payload.editorActions.primary.label,
          description,
          icon: 'floppy-disk',
          run: () => primaryAction(),
        },
        ...formActionItems.value.map((action, index) => ({
          id: `editor-save-menu-${index}`,
          label: action.label,
          description,
          icon: 'floppy-disk',
          run: action.onClick,
        })),
        ...saveButtons.value
          .filter((button) => !button.disabled)
          .map((button, index) => ({
            id: `editor-button-${index}`,
            label: button.label,
            description,
            icon: 'pen-to-square',
            run: button.onClick,
          }))
      );

      if (payload.notice && payload.canDiscardDraft) {
        items.push({
          id: 'editor-discard-changes',
          label: t('Discard changes'),
          description,
          icon: 'arrow-rotate-left',
          variant: 'danger',
          confirm: t('Are you sure you want to discard your changes?'),
          run: discardDraft,
        });
      }
    }

    if (!payload.readOnly && canChooseRelease.value) {
      items.push(
        ...payload.releaseOptions.map((release) => ({
          id: `editor-add-to-release-${release.id}`,
          label: t('Add to {release}', {release: release.name}),
          description,
          icon: 'rocket',
          run: () => chooseRelease(release),
        }))
      );

      if (payload.canCreateRelease) {
        items.push({
          id: 'editor-add-to-new-release',
          label: t('Add to a new release'),
          description,
          icon: 'rocket',
          run: () => createRelease(null),
        });
      }
    }

    const id = payload.canonicalId ?? payload.elementId;

    if (id !== null) {
      items.push({
        id: 'editor-copy-id',
        label: t('Copy ID'),
        description,
        icon: 'clipboard',
        action: {type: 'clipboard', value: String(id)},
      });

      if (payload.refHandle) {
        items.push({
          id: 'editor-copy-reference-tag',
          label: t('Copy reference tag'),
          description,
          icon: 'clipboard',
          action: {type: 'clipboard', value: `{${payload.refHandle}:${id}}`},
        });
      }
    }

    if (payload.elementUrl) {
      items.push({
        id: 'editor-copy-url',
        label: t('Copy URL'),
        description,
        icon: 'clipboard',
        action: {type: 'clipboard', value: payload.elementUrl},
      });
    }

    return items;
  }

  if (!isSlideout) {
    onBeforeUnmount(registerActions(paletteActions).unregister);
  }

  useAppLayout(() => ({
    title: payload.title,
    form,
    onSave: editorLocked.value ? undefined : primaryAction,
    saveDisabled: editorLocked.value,
    handlesReadOnly: true,
    submitButtonLabel: payload.editorActions.primary.label,
    // The element supplies its own full set of alternate saves — including its
    // own "Save and continue editing" — so the layout's default would duplicate.
    defaultFormActions: [],
    formActions: formActionItems.value,
    formAdditionalButtons: saveButtons.value,
    editUrl: payload.cpEditUrl,
  }));
</script>

<template>
  <!-- The shell's own summary is worded for settings screens. -->
  <LayoutSlot v-if="form.hasErrors" name="error-summary">
    <ErrorSummary :errors="form.errors" :title="t('Couldn’t save changes')" />
  </LayoutSlot>

  <LayoutSlot
    v-if="payload.isProvisionalDraft || payload.statusLabelHtml"
    name="content-toolbar-meta"
  >
    <craft-badge
      v-if="payload.isProvisionalDraft"
      fill="info"
      class="relative text-sm font-normal inline-flex"
    >
      <craft-icon name="pen-circle" slot="prefix"></craft-icon>
      {{ t('Edited') }}
    </craft-badge>
    <DynamicHtmlRenderer
      v-else-if="payload.statusLabelHtml"
      :html="payload.statusLabelHtml"
    />
  </LayoutSlot>

  <!--
    Each piece below is its own component, so moving one is a matter of changing
    the layout slot it's placed in. The save buttons are the exception: they
    ride in `useAppLayout`'s form-action props, so the shell groups them with
    its Save button.
  -->
  <LayoutSlot
    v-if="
      activity.activity.value.length ||
      (!isSlideout && payload.workflow.draftReviews.length)
    "
    name="content-toolbar-meta"
  >
    <ElementActivityAvatars :entries="activity.activity.value" />
    <WorkflowDraftsStatus
      v-if="!isSlideout && payload.workflow.draftReviews.length"
      :drafts="payload.workflow.draftReviews"
    />
  </LayoutSlot>

  <LayoutSlot v-if="payload.additionalButtons.length" name="additional-buttons">
    <craft-button
      v-for="button in payload.additionalButtons"
      :key="button.label"
      type="button"
      :variant="button.variant ?? 'outline'"
      @click="dispatchBehavior(button.behavior)"
    >
      <craft-icon
        v-if="button.icon"
        :name="button.icon"
        slot="prefix"
      ></craft-icon>
      {{ button.label }}
    </craft-button>
  </LayoutSlot>

  <LayoutSlot v-if="showViewButtons" name="content-toolbar-actions">
    <div class="flex gap-sm items-center">
      <craft-button
        v-if="canPreview && !isPhone"
        ref="previewButton"
        type="button"
        size="small"
        variant="plain"
        icon="eye"
        @click="livePreview.open()"
      >
        {{ t('Preview') }}
      </craft-button>
      <ElementViewButtons :targets="payload.previewTargets" />
    </div>
  </LayoutSlot>

  <LayoutSlot
    v-if="showActionMenu && payload.actionMenu.length"
    name="content-toolbar-actions"
  >
    <ElementActionMenu
      :items="payload.actionMenu"
      :current-entry-type-id="form.typeId"
      :slideout="slideout"
      :flush="!isSlideout"
    />
  </LayoutSlot>

  <!-- Where the legacy editor puts its spinner and checkmark. -->
  <LayoutSlot v-if="autosave.status.value !== 'idle'" name="footer-meta">
    <AutosaveMessage
      :status="autosave.status.value"
      :saved-at="autosave.savedAt.value"
      :error="autosave.error.value"
      :http-status="autosave.httpStatus.value"
      @refresh="reload"
    />
  </LayoutSlot>

  <LayoutSlot
    v-if="!payload.readOnly && canChooseRelease"
    name="additional-buttons"
  >
    <ReleasePicker
      :releases="payload.releaseOptions"
      :can-create="payload.canCreateRelease"
      :disabled="form.processing"
      :button-variant="ButtonVariant.Outline"
      @select="chooseRelease"
      @create="createRelease"
    />
  </LayoutSlot>

  <LayoutSlot
    v-if="
      activity.isStale.value ||
      payload.readOnly ||
      payload.notice ||
      payload.mergeNotice ||
      payload.workflow.convertedToDraft ||
      workflowReviewLocked ||
      (awaitingUserApproval && !approvalNoticeDismissed) ||
      payload.workflow.current?.status === 'withdrawn' ||
      payload.release
    "
    name="content-notices"
  >
    <div class="element-notices">
      <craft-callout
        v-if="payload.workflow.convertedToDraft && !workflowReviewLocked"
        variant="warning"
        icon="triangle-exclamation"
        class="mb-4"
        rounded="none"
        appearance="fill"
      >
        {{
          t(
            'Your changes are saved in a draft and won’t be published until the draft is approved and applied.'
          )
        }}
      </craft-callout>

      <WorkflowEditLockCallout
        v-if="workflowReviewLocked"
        :payload="payload"
        :update-payload="updatePayload"
        class="mb-4"
        @start-editing="startEditing"
      />

      <craft-callout
        v-if="awaitingUserApproval && !approvalNoticeDismissed"
        variant="info"
        icon="clipboard-check"
        class="mb-4"
        rounded="none"
        appearance="fill"
      >
        {{ t('This draft is waiting on your approval.') }}
        <craft-button
          slot="action"
          type="button"
          variant="plain"
          size="small"
          icon
          inherit
          @click="approvalNoticeDismissed = true"
        >
          <craft-icon name="xmark" :label="t('Dismiss')"></craft-icon>
        </craft-button>
      </craft-callout>

      <craft-callout
        v-if="payload.workflow.current?.status === 'withdrawn'"
        variant="info"
        icon="clipboard-list-check"
        class="mb-4"
        rounded="none"
        appearance="fill"
      >
        {{
          payload.workflow.current.canSubmit
            ? t(
                'This draft was withdrawn from review. Resubmit it when your edits are ready.'
              )
            : t('This draft was withdrawn from review.')
        }}
        <craft-button
          v-if="payload.workflow.current.canSubmit"
          slot="action"
          type="button"
          variant="outline"
          size="small"
          inherit
          @click="detailsPanels?.select('workflow')"
        >
          {{ payload.workflow.current.submitLabel }}
        </craft-button>
      </craft-callout>

      <ReleaseCallout
        v-if="payload.release"
        :release="payload.release"
        :can-edit="!payload.readOnly"
        :editing="!releaseDraftLocked"
        @start-editing="startEditing"
        class="mb-4"
      />

      <craft-callout
        v-if="activity.isStale.value"
        variant="warning"
        icon="triangle-exclamation"
        class="mb-4"
        appearance="fill"
        rounded="none"
      >
        {{ staleMessage }}

        <craft-button
          slot="action"
          type="button"
          variant="outline"
          size="small"
          @click="reload"
          inherit
        >
          {{ t('Reload') }}
        </craft-button>
      </craft-callout>

      <!-- A revision's notice already says why nothing is editable. -->
      <craft-callout
        v-if="payload.readOnly && !payload.notice"
        variant="neutral"
        rounded="none"
        appearance="fill"
        icon="lock"
      >
        {{ t('This is a read-only view.') }}
      </craft-callout>

      <craft-callout
        v-if="payload.notice"
        variant="accent"
        icon="edit"
        class="mb-4"
        rounded="none"
        appearance="fill"
      >
        {{ payload.notice }}

        <craft-button
          v-if="payload.canDiscardDraft"
          slot="action"
          type="button"
          variant="outline"
          size="small"
          @click="discardDraft"
          inherit
        >
          {{ t('Discard changes') }}
        </craft-button>
      </craft-callout>

      <craft-callout
        v-if="payload.mergeNotice"
        variant="warning"
        icon="triangle-exclamation"
        class="mb-4"
        rounded="none"
        appearance="fill"
      >
        {{ payload.mergeNotice }}
      </craft-callout>
    </div>
  </LayoutSlot>

  <!-- While Live Preview holds the content: seen only as it slides out. -->
  <div
    v-if="contentInPreview"
    class="element-editor__loading"
    :style="{blockSize: `${awayHeight}px`}"
  >
    <craft-spinner :label="t('Loading')"></craft-spinner>
  </div>
  <Teleport :to="livePreviewTarget" :disabled="!contentInPreview" defer>
    <div ref="content" :class="hasContentTabs ? 'pb-lg' : 'py-lg'">
      <CpContainer>
        <component
          :is="formWrapper ?? 'div'"
          v-bind="formWrapper ? {editor, region: 'content'} : {}"
        >
          <component
            :is="hasUntabbedFields ? 'craft-field-group' : 'div'"
            v-if="uiPayload"
          >
            <UiRenderer
              ref="renderer"
              :payload="uiPayload"
              :errors="errors"
              :refresh="uiPayload.refreshable ? refreshLayout : undefined"
              :modified="autosave.modified.value"
              :disabled="editorLocked"
              :sidebar-tab-outlets="
                contentInPreview ? undefined : sidebarTabOutlets
              "
              @update:mutation="onMutation"
            />
          </component>
        </component>

        <slot :payload="payload" />
      </CpContainer>
    </div>
  </Teleport>

  <LivePreview
    v-if="canPreview"
    ref="livePreviewPane"
    :controller="livePreview"
    :title="payload.title"
    :standalone="payload.standalonePreview ?? null"
    @save="primaryAction()"
    @closed="previewButton?.focus()"
  >
    <!-- The standalone page has no other header to reach them from. -->
    <template v-if="payload.standalonePreview" #actions>
      <ElementActionMenu
        :items="payload.actionMenu"
        :current-entry-type-id="form.typeId"
      />
    </template>
    <!-- The status a slideout's header shows beside its title. -->
    <template #meta>
      <craft-badge
        v-if="payload.isProvisionalDraft"
        fill="info"
        class="relative text-sm font-normal inline-flex"
      >
        <craft-icon name="pen-circle" slot="prefix"></craft-icon>
        {{ t('Edited') }}
      </craft-badge>
      <DynamicHtmlRenderer
        v-else-if="payload.statusLabelHtml"
        :html="payload.statusLabelHtml"
      />
    </template>
    <template #status>
      <AutosaveMessage
        v-if="autosave.status.value !== 'idle'"
        :status="autosave.status.value"
        :saved-at="autosave.savedAt.value"
        :error="autosave.error.value"
        :http-status="autosave.httpStatus.value"
        @refresh="reload"
      />
    </template>
    <template #footer>
      <craft-button
        type="button"
        variant="primary"
        :disabled="workflowReviewLocked"
        @click="primaryAction()"
      >
        {{ payload.editorActions.primary.label }}
      </craft-button>
    </template>
  </LivePreview>

  <ElementDetailsPanels
    v-if="hasDetails || $slots['details-header']"
    ref="detailsPanels"
    :payload="payload"
    :activity-timeline-version="activityTimelineVersion"
    :update-payload="updatePayload"
    :submit-action="submitAction"
    :sidebar-tabs="sidebarTabs"
  >
    <template #sidebar-tab="{tab}">
      <div
        :ref="sidebarTabOutletRef(tab.uid)"
        class="py-lg px-(--cp-container-padding)"
      ></div>
    </template>
    <template #info>
      <!-- Anything the element type shows above its meta fields, e.g. an
        asset's file preview. -->
      <slot name="details-header" :payload="payload" />

      <MetadataDetailsContent :html="payload.metadataHtml">
        <!-- Only when there's something to show above the metadata: a form
          wrapper has nothing of its own unless it says so through showDetails. -->
        <template
          v-if="sidebarPayload || (formWrapper && showDetails)"
          #default
        >
          <component
            :is="formWrapper ?? 'div'"
            v-bind="formWrapper ? {editor, region: 'sidebar'} : {}"
          >
            <craft-field-group v-if="sidebarPayload">
              <UiRenderer
                ref="sidebarRenderer"
                :payload="sidebarPayload"
                :errors="sidebarErrors"
                :modified="autosave.modified.value"
                :disabled="editorLocked"
                @update:mutation="onSidebarMutation"
              />
            </craft-field-group>
          </component>
        </template>
      </MetadataDetailsContent>
    </template>
  </ElementDetailsPanels>
</template>

<style scoped lang="scss">
  .element-editor__loading {
    display: flex;
    justify-content: center;

    & craft-spinner {
      position: sticky;
      inset-block-start: calc(50% + var(--cp-header-height, 0px) / 2);
      align-self: flex-start;
      translate: 0 -50%;
    }
  }

  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 1px);
    --c-callout-icon-padding-inline: 0 var(--c-spacing-sm);
  }
</style>
