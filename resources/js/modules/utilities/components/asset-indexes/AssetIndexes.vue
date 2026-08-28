<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import type {CheckboxOption} from '@/common/types';
  import IndexingSessions from '@/modules/utilities/components/asset-indexes/IndexingSessions.vue';
  import {reactive} from 'vue';
  import {useAssetIndexer} from '@/modules/utilities/composables/useAssetIndexer';
  import type {IndexingSession} from '@craftcms/ui/services/AssetIndexer';

  const props = withDefaults(
    defineProps<{
      existingSessions?: Array<IndexingSession>;
      volumeOptions?: Array<CheckboxOption>;
      dateFormat?: string;
      isEphemeral?: boolean;
    }>(),
    {volumeOptions: () => [], existingSessions: () => []}
  );

  const {hasSessions, startIndexing, isStarting} = useAssetIndexer({
    existingSessions: props.existingSessions,
  });

  const form = reactive({
    volumes: ['*', ...props.volumeOptions.map((v) => v.value)],
    cacheImages: false,
    listEmptyFolders: false,
  });

  async function handleSubmit() {
    await startIndexing(form);
  }
</script>

<template>
  <div class="cp:p-4" v-if="hasSessions">
    <IndexingSessions />
  </div>

  <template v-if="volumeOptions">
    <div class="cp:p-4">
      <form @submit.prevent="handleSubmit">
        <CheckboxGroup
          name="volumes[]"
          :label="t('Volumes')"
          v-model="form.volumes"
          :options="volumeOptions"
          :allow-select-all="true"
        />

        <h2 class="cp:text-sm cp:mb-2 cp:mt-6">{{ t('Options') }}</h2>
        <div class="cp:grid cp:gap-3">
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

        <div class="cp:mt-4 cp:flex cp:gap-2 cp:items-center">
          <craft-button
            type="submit"
            variant="accent"
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
