<template>
    <div>
        <template v-if="$root.pluginStoreDataLoaded && !$root.pluginStoreDataError">
            <router-view></router-view>
        </template>
        <template v-else>
            <status-message :error="$root.pluginStoreDataError" :message="$root.statusMessage"></status-message>
        </template>

        <modal :show.sync="$root.showModal" :plugin-id="$root.pluginId"></modal>
    </div>
</template>

<script>
    import './sass/main.scss'

    import Modal from './js/components/modal/Modal'
    import StatusMessage from './js/components/StatusMessage'

    export default {

        components: {
            Modal,
            StatusMessage,
        }

    }
</script>