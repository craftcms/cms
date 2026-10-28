<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, onScopeDispose, shallowRef} from 'vue';
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
    <PluginIndex :filter="{developerId}">
      <template #header>
        <div v-if="developer" class="flex items-center gap-lg">
          <img
            v-if="developer.photoUrl"
            :src="developer.photoUrl"
            alt=""
            class="developer-photo"
          />
          <div class="grid gap-sm">
            <div v-if="developer.location">{{ developer.location }}</div>
            <ul v-if="badges.length" class="flex flex-wrap gap-md">
              <li
                v-for="badge in badges"
                :key="badge.kind"
                class="flex items-center gap-xs"
              >
                <PartnerBadge :kind="badge.kind" />
                {{ badge.label }}
              </li>
            </ul>
            <div class="flex flex-wrap gap-sm">
              <craft-button
                v-if="developer.developerUrl"
                size="small"
                :href="developer.developerUrl"
                target="_blank"
                icon="arrow-up-right-from-square"
                icon-position="suffix"
              >
                {{ t('Website') }}
                <span class="sr-only">{{ t('Opens in a new window') }}</span>
              </craft-button>
              <craft-button
                v-if="developer.partnerInfo?.profileUrl"
                size="small"
                :href="developer.partnerInfo.profileUrl"
                target="_blank"
                icon="arrow-up-right-from-square"
                icon-position="suffix"
              >
                {{ t('Partner Profile') }}
                <span class="sr-only">{{ t('Opens in a new window') }}</span>
              </craft-button>
            </div>
          </div>
        </div>
      </template>
    </PluginIndex>
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
