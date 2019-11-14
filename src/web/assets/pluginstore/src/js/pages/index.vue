<template>
    <div class="ps-container">
        <template v-if="!loading">
            <div v-for="(featuredSection, key) in featuredSections" :key="'featuredSection-' + key">
                <div class="flex items-baseline justify-between" :class="{'mt-8': key > 0}">
                    <h2>{{ featuredSection.title }}</h2>
                    <router-link class="right" :to="'/featured/'+featuredSection.slug">{{ "See all"|t('app') }}</router-link>
                </div>

                <plugin-grid :plugins="featuredSection.plugins" :auto-limit="true"></plugin-grid>
            </div>

            <template v-if="activeTrialPlugins.length > 0 || activeTrialsError">
                <h2>{{ "Active Trials"|t('app') }}</h2>

                <template v-if="activeTrialPlugins.length > 0">
                    <plugin-grid :plugins="activeTrialPlugins" :trialMode="true"></plugin-grid>
                </template>

                <template v-if="activeTrialsError">
                    <div class="mb-8">
                        <p class="error">{{activeTrialsError}}</p>
                    </div>
                </template>
            </template>
        </template>

        <template v-else>
            <spinner></spinner>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginGrid from '../components/PluginGrid'

    export default {
        components: {
            PluginGrid,
        },

        data() {
            return {
                activeTrialsError: null,
                activeTrialsLoaded: false,
                featuredSectionsLoaded: false,
                loading: false,
            }
        },

        computed: {
            ...mapState({
                activeTrialPlugins: state => state.cart.activeTrialPlugins,
                featuredSections: state => state.pluginStore.featuredSections,
            }),
        },

        mounted() {
            // reset variables
            this.$store.commit('cart/updateActiveTrialPlugins', [])
            this.$store.commit('pluginStore/updateFeaturedSections', [])
            this.activeTrialsLoaded = false
            this.featuredSectionsLoaded = false

            // start loading
            this.loading = true

            // load featured sections
            this.$store.dispatch('pluginStore/getFeaturedSections')
                .then(() => {
                    this.featuredSectionsLoaded = true
                    this.$emit('dataLoaded')
                })
                .catch(() => {
                    this.featuredSectionsLoaded = true
                    this.$emit('dataLoaded')
                })

            // load active trial plugins
            this.$store.dispatch('cart/getActiveTrialPlugins')
                .then(() => {
                    this.activeTrialsLoaded = true
                    this.$emit('dataLoaded')
                })
                .catch(() => {
                    this.activeTrialsError = this.$options.filters.t('Couldn’t load active trials.', 'app')
                    this.activeTrialsLoaded = true
                    this.$emit('dataLoaded')
                })

            // stop loading when all the loaded has finished loading
            this.$on('dataLoaded', () => {
                if (!this.featuredSectionsLoaded || !this.activeTrialsLoaded) {
                    return null
                }

                this.loading = false
            })
        }
    }
</script>
