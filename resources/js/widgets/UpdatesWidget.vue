<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/cp';
  import {useUpdatesCheck} from '@/composables/useUpdatesService';

  const props = withDefaults(
    defineProps<{
      cached?: string;
      total?: string;
    }>(),
    {cached: '0', total: '0'}
  );

  const totalNumber = computed(() => parseInt(props.total, 10));
  const updateCopy = computed(() => {
    return t(
      'app',
      '{total, plural, =1{One update} =other{# updates}} available!',
      {
        total: totalNumber.value,
      }
    );
  });

  const {data, state, error, refetch} = useUpdatesCheck({
    enabled: !props.cached,
    initialData: {
      total: totalNumber.value,
    },
  });

  // @TODO
  function url(path: string) {
    return path;
  }
</script>

<template>
  <div class="tw:grid tw:gap-2 tw:py-4 tw:text-center">
    <template v-if="state === 'pending'">
      <craft-spinner></craft-spinner>
      <slot></slot>
    </template>
    <template v-if="state === 'error'">
      <div
        class="tw:text-red-800 tw:bg-red-100 tw:border tw:border-red-400 tw:py-2 tw:px-4 tw:rounded-md"
      >
        {{ error }}
      </div>
      <div>
        <craft-button
          :aria-label="t('app', 'Check again')"
          @click="() => refetch()"
        >
          <craft-icon name="refresh"></craft-icon>
          {{ t('app', 'Check again') }}
        </craft-button>
      </div>
    </template>
    <template v-if="state === 'success'">
      <template v-if="data!.total > 0">
        <p class="tw:text-center">
          {{ updateCopy }}
          <a class="go tw:whitespace-nowrap" :href="url('utilities/updates')">{{
            t('app', 'Go to Updates')
          }}</a>
        </p>
      </template>
      <template v-else>
        <div class="tw:grid tw:gap-3">
          <p class="tw:text-center">
            {{ t('app', 'Congrats! You’re up to date.') }}
          </p>
          <div>
            <craft-button
              :aria-label="t('app', 'Check again')"
              @click="() => refetch()"
            >
              <craft-icon name="refresh"></craft-icon>
              {{ t('app', 'Check again') }}
            </craft-button>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<style scoped lang="scss"></style>
