<template>
    <div class="cms-editions">
        <cms-edition v-for="(edition, key) in editions" :edition="edition" :key="key"></cms-edition>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import CmsEdition from './CmsEdition'

    export default {
        components: {
            CmsEdition,
        },

        computed: {

            ...mapState({
                editions: state => state.craft.editions,
            }),

        },
    }
</script>

<style lang="scss">
    .cms-editions {
        @apply .py-6;

        .cms-editions-edition {
            &:not(:last-child) {
                @apply .mb-6;
            }
        }
    }

    @media (min-width: 992px) {
        .cms-editions {
            @apply .flex .-mx-4 .justify-center;

            .cms-editions-edition {
                @apply .w-1/3 .mx-4;

                &:not(:last-child) {
                    @apply .mb-0;
                }
            }
        }
    }
</style>
