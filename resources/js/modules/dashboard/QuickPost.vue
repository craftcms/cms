<script setup lang="ts">
  import {onBeforeUnmount, ref} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import CreateEntryController from '@actions/Entries/CreateEntryController';
  import {router} from '@inertiajs/vue3';
  import {useSlideoutOpener} from '@/common/slideouts';
  import type {DashboardWidget} from './types';

  const props = defineProps<{widget: DashboardWidget}>();
  const creating = ref(false);
  const error = ref('');
  const {open} = useSlideoutOpener();
  const abort = new AbortController();

  onBeforeUnmount(() => {
    abort.abort();
  });

  async function create(event: MouseEvent) {
    if (creating.value) return;

    const opener = event.currentTarget as HTMLElement;

    creating.value = true;
    error.value = '';

    try {
      const {data} = await actionClient.post(
        CreateEntryController[
          '/{cpTrigger?}/{actionTrigger?}/entries/create'
        ].url(),
        props.widget.data!.params,
        {signal: abort.signal}
      );
      if (abort.signal.aborted) return;

      const url = new URL(data.cpEditUrl, window.location.origin);
      url.searchParams.set('fresh', '1');

      await open(url.toString(), {
        opener,
        onSaved: ({draft}) => {
          if (!draft) router.reload({only: ['widgets']});
        },
      });
    } catch {
      if (!abort.signal.aborted) error.value = t('A server error occurred.');
    } finally {
      creating.value = false;
    }
  }
</script>

<template>
  <craft-pane appearance="raised" padding="lg">
    <slot name="header" />
    <div class="body">
      <p v-if="widget.data?.message">{{ widget.data.message }}</p>
      <craft-button
        v-else
        type="button"
        icon="plus"
        class="w-full"
        :loading="creating"
        :disabled="creating"
        @click="create"
        >{{ t('Create entry') }}</craft-button
      >
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
    </div>
  </craft-pane>
</template>
