<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {ReleaseReadiness} from '@/modules/releases/types';

  defineProps<{readiness: ReleaseReadiness | null}>();
</script>

<template>
  <section class="flex flex-col gap-3" aria-labelledby="release-readiness">
    <h2 id="release-readiness" class="text-lg font-semibold">
      {{ t('Readiness') }}
    </h2>

    <craft-callout v-if="readiness?.ready" variant="success" icon="check">
      {{ t('This release is ready to publish.') }}
    </craft-callout>

    <craft-callout
      v-else-if="readiness"
      variant="error"
      icon="triangle-exclamation"
    >
      {{ t('Resolve the following blockers before publishing.') }}
      <ul v-if="readiness.errors.length" class="mt-2 list-disc ps-5">
        <li v-for="error in readiness.errors" :key="error">{{ error }}</li>
      </ul>
    </craft-callout>

    <craft-callout v-else variant="neutral" icon="lock">
      {{ t('Readiness details are not available.') }}
    </craft-callout>

    <craft-callout
      v-if="readiness?.warnings.length"
      variant="warning"
      icon="triangle-exclamation"
    >
      <p>{{ t('Review these publication outcomes.') }}</p>
      <ul class="mt-2 list-disc ps-5">
        <li v-for="warning in readiness.warnings" :key="warning">
          {{ warning }}
        </li>
      </ul>
    </craft-callout>
  </section>
</template>
