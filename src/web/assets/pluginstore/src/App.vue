<template>
    <div class="ps-wrapper">
        <screenshot-modal v-if="showingScreenshotModal"></screenshot-modal>

        <template v-if="$root.pluginStoreDataLoaded && !$root.pluginStoreDataError">
            <sidebar></sidebar>

            <div class="ps-main">
                <router-view :key="$route.fullPath"></router-view>
            </div>
        </template>
        <template v-else>
            <status-message :error="$root.pluginStoreDataError" :message="$root.statusMessage"></status-message>
        </template>

        <modal :show.sync="$root.showModal" :plugin-id="$root.pluginId"></modal>
    </div>
</template>

<style lang="scss">
    @import './sass/main.scss';
</style>

<script>
    import {mapState} from 'vuex'
    import Sidebar from './js/components/Sidebar'
    import Modal from './js/components/modal/Modal'
    import StatusMessage from './js/components/StatusMessage'
    import ScreenshotModal from './js/components/ScreenshotModal'

    export default {

        components: {
            Sidebar,
            Modal,
            StatusMessage,
            ScreenshotModal,
        },

        computed: {

            ...mapState({
                showingScreenshotModal: state => state.app.showingScreenshotModal,
            }),

        }

    }
</script>