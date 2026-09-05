<script setup lang="ts">
  import {computed, nextTick, ref} from 'vue';
  import {t} from '@craftcms/ui';
  import SupportSearch from './SupportSearch.vue';
  import SupportForm from './SupportForm.vue';
  import type {SupportData} from './types';

  defineProps<{
    id: number;
    data: SupportData;
  }>();

  const screen = ref<'home' | 'help' | 'feedback'>('home');
  const support = ref(false);
  const message = ref('');
  const home = ref<HTMLElement>();
  const supportForm = ref<InstanceType<typeof SupportForm>>();
  const sending = computed(() => supportForm.value?.sending ?? false);

  async function cancel() {
    if (sending.value) return;

    const previous = screen.value;
    screen.value = 'home';
    support.value = false;

    await nextTick();
    home.value
      ?.querySelector<HTMLButtonElement>(`[data-screen="${previous}"]`)
      ?.focus();
  }
</script>

<template>
  <craft-pane appearance="raised" padding="none">
    <slot name="header" />
    <div
      class="body"
      @keydown.esc.stop="support && !sending ? (support = false) : cancel()"
    >
      <div v-show="screen === 'home'" ref="home" class="support-tiles">
        <craft-button
          type="button"
          variant="fill"
          class="support-tile"
          data-screen="help"
          @click="screen = 'help'"
        >
          <span class="support-tile-content">
            <craft-icon name="life-ring" class="support-tile-icon"></craft-icon>
            <span>{{ t('Get help') }}</span>
            <span class="support-tile-description">{{
              t('How-to’s and other questions')
            }}</span>
          </span>
        </craft-button>
        <craft-button
          type="button"
          variant="fill"
          class="support-tile"
          data-screen="feedback"
          @click="screen = 'feedback'"
        >
          <span class="support-tile-content">
            <craft-icon name="bullhorn" class="support-tile-icon"></craft-icon>
            <span>{{ t('Give feedback') }}</span>
            <span class="support-tile-description">{{
              t('Bug reports and feature requests')
            }}</span>
          </span>
        </craft-button>
      </div>
      <div v-show="screen !== 'home'" class="space-y-4 p-4">
        <SupportSearch
          v-show="!support"
          v-model="message"
          :active="screen !== 'home' && !support"
          :screen="screen"
          :data="data"
          @contact-support="support = true"
        />
        <SupportForm
          v-show="support"
          ref="supportForm"
          v-model="message"
          :active="screen !== 'home' && support"
          :id="id"
          :data="data"
          :screen="screen"
        />
        <craft-button type="button" :disabled="sending" @click="cancel">{{
          t('Cancel')
        }}</craft-button>
      </div>
    </div>
  </craft-pane>
</template>

<style scoped>
  .support-tiles {
    overflow: hidden;
    border-radius: var(--c-radius-md);
  }
  .support-tile {
    display: flex;
    width: 100%;
    min-height: 150px;
    padding: 24px 16px;
    border: 0;
    border-radius: 0;
    white-space: normal;
  }
  .support-tile + .support-tile {
    border-block-start: 1px solid var(--c-color-neutral-border-normal);
  }
  .support-tile-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
  }
  .support-tile-icon {
    font-size: 48px;
    opacity: 0.3;
    margin-block-end: 4px;
  }
  .support-tile-description {
    font-size: var(--c-text-sm);
    color: var(--c-color-neutral-on-quiet);
  }
</style>
