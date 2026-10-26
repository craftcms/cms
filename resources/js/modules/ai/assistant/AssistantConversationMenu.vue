<script setup lang="ts">
  /**
   * The actions on the current conversation, in the chat’s header: renaming
   * it, and deleting it.
   */
  import {getActionUrl, t} from '@craftcms/ui';
  import type {ActionMenuItem} from '@craftcms/ui/components/action-menu/action-menu.types';
  import {shallowRef} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';
  import {AssistantRequestError, actionFetch} from './assistant';

  const props = defineProps<{
    threadId: string;
    title: string;
  }>();

  const emit = defineEmits<{
    renamed: [];
    deleted: [];
  }>();

  const renaming = shallowRef(false);
  const processing = shallowRef(false);
  const name = shallowRef('');
  const error = shallowRef<string | null>(null);

  const actions: ActionMenuItem[] = [
    {
      label: t('Rename…'),
      icon: 'pencil',
      onClick: () => {
        name.value = props.title;
        error.value = null;
        renaming.value = true;
      },
    },
    {
      label: t('Delete'),
      icon: 'trash',
      variant: 'danger',
      onClick: () => void remove(),
    },
  ];

  /** Posts to one of the conversation’s actions. */
  async function send(
    url: string,
    body: Record<string, unknown> = {}
  ): Promise<void> {
    await actionFetch(getActionUrl(url), {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({threadId: props.threadId, ...body}),
    });
  }

  /** Why an action failed, as the server says, if it did. */
  function failure(exception: unknown): string | undefined {
    return exception instanceof AssistantRequestError
      ? exception.message
      : undefined;
  }

  async function rename(): Promise<void> {
    const title = name.value.trim();

    if (!title) {
      error.value = t('Give the chat a name.');

      return;
    }

    processing.value = true;

    try {
      await send(AssistantController.rename.url(), {title});
      renaming.value = false;
      emit('renamed');
    } catch (exception) {
      error.value = failure(exception) ?? t('A server error occurred.');
    } finally {
      processing.value = false;
    }
  }

  /** Deletes the conversation once the user confirms it. */
  async function remove(): Promise<void> {
    if (
      !window.confirm(
        t('Delete “{title}”? This can’t be undone.', {title: props.title})
      )
    ) {
      return;
    }

    try {
      await send(AssistantController.destroy.url());
      emit('deleted');
    } catch (exception) {
      Craft.cp?.displayError?.(failure(exception));
    }
  }
</script>

<template>
  <craft-action-menu :label="t('Chat actions')" .actions="actions" />

  <ModalForm
    :is-active="renaming"
    :title="t('Rename Chat')"
    width="sm"
    :loading="processing"
    @close="renaming = false"
    @submit="rename"
  >
    <craft-field :label="t('Name')" required :has-errors="!!error">
      <craft-input slot="input">
        <input slot="input" v-model="name" type="text" required />
      </craft-input>
      <div v-if="error" slot="errors">{{ error }}</div>
    </craft-field>
  </ModalForm>
</template>
