<script setup lang="ts" generic="T">
  import type {AxiosFetchState} from '@/composables/useAxios';
  import {t} from '@craftcms/cp';

  withDefaults(
    defineProps<{
      items?: Array<T>;
      state: AxiosFetchState;
      title?: string;
      skipToId?: string;
    }>(),
    {items: () => [], state: 'idle', title: undefined, skipToId: undefined}
  );
</script>

<template>
  <template v-if="!items?.length && state === 'loading'">
    <div class="tw:my-6">
      <div class="tw:text-center tw:my-2 tw:font-bold" v-if="title">
        {{ title }}
      </div>
      <div
        class="tw:h-30 tw:border tw:border-subtle tw:rounded tw:bg-gray-50 tw:flex tw:justify-center tw:items-center"
      >
        <craft-spinner></craft-spinner>
      </div>
    </div>
  </template>
  <template v-else-if="!items?.length && state === 'success'">
    <div class="tw:my-6">
      <div class="tw:text-center tw:my-2 tw:font-bold" v-if="title">
        {{ title }}
      </div>
      <div
        class="tw:border tw:border-subtle tw:rounded tw:bg-gray-50 tw:relative tw:p-4 tw:text-gray-600 tw:text-center"
      >
        {{ t('app', 'No similar issues found.')}}
      </div>
    </div>
  </template>
  <template v-else-if="items?.length">
    <div class="tw:my-6">
      <div class="tw:text-center tw:my-2 tw:font-bold" v-if="title">
        {{ title }}
      </div>
      <div
        class="tw:border tw:border-subtle tw:rounded tw:bg-gray-50 tw:relative"
      >
        <a
          :href="`#${skipToId}`"
          v-if="skipToId"
          class="skip-link tw:inline-block tw:bg-white tw:px-2 tw:py-1"
          >{{
            t('app', 'Skip to {name}', {
              name: t('app', 'Submit'),
            })
          }}</a
        >
        <div class="tw:overflow-y-auto tw:max-h-60">
          <ul>
            <template v-for="item in items">
              <li>
                <slot name="item" :item="item"></slot>
              </li>
            </template>
          </ul>
        </div>
      </div>
    </div>
  </template>
</template>

<style scoped lang="scss"></style>
