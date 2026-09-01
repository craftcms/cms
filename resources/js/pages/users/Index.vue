<script setup lang="ts">
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import CpLink from '@/common/components/CpLink.vue';
  import ElementIndexPage from '@/modules/elements/components/ElementIndexPage.vue';
  import {
    appendIndexQuery,
    type ElementIndexRoute,
  } from '@/modules/elements/composables/useElementIndexVisits';
  import type {Source} from '@/modules/elements/types/sources';
  import {index} from '@routes/cp/users';
  import {create} from '@actions/Users/UsersController';

  type UserIndexPage = Omit<
    CraftCms.Cms.Http.ViewModels.UserIndexViewModel,
    'sources'
  > & {sources: Source[]};

  const page = usePage<UserIndexPage>();

  // Every user source publishes a URL slug (`all`, `admins`, `credentialed`,
  // `inactive`, and each group's handle), keyed here by source key so switching
  // sources moves the slug into the path — `users/admins` rather than a
  // trailing `?source=`, matching the URLs the legacy index produced.
  const sourceSlugs = computed(() => {
    const slugs = new Map<string, string>();

    const collect = (sources: Source[]) => {
      for (const source of sources) {
        if (source.type === 'heading') {
          collect(source.children ?? []);
        } else if (source.data?.slug) {
          slugs.set(source.key, String(source.data.slug));
        }
      }
    };

    collect(page.props.sources ?? []);

    return slugs;
  });

  // Keep the current source's slug in the URL so index reloads (sort, filter,
  // pagination) stay on the same source. A source with no slug — a custom
  // source added through "Customize sources" — has no path of its own, so it
  // keeps riding along as `?source=` on the bare `users` URL.
  const route: ElementIndexRoute = {
    url: (query = {}) => {
      const {source, ...rest} = query;
      const slug =
        source != null
          ? sourceSlugs.value.get(String(source))
          : (page.props.slug ?? undefined);

      if (source != null && slug === undefined) {
        rest.source = source;
      }

      return appendIndexQuery(index.url({slug}), rest);
    },
  };
</script>

<template>
  <ElementIndexPage :route="route">
    <template #actions>
      <CpLink
        v-if="page.props.canRegisterUsers"
        :inertia="false"
        :href="create().url"
        class="btn submit add icon"
        icon="plus"
        appearance="button"
        variant="accent"
        >{{ page.props.newUserLabel }}</CpLink
      >
    </template>
  </ElementIndexPage>
</template>

<style scoped lang="scss"></style>
