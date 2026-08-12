<script setup lang="ts">
  /**
   * Shared shell for the user edit screens — the Vue-side counterpart to
   * `EditUserTrait::asEditUserScreen()`.
   *
   * Every user screen (Profile, Permissions, Preferences, Addresses, Password,
   * Passkeys, Sign-in Providers) is built by that one server-side method, which
   * hands each of them the same details-column payload. Wrapping a page in this
   * component is what renders it, so a new screen picks the column up by
   * construction instead of by remembering to re-teleport it.
   *
   * Two transports carry the same content (`getSidebarHtml()` +
   * `metadataHtml()`), so both are handled: `detailsFragment` is an
   * `HtmlStack::capture()` fragment that brings its registered JS/CSS along,
   * while `details` is the plain HTML string `CpScreenResponse` derives from
   * `metaSidebarHtml()`. The fragment wins where a screen provides one.
   */
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';

  const page = usePage<{
    details?: string | null;
    detailsFragment?: CraftCms.Cms.View.HtmlFragment | null;
  }>();

  const detailsFragment = computed(() => page.props.detailsFragment ?? null);
  const details = computed(() => page.props.details ?? null);
  const hasDetails = computed(() =>
    Boolean(detailsFragment.value || details.value)
  );
</script>

<template>
  <slot></slot>

  <LayoutSlot v-if="hasDetails" name="details">
    <HtmlFragmentRenderer v-if="detailsFragment" :fragment="detailsFragment" />
    <div v-else v-html="details"></div>
  </LayoutSlot>
</template>
