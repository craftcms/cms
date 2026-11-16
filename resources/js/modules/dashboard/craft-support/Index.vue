<script setup lang="ts">
  import {computed, nextTick, ref, shallowRef} from 'vue';
  import {t} from '@craftcms/ui';
  import SupportSearch from './SupportSearch.vue';
  import SupportForm from './SupportForm.vue';
  import type {SupportData} from './types';
  import TileContent from '../TileContent.vue';
  import {useWidgetColor} from '../useWidgetColor';

  const color = useWidgetColor();

  defineProps<{
    id: number;
    data: SupportData;
  }>();

  const screen = ref<'home' | 'help' | 'feedback'>('home');
  const support = ref(false);
  const message = ref('');
  const home = ref<HTMLElement>();
  const sending = shallowRef(false);
  const cardHeading = computed(() => {
    if (support.value) return t('Contact Developer Support');

    return screen.value === 'help'
      ? t('Ask on Stack Exchange')
      : t('Post on GitHub');
  });

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
  <div @keydown.esc.stop="support && !sending ? (support = false) : cancel()">
    <slot name="header" />
    <div
      v-show="screen === 'home'"
      ref="home"
      class="support-tiles"
      :data-color="color"
    >
      <craft-button
        type="button"
        variant="fill"
        class="support-tile"
        data-screen="help"
        @click="screen = 'help'"
      >
        <TileContent
          icon="life-ring"
          :label="t('Get help')"
          :description="t('How-to’s and other questions')"
        />
      </craft-button>
      <craft-button
        type="button"
        variant="fill"
        class="support-tile"
        data-screen="feedback"
        @click="screen = 'feedback'"
      >
        <TileContent
          icon="bullhorn"
          :label="t('Give feedback')"
          :description="t('Bug reports and feature requests')"
        />
      </craft-button>
    </div>
    <craft-card :data-color="color" v-show="screen !== 'home'">
      <h2 slot="label" class="cp-h4">{{ cardHeading }}</h2>
      <div v-show="screen !== 'home'" class="space-y-4">
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
          v-model="message"
          v-model:sending="sending"
          :active="screen !== 'home' && support"
          :id="id"
          :data="data"
          :screen="screen"
        />
        <craft-button type="button" .disabled="sending" @click="cancel">{{
          t('Cancel')
        }}</craft-button>
      </div>
    </craft-card>
  </div>
</template>

<style scoped>
  .support-tiles {
    border-radius: var(--c-card-radius, var(--c-radius-md));
    box-shadow: var(--c-card-shadow, var(--c-shadow-sm));
  }
  .support-tile {
    display: flex;
    width: 100%;
    min-height: 150px;
    padding-block: 24px;
    padding-inline: 16px;
    border: var(--c-card-border-width, 1px) solid var(--_tile-border);
    border-radius: 0;
    white-space: normal;
  }
  /*
   * At rest, tiles match the other widgets: a colored card's tinted body,
   * gray by default, or no fill without a color. They darken from there, as
   * nav items do. The container's colors are captured in properties of its
   * own, which the tiles inherit already resolved, since each tile, a
   * craft-button, sets the color tokens on itself.
   */
  .support-tiles {
    --_tile-fill: transparent;
    --_tile-border: var(--c-color-gray-border-quiet);
  }
  .support-tiles[data-color] {
    --_tile-fill: color-mix(
      in srgb,
      var(--c-color-fill-quiet),
      transparent 70%
    );
    --_tile-border: var(--c-color-border-quiet);
    --_tile-text: var(--c-color-on-quiet);
  }
  .support-tiles[data-color] .support-tile {
    color: var(--_tile-text);
  }
  .support-tiles[data-color='black'] {
    --_tile-fill: var(--c-color-fill-quiet);
    --_tile-border: color-mix(
      in srgb,
      var(--c-color-black-on-quiet),
      transparent 80%
    );
  }
  .support-tile {
    background-color: var(--_tile-fill);

    &:hover {
      background-color: color-mix(in srgb, var(--_tile-fill), currentColor 5%);
    }

    &:active {
      background-color: color-mix(in srgb, var(--_tile-fill), currentColor 10%);
    }
  }
  .support-tile:first-child {
    border-start-start-radius: var(--c-card-radius, var(--c-radius-md));
    border-start-end-radius: var(--c-card-radius, var(--c-radius-md));
  }
  .support-tile:last-child {
    border-end-start-radius: var(--c-card-radius, var(--c-radius-md));
    border-end-end-radius: var(--c-card-radius, var(--c-radius-md));
  }
  .support-tile + .support-tile {
    border-block-start-width: 0;
  }
  .support-tile:focus-visible {
    position: relative;
    z-index: 1;
  }
</style>
