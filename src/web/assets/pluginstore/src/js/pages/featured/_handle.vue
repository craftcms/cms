<template>
    <div class="ps-container">
        <template v-if="!loading && featuredSection">
            <h1>{{featuredSection.title}}</h1>
            <plugin-grid :plugins="plugins"></plugin-grid>
        </template>
        <template v-else>
            <spinner></spinner>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginGrid from '../../components/PluginGrid'

    export default {
        components: {
            PluginGrid,
        },

        data() {
            return {
                loading: false,
                sectionLoaded: false,
                pluginsLoaded: false,
            }
        },

        computed: {
            ...mapState({
                featuredSection: state => state.pluginStore.featuredSection,
                plugins: state => state.pluginStore.plugins,
            }),
        },

        mounted() {
            this.$store.commit('pluginStore/updatePlugins', [])
            this.loading = true

            const featuredSectionHandle = this.$route.params.handle

            // retrieve featured section
            this.$store.dispatch('pluginStore/getFeaturedSectionByHandle', featuredSectionHandle)
                .then(() => {
                    this.sectionLoaded = true
                    this.$emit('dataLoaded')
                })
                .catch(() => {
                    this.sectionLoaded = true
                    this.$emit('dataLoaded')
                })

            // retrieve featured section’s plugins
            this.pluginsLoaded = true
            this.$store.dispatch('pluginStore/getPluginsByFeaturedSectionHandle', {
                featuredSectionHandle
            })
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
                if (!this.sectionLoaded || !this.pluginsLoaded) {
                    return null
                }

                this.loading = false
            })
        }
    }
</script>
