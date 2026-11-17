<script setup lang="ts">
  /**
   * Whether updates are available, for the “About Craft CMS” widget and
   * command palette view, as a row with a link to them and a button to check
   * again. It checks when it’s shown, if the updates haven’t been fetched
   * lately.
   */
  import {router} from '@inertiajs/vue3';
  import {useNow} from '@vueuse/core';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    ref,
    useTemplateRef,
    watch,
  } from 'vue';
  import {actionClient, formattingLocale, t} from '@craftcms/ui';
  import {check as checkUpdates} from '@actions/Updates/UpdatesController';
  import {show} from '@actions/Utilities/UtilitiesController';
  import type {AboutCraftUpdates} from './about-craft';
  import AboutCraftRow from './AboutCraftRow.vue';

  const props = defineProps<{updates: AboutCraftUpdates}>();
  const emit = defineEmits<{
    /** A check fetched the updates, along with Craft's license info. */
    checked: [];
  }>();

  const total = ref(props.updates.total);
  const dateFetched = ref(props.updates.dateFetched ?? null);
  /** Whether the updates have been fetched, so there's a result to show. */
  const known = ref(isResult(props.updates));
  const checking = ref(false);
  const error = ref('');
  /**
   * Told to screen readers as a check starts and ends, including the one when
   * it's shown, which runs at most every few minutes. The row keeps showing
   * the last result while it checks, so it doesn't jump, and doesn't change.
   * Cleared once it's been read, so browsing the row doesn't hear it again.
   */
  const announcement = ref('');
  let clearTimer: ReturnType<typeof setTimeout> | undefined;

  function announce(message: string) {
    clearTimeout(clearTimer);
    announcement.value = message;
    clearTimer = setTimeout(() => (announcement.value = ''), 5000);
  }

  onBeforeUnmount(() => clearTimeout(clearTimer));
  const checkButton = useTemplateRef<HTMLElement>('checkButton');
  const goButton = useTemplateRef<
    HTMLElement & {updateComplete?: Promise<unknown>}
  >('goButton');

  /**
   * Whether updates were fetched: a failed request for them is cached too,
   * with no fetch date, and isn't a result to show.
   */
  function isResult(
    updates: Pick<AboutCraftUpdates, 'dateFetched'> & {
      cached?: boolean;
    }
  ): boolean {
    return updates.cached !== false && !!updates.dateFetched;
  }

  async function check(forceRefresh = true) {
    if (checking.value) return;

    checking.value = true;
    error.value = '';
    announce(t('Checking for updates…'));

    try {
      const {data} = await actionClient.post(checkUpdates.url(), {
        forceRefresh,
      });

      // Left out when the updates couldn't be fetched, which keeps the last
      // result rather than claiming it's up to date.
      if (!isResult(data)) {
        throw new Error('The updates couldn’t be fetched.');
      }

      total.value = data.total;
      dateFetched.value = data.dateFetched;
      // Read before the result shows: with updates, the button goes away.
      const hadFocus = isFocused(checkButton.value);
      known.value = true;
      announce(total.value ? availableLabel.value : t('Up to date'));

      if (total.value && hadFocus) {
        await focusGoButton();
      }
      // The nav's badge counts the updates too.
      router.reload({only: ['craft']});
      emit('checked');
    } catch {
      error.value = t('Unable to fetch updates at this time.');
      // With no result to show, the row says so instead of an alert.
      if (!known.value) {
        announce(error.value);
      } else {
        clearTimeout(clearTimer);
        announcement.value = '';
      }
    } finally {
      checking.value = false;
    }
  }

  function isFocused(element: HTMLElement | null): boolean {
    return !!element && element.contains(document.activeElement);
  }

  /**
   * Focuses “Go to Updates”. As a link, the button doesn't take focus
   * itself, so its anchor does, once it's rendered.
   */
  async function focusGoButton() {
    await nextTick();
    const button = goButton.value;
    await button?.updateComplete;
    (button?.shadowRoot?.querySelector<HTMLElement>('a') ?? button)?.focus();
  }

  // The palette view gets the updates after it shows, so they're followed.
  watch(
    () => props.updates,
    (updates) => {
      total.value = updates.total;
      dateFetched.value = updates.dateFetched ?? null;
      known.value = isResult(updates);

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
