<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import {Link} from '@inertiajs/vue3';
  import {default as settingsIndex} from '@actions/Settings/SettingsIndexController';

  interface SettingItem {
    icon?: string;
    iconName?: string;
    label: string;
    url?: string;
    handle: string;
  }

  defineProps<{
    readOnly: boolean;
    settings: Record<string, Record<string, SettingItem>>;
  }>();
</script>

<template>
  <AppLayout :title="t('Settings')">
    <div class="py-3">
      <template v-if="readOnly">
        <CalloutReadOnly />
      </template>

      <div class="grid gap-6">
        <div v-for="(items, category, index) in settings" :key="category">
          <h2
            :id="`category-heading-${index}`"
            class="mb-2 text-lg leading-tight"
          >
            {{ category }}
          </h2>
          <nav :aria-labelledby="`category-heading-${index}`">
            <ul class="settings-grid">
              <template v-for="(item, handle) in items">
                <li>
                  <Link
                    :href="item.url || `${settingsIndex().url}/${handle}`"
                    class="settings-item"
                  >
                    <div class="settings-content">
                      <div class="settings-icon">
                        <craft-icon
                          v-if="item.iconName"
                          :name="item.iconName"
                          style="font-size: calc(40rem / 16)"
                        ></craft-icon>
                        <div
                          v-else-if="item.icon"
                          v-html="item.icon"
                          class="w-[40px] h-[40px] inline-block align-self-center"
                        ></div>
                      </div>
                      {{ item.label
                      }}<span class="sr-only"> - {{ t('Settings') }}</span>
                    </div>
                  </Link>
                </li>
              </template>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .settings-icon {
    display: grid;
    justify-content: center;
    align-items: center;
  }

  .settings-icon :deep(svg) {
    width: 100%;
    height: 100%;
  }

  .settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(calc(120rem / 16), 1fr));
    gap: var(--c-spacing-md);
  }

  .settings-item {
    display: grid;
    justify-content: center;
    background-color: var(--c-surface-overlay);
    color: var(--c-text-default);
    border: 1px solid var(--c-color-neutral-border-quiet);
    padding: calc(var(--c-spacing-md) * 1.5) var(--c-spacing-md)
      var(--c-spacing-md);
    aspect-ratio: 5/4;
    border-radius: var(--c-radius-md);
    text-align: center;
    text-decoration: none;
    cursor: pointer;
  }

  .settings-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: var(--c-spacing-md);
  }

  .settings-item:hover {
    background-color: var(--c-color-accent-fill-quiet);
    color: var(--c-color-accent-on-quiet);
    border: 1px solid var(--c-color-accent-border-quiet);
  }
</style>
