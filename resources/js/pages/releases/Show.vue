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
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import UiPage from '@/pages/Ui.vue';
  import ReleaseStatusBadge from '@/modules/releases/components/ReleaseStatusBadge.vue';
  import {openUiModal} from '@/modules/ui/open-ui-modal';
  import {formatReleaseDate} from '@/modules/releases/types';
  import type {UiPayload} from '@/modules/ui/types';

  type ReleaseViewModel = CraftCms.Cms.Http.ViewModels.ReleaseViewModel;

  const props = defineProps<{
    release: ReleaseViewModel['release'];
    readOnly: boolean;
    formAdditionalButtons: ReleaseViewModel['formAdditionalButtons'];
    readiness: ReleaseViewModel['readiness'];
    ui: UiPayload;
    editUi: UiPayload | null;
    metadataHtml: string | null;
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
        '{count, plural, =1{# entry is blocking publication.} other{# entries are blocking publication.}}',
        {count: blocked}
      );
    }

    const withWarnings = Object.keys(readiness.itemWarnings).length;
    if (withWarnings) {
      return t(
        'Ready to publish. {count, plural, =1{# entry has warnings.} other{# entries have warnings.}}',
        {count: withWarnings}
      );
    }

    return t('Ready to publish.');
  });

  function scheduleRelease(): void {
    void openUiModal({
      modalUrl: scheduleModal.url({release: props.release.id}),
      actionUrl: schedule.url({release: props.release.id}),
      onSubmitted: () => router.reload(),
    });
  }

  function publishRelease(): void {
    if (
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
      <span v-else-if="release.plannedAt">
        {{
          t('Planned for {date}', {
            date: formatReleaseDate(release.plannedAt),
          })
        }}
      </span>
      <span v-if="readinessSummary">{{ readinessSummary }}</span>
    </LayoutSlot>

    <!-- Filling the primary action keeps the form's Save button out of the
    footer even when there is nothing to publish; Save sits in the sidebar. -->
    <LayoutSlot
      v-if="editUi || canPublishNow"
      :name="editUi ? 'primary-action' : 'footer-meta'"
    >
      <div v-if="canPublishNow" class="flex flex-wrap gap-md">
        <craft-button
          type="button"
          variant="primary"
          .disabled="!readiness?.ready"
          @click="publishRelease"
        >
          {{ t('Publish now') }}
        </craft-button>
        <craft-button
          type="button"
          variant="outline"
          .disabled="!readiness?.ready"
          @click="scheduleRelease"
        >
          {{ t('Schedule') }}
        </craft-button>
      </div>
    </LayoutSlot>

    <LayoutSlot v-if="release.canUnschedule" name="footer-meta">
      <craft-button type="button" variant="outline" @click="unschedule">
        {{ t('Unschedule') }}
      </craft-button>
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
      :default-form-actions="[]"
      :form-additional-buttons="formAdditionalButtons"
    >
      <template #sidebar-actions>
        <craft-button type="submit" variant="primary" class="mt-lg">
          {{ t('Save') }}
        </craft-button>
      </template>
    </UiPage>
  </div>
</template>
