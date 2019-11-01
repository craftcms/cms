<template>
    <div>
        <div class="border-b border-solid border-grey-light py-2 flex justify-between">
            <slot name="header"></slot>

            <template v-if="!disableSorting">
                <plugin-index-sort :loading="loading" :orderBy.sync="orderBy" :direction.sync="direction" @change="onOrderByChange"></plugin-index-sort>
            </template>
        </div>

        <plugin-grid :plugins="plugins"></plugin-grid>

        <spinner v-if="loadingBottom" class="my-4"></spinner>
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
            onOrderByChange() {
                this.loading = true
                this.page = 1

                this.$store.dispatch(this.action, this.requestActionData)
                    .then((response) => {
                        this.loading = false
                        if (response.data.currentPage < response.data.total) {
                            this.hasMore = true
                            this.page++
                            this.$root.$on('viewScroll', this.onScroll)
                            this.$root.$on('windowScroll', this.onScroll)
                        } else {
                            this.hasMore = false
                        }
                    })
                    .catch(() => {
                        this.loading = false
                    })
            },

            onScroll() {
                this.$root.$off('viewScroll', this.onScroll)
                this.$root.$off('windowScroll', this.onScroll)

                if (this.loadingBottom === true && this.hasMore === true) {
                    return null
                }

                if (this.scrollDistFromBottom() < 300) {
                    this.loadingBottom = true
                    this.$store.dispatch(this.action, {
                            ...this.requestActionData,
                            appendData: true,
                        })
                        .then(response => {
                            this.loadingBottom = false

                            if (response.data.currentPage < response.data.total) {
                                this.hasMore = true
                                this.page++
                                this.$root.$on('viewScroll', this.onScroll)
                                this.$root.$on('windowScroll', this.onScroll)
                            } else {
                                this.hasMore = false
                            }
                        })
                } else {
                    this.$root.$on('viewScroll', this.onScroll)
                    this.$root.$on('windowScroll', this.onScroll)
                }
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

            requestPlugins(dontAppendData) {
                if (this.loading) {
                    return null
                }

                if (this.loadingBottom) {
                    return null
                }

                if (!this.hasMore) {
                    return null
                }

                if (!dontAppendData && this.viewHasScrollbar()) {
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
                    })
                    .catch(() => {
                        this.loading = false
                        this.loadingBottom = false
                    })
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

            viewHasScrollbar() {
                const $container = this.scrollContainer()

                if ($container.clientHeight < $container.scrollHeight) {
                    return true
                }

                return false
            },

            mountPluginIndex() {
                this.$store.commit('pluginStore/updatePlugins', [])
                this.loading = true
                this.page = 1

                this.$store.dispatch(this.action, this.requestActionData)
                    .then((response) => {
                        this.loading = false
                        if (response.data.currentPage < response.data.total) {
                            this.hasMore = true
                            this.page++

                            if (!this.viewHasScrollbar()) {
                                this.requestPlugins()
                            }

                            this.$root.$on('viewScroll', this.onScroll)
                            this.$root.$on('windowScroll', this.onScroll)
                            this.$root.$on('windowResize', this.onWindowResize)
                        } else {
                            this.hasMore = false
                        }
                    })
                    .catch(() => {
                        this.loading = false
                    })
            },

            destroyPluginIndex() {
                this.$root.$off('viewScroll', this.onScroll)
                this.$root.$off('windowScroll', this.onScroll)
                this.$root.$off('windowResize', this.onWindowResize)

                this.$store.dispatch('pluginStore/cancelRequests')
            },

            refreshPluginIndex() {
                this.$nextTick(() => {
                    this.destroyPluginIndex()
                    this.mountPluginIndex()
                })
            }
        },

        mounted() {
            this.mountPluginIndex()
        },

        beforeDestroy() {
            this.destroyPluginIndex()
        }
    }
</script>
