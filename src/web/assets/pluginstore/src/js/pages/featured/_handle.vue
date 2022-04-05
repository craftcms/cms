<template>
    <div class="ps-container">
        <template v-if="!loading">
            <plugin-index
                    action="pluginStore/getPluginsByFeaturedSectionHandle"
                    :requestData="requestData"
                    :plugins="plugins"
                    :disableSorting="true"
            >
                <template v-slot:header>
                    <template v-if="featuredSection">
                        <h1>{{featuredSection.title}}</h1>
                    </template>
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
                loading: true,
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
