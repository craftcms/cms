<template>
    <div class="ps-container">
        <template v-if="loading || !developer">
            <spinner class="mt-8"></spinner>
        </template>
        <template v-else>
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

            <plugin-index :plugins="plugins"></plugin-index>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'

    export default {
        data() {
            return {
                loading: false,
                developerLoaded: false,
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
        },

        mounted() {
            const developerId = this.$route.params.id

            // start loading
            this.loading = true

            // load developer details
            this.$store.dispatch('pluginStore/getDeveloper', developerId)
                .then(() => {
                    this.developerLoaded = true
                    this.$emit('dataLoaded')
                })
                .catch(() => {
                    this.developerLoaded = true
                    this.$emit('dataLoaded')
                })

            // load developer plugins
            this.$store.dispatch('pluginStore/getPluginsByDeveloperId', {developerId})
                .then(() => {
                    this.pluginsLoaded = true
                    this.$emit('dataLoaded')
                })
                .catch(() => {
                    this.pluginsLoaded = true
                    this.$emit('dataLoaded')
                })

            // stop loading when all the loaded has finished loading
            this.$on('dataLoaded', () => {
                if (!this.developerLoaded || !this.pluginsLoaded) {
                    return null
                }

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
