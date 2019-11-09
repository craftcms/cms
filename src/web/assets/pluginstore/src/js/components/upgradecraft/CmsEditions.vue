<template>
    <div>
        <template v-if="!loading">
            <div class="cms-editions">
                <cms-edition v-for="(edition, key) in cmsEditions" :edition="edition" :key="key"></cms-edition>
            </div>
        </template>
        <template v-else>
            <spinner></spinner>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import CmsEdition from './CmsEdition'

    export default {
        components: {
            CmsEdition,
        },

        data() {
            return {
                loading: false,
            }
        },

        computed: {
            ...mapState({
                cmsEditions: state => state.pluginStore.cmsEditions,
            }),
        },

        mounted() {
            if (!this.cmsEditions) {
                this.loading = true

                this.$store.dispatch('pluginStore/getCmsEditions')
                    .then(() => {
                        this.loading = false
                    })
                    .catch(() => {
                        this.loading = false
                    })
            }
        },

        beforeDestroy() {
            this.$store.dispatch('pluginStore/cancelRequests')
        }
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
