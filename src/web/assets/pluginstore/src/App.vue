<template>
    <div class="ps-wrapper">
        <transition name="fade">
            <screenshot-modal v-if="showingScreenshotModal"></screenshot-modal>
        </transition>

        <template v-if="$root.pluginStoreDataLoaded">
            <sidebar></sidebar>

            <div class="ps-main" @scroll.passive="onViewScroll">
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
        },

        methods: {
            onViewScroll($event) {
                this.$root.$emit('viewScroll', $event)
            }
        },

        mounted() {
            window.addEventListener('resize', function($event) {
                this.$root.$emit('windowResize', $event)
            }.bind(this))

            window.addEventListener('scroll', function($event) {
                this.$root.$emit('windowScroll', $event)
            }.bind(this))
        }
    }
</script>

<style style="scss">
    .fade-enter-active, .fade-leave-active {
        transition: opacity .3s;
    }
    .fade-enter, .fade-leave-to {
        opacity: 0;
    }
</style>