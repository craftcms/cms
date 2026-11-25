<script setup lang="ts">
  /**
   * The control panel alerts, such as licensing issues and the ones plugins
   * add with `CpAlertsResolving`, shown above the top bar as the Twig layout
   * shows them above its header.
   */
  import {computed, ref, watch} from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui/utilities/translate';
  import {sendApiRequest} from '@craftcms/ui/utilities/api/apiClient';
  import {http} from '@craftcms/ui/utilities/api/http';
  import {destroy as shunAlert} from '@actions/App/CpAlertsController';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {showError} from '@/modules/messages/messages';

  /** An alert from `CraftCms\Cms\Cp\Alerts::get()`. Its content is HTML. */
  type Alert = {
    content: string;
    showIcon: boolean;
    title?: string;
    actions?: Array<{label: string; url: string}>;
    refreshable?: boolean;
  };

  const page = usePage<{alerts?: Alert[]}>();
  const shunned = ref(new Set<string>());
  const alerts = computed(() =>
    (page.props.alerts ?? []).filter(
      (alert) => !shunned.value.has(alert.content)
    )
  );

  // Alerts that arrive after the page has loaded, such as deferred ones, are
  // announced. The ones already showing aren't repeated.
  const {announce} = useAnnouncer();
  watch(
    () => alerts.value,
    (current, previous) => {
      const shown = new Set(previous.map((alert) => alert.content));
      const added = current.filter((alert) => !shown.has(alert.content));
      if (added.length) {
        announce(
          added
            .map((alert) =>
              [alert.title, plainText(alert.content)].filter(Boolean).join(' ')
            )
            .join(' ')
        );
      }
    }
  );

  function plainText(html: string): string {
    return (
      new DOMParser().parseFromString(html, 'text/html').body.textContent ?? ''
    ).trim();
  }

  const refreshing = ref(false);

  async function refresh(): Promise<void> {
    refreshing.value = true;

    try {
      // Refreshes the cached license info.
      await sendApiRequest('GET', 'ping');
      await new Promise<void>((resolve) =>
        router.reload({only: ['alerts'], onFinish: () => resolve()})
      );
    } finally {
      refreshing.value = false;
    }
  }

  /** Dismisses an alert from a link with a `shun:<message>` class, as `Craft.cp` does. */
  async function onClick(event: MouseEvent): Promise<void> {
    const link = (event.target as Element | null)?.closest<HTMLAnchorElement>(
      'a[class^="shun:"]'
    );
    const index =
      link?.closest<HTMLElement>('[data-alert-index]')?.dataset.alertIndex;
    const alert = index === undefined ? undefined : alerts.value[Number(index)];
    if (!link || !alert) {
      return;
    }

    event.preventDefault();

    try {
      await http.post(shunAlert().url, {
        message: link.className.substring(5),
      });
      shunned.value = new Set(shunned.value).add(alert.content);
    } catch (error) {
      showError(
        (error as {response?: {data?: {message?: string}}}).response?.data
          ?.message
      );
    }
  }
</script>

<template>
  <section v-if="alerts.length" :aria-label="t('Alerts')">
    <ul class="cp-alerts" @click="onClick">
      <li
        v-for="(alert, index) in alerts"
        :key="alert.content"
        :data-alert-index="index"
      >
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
          <h2 v-if="alert.title" slot="title" class="cp-alerts__title">
            {{ alert.title }}
          </h2>
          <div class="cp-alerts__content" v-html="alert.content"></div>
          <div
            v-if="alert.actions?.length || alert.refreshable"
            slot="action"
            class="cp-alerts__actions"
          >
            <craft-button
              v-for="action in alert.actions"
              :key="action.url"
              :href="action.url"
              variant="outline"
              size="small"
              inherit
              >{{ action.label }}</craft-button
            >
            <!-- Once a purchase is made, a refresh of the license info clears the alert. -->
            <craft-button
              v-if="alert.refreshable"
              type="button"
              variant="outline"
              size="small"
              inherit
              .loading="refreshing"
              @click="refresh"
              >{{ t('Refresh') }}</craft-button
            >
          </div>
        </craft-callout>
      </li>
    </ul>
  </section>
</template>

<style scoped>
  .cp-alerts {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .cp-alerts > li + li {
    border-block-start: 1px solid var(--c-color-danger-border-quiet);
  }

  craft-callout {
    --c-callout-padding-inline: calc(var(--c-spacing-md) - 1px);
    --c-callout-icon-padding-inline: 0 var(--c-spacing-sm);
    --c-callout-text-padding-inline: 0;
  }

  .cp-alerts__content > :deep(* + *) {
    margin-block-start: var(--c-spacing-sm);
  }

  /* Alert links are inline-flex, so their external-link icon is centered on the line by hand. */
  .cp-alerts__content :deep(a) {
    align-items: start;
  }

  /* Centered on the line, then nudged down a touch to sit with lowercase link text. */
  .cp-alerts__content :deep(a[data-external-link]::after) {
    translate: 0 calc((1lh - 0.75em) / 2 + 0.075em);
  }

  .cp-alerts__title {
    margin: 0;
    font-size: var(--c-text-md);
    font-weight: bold;
  }

  .cp-alerts__content :deep(ul.errors) {
    padding-inline-start: 1.25em;
    list-style-type: square;
  }

  .cp-alerts__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
  }
</style>
