<script setup lang="ts">
  /**
   * The fallback screen for `CpScreenResponse`s that have no `inertiaPage()`.
   *
   * Draws the server-rendered HTML fragments the response already carries —
   * the same ones the legacy jQuery slideout consumes — into the shell's
   * slots. That means every CP screen works in a Vue slideout before it's been
   * ported to a real Vue page, and porting one is just adding `inertiaPage()`.
   */
  import {computed, onMounted} from 'vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useScreenContentReady} from '@/common/composables/screen';

  const props = defineProps<{
    content?: string | null;
    details?: string | null;
    tabs?: string | null;
    contentNotice?: string | null;
    errorSummary?: string | null;
    toolbar?: string | null;
    headHtml?: string | null;
    bodyHtml?: string | null;
  }>();

  /**
   * Only the main content carries `headHtml`/`bodyHtml`: those are per-response,
   * not per-fragment, and appending them once avoids loading a screen's assets
   * several times over.
   */
  const contentFragment = computed(() =>
    props.content
      ? {
          html: props.content,
          headHtml: props.headHtml ?? '',
          bodyHtml: props.bodyHtml ?? '',
        }
      : null
  );

  const fragment = (html?: string | null) =>
    html ? {html, headHtml: '', bodyHtml: ''} : null;

  /**
   * Screens whose behavior is driven from JS — the element editor, notably —
   * wait on this before wiring themselves up.
   *
   * Every fragment has to be in the document first, not just the content one:
   * the element editor takes over the tabs, which land in their own outlet on
   * their own schedule. So the signal is held back until each renderer that's
   * going to report in has.
   */
  const signalReady = useScreenContentReady();

  const expected = computed(
    () =>
      [
        props.tabs,
        props.contentNotice,
        props.errorSummary,
        props.toolbar,
        props.details,
        contentFragment.value,
      ].filter(Boolean).length
  );

  let ready = 0;

  function fragmentReady(): void {
    if (++ready >= expected.value) {
      signalReady();
    }
  }

  onMounted(() => {
    if (!expected.value) {
      signalReady();
    }
  });
</script>

<template>
  <LayoutSlot v-if="tabs" name="tabs">
    <HtmlFragmentRenderer :fragment="fragment(tabs)" @ready="fragmentReady" />
  </LayoutSlot>

  <LayoutSlot v-if="contentNotice" name="content-notice">
    <HtmlFragmentRenderer
      :fragment="fragment(contentNotice)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <LayoutSlot v-if="errorSummary" name="error-summary">
    <HtmlFragmentRenderer
      :fragment="fragment(errorSummary)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <LayoutSlot v-if="toolbar" name="toolbar">
    <HtmlFragmentRenderer
      :fragment="fragment(toolbar)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <LayoutSlot v-if="details" name="details">
    <HtmlFragmentRenderer
      :fragment="fragment(details)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <HtmlFragmentRenderer :fragment="contentFragment" @ready="fragmentReady" />
</template>
