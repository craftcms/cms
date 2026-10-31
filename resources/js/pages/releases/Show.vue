<script setup lang="ts">
  import {router} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {
    publish,
    schedule,
    scheduleModal,
    update,
    unschedule as unscheduleRelease,
  } from '@actions/ReleasesController';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import FormActions from '@/common/components/FormActions.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import UiPage from '@/pages/Ui.vue';
  import ReleasePreview from '@/modules/releases/components/ReleasePreview.vue';
  import ReleaseStatusBadge from '@/modules/releases/components/ReleaseStatusBadge.vue';
  import {openUiModal} from '@/modules/ui/open-ui-modal';
  import {
    formatReleaseDate,
    type ReleasePreviewPayload,
  } from '@/modules/releases/types';
  import type {UiPayload} from '@/modules/ui/types';

  type ReleaseViewModel = CraftCms.Cms.Http.ViewModels.ReleaseViewModel;

  const props = defineProps<{
    release: ReleaseViewModel['release'];
    readOnly: boolean;
    actionMenu: ReleaseViewModel['actionMenu'];
    formActions: ReleaseViewModel['formActions'];
    readiness: ReleaseViewModel['readiness'];
    ui: UiPayload;
    editUi: UiPayload | null;
    metadataHtml: string | null;
    publishDisabledReason: ReleaseViewModel['publishDisabledReason'];
    /** Set when the release preview is open over the screen. */
    preview?: ReleasePreviewPayload | null;
  }>();

  const canPublishNow = computed(
    () => props.release.canPublish && !props.readOnly
  );

  const readinessSummary = computed((): string | null => {
    const readiness = props.readiness;

    if (!readiness || readiness.errors.length) {
      return null;
    }

    const blocked = Object.keys(readiness.itemErrors).length;
    if (blocked) {
      return t(
        '{count, plural, =1{# draft is blocking publication.} other{# drafts are blocking publication.}}',
        {count: blocked}
      );
    }

    const withWarnings = Object.keys(readiness.itemWarnings).length;
    if (withWarnings) {
      return t(
        'Ready to publish. {count, plural, =1{# draft has warnings.} other{# drafts have warnings.}}',
        {count: withWarnings}
      );
    }

    return t('Ready to publish.');
  });

  function scheduleRelease(): void {
    if (!props.readiness?.ready) {
      return;
    }

    void openUiModal({
      modalUrl: scheduleModal.url({release: props.release.id}),
      actionUrl: schedule.url({release: props.release.id}),
      onSubmitted: () => router.reload(),
    });
  }

  function publishRelease(): void {
    if (
      !props.readiness?.ready ||
      !window.confirm(
        t('Publish this release now? Every entry will be published together.')
      )
    ) {
      return;
    }

    router.visit(publish({release: props.release.id}));
  }

  function unschedule(): void {
    if (
      window.confirm(
        t('Unschedule this release? The release can then be changed again.')
      )
    ) {
      router.visit(unscheduleRelease({release: props.release.id}));
    }
  }
</script>

<template>
  <div class="contents">
    <LayoutSlot name="content-toolbar-meta">
      <ReleaseStatusBadge :status="release.status" />
      <span v-if="release.status === 'scheduled' && release.publishedAt">
        {{
          t('Scheduled for {date}', {
            date: formatReleaseDate(release.publishedAt),
          })
        }}
      </span>
      <span v-else-if="release.status === 'published' && release.publishedAt">
        {{
          t('Published {date}', {
            date: formatReleaseDate(release.publishedAt),
          })
        }}
      </span>
      <span v-else-if="release.targetDate">
        {{
          t('Targeted for {date}', {
            date: formatReleaseDate(release.targetDate),
          })
        }}
      </span>
      <span v-if="readinessSummary">{{ readinessSummary }}</span>
    </LayoutSlot>

    <LayoutSlot v-if="actionMenu.length" name="content-toolbar-actions">
      <ActionMenu :actions="actionMenu" />
    </LayoutSlot>

    <!-- Filling the primary action keeps the form's Save button out of the
    footer even when there is nothing to publish; Save sits in the sidebar. -->
    <LayoutSlot
      v-if="editUi || canPublishNow"
      :name="editUi ? 'primary-action' : 'footer-meta'"
    >
      <div v-if="canPublishNow" class="flex flex-wrap gap-md">
        <craft-button
          id="release-publish-now"
          type="button"
          variant="primary"
          .disabled="!readiness?.ready"
          :focusable-when-disabled="publishDisabledReason ? true : undefined"
          @click="publishRelease"
        >
          {{ t('Publish now') }}
        </craft-button>
        <craft-tooltip v-if="publishDisabledReason" for="release-publish-now">
          {{ publishDisabledReason }}
        </craft-tooltip>
        <craft-button
          id="release-schedule"
          type="button"
          variant="outline"
          .disabled="!readiness?.ready"
          :focusable-when-disabled="publishDisabledReason ? true : undefined"
          @click="scheduleRelease"
        >
          {{ t('Schedule') }}
        </craft-button>
        <craft-tooltip v-if="publishDisabledReason" for="release-schedule">
          {{ publishDisabledReason }}
        </craft-tooltip>
      </div>
    </LayoutSlot>

    <craft-callout
      v-if="release.status === 'scheduled'"
      variant="info"
      icon="lock"
      rounded="none"
      appearance="fill"
    >
      {{
        t(
          'This release is scheduled. Unschedule it before adding or removing drafts.'
        )
      }}
    </craft-callout>

    <craft-callout
      v-if="release.lastError"
      variant="error"
      icon="triangle-exclamation"
      rounded="none"
      appearance="fill"
    >
      {{
        t('The last publication attempt failed: {error}', {
          error: release.lastError,
        })
      }}
    </craft-callout>

    <UiPage
      :ui="ui"
      :submit="editUi ? update({release: release.id}) : undefined"
      :sidebar-ui="editUi ?? undefined"
      :metadata-html="editUi ? undefined : (metadataHtml ?? undefined)"
      :form-actions="formActions"
      form-actions-in-sidebar
      :hide-footer="!canPublishNow"
    >
      <template
        v-if="editUi || release.canUnschedule"
        #sidebar-actions="{form, formActionItems}"
      >
        <FormActions
          v-if="editUi"
          class="mt-lg"
          :form="form"
          :action-items="formActionItems"
        />
        <craft-button
          v-else
          type="button"
          variant="outline"
          @click="unschedule"
        >
          {{ t('Unschedule') }}
        </craft-button>
      </template>
    </UiPage>

    <ReleasePreview v-if="preview" :release="release" :preview="preview" />
  </div>
</template>
