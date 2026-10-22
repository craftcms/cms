<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/pane/pane';
  import '@craftcms/ui/components/select/select';
  import CraftSwitch from '@craftcms/ui/components/switch/switch';
  import {actionClient} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {router} from '@inertiajs/vue3';
  import {computed, ref, useId} from 'vue';
  import {destroyForSite} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/DeleteElementController';
  import {useSlideout} from '@/common/slideouts/useSlideout';
  import type {UiControlPayload} from './types';
  import {ignoreModelValueInitialization, inputName} from './runtime';

  type Site = {
    id: number;
    name: string;
    url: string | null;
    current: boolean;
    removable: boolean;
  };

  type AdditionalSite = {
    id: number;
    name: string;
    enabledByDefault: boolean;
  };

  type ElementSitesProps = {
    sites: Site[];
    additionalSites: AdditionalSite[];
    withStatuses: boolean;
    deleteParams: {elementId: number; draftId: number | null} | null;
  };

  type Statuses = Record<string, boolean>;

  const props = defineProps<{
    control: UiControlPayload<ElementSitesProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: Statuses, kind: 'discrete'): void;
  }>();

  const slideout = useSlideout();
  const idPrefix = `element-sites-${useId()}`;
  const expanded = ref(false);
  const deleting = ref<number | null>(null);

  const statuses = computed<Statuses>(() =>
    props.value &&
    typeof props.value === 'object' &&
    !Array.isArray(props.value)
      ? (props.value as Statuses)
      : {}
  );
  const name = computed(() => inputName(props.control.path));

  const currentSites = computed(() =>
    props.control.props.sites.filter((site) => site.current)
  );
  const otherSites = computed(() =>
    props.control.props.sites.filter((site) => !site.current)
  );

  const orderedSites = computed(() => [
    ...currentSites.value,
    ...otherSites.value,
  ]);

  // If the element is saved to 2+ other sites, list them within a disclosure
  const collapsible = computed(() => otherSites.value.length > 1);
  const toggleLabel = computed(() =>
    t('{num, number} other {num, plural, =1{site} other{sites}}', {
      num: otherSites.value.length,
    })
  );

  // Sites that have been added here, but not saved yet
  const addedSites = computed(() =>
    props.control.props.additionalSites.filter((site) =>
      Object.hasOwn(statuses.value, String(site.id))
    )
  );
  const addableSites = computed(() =>
    props.control.props.additionalSites.filter(
      (site) => !Object.hasOwn(statuses.value, String(site.id))
    )
  );

  function rowId(site: {id: number}): string {
    return `${idPrefix}-${site.id}`;
  }

  function nameId(site: {id: number}): string {
    return `${rowId(site)}-name`;
  }

  function setStatus(siteId: number, enabled: boolean): void {
    emit('update:value', {...statuses.value, [siteId]: enabled}, 'discrete');
  }

  function removeStatus(siteId: number): void {
    const value = {...statuses.value};
    delete value[siteId];
    emit('update:value', value, 'discrete');
  }

  function onStatusChanged(siteId: number, event: Event): void {
    ignoreModelValueInitialization((event) => {
      if (!(event.target instanceof CraftSwitch)) {
        throw new TypeError('Expected a switch event target.');
      }

      // Setting `checked` programmatically fires the event too
      if (event.target.checked !== Boolean(statuses.value[siteId])) {
        setStatus(siteId, event.target.checked);
      }
    })(event);
  }

  function addSite(event: Event): void {
    const select = event.target as HTMLSelectElement;
    const site = addableSites.value.find(
      (site) => String(site.id) === select.value
    );
    select.value = '';

    if (site) {
      setStatus(
        site.id,
        props.control.props.withStatuses ? site.enabledByDefault : true
      );
    }
  }

  function switchSite(event: MouseEvent, site: Site): void {
    if (
      !site.url ||
      event.altKey ||
      event.ctrlKey ||
      event.metaKey ||
      event.shiftKey ||
      event.button !== 0
    ) {
      return;
    }

    event.preventDefault();

    if (slideout) {
      slideout.instance.href = site.url;
      void slideout.reload();
    } else {
      router.visit(site.url);
    }
  }

  async function deleteSite(site: Site): Promise<void> {
    const params = props.control.props.deleteParams;

    if (!params) {
      return;
    }

    deleting.value = site.id;

    try {
      await actionClient.post(destroyForSite.url(), {
        elementId: params.elementId,
        siteId: site.id,
        ...(params.draftId ? {draftId: params.draftId} : {}),
      });
    } catch (error: any) {
      deleting.value = null;
      window.Craft?.cp?.displayError(
        error?.response?.data?.message ?? t('A server error occurred.')
      );
      return;
    }

    removeStatus(site.id);

    if (slideout) {
      await slideout.reload();
    } else {
      router.reload();
    }

    deleting.value = null;
  }
