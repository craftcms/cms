<template>
    <div>
        <div class="border-b border-solid border-grey-light py-2 flex justify-between">
            <slot name="header"></slot>

            <template v-if="!disableSorting">
                <plugin-index-sort :loading="(loading || forceLoading)" :orderBy.sync="orderBy" :direction.sync="direction" @change="onOrderByChange"></plugin-index-sort>
            </template>
        </div>

        <plugin-grid :plugins="plugins"></plugin-grid>

        <spinner v-if="loadingMore"></spinner>
    </div>
</template>

<script>
    import PluginGrid from './PluginGrid'
    import PluginIndexSort from './PluginIndexSort'

    export default {
        props: ['plugins', 'action', 'requestData', 'disableSorting', 'forceLoading'],

        components: {
            PluginGrid,
            PluginIndexSort,
        },

        data() {
            return {
                orderBy: 'activeInstalls',
                direction: 'desc',

                loading: false,
                loadingMore: false,
                hasMore: false,
                offset: 0,
            }
        },

        computed: {
            requestActionData() {
                return {
                    ...this.requestData,
                    limit: this.limit,
                    offset: this.offset,
                    orderBy: this.orderBy,
                    direction: this.direction,
                }
            },

            limit() {
                return this.$store.state.pluginStore.pluginIndexLimit
            }
        },

        methods: {
            onOrderByChange() {
                this.loading = true
                this.offset = 0

                this.$store.dispatch(this.action, this.requestActionData)
                    .then((response) => {
                        this.loading = false
                        if (response.data.length >= this.limit) {
                            this.hasMore = true
                            this.offset = this.limit;
                            this.$root.$on('viewScroll', this.onViewScroll)
                        } else {
                            this.hasMore = false
                        }
                    })
                    .catch(() => {
                        this.loading = false
                    })
            },

            onViewScroll($event) {
                this.$root.$off('viewScroll')

                if (this.loadingMore === true && this.hasMore === true) {
                    return null
                }

                const scrollTop = $event.target.scrollTop
                const scrollHeight = $event.target.scrollHeight
                const offsetHeight = $event.target.offsetHeight
                const distFromBottom = scrollHeight - Math.max((scrollTop + offsetHeight), 0)

                if (distFromBottom < 300) {
                    this.loadingMore = true
                    this.$store.dispatch(this.action, {
                            ...this.requestActionData,
                            appendData: true,
                        })
                        .then(response => {
                            this.loadingMore = false

                            if (response.data.length === this.limit) {
                                this.offset += this.limit;
                                this.$root.$on('viewScroll', this.onViewScroll)
                            } else {
                                this.hasMore = false
                            }
                        })
                } else {
                    this.$root.$on('viewScroll', this.onViewScroll)
                }
            },

            requestPlugins(dontAppendData) {
                if (this.loading) {
                    return null
                }

                if (this.loadingMore) {
                    return null
                }

                if (!this.hasMore) {
                    return null
                }

                if (!dontAppendData && this.viewHasScrollbar()) {
                    return null
                }

                this.loading = true

                if (dontAppendData) {
                    this.offset = 0
                }

                this.$store.dispatch(this.action, {
                        ...this.requestActionData,
                        appendData: !dontAppendData,
                    })
                    .then((response) => {
                        this.loading = false

                        if (response.data.length >= this.limit) {
                            this.hasMore = true
                            this.offset += this.limit

                            if (!this.viewHasScrollbar()) {
                                this.requestPlugins()
                            }
                        } else {
                            this.hasMore = false
                        }
                    })
                    .catch(() => {
                        this.loading = false
                    })
            },

            onWindowResize() {
                this.requestPlugins()
            },

            viewHasScrollbar() {
                const scrollableDiv = document.getElementById('content').getElementsByClassName('ps-container')[0]

                if (scrollableDiv.clientHeight < scrollableDiv.scrollHeight) {
                    return true
                } else {
                    return false
                }
            },
        },

        mounted() {
            this.$store.commit('pluginStore/updatePlugins', [])
            this.loading = true
            this.offset = 0

            this.$store.dispatch(this.action, this.requestActionData)
                .then((response) => {
                    this.loading = false
                    if (response.data.length >= this.limit) {
                        this.hasMore = true
                        this.offset = this.limit;

                        if (!this.viewHasScrollbar()) {
                            this.requestPlugins()
                        }

                        this.$root.$on('viewScroll', this.onViewScroll)
                        this.$root.$on('windowResize', this.onWindowResize)
                    } else {
                        this.hasMore = false
                    }
                })
                .catch(() => {
                    this.loading = false
                })
        },

        beforeDestroy() {
            this.$root.$off('viewScroll')
            this.$root.$off('windowResize')

            this.$store.dispatch('pluginStore/cancelRequests')
        }
    }
</script>
