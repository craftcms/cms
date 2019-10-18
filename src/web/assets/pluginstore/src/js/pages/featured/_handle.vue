<template>
    <div class="ps-container">
        <template v-if="!loading && featuredSection">
            <plugin-index
                    action="pluginStore/getPluginsByFeaturedSectionHandle"
                    :requestData="requestData"
                    :plugins="plugins"
            >
                <template v-slot:header>
                    <h1>{{featuredSection.title}}</h1>
                </template>
            </plugin-index>
        </template>
        <template v-else>
            <spinner></spinner>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'

    export default {
        components: {
            PluginIndex,
        },

        data() {
            return {
                loading: false,
                pluginsLoaded: false,
                sectionLoaded: false,
            }
        },

        computed: {
            ...mapState({
                featuredSection: state => state.pluginStore.featuredSection,
                plugins: state => state.pluginStore.plugins,
            }),

            requestData() {
                return {
                    featuredSectionHandle: this.$route.params.handle
                }
            }
        },

        mounted() {
            this.$store.commit('pluginStore/updatePlugins', [])
            this.loading = true

            const featuredSectionHandle = this.$route.params.handle

            // retrieve featured section
            this.$store.dispatch('pluginStore/getFeaturedSectionByHandle', featuredSectionHandle)
                .then(() => {
                    this.loading = false
                })
                .catch(() => {
                    this.loading = false
                })
        }
    }
</script>