</script>

<template>
  <craft-pane class="element-sites-pane" appearance="outline" padding="sm">
    <div class="element-sites" :aria-invalid="invalid ? 'true' : undefined">
      <template v-for="site in orderedSites" :key="site.id">
        <button
          v-if="collapsible && site.id === otherSites[0]?.id"
          type="button"
          class="element-sites__toggle"
          :aria-expanded="expanded ? 'true' : 'false'"
          :aria-controls="otherSites.map(rowId).join(' ')"
          @click="expanded = !expanded"
        >
          <craft-icon
            :name="expanded ? 'chevron-down' : 'chevron-right'"
          ></craft-icon>
          {{ toggleLabel }}
        </button>
        <div
          v-show="site.current || !collapsible || expanded"
          :id="rowId(site)"
          class="element-sites__site"
        >
          <span class="element-sites__name">
            <craft-icon
              v-if="site.current"
              name="check"
              class="element-sites__check"
            ></craft-icon>
            <span :id="nameId(site)">
              <a
                v-if="site.url"
                :href="site.url"
                @click="switchSite($event, site)"
                >{{ site.name }}</a
              >
              <template v-else>{{ site.name }}</template>
            </span>
            <span v-if="site.current" class="sr-only"
              >, {{ t('selected') }}</span
            >
          </span>
          <craft-button
            v-if="editable && site.removable"
            type="button"
            icon="xmark-large"
            variant="plain"
            size="small"
            :aria-label="t('Remove {name}', {name: site.name})"
            .loading="deleting === site.id"
            @click="deleteSite(site)"
          ></craft-button>
          <craft-switch
            v-if="control.props.withStatuses"
            :name="editable ? `${name}[${site.id}]` : ''"
            .checked="Boolean(statuses[site.id])"
            size="small"
            :labelled-by="nameId(site)"
            .disabled="!editable"
            @model-value-changed="onStatusChanged(site.id, $event)"
          ></craft-switch>
        </div>
      </template>

      <div
        v-for="site in addedSites"
        :id="rowId(site)"
        :key="site.id"
        class="element-sites__site"
      >
        <span class="element-sites__name">
          <span :id="nameId(site)">{{ site.name }}</span>
        </span>
        <craft-button
          type="button"
          icon="xmark-large"
          variant="plain"
          size="small"
          :aria-label="t('Remove {name}', {name: site.name})"
          @click="removeStatus(site.id)"
        ></craft-button>
        <craft-switch
          v-if="control.props.withStatuses"
          :name="`${name}[${site.id}]`"
          .checked="Boolean(statuses[site.id])"
          size="small"
          :labelled-by="nameId(site)"
          @model-value-changed="onStatusChanged(site.id, $event)"
        ></craft-switch>
      </div>

      <craft-select
        v-if="editable && addableSites.length"
        class="element-sites__add"
        label-sr-only
      >
        <span slot="label">{{ t('Add a site…') }}</span>
        <select slot="input" @change="addSite">
          <option value="">{{ t('Add a site…') }}</option>
          <option
            v-for="site in addableSites"
            :key="site.id"
            :value="String(site.id)"
          >
            {{ site.name }}
          </option>
        </select>
      </craft-select>
    </div>
  </craft-pane>
</template>

<style scoped>
  .element-sites-pane {
    --c-pane-background: transparent;
  }

  .element-sites {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs, 0.25rem);
    padding-inline-end: var(--c-spacing-xs, 0.25rem);
  }

  .element-sites__site {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs, 0.25rem);
    min-block-size: 1.75rem;
  }

  .element-sites__name {
    position: relative;
    flex-grow: 1;
    padding-inline-start: 1.25rem;
  }

  .element-sites__check {
    position: absolute;
    inset-inline-start: 0;
    inset-block-start: 50%;
    translate: 0 -50%;
    font-size: 0.75rem;
  }

  .element-sites__toggle {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    min-block-size: 1.75rem;
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    cursor: pointer;
    text-align: start;
  }

  .element-sites__toggle craft-icon {
    inline-size: 1rem;
    font-size: 0.625rem;
  }

  .element-sites__add {
    margin-block-start: var(--c-spacing-xs, 0.25rem);
  }
</style>
