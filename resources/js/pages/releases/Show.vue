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
  import ReleaseStatusBadge from '@/modules/releases/components/ReleaseStatusBadge.vue';
  import {openUiModal} from '@/modules/ui/open-ui-modal';
  import {formatReleaseDate} from '@/modules/releases/types';
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
  }>();

  /** Draft and failed releases can be scheduled. */
  const canSchedule = computed(
    () => props.release.canSchedule && !props.readOnly
  );

  const canPublishNow = computed(
    () => props.release.canPublish && !props.readOnly
  );

  /** Publication controls sit in the footer, below the drafts table. */
  const hasFooterControls = computed(
    () =>
      canSchedule.value || props.release.canUnschedule || canPublishNow.value
  );

  const blockingSummary = computed((): string | null => {
    const blocked = Object.keys(props.readiness?.itemErrors ?? {}).length;

    return blocked
      ? t(
          '{count, plural, =1{# draft is blocking publication.} other{# drafts are blocking publication.}}',
          {count: blocked}
        )
      : null;
  });

  const readinessSummary = computed((): string | null => {
    const readiness = props.readiness;

    // Blocking drafts are called out below the toolbar instead.
    if (
      !readiness ||
      readiness.errors.length ||
      Object.keys(readiness.itemErrors).length
    ) {
      return null;
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
        t(
          'Unschedule this release? It won’t publish until it’s scheduled again.'
        )
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
      <span v-if="release.status === 'published' && release.publishedAt">
        {{
          t('Published {date}', {
            date: formatReleaseDate(release.publishedAt),
          })
        }}
      </span>
      <span v-else-if="release.targetDate && release.status !== 'scheduled'">
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
      v-if="editUi || hasFooterControls"
      :name="editUi ? 'primary-action' : 'footer-meta'"
    >
      <div
        v-if="canSchedule || release.canUnschedule"
        class="flex flex-wrap gap-md"
      >
        <template v-if="canSchedule">
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
        </template>
        <craft-button
          v-else-if="release.canUnschedule"
          type="button"
          variant="outline"
          @click="unschedule"
        >
          {{ t('Unschedule') }}
        </craft-button>
      </div>
    </LayoutSlot>

    <LayoutSlot v-if="canPublishNow" name="additional-buttons">
      <craft-button
        id="release-publish-now"
        type="button"
        variant="danger"
        .disabled="!readiness?.ready"
        :focusable-when-disabled="publishDisabledReason ? true : undefined"
        @click="publishRelease"
      >
        {{ t('Publish now') }}
      </craft-button>
      <craft-tooltip v-if="publishDisabledReason" for="release-publish-now">
        {{ publishDisabledReason }}
      </craft-tooltip>
    </LayoutSlot>

    <craft-callout
      v-if="blockingSummary"
      variant="danger"
      icon="triangle-exclamation"
      rounded="none"
      appearance="fill"
    >
      {{ blockingSummary }}
    </craft-callout>

    <craft-callout
      v-if="release.lastError"
      variant="danger"
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
      :metadata-html="metadataHtml ?? undefined"
      :form-actions="formActions"
      form-actions-in-sidebar
      :hide-footer="!hasFooterControls"
    >
      <template v-if="editUi" #sidebar-actions="{form, formActionItems}">
        <FormActions
          class="mt-lg"
          :form="form"
          :action-items="formActionItems"
        />
      </template>
    </UiPage>
  </div>
</template>
