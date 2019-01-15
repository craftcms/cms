<template>
    <div class="plugin-editions mb-4">
        <plugin-edition v-for="(edition, key) in plugin.editions" :plugin="plugin" :edition="edition" :key="key"></plugin-edition>
    </div>
</template>

<script>
    import PluginEdition from './PluginEdition'

    export default {

        props: ['plugin'],

        components: {
            PluginEdition
        }

    }
</script>

<style lang="scss">
    .plugin-editions {
        @apply .py-6;

        .plugin-editions-edition {
            &:not(:last-child) {
                @apply .mb-6;
            }
        }
    }

    @media (min-width: 992px) {
        .plugin-editions {
            @apply .flex .-mx-4 .justify-center;

            .plugin-editions-edition {
                @apply .w-1/3 .mx-4;

                &:not(:last-child) {
                    @apply .mb-0;
                }
            }
        }
    }
</style>
