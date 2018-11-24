<template>
    <div class="ps-container">
        <div class="developer-card tw-flex tw-border-b tw-border-solid tw-border-grey-light tw-pb-6 tw-items-center">
            <div class="avatar tw-inline-block tw-overflow-hidden tw-rounded-full tw-bg-grey tw-mr-6 no-line-height">
                <template v-if="!loading && developer">
                    <img :src="developer.photoUrl" width="120" height="120" />
                </template>
            </div>

            <div class="tw-flex-1">
                <template v-if="loading || !developer">
                    <div class="spinner mt-8"></div>
                </template>
                <template v-else>
                    <h1>{{developer.developerName}}</h1>

                    <ul>
                        <li>{{ developer.location }}</li>
                    </ul>

                    <ul>
                        <li class="tw-mr-4 tw-inline-block"><a class="btn block" :href="developer.developerUrl">{{ "Website"|t('app') }}</a></li>
                        <li class="tw-mr-4 tw-inline-block"><a class="btn block" :href="developer.developerUrl">{{ "Contact"|t('app') }}</a></li>
                    </ul>
                </template>
            </div>
        </div>

        <plugin-index :plugins="plugins" columns="3"></plugin-index>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginIndex from '../components/PluginIndex'

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
            let developerId = this.$route.params.id

            this.loading = true

            this.plugins = this.$store.getters['pluginStore/getPluginsByDeveloperId'](developerId)

            this.$store.dispatch('pluginStore/getDeveloper', developerId)
                .then(developer => {
                    this.$root.loading = false
                    this.loading = false
                })
                .catch(response => {
                    this.$root.loading = false
                    this.loading = false
                })
        },

    }
</script>

<style lang="scss">
    .developer-card {
        .avatar {
            width: 120px;
            height: 120px;
        }
        
        h1 {
            @apply .tw-border-b-0;
        }
    }
</style>
