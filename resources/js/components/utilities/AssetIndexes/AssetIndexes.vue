<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import CheckboxGroup from '@/components/form/CheckboxGroup.vue';
  import type {CheckboxOption} from '@/types';
  import IndexingSessions from '@/components/utilities/AssetIndexes/IndexingSessions.vue';
  import {reactive} from 'vue';
  import {useAssetIndexer} from '@/composables/useAssetIndexer';
  import Pane from '@/components/Pane.vue';
  import type {IndexingSession} from '@craftcms/cp/src/services/AssetIndexer.js';

  const props = withDefaults(
    defineProps<{
      existingSessions?: Array<IndexingSession>;
      volumeOptions?: Array<CheckboxOption>;
      dateFormat?: string;
      isEphemeral?: boolean;
    }>(),
    {volumeOptions: () => []}
  );

  const {sessionsArray, hasSessions, startIndexing, isStarting} =
    useAssetIndexer({
      existingSessions: props.existingSessions,
    });

  const form = reactive({
    volumes: ['*', ...props.volumeOptions.map((v) => v.value)],
    cacheImages: false,
    listEmptyFolders: false,
  });

  async function handleSubmit() {
    await startIndexing(form);
    console.log('refreshing');
  }
</script>

<template>
  <div class="p-4" v-if="hasSessions">
    <Pane appearance="outline" :padding="0">
      <IndexingSessions :existing-sessions="sessionsArray" />
    </Pane>
  </div>

  <template v-if="volumeOptions">
    <div class="p-4">
      <form @submit.prevent="handleSubmit">
        <CheckboxGroup
          name="volumes[]"
          :label="t('Volumes')"
          v-model="form.volumes"
          :options="volumeOptions"
          :allow-select-all="true"
        />

        <h2 class="text-sm mb-2 mt-6">{{ t('Options') }}</h2>
        <div class="grid gap-3">
          <template v-if="!isEphemeral">
            <craft-switch
              name="cacheImages"
              :label="t('Cache remote images')"
              :checked="form.cacheImages"
              :disabled="isEphemeral"
              @change="
                form.cacheImages = ($event.target as HTMLInputElement).checked
              "
            >
              <div slot="help-text">
                {{
                  t('Download copies of remote images to the local filesystem.')
                }}
                <template v-if="isEphemeral">
                  <br />
                  <em>{{
                    t('This option is disabled for ephemeral environments.')
                  }}</em>
                </template>
              </div>
            </craft-switch>
          </template>

          <craft-switch
            name="listEmptyFolders"
            :label="t('List empty folders')"
            :checked="form.listEmptyFolders"
            @change="
              form.listEmptyFolders = (
                $event.target as HTMLInputElement
              ).checked
            "
          >
            <div slot="help-text">
              {{ t('Include empty folders in the review step.') }}
            </div>
          </craft-switch>
        </div>

        <div class="mt-4 flex gap-2 items-center">
          <craft-button
            type="submit"
            variant="primary"
            :loading="isStarting"
            :disabled="form.volumes.length === 0"
          >
            {{ t('Update asset indexes') }}
          </craft-button>
        </div>
      </form>
    </div>
  </template>
</template>

<style scoped lang="scss"></style>
