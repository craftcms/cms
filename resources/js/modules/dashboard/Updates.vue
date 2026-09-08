<script setup lang="ts">
  import {router} from '@inertiajs/vue3';
  import {onMounted, ref} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import {check as checkUpdates} from '@actions/Updates/UpdatesController';
  import {show} from '@actions/Utilities/UtilitiesController';
  import CpLink from '@/common/components/CpLink.vue';

  const props = defineProps<{data: {cached: boolean; total: number}}>();
  const total = ref(props.data.total);
  const checking = ref(false);
  const error = ref('');

  async function check(forceRefresh = true) {
    if (checking.value) return;

    checking.value = true;
    error.value = '';

    try {
      const {data} = await actionClient.post(checkUpdates.url(), {
        forceRefresh,
      });

      total.value = data.total;
      router.reload({only: ['craft']});
    } catch {
      error.value = t('Unable to fetch updates at this time.');
    } finally {
      checking.value = false;
    }
  }

  onMounted(() => {
    if (!props.data.cached) void check(false);
  });
</script>

<template>
  <craft-pane appearance="raised" padding="lg">
    <slot name="header" />
    <div class="body text-center space-y-3" aria-live="polite">
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
      <craft-spinner v-if="checking" visible>{{
        t('Checking for updates…')
      }}</craft-spinner>
      <template v-else-if="total">
        <p>
          {{
            total === 1
              ? t('One update available!')
              : t('{total} updates available!', {total})
          }}
        </p>
        <CpLink :href="show.url({id: 'updates'})">{{
          t('Go to Updates')
        }}</CpLink>
      </template>
      <template v-else
        ><p>{{ t('Congrats! You’re up to date.') }}</p>
        <craft-button
          type="button"
          icon="arrows-rotate"
          :disabled="checking"
          @click="check()"
          >{{ t('Check again') }}</craft-button
        ></template
      >
    </div>
  </craft-pane>
</template>
