<script setup lang="ts">
  import {usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import Pane from '@/common/components/Pane.vue';

  defineOptions({
    inheritAttrs: false,
  });

  type UserAddressesPageProps =
    CraftCms.Cms.Http.ViewModels.UserAddressesViewModel & {
      details?: string | null;
    };

  const props = usePage<UserAddressesPageProps>().props;
</script>

<template>
  <Pane appearance="raised">
    <div class="grid gap-3">
      <h2 v-if="!props.showIndex" class="text-lg m-0!">{{ t('Addresses') }}</h2>
      <HtmlFragmentRenderer :fragment="props.contentFragment" />
    </div>
  </Pane>

  <LayoutSlot v-if="props.details" name="details">
    <div v-html="props.details"></div>
  </LayoutSlot>
</template>
