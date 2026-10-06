<script setup lang="ts">
/**
 * A plugin-owned page. Everything here goes through public API only — the
 * global `craft:layout-slot` component — the way a third-party plugin would.
 *
 * The page owns the screen, so it may replace any slot. The same component
 * on a page the plugin doesn't own renders nothing — see the dashboard widget.
 */
import { reactive, resolveComponent } from "vue";

defineProps<{ owner: string }>();

const LayoutSlot = resolveComponent("craft:layout-slot");
const CpContainer = resolveComponent("craft:cp-container");

const playground = reactive({
  sidebar: false,
  notice: false,
  unknown: false,
});
</script>

<template>
  <!-- Page-owned replacements -->
  <LayoutSlot name="title">
    <h1 class="text-xl flex items-center gap-sm">
      Layout Slots
      <craft-chip size="small">plugin page</craft-chip>
    </h1>
  </LayoutSlot>

  <LayoutSlot name="content-actions">
    <craft-button type="button" variant="primary">Page action</craft-button>
  </LayoutSlot>

  <LayoutSlot name="content-toolbar-meta">
    <span class="text-sm text-(--c-text-quiet)">Rendered by {{ owner }}</span>
  </LayoutSlot>

  <LayoutSlot name="content-details">
    <div class="p-md">
      <h2 class="text-md mb-sm">Details</h2>
      <p class="text-sm">Filled from the page with <code>name="content-details"</code>.</p>
    </div>
  </LayoutSlot>

  <LayoutSlot name="page-footer">
    <p class="text-sm text-(--c-text-quiet) py-md">Page footer from a plugin.</p>
  </LayoutSlot>

  <!-- Content -->
  <CpContainer>
    <div class="cp-container__full">This should span the full width</div>

    <div class="pane mb-xl">
      <h2 class="text-lg mb-sm">Playground</h2>
      <p class="mb-md">
        Toggle more slots this page fills. Open the browser console to see the dev warnings.
      </p>

      <div class="flex flex-col gap-md">
        <label class="flex items-center gap-sm">
          <input v-model="playground.sidebar" type="checkbox" />
          Replace <code>content-sidebar</code> — the secondary nav is hidden.
        </label>

        <label class="flex items-center gap-sm">
          <input v-model="playground.notice" type="checkbox" />
          Fill <code>content-notices</code> — opens the notices bar.
        </label>

        <label class="flex items-center gap-sm">
          <input v-model="playground.unknown" type="checkbox" />
          Fill <code>actions</code>, which isn't a slot — nothing renders, and the console warns.
        </label>
      </div>
    </div>
  </CpContainer>

  <LayoutSlot v-if="playground.sidebar" name="content-sidebar">
    <div class="p-md text-sm">This page's own sidebar.</div>
  </LayoutSlot>

  <LayoutSlot v-if="playground.notice" name="content-notices">
    <craft-callout variant="info">A notice from the page.</craft-callout>
  </LayoutSlot>

  <LayoutSlot v-if="playground.unknown" name="actions">
    <craft-button type="button">Never shown</craft-button>
  </LayoutSlot>
</template>
