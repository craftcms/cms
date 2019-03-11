<template>
    <div class="ps-container">
        <div class="developer-card tw-flex border-b border-solid border-grey-light pb-6 items-center">
            <div class="avatar inline-block overflow-hidden rounded-full bg-grey mr-6 no-line-height">
                <template v-if="!loading && developer">
                    <img :src="developer.photoUrl" width="120" height="120" />
                </template>
            </div>

            <div class="flex-1">
                <template v-if="loading || !developer">
                    <spinner class="mt-8"></spinner>
                </template>
                <template v-else>
                    <h1>{{developer.developerName}}</h1>

                    <ul>
                        <li>{{ developer.location }}</li>
                    </ul>

                    <ul>
                        <li class="mr-4 inline-block"><btn :href="developer.developerUrl" block>{{ "Website"|t('app') }}</btn></li>
                    </ul>
                </template>
            </div>
        </div>

        <plugin-index :plugins="plugins"></plugin-index>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'

    export default {
        data() {
            return {
                plugins: [],
                loading: false,
            }
        },

        components: {
            PluginIndex,
        },

        computed: {
            ...mapState({
                developer: state => state.pluginStore.developer,
            }),
        },

        mounted() {
            const developerId = this.$route.params.id
            this.loading = true
            this.plugins = this.$store.getters['pluginStore/getPluginsByDeveloperId'](developerId)

            this.$store.dispatch('pluginStore/getDeveloper', developerId)
                .then(() => {
                    this.$root.loading = false
                    this.loading = false
                })
                .catch(() => {
                    this.$root.loading = false
                    this.loading = false
                })
        },
    }
</script>

<style lang="scss" scoped>
    .developer-card {
        .avatar {
            width: 120px;
            height: 120px;
        }

        h1 {
            border-bottom: 0;
        }
    }
</style>
