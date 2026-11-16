<script setup lang="ts">
  import {router} from '@inertiajs/vue3';
  import {useNow} from '@vueuse/core';
  import {computed, onMounted, ref} from 'vue';
  import {actionClient, formattingLocale, t} from '@craftcms/ui';
  import {check as checkUpdates} from '@actions/Updates/UpdatesController';
  import {show} from '@actions/Utilities/UtilitiesController';
  import TileContent from './TileContent.vue';
  import WidgetHeaderActions from './WidgetHeaderActions.vue';
  import {useWidgetColor} from './useWidgetColor';

  const color = useWidgetColor();

  const props = defineProps<{
    data: {
      cached: boolean;
      total: number;
      /** When the updates were last fetched, if they're cached. */
      dateFetched?: string | null;
    };
  }>();
  const total = ref(props.data.total);
  const dateFetched = ref(props.data.dateFetched ?? null);
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
      dateFetched.value = data.dateFetched ?? new Date().toISOString();
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

  /**
   * Such as “3 updates available”. Built here: the message's closing braces
   * would end a template interpolation.
   */
  const availableLabel = computed(() =>
    t('{num, number} {num, plural, =1{update} other{updates}} available', {
      num: total.value,
    })
  );

  // Kept current, so “Checked 5 minutes ago” doesn't go stale on screen
  const now = useNow({interval: 60_000});

  /** When the updates were last checked, such as “5 minutes ago”. */
  const checkedAgo = computed(() => {
    if (!dateFetched.value) {
      return null;
    }

    const seconds = Math.round(
      (new Date(dateFetched.value).getTime() - now.value.getTime()) / 1000
    );
    const format = new Intl.RelativeTimeFormat(formattingLocale(), {
      numeric: 'auto',
    });
    const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
      ['day', 86_400],
      ['hour', 3_600],
      ['minute', 60],
    ];

    for (const [unit, size] of units) {
      if (Math.abs(seconds) >= size) {
        return format.format(Math.round(seconds / size), unit);
      }
    }

    return format.format(0, 'second');
  });
</script>

<template>
  <craft-card :data-color="color" class="updates">
    <slot name="header" />
    <WidgetHeaderActions>
      <craft-button
        type="button"
        icon="arrows-rotate"
        variant="plain"
        flush="inline-end block"
        :aria-label="t('Check for updates')"
        .disabled="checking"
        @click="check()"
      ></craft-button>
    </WidgetHeaderActions>
    <div class="body" aria-live="polite">
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
      <div v-if="checking" class="updates__status">
        <craft-spinner visible>{{ t('Checking for updates…') }}</craft-spinner>
      </div>
      <div v-else-if="total" class="updates__status">
        <TileContent icon="certificate" :label="availableLabel">
          <craft-button
            variant="primary"
            :href="show.url({id: 'updates'})"
            class="updates__action"
            >{{ t('Go to Updates') }}</craft-button
          >
        </TileContent>
      </div>
      <div v-else class="updates__status">
        <TileContent
          icon="circle-check"
          :label="t('You’re up to date')"
          :description="
            checkedAgo ? t('Checked {time}', {time: checkedAgo}) : null
          "
        />
      </div>
    </div>
  </craft-card>
</template>

<style scoped>
  .updates__status {
    --tile-content-icon-color: var(
      --c-color-fill-loud,
      var(--c-color-neutral-fill-loud)
    );
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-block: var(--c-spacing-md);
  }

  .updates__action {
    margin-block-start: var(--c-spacing-md);
  }
</style>
