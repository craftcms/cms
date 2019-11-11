<template>
    <div>
        <div class="border-b border-solid border-grey-light py-2 flex justify-between">
            <slot name="header"></slot>

            <template v-if="!disableSorting">
                <plugin-index-sort :loading="loading" :orderBy.sync="orderBy" :direction.sync="direction" @change="onOrderByChange"></plugin-index-sort>
            </template>
        </div>

        <plugin-grid :plugins="plugins"></plugin-grid>

        <div v-if="error" class="my-4 text-red">{{error}}</div>

        <spinner v-if="loadingBottom || (disableSorting && loading)" class="my-4"></spinner>
    </div>
</template>

<script>
    import PluginGrid from './PluginGrid'
    import PluginIndexSort from './PluginIndexSort'

    export default {
        props: ['plugins', 'action', 'requestData', 'disableSorting'],

        components: {
            PluginGrid,
            PluginIndexSort,
        },

        data() {
            return {
                orderBy: 'activeInstalls',
                direction: 'desc',

                loading: false,
                loadingBottom: false,
                hasMore: false,
                page: 1,

                error: null,
            }
        },

        computed: {
            requestActionData() {
                return {
                    ...this.requestData,
                    page: this.page,
                    orderBy: this.orderBy,
                    direction: this.direction,
                }
            },
        },

        methods: {
            destroyPluginIndex() {
                this.error = null
                this.$root.$off('viewScroll', this.onScroll)
                this.$root.$off('windowScroll', this.onScroll)
                this.$root.$off('windowResize', this.onWindowResize)

                this.$store.dispatch('pluginStore/cancelRequests')
            },

            mountPluginIndex() {
                this.$store.commit('pluginStore/updatePlugins', [])

                this.requestPlugins(true, (response) => {
                    if (response.data.currentPage < response.data.total) {
                        this.$root.$on('viewScroll', this.onScroll)
                        this.$root.$on('windowScroll', this.onScroll)
                        this.$root.$on('windowResize', this.onWindowResize)
                    }
                })
            },

            onOrderByChange() {
                this.error = null

                this.requestPlugins(true)
            },

            onScroll() {
                this.$root.$off('viewScroll', this.onScroll)
                this.$root.$off('windowScroll', this.onScroll)

                if (this.loadingBottom === true && this.hasMore === true) {
                    return null
                }

                if (this.scrollDistFromBottom() < 300) {
                    this.requestPlugins(false, (response) => {
                        if (response.data.currentPage < response.data.total) {
                            this.$root.$on('viewScroll', this.onScroll)
                            this.$root.$on('windowScroll', this.onScroll)
                        }
                    })
                } else {
                    this.$root.$on('viewScroll', this.onScroll)
                    this.$root.$on('windowScroll', this.onScroll)
                }
            },

            onWindowResize() {
                if (!this.hasMore) {
                    return null
                }

                if (this.viewHasScrollbar()) {
                    return null
                }

                this.requestPlugins()
            },

            refreshPluginIndex() {
                this.$nextTick(() => {
                    this.destroyPluginIndex()
                    this.mountPluginIndex()
                })
            },

            requestPlugins(dontAppendData, onAfterSuccess) {
                if (this.loading) {
                    return null
                }

                if (this.loadingBottom) {
                    return null
                }

                if (!dontAppendData && !this.hasMore) {
                    return null
                }

                if (dontAppendData) {
                    this.page = 1
                    this.loading = true
                } else {
                    this.loadingBottom = true
                }

                this.$store.dispatch(this.action, {
                        ...this.requestActionData,
                        appendData: !dontAppendData,
                    })
                    .then((response) => {
                        if (response.data && response.data.error) {
                            throw response.data.error
                        }

                        this.loading = false
                        this.loadingBottom = false

                        if (response.data.currentPage < response.data.total) {
                            this.hasMore = true
                            this.page++

                            if (!this.viewHasScrollbar()) {
                                this.requestPlugins()
                            }
                        } else {
                            this.hasMore = false
                        }

                        if (typeof onAfterSuccess === 'function') {
                            onAfterSuccess(response)
                        }
                    })
                    .catch((thrown) => {
                        let errorMsg

                        if (typeof thrown === 'string') {
                            errorMsg = thrown
                        } else if(thrown.response.data.error) {
                            errorMsg = thrown.response.data.error
                        } else {
                            errorMsg = thrown.response.data.message
                        }

                        this.error = errorMsg
                        this.loading = false
                        this.loadingBottom = false
                    })
            },

            scrollContainer() {
                return this.scrollMode() === 'view' ? document.getElementById('content').getElementsByClassName('ps-main')[0] : document.documentElement
            },

            scrollDistFromBottom() {
                const $container = this.scrollContainer()
                const scrollTop = $container.scrollTop
                const scrollHeight = $container.scrollHeight

                let offsetHeight = window.outerHeight

                if (this.scrollMode() === 'view') {
                    offsetHeight = $container.offsetHeight
                }

                return scrollHeight - Math.max((scrollTop + offsetHeight), 0)
            },

            scrollMode() {
                if (window.innerWidth >= 975) {
                    return 'view'
                }

                return 'window'
            },

            viewHasScrollbar() {
                const $container = this.scrollContainer()

                if ($container.clientHeight < $container.scrollHeight) {
                    return true
                }

                return false
            },
        },

        mounted() {
            this.mountPluginIndex()
        },

        beforeDestroy() {
            this.destroyPluginIndex()
        }
    }
</script>
