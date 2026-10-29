<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, onScopeDispose, shallowRef} from 'vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import PartnerBadge from '@/modules/plugin-store/components/PartnerBadge.vue';
  import PluginBand from '@/modules/plugin-store/components/PluginBand.vue';
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
      <section class="developer-hero" :aria-label="developer.developerName">
        <PluginBand :icon-url="developer.photoUrl" />
        <div class="developer-hero__body">
          <img
            v-if="developer.photoUrl"
            :src="developer.photoUrl"
            alt=""
            class="developer-hero__photo"
          />
          <div class="developer-hero__info">
            <h1 class="text-2xl">{{ developer.developerName }}</h1>
            <span v-if="developer.location" class="developer-hero__location">
              {{ developer.location }}
            </span>
            <div
              v-if="developer.developerUrl || developer.partnerInfo?.profileUrl"
              class="developer-hero__links"
            >
              <a
                v-if="developer.developerUrl"
                :href="developer.developerUrl"
                target="_blank"
                rel="noopener"
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
              >
                {{ t('Partner Profile') }}
                <craft-icon name="arrow-up-right-from-square"></craft-icon>
                <span class="sr-only">{{ t('Opens in a new window') }}</span>
              </a>
            </div>
            <ul v-if="badges.length" class="developer-hero__badges">
              <li v-for="badge in badges" :key="badge.kind">
                <PartnerBadge :kind="badge.kind" />
                {{ badge.label }}
              </li>
            </ul>
          </div>
        </div>
      </section>
    </LayoutSlot>
    <PluginIndex :filter="{developerId}" />
  </StoreContainer>
</template>

<style scoped>
  /* Headed like a plugin page: a band spanning the container, with the photo over it. */
  .developer-hero {
    flex: 0 0 calc(100% + var(--cp-container-padding) * 2);
    margin-block: calc(var(--c-spacing-lg) * -1) var(--c-spacing-sm);
    margin-inline: calc(var(--cp-container-padding) * -1);
  }

  .developer-hero__body {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-md) var(--c-spacing-xl);
    align-items: flex-start;
    padding-block-start: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
  }

  .developer-hero__photo {
    position: relative;
    inline-size: 6rem;
    block-size: 6rem;
    margin-block-start: calc((var(--c-spacing-lg) + 1rem) * -1);
    border-radius: var(--c-radius-full);
    background: var(--c-color-neutral-fill-quiet);
    box-shadow: 0 0 0 4px var(--c-surface-default);
  }

  .developer-hero__info {
    display: flex;
    flex: 1 1 20rem;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    min-inline-size: 0;
  }

  .developer-hero__location {
    color: var(--c-text-quiet);
  }

  .developer-hero__badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    margin-block: var(--c-spacing-md) 0;
    margin-inline: 0;
    padding: 0;
    list-style: none;
  }

  .developer-hero__badges li {
    display: inline-flex;
    gap: var(--c-spacing-xs);
    align-items: center;
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-xs) var(--c-spacing-md);
    border-radius: var(--c-radius-full);
    background: var(--c-color-accent-fill-quiet);
    color: var(--c-color-accent-on-quiet);
    font-size: var(--c-text-sm);
    font-weight: 600;
    white-space: nowrap;
  }

  .developer-hero__links {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-lg);
  }

  .developer-hero__links a {
    display: inline-flex;
    gap: var(--c-spacing-xs);
    align-items: center;
  }
</style>
