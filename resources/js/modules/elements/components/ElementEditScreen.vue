<script setup lang="ts">
/**
 * The element editor on the new `EditorScreen` shell.
 *
 * Same pipeline as `ElementEditPage` — `useElementEditPage()` owns the form,
 * autosave, activity polling and saving — but none of the page chrome is the
 * layout's: the breadcrumb bar, header, form element, content column and
 * details column are all rendered here, and free to be rearranged.
 *
 * Full-page only. In a slideout the panel's shell renders its own header,
 * form and footer, so pages dispatch to `ElementEditPage` there.
 */
import { t } from "@craftcms/ui";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/common/layouts/AppLayout.vue";
import EditorScreen from "@/common/layouts/screens/EditorScreen.vue";
import Breadcrumbs, { type BreadcrumbItem } from "@/common/components/Breadcrumbs.vue";
import DynamicHtmlRenderer from "@/common/components/DynamicHtmlRenderer.vue";
import FormActions from "@/common/components/FormActions.vue";
import ErrorSummary from "@/common/form/ErrorSummary.vue";
import FormRenderer from "@/modules/forms/FormRenderer.vue";
import { useElementEditPage } from "@/modules/elements/composables/useElementEditPage";
import { useElementActionMenu } from "@/modules/elements/composables/useElementActionMenu";
import RevisionsList from "@/modules/elements/components/RevisionsList.vue";
import AutosaveMessage from "@/modules/elements/components/AutosaveMessage.vue";

const props = defineProps<{
  /**
   * Identity attributes merged into every submission — the one per-type
   * piece of the pipeline (e.g. an entry's `entryId`/`sectionId`).
   */
  saveData?: () => Record<string, unknown>;
}>();

defineSlots<{
  /** Extra content below the field layout. */
  default?: (props: { payload: Record<string, unknown> }) => any;
  /** Above the meta fields, e.g. an asset's file preview. */
  "details-header"?: (props: { payload: Record<string, unknown> }) => any;
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
} = useElementEditPage({ saveData: props.saveData });

const crumbs = computed(() => (payload.crumbs ?? []) as Array<BreadcrumbItem>);

// Alternate saves in the Save button's menu, and the buttons beside it.
const formActionItems = computed(() =>
  payload.formActions.map((action) => ({
    label: action.label,
    onClick: () => submitAction(action),
  })),
);

const headerButtons = computed(() =>
  payload.headerActions.map((action) => ({
    label: action.label,
    variant: action.variant,
    onClick: () => submitAction(action),
  })),
);

// The element's own actions (Validate, Copy, Delete, …). Behaviors are
// dispatched client-side rather than via registered jQuery handlers.
const actionMenuItems = useElementActionMenu(() => payload.actionMenu, {
  // The entry type can be switched in the sidebar without saving, so the
  // settings slideout should follow the field rather than the stored value.
  currentEntryTypeId: () => form.typeId,
});

// "View" opens the element on the front end. The hrefs arrive ready to
// follow — a live element points at its own URL, anything else at a
// token-minting redirect that lands on the tokenized preview.
const viewButtons = computed(() =>
  payload.previewTargets.map((target, index) => ({
    label: payload.previewTargets.length === 1 ? t("View") : target.label,
    variant: "outline",
    key: `view-${index}`,
    onClick: () => window.open(target.url, "_blank", "noopener"),
  })),
);

console.log({ viewButtons: viewButtons.value });

const additionalButtons = computed(() => [...headerButtons.value]);

const hasDetails = computed(() => Boolean(sidebarPayload.value) || Boolean(payload.metadataHtml));

// Mirrors the legacy wording: a changed draft names the draft, anything else
// names the element type.
const staleMessage = computed(() =>
  t("This {type} has been updated.", {
    type:
      activity.staleType.value === "element" &&
      payload.draftId !== null &&
      !payload.isProvisionalDraft
        ? t("draft")
        : payload.elementDisplayName,
  }),
);

function reload(): void {
  router.reload();
}
</script>

