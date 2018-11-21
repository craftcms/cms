<template>
    <div>
        <template v-if="$root.pluginStoreDataLoaded && !$root.pluginStoreDataError">
            <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false" :plugins="plugins"></plugin-search>

            <category-selector></category-selector>

            <div v-if="!showingSearchResults" class="ps-grid-wrapper has-sidebar">
                <sidebar></sidebar>

                <div class="ps-grid-main">
                    <router-view></router-view>
                </div>
            </div>
        </template>
        <template v-else>
            <status-message :error="$root.pluginStoreDataError" :message="$root.statusMessage"></status-message>
        </template>

        <modal :show.sync="$root.showModal" :plugin-id="$root.pluginId"></modal>
    </div>
</template>

<script>
    import './sass/main.scss'
    import {mapState} from 'vuex'
    import CategorySelector from './js/components/CategorySelector'
    import Sidebar from './js/components/Sidebar'
    import PluginSearch from './js/components/PluginSearch'
    import Modal from './js/components/modal/Modal'
    import StatusMessage from './js/components/StatusMessage'

    export default {

        components: {
            CategorySelector,
            Sidebar,
            PluginSearch,
            Modal,
            StatusMessage,
        },

        data() {
            return {
                showingSearchResults: false,
            }
        },


        computed: {

            ...mapState({
                categories: state => state.pluginStore.categories,
                plugins: state => state.pluginStore.plugins,
            }),
        }
    }
</script>