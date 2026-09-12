<script setup lang="ts">
    import {computed, onBeforeUnmount, onMounted, ref} from 'vue';
    import {t} from '@craftcms/ui';
    import {init} from '@craftcms/graphiql';
    import AppLayout from '@/common/layouts/AppLayout.vue';
    import Select from '@/common/form/Select.vue';
    import type {BaseOption} from '@/common/types';

    defineOptions({layout: []});

    const props = defineProps<{
        endpoint: string;
        exploreUrl: string;
        schemaOptions: Array<BaseOption>;
        selectedSchema: {
            name: string;
            schema: string;
        };
    }>();

    const selectedSchemaUid = ref(props.selectedSchema.schema);
    const graphiql = ref<HTMLElement | null>(null);
    let graphiqlRoot: {unmount(): void} | null = null;

    const selectedSchemaJson = computed(() =>
        JSON.stringify(props.selectedSchema)
    );

    onMounted(() => {
        if (!graphiql.value) {
            return;
        }

        graphiqlRoot = init(graphiql.value);
    });

    onBeforeUnmount(() => {
        graphiqlRoot?.unmount();
    });
</script>

<template>
    <AppLayout>
        <template #main>
            <main id="main" tabindex="-1" class="cp-graphiql">
                <div class="cp-graphiql__header">
                    <h1>{{ t('Explore the GraphQL API') }}</h1>
                    <form
                        method="get"
                        :action="exploreUrl"
                        class="schema-selector"
                    >
                        <Select
                            :label="t('Select Schema')"
                            id="schemaUid"
                            name="schemaUid"
                            v-model="selectedSchemaUid"
                            :options="schemaOptions"
                        />

                        <craft-button type="submit" variant="accent">
                            {{ t('Go') }}
                        </craft-button>
                    </form>
                </div>

                <div
                    ref="graphiql"
                    dir="ltr"
                    class="graphiql-editor"
                    :data-endpoint="endpoint"
                    :data-selected-schema="selectedSchemaJson"
                >
                    <craft-spinner></craft-spinner>
                </div>
            </main>
        </template>
    </AppLayout>
</template>

<style scoped lang="css">
    .cp-graphiql {
        display: grid;
        grid-template-rows: auto minmax(0, 1fr);
        height: 100dvh;
        min-height: 0;
        overflow: hidden;
    }

    .cp-graphiql__header {
        display: flex;
        gap: var(--c-spacing-lg);
        align-items: center;
        justify-content: space-between;
        padding-block: var(--c-spacing-sm);
        padding-inline: var(--c-spacing-lg);
        border-block-end: var(--c-border-width, 1px) solid
            var(--c-color-neutral-border-quiet);
    }

    .cp-graphiql__header h1 {
        margin: 0;
        font-size: var(--c-text-xl);
    }

    .schema-selector {
        display: flex;
        gap: var(--c-spacing-sm);
        align-items: end;
    }

    .graphiql-editor {
        min-height: 0;
        overflow: hidden;
        color: var(--text-color);
        font-size: 14px;
        line-height: 20px;
    }

    .graphiql-editor:has(craft-spinner) {
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
