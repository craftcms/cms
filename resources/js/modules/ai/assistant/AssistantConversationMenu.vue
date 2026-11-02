<script setup lang="ts">
  /**
   * The chat’s actions, in its header: renaming and deleting the current
   * conversation, once it’s stored, and links to where the assistant’s
   * guidance and skills are edited.
   */
  import {actionClient, t} from '@craftcms/ui';
  import {usePage} from '@inertiajs/vue3';
  import {computed, shallowRef} from 'vue';
  import type {CraftData} from '@/common/composables/useCraftData';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import type {ActionItem} from '@/common/types';
  import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';
  import {AssistantRequestError, requestError} from './assistant';

  const props = defineProps<{
    threadId: string;
    title: string;
    /** Whether the conversation is stored, so it can be renamed and deleted. */
    stored: boolean;
  }>();

  const emit = defineEmits<{
    renamed: [];
    deleted: [];
  }>();

  const renaming = shallowRef(false);
  const processing = shallowRef(false);
  const name = shallowRef('');
  const error = shallowRef<string | null>(null);

  const page = usePage<{craft?: CraftData}>();

  const links = computed<ActionItem[]>(() => {
    const user = page.props?.craft?.currentUser;

    return [
      ...(user?.personalGuidanceUrl
        ? [
            {
              type: 'link' as const,
              label: t('Personal guidance'),
              icon: 'user',
              href: user.personalGuidanceUrl,
            },
          ]
        : []),
      ...(user?.aiGuidanceUrl
        ? [
            {
              type: 'link' as const,
              label: t('Site guidance'),
              icon: 'sparkles',
              href: user.aiGuidanceUrl,
            },
          ]
        : []),
      ...(user?.aiSkillsUrl
        ? [
            {
              type: 'link' as const,
              label: t('Skills'),
              icon: 'layer-group',
              href: user.aiSkillsUrl,
            },
          ]
        : []),
    ];
  });

  const conversationActions: ActionItem[] = [
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

  // The links go first, as the menu moves Delete to the end, which would put it with them.
  const actions = computed<ActionItem[]>(() => [
    ...links.value,
    ...(props.stored && links.value.length ? [{type: 'hr' as const}] : []),
    ...(props.stored ? conversationActions : []),
  ]);

  /** Posts to one of the conversation’s actions. */
  async function send(
    url: string,
    body: Record<string, unknown> = {}
  ): Promise<void> {
    try {
      await actionClient.post(url, {threadId: props.threadId, ...body});
    } catch (error) {
      throw requestError(error);
    }
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
  <!-- Links make Inertia visits, as `ActionMenu` renders them as `CpLink`s. -->
  <ActionMenu
    v-if="actions.length"
    :label="t('Chat actions')"
    :actions="actions"
  />

  <ModalForm
    :is-active="renaming"
    :title="t('Rename Chat')"
    width="sm"
    :loading="processing"
    @close="renaming = false"
    @submit="rename"
  >
    <craft-field :label="t('Name')" required .hasErrors="!!error">
      <craft-input slot="input">
        <input slot="input" v-model="name" type="text" required />
      </craft-input>
      <div v-if="error" slot="errors">{{ error }}</div>
    </craft-field>
  </ModalForm>
</template>
