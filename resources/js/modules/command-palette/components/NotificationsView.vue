<script setup lang="ts">
  /**
   * The current user's notifications, for the palette's “Notifications” item.
   * They come with the page, as they do for the global header's notification
   * center, so they show without a request. Opening one closes the palette
   * first.
   */
  import {t} from '@craftcms/ui';
  import {computed, nextTick, onBeforeUnmount, shallowRef} from 'vue';
  import {
    getNotifications,
    markNotificationsRead,
    navigateToNotification,
    onNotificationsChange,
  } from '../../notifications/store';
  import type {
    CommandPaletteViewProps,
    CommandPaletteViewResult,
  } from '../types';

  import '../../notifications/components/cp-notification-list';

  defineProps<Partial<CommandPaletteViewProps>>();

  const emit = defineEmits<{
    (event: 'done', result: CommandPaletteViewResult): void;
    (event: 'back'): void;
  }>();

  const notifications = shallowRef(getNotifications());
  const stopListening = onNotificationsChange(() => {
    notifications.value = getNotifications();
  });
  onBeforeUnmount(stopListening);

  const unreadIds = computed(() =>
    notifications.value
      .filter((notification) => notification.unread)
      .map((notification) => notification.id)
  );

  /**
   * Marks every unread notification as read. The button goes once they are,
   * so focus moves to the view's heading rather than being dropped.
   */
  async function markAllRead(event: MouseEvent): Promise<void> {
    const view = (event.currentTarget as HTMLElement).closest<HTMLElement>(
      '.command-palette__view'
    );
    void markNotificationsRead(unreadIds.value);
    await nextTick();
    view?.querySelector<HTMLElement>('h2[tabindex="-1"]')?.focus();
  }

  function open(event: CustomEvent<{url: string}>): void {
    event.preventDefault();
    emit('done', {run: () => navigateToNotification(event.detail.url)});
  }
</script>

<template>
  <div class="command-palette-notifications">
    <craft-empty
      v-if="!notifications.length"
      class="command-palette-notifications__empty"
      :label="t('No notifications')"
    ></craft-empty>
    <template v-else>
      <Teleport
        v-if="unreadIds.length"
        defer
        :to="actionsTarget ?? 'body'"
        :disabled="!actionsTarget"
      >
        <craft-button
          type="button"
          size="small"
          variant="plain"
          flush="inline-end"
          @click="markAllRead"
        >
          {{ t('Mark all as read') }}
        </craft-button>
      </Teleport>
      <cp-notification-list
        class="command-palette-notifications__list"
        @cp-notification-navigate="open"
      ></cp-notification-list>
    </template>
  </div>
</template>

<style scoped>
  /* Lines the notifications up with the view's heading, rather than insetting them as the notification center does. */
  .command-palette-notifications__list::part(item) {
    padding-inline: 0;
  }

  /* The view header's margin already spaces the first one from the heading. */
  .command-palette-notifications__list::part(first-item) {
    padding-block-start: 0;
  }

  .command-palette-notifications {
    display: flex;
    flex: 1;
    flex-direction: column;
  }

  /* Fills the view below its header, like an empty table, so the message sits in its middle. */
  .command-palette-notifications__empty {
    display: grid;
    flex: 1;
  }
</style>
