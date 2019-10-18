<template>
    <div class="ps-container">
        <template v-if="loading || !developer">
            <spinner class="mt-8"></spinner>
        </template>
        <template v-else>
            <plugin-index
                    action="pluginStore/getPluginsByDeveloperId"
                    :requestData="requestData"
                    :plugins="plugins"
            >
                <template v-slot:header>
                    <div class="developer-card tw-flex border-b border-solid border-grey-light pb-6 items-center">
                        <div class="avatar inline-block overflow-hidden rounded-full bg-grey mr-6 no-line-height">
                            <img :src="developer.photoUrl" width="120" height="120" />
                        </div>

                        <div class="flex-1">
                            <h1>{{developer.developerName}}</h1>

                            <ul>
                                <li>{{ developer.location }}</li>
                            </ul>

                            <ul>
                                <li class="mr-4 inline-block"><btn :href="developer.developerUrl" block>{{ "Website"|t('app') }}</btn></li>
                            </ul>
                        </div>
                    </div>
                </template>
            </plugin-index>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'

    export default {
        data() {
            return {
                developerLoaded: false,
                loading: false,
                pluginsLoaded: false,
            }
        },

        components: {
            PluginIndex,
        },

        computed: {
            ...mapState({
                developer: state => state.pluginStore.developer,
                plugins: state => state.pluginStore.plugins,
            }),

            requestData() {
                return {
                    developerId: this.$route.params.id,
                }
            },
        },

        mounted() {
            const developerId = this.$route.params.id

            // start loading
            this.loading = true

            // load developer details
            this.$store.dispatch('pluginStore/getDeveloper', developerId)
                .then(() => {
                    this.loading = false
                })
                .catch(() => {
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
