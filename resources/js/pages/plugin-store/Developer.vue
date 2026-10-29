<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, onScopeDispose, shallowRef} from 'vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import PartnerBadge from '@/modules/plugin-store/components/PartnerBadge.vue';
  import PluginIndex from '@/modules/plugin-store/components/PluginIndex.vue';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {getDeveloper} from '@/modules/plugin-store/api';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';
  import type {Developer} from '@/modules/plugin-store/types';

  const props = defineProps<{developerId: number}>();

  const developer = shallowRef<Developer | null>(null);
  const controller = new AbortController();
  onScopeDispose(() => controller.abort());

  getDeveloper(props.developerId, controller.signal)
    .then((response) => (developer.value = response))
    .catch(() => {});

  usePluginStoreLayout({title: () => developer.value?.developerName});

  const badges = computed(() => {
    const info = developer.value?.partnerInfo;

    return [
      info?.isCraftVerified && {kind: 'craft', label: 'Craft Verified'},
      info?.isCommerceVerified && {
        kind: 'commerce',
        label: 'Craft Commerce Verified',
      },
      info?.isEnterpriseVerified && {
        kind: 'enterprise',
        label: 'Enterprise Verified',
      },
    ].filter(Boolean) as Array<{
      kind: 'craft' | 'commerce' | 'enterprise';
      label: string;
    }>;
  });
</script>

<template>
  <StoreContainer>
    <LayoutSlot v-if="developer" name="title">
      <div class="flex items-center gap-lg">
        <img
          v-if="developer.photoUrl"
          :src="developer.photoUrl"
          alt=""
          class="developer-photo"
        />
        <div class="grid gap-xs">
          <h1 class="text-xl">{{ developer.developerName }}</h1>
          <div
            v-if="developer.location || badges.length"
            class="flex items-center gap-md whitespace-nowrap"
          >
            <span v-if="developer.location">{{ developer.location }}</span>
            <span
              v-for="badge in badges"
              :key="badge.kind"
              class="flex items-center gap-xs"
            >
              <PartnerBadge :kind="badge.kind" />
              {{ badge.label }}
            </span>
          </div>
          <div
            v-if="developer.developerUrl || developer.partnerInfo?.profileUrl"
            class="flex flex-wrap items-center gap-md"
          >
            <a
              v-if="developer.developerUrl"
              :href="developer.developerUrl"
              target="_blank"
              rel="noopener"
              class="flex items-center gap-xs"
            >
              {{ t('Website') }}
              <craft-icon name="arrow-up-right-from-square"></craft-icon>
              <span class="sr-only">{{ t('Opens in a new window') }}</span>
            </a>
            <a
              v-if="developer.partnerInfo?.profileUrl"
              :href="developer.partnerInfo.profileUrl"
              target="_blank"
              rel="noopener"
              class="flex items-center gap-xs"
            >
              {{ t('Partner Profile') }}
              <craft-icon name="arrow-up-right-from-square"></craft-icon>
              <span class="sr-only">{{ t('Opens in a new window') }}</span>
            </a>
          </div>
        </div>
      </div>
    </LayoutSlot>
    <PluginIndex :filter="{developerId}" />
  </StoreContainer>
</template>

<style scoped>
  .developer-photo {
    inline-size: 6rem;
    block-size: 6rem;
    border-radius: var(--c-radius-full);
    background: var(--c-color-neutral-fill-quiet);
  }
</style>
