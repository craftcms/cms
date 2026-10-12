<script setup lang="ts">
  import {router} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {
    publish,
    schedule,
    scheduleModal,
    update,
    unschedule as unscheduleRelease,
  } from '@actions/ReleasesController';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import UiPage from '@/pages/Ui.vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import ReleaseReadiness from '@/modules/releases/components/ReleaseReadiness.vue';
  import ReleaseStatusBadge from '@/modules/releases/components/ReleaseStatusBadge.vue';
  import {openUiModal} from '@/modules/ui/open-ui-modal';
  import {formatReleaseDate} from '@/modules/releases/types';
  import type {UiPayload} from '@/modules/ui/types';

  type ReleasePageProps = Omit<
    CraftCms.Cms.Http.ViewModels.ReleaseViewModel,
    'ui' | 'editUi'
  > & {
    ui: UiPayload;
    editUi: UiPayload | null;
  };

  const props = defineProps<ReleasePageProps>();

  if (!props.editUi) {
    useAppLayout({title: props.release.name, form: null});
  }

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
      <span v-else-if="release.plannedAt">
        {{
          t('Planned for {date}', {
            date: formatReleaseDate(release.plannedAt),
          })
        }}
      </span>
    </LayoutSlot>

    <LayoutSlot name="content-actions">
      <div class="flex flex-wrap gap-2">
        <craft-button
          v-if="release.canUnschedule"
          type="button"
          variant="outline"
          @click="unschedule"
        >
          {{ t('Unschedule') }}
        </craft-button>
        <craft-button
          v-if="release.canSchedule && !readOnly"
          type="button"
          variant="outline"
          .disabled="!readiness?.ready"
          @click="scheduleRelease"
        >
          {{ t('Schedule') }}
        </craft-button>
        <craft-button
          v-if="release.canPublish && !readOnly"
          type="button"
          variant="primary"
          .disabled="!readiness?.ready"
          @click="publishRelease"
        >
          {{ t('Publish now') }}
        </craft-button>
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
      {{ release.lastError }}
    </craft-callout>

    <UiPage
      v-if="editUi"
      :ui="editUi"
      :submit="update({release: release.id})"
      :default-form-actions="[]"
      :form-additional-buttons="formAdditionalButtons"
    />

    <div class="py-4">
      <CpContainer>
        <div class="flex flex-col gap-8">
          <craft-callout
            v-if="release.status === 'published'"
            variant="success"
            icon="check"
          >
            {{
              t('Published {date}', {
                date: formatReleaseDate(release.publishedAt),
              })
            }}
          </craft-callout>
          <div
            v-if="!editUi && descriptionHtml"
            class="markdown pane"
            v-html="descriptionHtml"
          />
          <ReleaseReadiness
            v-if="release.status !== 'published'"
            :readiness="readiness"
          />
          <section aria-labelledby="release-items">
            <h2 id="release-items" class="mb-3 text-lg font-semibold">
              {{ t('Entries') }}
            </h2>
            <UiRenderer :payload="ui" />
          </section>
        </div>
      </CpContainer>
    </div>
  </div>
</template>