<template>
  <!-- The form-related props aren't `EditorScreen`'s business — they're passed
    through so the same markup keeps working if this ever renders under a shell
    that owns the save UI. -->
  <AppLayout :shell="EditorScreen" :title="payload.title">
    <main id="main" tabindex="-1" class="element-editor">
      <!-- No drafts-and-revisions switcher beside the crumbs: the Revisions
        tab in the details column is that list now. -->
      <div v-if="crumbs.length" class="element-editor__crumbs">
        <Breadcrumbs :items="crumbs" />
      </div>

      <div>
        <form method="post" @submit.prevent="save()">
          <header class="pt-3 pb-1">
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
                  {{ t("Edited") }}
                </craft-badge>
                <DynamicHtmlRenderer
                  v-else-if="payload.statusLabelHtml"
                  :html="payload.statusLabelHtml"
                />

                <!--              <template v-if="viewButtons.length === 1">-->
                <!--                <a-->
                <!--                  v-for="viewButton in viewButtons"-->
                <!--                  :key="viewButton.key"-->
                <!--                  href="#"-->
                <!--                  >{{ viewButton.label }}-->
                <!--                  <craft-icon-->
                <!--                    class="no-underline"-->
                <!--                    name="up-right-from-square"-->
                <!--                  ></craft-icon>-->
                <!--                </a>-->
                <!--              </template>-->

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
            <div class="element-notices my-1">
              <div v-if="form.hasErrors" class="px-4">
                <ErrorSummary v-if="form.hasErrors" :errors="form.errors" />
              </div>
              <craft-callout v-if="payload.notice" variant="info" icon="edit" class="mb-4">
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
                  {{ t("Discard changes") }}
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
                  {{ t("Reload") }}
                </craft-button>
              </craft-callout>

              <craft-callout v-if="payload.readOnly" variant="neutral" icon="lock">
                {{ t("This is a read-only view.") }}
              </craft-callout>

              <craft-callout
                v-if="payload.mergeNotice"
                variant="warning"
                icon="triangle-exclamation"
              >
                {{ payload.mergeNotice }}
              </craft-callout>
            </div>
          </header>

          <div
            class="element-editor__body"
            :class="{ 'element-editor__body--details': hasDetails }"
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

            <div v-if="hasDetails || $slots['details-header']" class="element-editor__details">
              <craft-pane padding="none" appearance="plain">
                <craft-tabs size="small">
                  <craft-tab slot="tab">Info</craft-tab>
                  <div slot="panel" class="pb-4">
                    <div class="grid gap-4">
                      <slot name="details-header" :payload="payload" />

                      <!--
                  The meta fields render as their own Form, bridged into the same
                  Inertia form as the field layout, so they submit as ordinary
                  inputs.
                -->
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

                  <craft-tab slot="tab">{{ t("Activity") }}</craft-tab>
                  <div slot="panel" class="px-4 pb-4">@TODO</div>

                  <craft-tab slot="tab">{{ t("Revisions") }}</craft-tab>
                  <div slot="panel" class="px-4 pb-4">
                    <RevisionsList :items="payload.contextMenu?.items ?? []" />
                  </div>
                </craft-tabs>
              </craft-pane>
            </div>
          </div>
        </form>
      </div>
      <!--      <div class="sticky bottom-0 z-1000" v-if="payload.canDiscardDraft">-->
      <!--        <div-->
      <!--          class="p-4 flex justify-between bg-info-fill-quiet border-t border-b border-t-info-border-quiet border-b-info-border-quiet"-->
      <!--        >-->
      <!--          <div class="flex gap-2 items-center">-->
      <!--            <craft-icon name="pencil"></craft-icon>-->
      <!--            {{ payload.notice }}-->
      <!--          </div>-->

      <!--          <craft-button slot="action" type="button" variant="outline" @click="discardDraft" inherit>-->
      <!--            {{ t("Discard changes") }}-->
      <!--          </craft-button>-->
      <!--        </div>-->
      <!--      </div>-->
    </main>
  </AppLayout>
</template>

<style scoped lang="css">
.element-editor {
  border-radius: var(--c-radius-lg);
  margin-block: var(--c-spacing-sm);
  margin-inline-end: var(--c-spacing-sm);
  overflow: clip;
}

.element-editor__crumbs {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: var(--c-spacing-sm);
  padding-block: var(--c-spacing-xs);
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
  z-index: 1000;
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

  /* `EditorScreen` sets `container-type: size` on the main region. */
  @container (width >= 768px) {
    align-items: start;

    &.element-editor__body--details {
      grid-template-columns: minmax(0, 1fr) clamp(14rem, 25%, 20rem);
    }
  }
}

.element-editor__content {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: var(--c-spacing-md);
  align-content: start;
  border-radius: var(--c-radius-lg);
  overflow: clip;
}

@container (width >= 768px) {
  .element-editor__body {
    align-items: start;
  }

  .element-editor__body.element-editor__body--details {
    grid-template-columns: minmax(0, 1fr) clamp(14rem, 25%, 20rem);
  }
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

.element-form {
  background-color: var(--c-surface-overlay);
}
</style>
