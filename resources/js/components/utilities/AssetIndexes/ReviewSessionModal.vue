<script setup lang="ts">
  import ModalForm from '@/components/ModalForm.vue';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {useAssetIndexer} from '@/composables/useAssetIndexer';
  import {computed, reactive} from 'vue';
  import {escapeHtml} from '@craftcms/cp/src/utilities/escapeHtml.js';
  import CheckboxGroup from '@/components/form/CheckboxGroup.vue';
  import type {FinishIndexingParams} from '@craftcms/cp/src/services/AssetIndexer';
  import {router} from '@inertiajs/vue3';
  import {show} from '@routes/cp/utilities';

  withDefaults(
    defineProps<{
      isActive?: boolean;
    }>(),
    {isActive: false}
  );

  const {
    sessionsArray,
    currentSessionId,
    stopSession,
    reviewSessionOverview,
    finishSession,
    isReviewOpen,
    closeReview,
    reviewSession,
  } = useAssetIndexer();

  const missingEntries = computed(() => reviewSession.value?.missingEntries);
  const missingFolders = computed(() => missingEntries.value?.folders ?? {});
  const missingFiles = computed(() => missingEntries.value?.files ?? {});

  const hasMissingFolders = computed(() =>
    missingFolders.value ? Object.keys(missingFolders.value).length > 0 : false
  );
  const hasMissingFiles = computed(() =>
    missingFiles.value
      ? Object.keys(missingFiles.value ?? {}).length > 0
      : false
  );

  const hasMissingItems = computed(() => {
    return hasMissingFolders.value || hasMissingFiles.value;
  });

  const deleteFolderOptions = computed(() =>
    missingFolders.value
      ? Object.keys(missingFolders.value).map((id: string) => {
          return {
            label: escapeHtml(missingFolders.value[id]!),
            value: id,
          };
        })
      : []
  );

  const deleteAssetOptions = computed(() =>
    missingFiles.value
      ? Object.keys(missingFiles.value).map((id: string) => {
          return {
            label: escapeHtml(missingFiles.value[id]!),
            value: id,
          };
        })
      : []
  );

  const formData = reactive<Partial<FinishIndexingParams>>({
    deleteFolder: [
      '',
      ...deleteFolderOptions.value.map((o) => o.value.toString()),
    ],
    deleteAsset: [
      '',
      ...deleteAssetOptions.value.map((o) => o.value.toString()),
    ],
  });

  function missingItemsHeading(type: string, params: Record<any, any>) {
    if (type === 'folders' && reviewSession.value?.listEmptyFolders) {
      return t('Missing or empty {items}', params);
    }

    return t('Missing {items}', params);
  }

  function missingItemsCopy(type: string, params: Record<any, any>) {
    if (type === 'files' && reviewSession.value?.listEmptyFolders) {
      return t(
        'The following {items} could not be found or are empty. Should they be deleted from the index?',
        params
      );
    }

    return t(
      'The following {items} could not be found. Should they be deleted from the index?',
      params
    );
  }

  async function handleSubmit() {
    await finishSession({
      sessionId: reviewSession.value!.id,
      deleteFolder: formData.deleteFolder.filter(Boolean),
      deleteAsset: formData.deleteAsset.filter(Boolean),
    });
    // Refresh the page because we did a bunch outside of inertia
    router.visit(show({id: 'asset-indexes'}));
  }
</script>

<template>
  <template v-if="reviewSession">
    <ModalForm
      :is-active="true"
      @close="closeReview"
      @cancel="() => stopSession(reviewSession!.id)"
      :reset-label="hasMissingItems ? t('Keep them') : undefined"
      :submit-label="hasMissingItems ? t('Delete them') : t('OK')"
      @submit="handleSubmit"
    >
      <div class="grid gap-3">
        <template v-if="reviewSession.skippedEntries">
          <div>
            <h2 class="mb-2">{{ t('Skipped files') }}</h2>
            <p>{{ t('The following items were not indexed.') }}</p>
            <ul class="my-2">
              <li v-for="entry in reviewSession.skippedEntries" :key="entry">
                <code>{{ entry }}</code>
              </li>
            </ul>
          </div>
        </template>

        <template v-if="hasMissingItems">
          <div>
            <template v-if="hasMissingFolders">
              <h2>{{ missingItemsHeading('folders', {items: 'folders'}) }}</h2>
              <p>{{ missingItemsCopy('folders', {items: 'folders'}) }}</p>
              <CheckboxGroup
                class="my-2"
                :label="t('Delete folders')"
                :model-value="formData.deleteFolder"
                :options="deleteFolderOptions"
                :allow-select-all="true"
              >
                <template #label="{option}">
                  <code>{{ option.label }}</code>
                </template>
              </CheckboxGroup>
            </template>
            <template v-if="hasMissingFiles">
              <h2>{{ missingItemsHeading('files', {items: 'files'}) }}</h2>
              <p>{{ missingItemsCopy('files', {items: 'files'}) }}</p>
              <CheckboxGroup
                class="my-2"
                :label="t('Delete assets')"
                :model-value="formData.deleteAsset"
                :options="deleteAssetOptions"
                :allow-select-all="true"
              >
                <template #label="{option}">
                  <code>{{ option.label }}</code>
                </template>
              </CheckboxGroup>
            </template>
          </div>
        </template>
      </div>
    </ModalForm>
  </template>
</template>

<style scoped lang="scss"></style>
