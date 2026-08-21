<script setup lang="ts">
    import {computed, ref, watch} from 'vue';
    import {t} from '@craftcms/ui';
    import ProjectConfigController from '@actions/Utilities/ProjectConfigController';
    import {useFetch} from '@/common/composables/useFetch';

    const props = defineProps<{
        invert: boolean;
    }>();

    // const {isLoading, displayedLines, hasMoreLines, fetchDiff, showAll, cancel} =
    //   useProjectConfigDiff(props.invert);

    const {data, isLoading} = useFetch(ProjectConfigController.diff().url, {
        params: {invert: props.invert},
    });
    const diffLines = ref<string[]>([]);
    const showAllLines = ref(false);
    const maxInitialLines = 20;

    watch(data, (newValue) => {
        diffLines.value = newValue.split(/\n/);
    });

    function showAll(): void {
        showAllLines.value = true;
    }

    const displayedLines = computed(() => {
        if (showAllLines.value || diffLines.value.length <= maxInitialLines) {
            return diffLines.value;
        }
        return diffLines.value.slice(0, maxInitialLines);
    });

    const hasMoreLines = computed(() => {
        return diffLines.value.length > maxInitialLines && !showAllLines.value;
    });
</script>

<template>
    <!-- craft-pane makes its own scroll container the tab stop -->
    <craft-pane
        variant="code"
        padding="0"
        :class="{loading: isLoading}"
        :aria-label="t('Project config changes')"
    >
        <!-- Loading state -->
        <div v-if="isLoading" class="diff-loading">
            <craft-spinner :visible="true" class="spinner"></craft-spinner>
        </div>

        <!-- Diff content -->
        <template v-else>
            <pre
                class="py-2"
            ><template v-for="(line, index) in displayedLines" :key="index"
          ><code
            :class="{
              'diff-line': true,
              'diff-line--add': line.startsWith('+'),
              'diff-line--remove': line.startsWith('-'),
              'diff-line--info': line.startsWith('@@'),
            }"
            >{{ line }}</code
          ></template></pre>

            <!-- Show all button -->
            <div v-if="hasMoreLines" class="diff-show-all">
                <craft-button
                    type="button"
                    variant="hairline"
                    size="lg"
                    @click="showAll"
                >
                    {{ t('Show all changes') }}
                </craft-button>
            </div>
        </template>
    </craft-pane>
</template>

<style scoped lang="scss">
    .diff-loading {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;

        .spinner {
            --size: 2rem;
        }
    }

    .diff-content {
        padding-block: var(--c-spacing-md);
        font-family: var(--c-font-mono);
        background: transparent;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .diff-line {
        display: block;
        padding: 0.125em 0.5em;
    }

    .diff-line--add {
        background-color: var(--c-color-success-fill-normal);
        color: var(--c-color-success-on-normal);
    }

    .diff-line--remove {
        background-color: var(--c-color-danger-fill-normal);
        color: var(--c-color-danger-on-normal);
    }

    .diff-line--info {
        background-color: var(--c-color-info-fill-quiet);
        color: var(--c-color-info-on-quiet);
    }

    .diff-show-all {
        padding: var(--c-spacing-md);
        text-align: center;
        border-top: 1px solid var(--c-color-neutral-border-quiet);
    }
</style>
