<script setup lang="ts">
  /**
   * Asks before running an item whose `confirm` message — its own, or its
   * action's — says it should be, then runs it.
   */
  import {ButtonVariant, t} from '@craftcms/ui';
  import {runAction} from '@craftcms/ui/actions.mjs';
  import {shallowRef} from 'vue';
  import {redirectAfter, runHttpAction} from '../run';
  import type {
    CommandPaletteViewProps,
    CommandPaletteViewResult,
  } from '../types';

  const props = defineProps<CommandPaletteViewProps>();

  const emit = defineEmits<{
    (event: 'done', result?: CommandPaletteViewResult): void;
    (event: 'back'): void;
  }>();

  const running = shallowRef(false);

  async function confirm(): Promise<void> {
    const action = props.item.action;

    if (running.value) {
      return;
    }

    // A client-side item runs once the palette has closed, as it would
    // without a confirmation.
    if (!action) {
      if (props.item.run) {
        emit('done', {run: props.item.run});
      }

      return;
    }

    running.value = true;

    try {
      if (action.type === 'http') {
        const {redirect} = await runHttpAction(action);
        emit('done', {
          ...redirectAfter(props.item, redirect),
          reload: props.item.reload,
        });
      } else {
        emit('done');
        await runAction({...action, confirm: undefined} as typeof action);
      }
    } catch {
      // `runHttpAction` has already shown the error; stay put so it can be retried.
    } finally {
      running.value = false;
    }
  }
</script>

<template>
  <div class="flex flex-col gap-lg">
    <p class="m-0">{{ view.message }}</p>
    <div class="flex flex-wrap justify-end gap-sm">
      <craft-button type="button" @click="emit('back')">
        {{ t('Cancel') }}
      </craft-button>
      <craft-button
        type="button"
        :variant="
          item.variant === 'danger'
            ? ButtonVariant.Danger
            : ButtonVariant.Primary
        "
        .loading="running"
        @click="confirm"
      >
        {{ item.label }}
      </craft-button>
    </div>
  </div>
</template>
