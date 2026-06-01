<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import type {Source} from '@/modules/elements/types/sources';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import CraftSelectRich from '@craftcms/cp/vue/CraftSelectRich.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {index} from '@/routes/craft/cp/content/index.js';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';

  const props = withDefaults(
    defineProps<{
      elementType: string;
      elementDisplayName: string;
      elementPluralDisplayName: string;
      context?: string;
      canHaveDrafts?: boolean;
      criteria?: Record<any, any>;
      defaultSource?: string | null;
      defaultSourcePath?: string | null;
      page?: string | null;
      sources?: Array<Source>;
      source?: Source;
      contentHtml?: string;
      search?: string | null;
      status: string;
      viewMode?: string | null;
      statusOptions?: Array<{label: string; value: string}>;
      sectionHandle?: string | null;
    }>(),
    {
      context: 'index',
      canHaveDrafts: false,
      criteria: Craft.defaultIndexCriteria,
      defaultSource: null,
      defaultSourcePath: null,
      page: null,
    }
  );

  const searchForm = useForm({
    search: props.search ?? '',
    status: props.status,
    viewMode: props.viewMode,
  });

  function handleSearch() {
    searchForm.submit(
      index({page: props.page, sectionHandle: props.sectionHandle})
    );
  }
</script>

<template>
  <IndexLayout>
    <template #interior-nav>
      <nav aria-labelledby="source-heading">
        <h2 id="source-heading" class="sr-only">
          {{ t('Sources') }}
        </h2>
        <ElementSources :sources="sources" :active-source="source?.key" />
      </nav>

      <div id="source-actions"></div>
    </template>

    <div id="elements" v-if="contentHtml">
      <div class="p-1">
        <form @submit="handleSearch" class="w-full">
          <div class="flex gap-2 items-center">
            <div>
              <CraftSelectRich
                v-model="searchForm.status"
                :options="statusOptions"
                :label="t('Status')"
                label-sr-only
              >
                <template #option="{option}">
                  <ElementStatus :label="option.label" :value="option.value" />
                </template>
              </CraftSelectRich>
            </div>

            <CraftInput
              class="flex-1"
              name="search"
              :label="t('Search term')"
              v-model="searchForm.search"
              label-sr-only
            >
              <craft-button
                type="button"
                slot="suffix"
                icon
                size="small"
                appearance="plain"
              >
                <craft-icon
                  name="filter"
                  :label="t('Filter results')"
                ></craft-icon>
              </craft-button>
            </CraftInput>

            <craft-button-group
              v-model="searchForm.viewMode"
              name="viewMode"
              @change="
                (event: CustomEvent) =>
                  (searchForm.viewMode = event.detail.value)
              "
            >
              <craft-button
                type="button"
                appearance="filled"
                icon="list"
                :aria-label="t('Display in a table')"
                value="table"
              ></craft-button>
              <craft-button
                type="button"
                appearance="filled"
                icon="custom-icons/element-cards"
                :aria-label="t('Display as cards')"
                value="cards"
              ></craft-button>
            </craft-button-group>

            <craft-action-menu>
              <craft-button
                type="button"
                slot="invoker"
                icon="sliders"
                appearance="filled"
              >
                {{ t('View') }}
              </craft-button>

              <div slot="content">Hey</div>
            </craft-action-menu>

            <div>
              <craft-button type="submit" :loading="searchForm.processing">{{
                t('Update')
              }}</craft-button>
            </div>
          </div>
        </form>
      </div>

      <DynamicHtmlRenderer :html="contentHtml" />
    </div>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
