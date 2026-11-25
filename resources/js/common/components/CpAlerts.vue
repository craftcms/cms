<script setup lang="ts">
  /**
   * The control panel alerts, such as licensing issues and the ones plugins
   * add with `CpAlertsResolving`, shown above the top bar as the Twig layout
   * shows them above its header.
   */
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui/utilities/translate';

  /** An alert from `CraftCms\Cms\Cp\Alerts::get()`. Its content is HTML. */
  type Alert = {content: string; showIcon: boolean};

  const page = usePage<{alerts?: Alert[]}>();
  const alerts = computed(() => page.props.alerts ?? []);
</script>

<template>
  <ul v-if="alerts.length" class="cp-alerts">
    <li v-for="(alert, index) in alerts" :key="index">
      <craft-callout
        variant="danger"
        appearance="fill"
        rounded="none"
        .hideIcon="!alert.showIcon"
      >
        <craft-icon
          v-if="alert.showIcon"
          slot="icon"
          name="triangle-exclamation"
          :label="t('Error')"
        ></craft-icon>
        <div v-html="alert.content"></div>
      </craft-callout>
    </li>
  </ul>
</template>

<style scoped>
  .cp-alerts {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  craft-callout {
    --c-callout-padding-inline: var(--cp-container-padding);
  }
</style>
