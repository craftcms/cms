<script setup lang="ts">
  /**
   * The fallback screen for `CpScreenResponse`s that have no `inertiaPage()`.
   *
   * Draws the server-rendered HTML fragments the response already carries —
   * the same ones the legacy jQuery slideout consumes — into the shell's
   * slots. That means every CP screen works in a Vue slideout before it's been
   * ported to a real Vue page, and porting one is just adding `inertiaPage()`.
   */
  import {computed} from 'vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

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
</script>

<template>
  <LayoutSlot v-if="tabs" name="tabs">
    <HtmlFragmentRenderer :fragment="fragment(tabs)" />
  </LayoutSlot>

  <LayoutSlot v-if="contentNotice" name="content-notice">
    <HtmlFragmentRenderer :fragment="fragment(contentNotice)" />
  </LayoutSlot>

  <LayoutSlot v-if="errorSummary" name="error-summary">
    <HtmlFragmentRenderer :fragment="fragment(errorSummary)" />
  </LayoutSlot>

  <LayoutSlot v-if="toolbar" name="toolbar">
    <HtmlFragmentRenderer :fragment="fragment(toolbar)" />
  </LayoutSlot>

  <LayoutSlot v-if="details" name="details">
    <HtmlFragmentRenderer :fragment="fragment(details)" />
  </LayoutSlot>

  <HtmlFragmentRenderer :fragment="contentFragment" />
</template>
