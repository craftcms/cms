<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {Form, useForm} from '@inertiajs/vue3';
  import {
    clearCaches,
    invalidateTags,
  } from '@actions/Utilities/ClearCachesController';
  import TransitionFade from '@/components/TransitionFade.vue';
  import CheckboxGroup from '@/components/form/CheckboxGroup.vue';
  import {useAnnouncer} from '@/composables/useAnnouncer';
  import type {SelectOption} from '@/types';

  interface CacheOption extends SelectOption {
    info?: string;
  }

  const props = defineProps<{
    cacheOptions: Array<CacheOption>;
    tagOptions: Array<CacheOption>;
  }>();

  const {announce} = useAnnouncer();

  const cacheForm = useForm({
    caches: props.cacheOptions.map((o) => o.value),
  });

  const tagForm = useForm({
    tags: props.tagOptions.map((o) => o.value),
  });

  function removeEmptyValues(arr: Array<string> = []) {
    return arr.filter(Boolean);
  }
</script>

<template>
  <div class="p-4">
    <h2 class="mb-3">{{ t('Clear Caches') }}</h2>

    <Form
      method="post"
      :action="clearCaches()"
      :transform="
        (data) => ({
          caches: removeEmptyValues(data.caches as Array<string>),
        })
      "
      :on-success="
        () => {
          announce(t('Caches cleared'));
        }
      "
      #default="{processing, recentlySuccessful}"
    >
      <CheckboxGroup
        name="caches[]"
        :label="t('Caches')"
        v-model="cacheForm.caches"
        :options="cacheOptions"
        :allow-select-all="true"
      />

      <div class="mt-4 flex gap-2 items-center">
        <craft-button type="submit" :loading="processing">
          {{ t('Clear caches') }}
        </craft-button>
        <TransitionFade>
          <template v-if="recentlySuccessful">
            <craft-callout
              variant="success"
              icon="circle-check"
              appearance="plain"
              class="p-0"
            >
              {{ t('Caches cleared.') }}
            </craft-callout>
          </template>
        </TransitionFade>
      </div>
    </Form>
  </div>

  <hr />

  <div class="p-4">
    <h2 class="mb-3">{{ t('Invalidate Data Caches') }}</h2>

    <Form
      method="post"
      :action="invalidateTags()"
      :transform="
        (data) => ({
          tags: removeEmptyValues(data.tags as Array<string>),
        })
      "
      :on-success="
        () => {
          announce(t('Data caches invalidated'));
        }
      "
      #default="{processing, recentlySuccessful}"
    >
      <CheckboxGroup
        name="tags[]"
        :label="t('Data Caches')"
        v-model="tagForm.tags"
        :options="tagOptions"
        :allow-select-all="true"
      />

      <div class="mt-4">
        <div class="flex gap-2 items-center">
          <craft-button type="submit" :loading="processing">
            {{ t('Invalidate caches') }}
          </craft-button>
          <TransitionFade>
            <template v-if="recentlySuccessful">
              <craft-callout
                variant="success"
                icon="circle-check"
                appearance="plain"
                class="p-0"
              >
                {{ t('Data caches invalidated.') }}
              </craft-callout>
            </template>
          </TransitionFade>
        </div>
      </div>
    </Form>
  </div>
</template>

<style scoped lang="scss"></style>
