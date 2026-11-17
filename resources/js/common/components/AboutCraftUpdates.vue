<script setup lang="ts">
  /**
   * Whether updates are available, for the “About Craft CMS” widget and
   * command palette view, as a row with a link to them and a button to check
   * again. It checks when it’s shown, if the updates haven’t been fetched
   * lately.
   */
  import {router} from '@inertiajs/vue3';
  import {useNow} from '@vueuse/core';
  import {computed, nextTick, ref, useTemplateRef, watch} from 'vue';
  import {actionClient, formattingLocale, t} from '@craftcms/ui';
  import {check as checkUpdates} from '@actions/Updates/UpdatesController';
  import {show} from '@actions/Utilities/UtilitiesController';
  import type {AboutCraftUpdates} from './about-craft';
  import AboutCraftRow from './AboutCraftRow.vue';

  const props = defineProps<{updates: AboutCraftUpdates}>();

  const total = ref(props.updates.total);
  const dateFetched = ref(props.updates.dateFetched ?? null);
  /** Whether the updates have been fetched, so there's a result to show. */
  const known = ref(props.updates.cached);
  const checking = ref(false);
  const error = ref('');
  /**
   * Told to screen readers as a check starts and ends. The row keeps showing
   * the last result while it checks, so it doesn't jump, and doesn't change.
   */
  const announcement = ref('');
  const checkButton = useTemplateRef<HTMLElement>('checkButton');
  const goButton = useTemplateRef<HTMLElement>('goButton');

  async function check(forceRefresh = true) {
    if (checking.value) return;

    checking.value = true;
    error.value = '';
    announcement.value = t('Checking for updates…');

    try {
      const {data} = await actionClient.post(checkUpdates.url(), {
        forceRefresh,
      });

      total.value = data.total;
      // Left out when the updates couldn't be fetched
      dateFetched.value = data.dateFetched ?? null;
      // Read before the result shows: with updates, the button goes away.
      const hadFocus = isFocused(checkButton.value);
      known.value = true;
      announcement.value = total.value ? availableLabel.value : t('Up to date');

      if (total.value && hadFocus) {
        await nextTick();
        goButton.value?.focus();
      }
      // The nav's badge counts the updates too.
      router.reload({only: ['craft']});
    } catch {
      error.value = t('Unable to fetch updates at this time.');
      // With no result to show, the row says so instead of an alert.
      announcement.value = known.value ? '' : error.value;
    } finally {
      checking.value = false;
    }
  }

  function isFocused(element: HTMLElement | null): boolean {
    return !!element && element.contains(document.activeElement);
  }

  // The palette view gets the updates after it shows, so they're followed.
  watch(
    () => props.updates,
    (updates) => {
      total.value = updates.total;
      dateFetched.value = updates.dateFetched ?? null;
      known.value = updates.cached;

      if (!updates.cached) void check(false);
    },
    {immediate: true}
  );

  /**
   * Such as “3 updates available”. Built here: the message's closing braces
   * would end a template interpolation.
   */
  const availableLabel = computed(() =>
    t('{num, number} {num, plural, =1{update} other{updates}} available', {
      num: total.value,
    })
  );

  /** The row's line: the last result, or, before there is one, the check. */
  const title = computed(() => {
    if (!known.value) {
      return checking.value
        ? t('Checking for updates…')
        : t('Unable to fetch updates at this time.');
    }

    return total.value ? availableLabel.value : t('Up to date');
  });

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
  <craft-callout v-if="error && known" variant="danger" role="alert">{{
    error
  }}</craft-callout>
  <div class="sr-only" role="status">{{ announcement }}</div>
  <AboutCraftRow
    :tone="known && total ? 'accent' : undefined"
    class="about-craft-updates"
  >
    <template #icon>
      <craft-spinner v-if="!known && checking"></craft-spinner>
      <craft-icon v-else-if="!known" name="triangle-exclamation"></craft-icon>
      <craft-icon v-else-if="total" name="circle-up"></craft-icon>
      <craft-icon v-else name="circle-check"></craft-icon>
    </template>
    <span class="about-craft-row__title">{{ title }}</span>
    <!-- Kept a line tall before the first result, which fills it. Left out
         with updates to go to, which are what matters then. -->
    <span v-if="!(known && total)" class="about-craft-row__detail">{{
      checkedAgo ? t('Checked {time}', {time: checkedAgo}) : '\u00a0'
    }}</span>
    <template #action>
      <craft-button
        v-if="known && total"
        ref="goButton"
        variant="plain"
        inherit
        flush="inline block"
        icon="circle-right"
        icon-position="suffix"
        :href="show.url({id: 'updates'})"
        class="about-craft-row__link about-craft-updates__go"
        >{{ t('Go to Updates') }}</craft-button
      >
      <!-- Spins in place while it checks, keeping its size and focus. Once
           there are updates, they're the next step, so it goes, handing its
           focus to their link. -->
      <craft-button
        v-else
        ref="checkButton"
        type="button"
        icon="arrows-rotate"
        variant="plain"
        flush
        class="about-craft-updates__check"
        :aria-label="t('Check for updates')"
        .loading="checking"
        @click="check()"
      ></craft-button>
    </template>
  </AboutCraftRow>
</template>
