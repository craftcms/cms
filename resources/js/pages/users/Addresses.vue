<script setup lang="ts">
  import {computed} from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {savedNestedOwner} from '@/modules/elements/nested-owner';
  import NestedElements from '@/modules/forms/nested-elements/NestedElements.vue';
  import type {NestedElementsProps} from '@/modules/forms/nested-elements/nested-elements';
  import UserScreen from '@/modules/user/components/UserScreen.vue';

  defineOptions({
    inheritAttrs: false,
  });

  type UserAddressesPageProps =
    CraftCms.Cms.Http.ViewModels.UserAddressesViewModel;

  // Read props through the page object (not a captured `page.props`
  // reference) so partial reloads — which replace `page.props` wholesale —
  // stay reactive.
  const page = usePage<UserAddressesPageProps>();
  const props = computed(() => page.props);
  const addresses = computed(
    () => page.props.addresses as unknown as NestedElementsProps
  );

  // Users are always saved and never drafted, so address changes apply to the
  // user directly; the manager re-renders from the reloaded props.
  const owner = savedNestedOwner(
    page.props.userId,
    () =>
      new Promise((resolve) => {
        router.reload({
          only: ['addresses', 'showIndex'],
          onFinish: () => resolve(),
        });
      })
  );
</script>

<template>
  <UserScreen>
    <div class="grid gap-3">
      <h2 v-if="!props.showIndex" class="text-lg m-0!">
        {{ t('Addresses') }}
      </h2>

      <NestedElements
        :path="['addresses']"
        :nested="addresses"
        :editable="props.editable"
        :owner="owner"
      />
    </div>
  </UserScreen>
</template>
