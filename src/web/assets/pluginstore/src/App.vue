<template>
    <div>
        <template v-if="$root.pluginStoreDataLoaded && !$root.pluginStoreDataError">
            <div class="ps-grid-wrapper has-sidebar">
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
    import Sidebar from './js/components/Sidebar'
    import Modal from './js/components/modal/Modal'
    import StatusMessage from './js/components/StatusMessage'

    export default {

        components: {
            Sidebar,
            Modal,
            StatusMessage,
        },

    }
</script>