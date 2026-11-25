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
  type Alert = {content: string; showIcon: boolean};

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
    () => alerts.value.map((alert) => alert.content),
    (contents, previous) => {
      const added = contents.filter((content) => !previous.includes(content));
      if (added.length) {
        announce(added.map(plainText).join(' '));
      }
    }
  );

  function plainText(html: string): string {
    return (
      new DOMParser().parseFromString(html, 'text/html').body.textContent ?? ''
    ).trim();
  }

  /** Licensing alerts that a Craft Console purchase resolves can be refreshed once it's made. */
  function isResolvable(alert: Alert): boolean {
    return alert.content.includes('resolvable-alert-buttons');
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
          <div class="cp-alerts__content" v-html="alert.content"></div>
        </craft-callout>
        <!-- Beside “Resolve now”, as `Craft.cp.initAlerts()` puts it. -->
        <Teleport
          v-if="isResolvable(alert)"
          defer
          :to="`.cp-alerts [data-alert-index='${index}'] .resolvable-alert-buttons`"
        >
          <craft-button
            type="button"
            variant="outline"
            inherit
            .loading="refreshing"
            @click="refresh"
            >{{ t('Refresh') }}</craft-button
          >
        </Teleport>
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

  craft-callout {
    --c-callout-padding-inline: calc(var(--c-spacing-md) - 1px);
    --c-callout-icon-padding-inline: 0 var(--c-spacing-sm);
    --c-callout-text-padding-inline: 0;
  }

  .cp-alerts__content > :deep(* + *) {
    margin-block-start: var(--c-spacing-sm);
  }

  .cp-alerts__content :deep(h2) {
    margin: 0;
    font-size: var(--c-text-md);
    font-weight: bold;
  }

  .cp-alerts__content :deep(ul.errors) {
    padding-inline-start: 1.25em;
    list-style-type: square;
  }

  .cp-alerts__content :deep(.resolvable-alert-buttons) {
    margin-block-start: var(--c-spacing-md);
  }

  .cp-alerts__content :deep(.resolvable-alert-buttons > * + *) {
    margin-inline-start: var(--c-spacing-sm);
  }
</style>
