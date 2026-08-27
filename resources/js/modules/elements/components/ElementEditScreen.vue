<script setup lang="ts">
  /**
   * Full-page element editor. Takes the shell's `main` slot, so it renders the
   * `#main` landmark and owns its own `<form>`, and is free to arrange the
   * breadcrumbs, header and columns itself. `ElementEditor` is the counterpart
   * for hosts that supply their own chrome, e.g. a slideout panel.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {router} from '@inertiajs/vue3';
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import Breadcrumbs, {
    type BreadcrumbItem,
  } from '@/common/components/Breadcrumbs.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import FormActions from '@/common/components/FormActions.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import {useElementEditor} from '@/modules/elements/composables/useElementEditor';
  import {useElementActionMenu} from '@/modules/elements/composables/useElementActionMenu';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import AutosaveMessage from '@/modules/elements/components/AutosaveMessage.vue';
  import type {FormValues} from '@/modules/forms/types';

  const props = defineProps<{
    /**
     * Identity attributes merged into every submission — the one per-type
     * piece of the pipeline (e.g. an entry's `entryId`/`sectionId`).
     */
    saveData?: () => FormValues;
  }>();

  defineSlots<{
    /** Extra content below the field layout. */
    default?: (props: {payload: Record<string, unknown>}) => any;
    /** Above the meta fields, e.g. an asset's file preview. */
    'details-header'?: (props: {payload: Record<string, unknown>}) => any;
  }>();

  const {
    activity,
    autosave,
    discardDraft,
    errors,
    form,
    formPayload,
    onMutation,
    onSidebarMutation,
    props: payload,
    renderer,
    save,
    sidebarErrors,
    sidebarPayload,
    sidebarRenderer,
    submitAction,
  } = useElementEditor({saveData: props.saveData});

  const crumbs = computed(
    () => (payload.crumbs ?? []) as Array<BreadcrumbItem>
  );

  // Alternate saves in the Save button's menu, and the buttons beside it.
  const formActionItems = computed(() =>
    payload.formActions.map((action) => ({
      label: action.label,
      onClick: () => submitAction(action),
    }))
  );

  const headerButtons = computed(() =>
    payload.headerActions.map((action) => ({
      label: action.label,
      variant: action.variant,
      onClick: () => submitAction(action),
    }))
  );

  // The element's own actions (Validate, Copy, Delete, …). Behaviors are
  // dispatched client-side rather than via registered jQuery handlers.
  const actionMenuItems = useElementActionMenu(() => payload.actionMenu, {
    // The entry type can be switched in the sidebar without saving, so the
    // settings slideout should follow the field rather than the stored value.
    currentEntryTypeId: () => form.typeId ?? null,
  });

  // "View" opens the element on the front end. The hrefs arrive ready to
  // follow — a live element points at its own URL, anything else at a
  // token-minting redirect that lands on the tokenized preview.
  const viewButtons = computed(() =>
    payload.previewTargets.map((target, index) => ({
      label: payload.previewTargets.length === 1 ? t('View') : target.label,
      variant: 'outline',
      key: `view-${index}`,
      onClick: () => window.open(target.url, '_blank', 'noopener'),
    }))
  );

  const additionalButtons = computed(() => [
    ...viewButtons.value,
    ...headerButtons.value,
  ]);

  const hasDetails = computed(
    () => Boolean(sidebarPayload.value) || Boolean(payload.metadataHtml)
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

  function reload(): void {
    router.reload();
  }
</script>

<template>
  <!-- The form-related props aren't the shell's business here — the `main`
    slot replaces the region that would have used them. They're left off so the
    same markup keeps working if this ever renders under a shell that owns the
    save UI. -->
  <AppLayout :title="payload.title">
    <template #main>
      <main id="main" tabindex="-1" class="element-editor">
        <!-- No drafts-and-revisions switcher beside the crumbs: the Revisions
        tab in the details column is that list now. -->

        <form method="post" @submit.prevent="save()">
          <div class="sticky top-0 z-1000 pb-2">
            <header
              class="pt-3 pb-1 bg-(--c-color-neutral-fill-quiet) px-(--c-spacing-lg)"
            >
              <div>
                <h1 class="text-xl/7">
                  {{ payload.title }}
                </h1>
              </div>
              <div class="flex justify-between">
                <div class="flex gap-1 items-center">
                  <craft-badge
                    fill="info"
                    v-if="payload.isProvisionalDraft"
                    class="relative text-sm font-normal inline-flex"
                  >
                    <craft-icon name="pen-circle" slot="prefix"></craft-icon>
                    {{ t('Edited') }}
                  </craft-badge>
                  <DynamicHtmlRenderer
                    v-else-if="payload.statusLabelHtml"
                    :html="payload.statusLabelHtml"
                  />

                  <AutosaveMessage :autosave="autosave" />
                </div>

                <div class="element-editor__status">
                  <!-- Who else is working on this element. -->
                  <div
                    v-if="activity.activity.value.length"
                    role="region"
                    :aria-label="t('Recent Activity')"
                    class="flex items-center gap-1"
                  >
                    <span
                      v-for="entry in activity.activity.value"
                      :key="entry.userId"
                      :title="entry.message"
                      :aria-label="entry.message"
                      class="inline-flex"
                      v-html="entry.userThumb"
                    />
                  </div>
                </div>

                <FormActions
                  :form="form"
                  :action-items="formActionItems"
                  :additional-actions="actionMenuItems"
                  :additional-buttons="additionalButtons"
                  :submit-label="payload.submitButtonLabel"
                  :read-only="payload.readOnly"
                />
              </div>
            </header>
            <div class="element-notices">
              <craft-callout
                v-if="payload.notice"
                variant="info"
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
                v-if="activity.isStale.value"
                variant="warning"
                icon="triangle-exclamation"
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

              <craft-callout
                v-if="payload.readOnly"
                variant="neutral"
                icon="lock"
              >
                {{ t('This is a read-only view.') }}
              </craft-callout>

              <craft-callout
                v-if="payload.mergeNotice"
                variant="warning"
                icon="triangle-exclamation"
              >
                {{ payload.mergeNotice }}
              </craft-callout>
            </div>
          </div>

          <div v-if="form.hasErrors" class="px-4">
            <ErrorSummary v-if="form.hasErrors" :errors="form.errors" />
          </div>

          <div
            class="element-editor__body"
            :class="{'element-editor__body--details': hasDetails}"
          >
            <div class="element-editor__content">
              <craft-pane padding="none" appearance="plain">
                <div class="py-1">
                  <!-- Tabs are rendered by `FormNodeList` inside the form itself. -->
                  <div class="element-form">
                    <FormRenderer
                      v-if="formPayload"
                      ref="renderer"
                      :payload="formPayload"
                      :errors="errors"
                      @update:mutation="onMutation"
                    />

                    <slot :payload="payload" />
                  </div>
                </div>
              </craft-pane>
            </div>

            <div
              v-if="hasDetails || $slots['details-header']"
              class="element-editor__details"
            >
              <craft-tabs size="small" placement="inline-end" collapsible>
                <craft-tab slot="tab">
                  <craft-icon
                    name="circle-info"
                    :label="t('Info')"
                  ></craft-icon>
                </craft-tab>
                <div slot="panel">
                  <craft-pane appearance="plain" padding="none">
                    <div
                      slot="header"
                      class="px-2 py-1 border-b border-b-(--c-color-neutral-border-quiet)"
                    >
                      <h3 slot="title" class="text-xs/4">{{ t('Info') }}</h3>
                    </div>
                    <div class="py-4">
                      <div class="grid gap-4">
                        <slot name="details-header" :payload="payload" />

                        <craft-field-group>
                          <FormRenderer
                            v-if="sidebarPayload"
                            ref="sidebarRenderer"
                            :payload="sidebarPayload"
                            :errors="sidebarErrors"
                            @update:mutation="onSidebarMutation"
                          />
                        </craft-field-group>

                        <hr />
                        <div class="px-4">
                          <DynamicHtmlRenderer
                            v-if="payload.metadataHtml"
                            :html="payload.metadataHtml"
                          />
                        </div>
                      </div>
                    </div>
                  </craft-pane>
                </div>

                <craft-tab slot="tab" id="tab-1">
                  <craft-icon
                    name="wave-pulse"
                    :label="t('Activity')"
                  ></craft-icon>
                </craft-tab>
                <div slot="panel">
                  <craft-pane appearance="plain">
                    <div
                      slot="header"
                      class="px-2 py-1 border-b border-b-(--c-color-neutral-border-quiet)"
                    >
                      <h3 slot="title" class="text-xs/4">
                        {{ t('Activity') }}
                      </h3>
                    </div>
                    @TODO
                  </craft-pane>
                </div>

                <craft-tab slot="tab">
                  <craft-icon
                    name="clock-rotate-left"
                    :label="t('Revisions')"
                  ></craft-icon>
                </craft-tab>
                <div slot="panel">
                  <craft-pane appearance="plain">
                    <div
                      slot="header"
                      class="px-2 py-1 border-b border-b-(--c-color-neutral-border-quiet)"
                    >
                      <h3 slot="title" class="text-xs/4">
                        {{ t('Revisions') }}
                      </h3>
                    </div>
                    <RevisionsList :items="payload.contextMenu?.items ?? []" />
                  </craft-pane>
                </div>
              </craft-tabs>
            </div>
          </div>
        </form>
      </main>
    </template>
  </AppLayout>
</template>

<style scoped lang="css">
  .element-editor__crumbs {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: var(--c-spacing-sm);
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-md);
  }

  .element-editor__header {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    padding-inline: var(--c-spacing-lg);
    padding-block: var(--c-spacing-md);
    border-block-end: 1px solid var(--color-neutral-border-quiet);
    position: sticky;
    top: 0;
    z-index: var(--c-z-page-header);
    background-color: white;
  }

  .element-editor__status {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-inline-start: auto;
    padding-inline: var(--c-spacing-md);
  }

  .element-editor__body {
    display: grid;
    gap: var(--c-spacing-md);
    padding-inline: var(--c-spacing-lg);
  }

  .element-editor__content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--c-spacing-md);
    align-content: start;
  }

  /* Wide content — a many-columned table, a long code block — otherwise sets a
     min-content floor that pushes this column out of its track. */
  .element-editor__content > :deep(*) {
    min-width: 0;
  }

  .element-editor__details {
    display: grid;
    gap: var(--c-spacing-lg);
    container-type: inline-size;
    position: sticky;
    top: 60px;
    border-radius: var(--c-radius-lg);
    height: 100%;
  }

  .element-editor__body.element-editor__body--details {
    grid-template-columns: minmax(0, 1fr) clamp(21rem, 25%, 25rem);
  }

  /* Closing the details tabs shrinks the element to just its rail, but the
   track it sits in is sized independently — without this the content would
   keep its width and leave a hole. `auto` hands the space back, and the
   `collapsed` attribute is reflected by `craft-tabs`, so this needs no
   listener and can't fall out of step with the strip. */
  .element-editor__body.element-editor__body--details:has(
      craft-tabs[collapsed]
    ) {
    grid-template-columns: minmax(0, 1fr) auto;

    /* `container-type: inline-size` below is `contain: inline-size`, so the
     column's contents can't size it — an `auto` track would collapse to
     zero and let the rail overhang the page. Containment only exists for
     queries inside the panel, and a collapsed strip has no panel showing,
     so it costs nothing to drop it while closed. */
    .element-editor__details {
      container-type: normal;
    }
  }

  @container (width >= 768px) {
    .element-editor__body {
      align-items: start;
    }
  }

  craft-tabs::part(base) {
    gap: var(--c-spacing-sm);
  }

  craft-tabs::part(strip) {
    border: 0;
  }

  craft-tab {
    padding: 0;
    width: var(--c-size-touch-target);
    background-color: white;
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border-radius: var(--c-radius-md);
    border: 1px solid transparent;
  }

  craft-tab[selected='true'] {
    background-color: var(--c-color-neutral-fill-normal);
    border-color: var(--c-color-neutral-border-normal);
    color: var(--c-color-neutral-on-normal);

    &:after {
      display: none;
    }
  }
</style>
