<script setup lang="ts">
    import {t} from '@craftcms/ui';
    import CpLink from '@/common/components/CpLink.vue';
    import Empty from '@/common/components/Empty.vue';
    import {index as importConfigsIndex} from '@actions/Import/ImportConfigController';
    import {index as importRunsIndex} from '@actions/Import/ImportRunController';

    interface NavItem {
        label: string;
        iconName: string;
        url: string;
    }

    const props = defineProps<{
        canViewConfigs: boolean;
        canViewRuns: boolean;
    }>();

    const items: NavItem[] = [
        ...(props.canViewConfigs
            ? [
                  {
                      label: t('Configs'),
                      iconName: 'light/gear',
                      url: importConfigsIndex().url,
                  },
              ]
            : []),
        ...(props.canViewRuns
            ? [
                  {
                      label: t('Runs'),
                      iconName: 'light/upload',
                      url: importRunsIndex().url,
                  },
              ]
            : []),
    ];
</script>

<template>
    <div class="py-3">
        <Empty
            v-if="items.length === 0"
            :label="t('You don’t have access to view import configs or runs.')"
        />

        <nav v-else :aria-label="t('Import')">
            <ul class="import-grid">
                <li v-for="item in items" :key="item.label">
                    <CpLink :href="item.url" class="import-item" block>
                        <div class="import-content">
                            <craft-icon
                                :name="item.iconName"
                                style="font-size: calc(40rem / 16)"
                            ></craft-icon>
                            {{ item.label }}
                        </div>
                    </CpLink>
                </li>
            </ul>
        </nav>
    </div>
</template>

<style scoped lang="scss">
    .import-grid {
        display: grid;
        grid-template-columns: repeat(
            auto-fill,
            minmax(calc(120rem / 16), 1fr)
        );
        gap: var(--c-spacing-md);
    }

    .import-item {
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

    .import-item:hover {
        background-color: var(--c-color-info-fill-quiet);
        color: var(--c-color-info-on-quiet);
        border: 1px solid var(--c-color-info-border-quiet);
    }

    .import-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: var(--c-spacing-md);
    }
</style>
