<template>
    <div class="ps-wrapper has-sidebar">
        <template v-if="$root.pluginStoreDataLoaded && !$root.pluginStoreDataError">
            <sidebar></sidebar>

            <div class="ps-main">
                <router-view></router-view>
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