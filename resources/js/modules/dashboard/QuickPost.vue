<script setup lang="ts">
  import {computed, onBeforeUnmount, ref} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import {elementTypeName} from '@/common/craft/element-types';
  import {uppercaseFirst} from '@/common/craft/strings';
  import CreateEntryController from '@actions/Entries/CreateEntryController';
  import {useSlideoutOpener} from '@/common/slideouts';
  import type {DashboardWidget} from './types';
  import WidgetTile from './WidgetTile.vue';
  import {useWidgetColor} from './useWidgetColor';

  const color = useWidgetColor();

  const props = defineProps<{widget: DashboardWidget}>();

  /**
   * The entry type the widget creates, whose icon and color its tile takes
   * (the color only while the widget isn't tinted). The tile doesn't name
   * it: the widget's title names the section.
   */
  const entryType = computed(
    () =>
      props.widget.data?.entryType as
        | {icon: string | null; color: string | null}
        | undefined
  );

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

      // Publishing it reloads the dashboard's widgets, as any element save
      // does.
      await open(url.toString(), {opener});
    } catch {
      if (!abort.signal.aborted) error.value = t('A server error occurred.');
    } finally {
      creating.value = false;
    }
  }
</script>

<template>
  <craft-card :data-color="color">
    <slot name="header" />
    <div class="body">
      <p v-if="widget.data?.message">{{ widget.data.message }}</p>
      <WidgetTile
        v-else
        :icon="entryType?.icon || 'newspaper'"
        :label="
          uppercaseFirst(
            t('Create {type}', {
              type:
                elementTypeName('CraftCms\\Cms\\Entry\\Elements\\Entry') ??
                'entry',
            })
          )
        "
        :color="entryType?.color"
        :loading="creating"
        @click="create"
      />
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
    </div>
  </craft-card>
</template>
