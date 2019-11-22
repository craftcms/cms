<template>
    <div>
        <div class="border-b border-solid border-grey-light py-2 flex justify-between">
            <slot name="header"></slot>

            <template v-if="!disableSorting">
                <plugin-index-sort :loading="loading" :orderBy.sync="orderBy" :direction.sync="direction" @change="onOrderByChange"></plugin-index-sort>
            </template>
        </div>

        <plugin-grid :plugins="plugins"></plugin-grid>

        <div v-if="plugins.length === 0 && !loadingBottom && !loading" class="mt-4">
            <p>{{"No results."|t('app')}}</p>
        </div>

        <div v-if="error" class="my-4 text-red">{{error}}</div>

        <spinner v-if="loadingBottom || (disableSorting && loading)" class="my-4"></spinner>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
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
                orderBy: null,
                direction: null,

                loading: false,
                loadingBottom: false,
                hasMore: false,
                page: 1,

                error: null,
            }
        },

        computed: {
            ...mapState({
                sortOptions: state => state.pluginStore.sortOptions,
            }),

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
                    this.requestPlugins(false, (responseData) => {
                        if (responseData.currentPage < responseData.total) {
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

                    if (this.plugins.length > 0) {
                        this.loading = true
                    } else {
                        this.loadingBottom = true
                    }
                } else {
                    this.loadingBottom = true
                }

                this.$store.dispatch(this.action, {
                        ...this.requestActionData,
                        appendData: !dontAppendData,
                    })
                    .then((responseData) => {
                        if (responseData && responseData.error) {
                            throw responseData.error
                        }

                        this.loading = false
                        this.loadingBottom = false

                        if (responseData.currentPage < responseData.total) {
                            this.hasMore = true
                            this.page++

                            if (!this.viewHasScrollbar()) {
                                this.requestPlugins()
                            }
                        } else {
                            this.hasMore = false
                        }

                        if (typeof onAfterSuccess === 'function') {
                            onAfterSuccess(responseData)
                        }
                    })
                    .catch((thrown) => {
                        let errorMsg = this.$options.filters.t("Couldn’t get plugins.", 'app')

                        if (typeof thrown === 'string') {
                            errorMsg = thrown
                        }

                        this.error = errorMsg
                        this.loading = false
                        this.loadingBottom = false

                        throw thrown
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

        created() {
            const keys = Object.keys(this.sortOptions)
            const firstOptionKey = keys[0]

            this.orderBy = firstOptionKey
            this.direction = this.sortOptions[firstOptionKey]
        },

        mounted() {
            this.$store.commit('pluginStore/updatePlugins', [])

            this.$nextTick(() => {
                this.requestPlugins(true, (responseData) => {
                    if (responseData.currentPage < responseData.total) {
                        this.$root.$on('viewScroll', this.onScroll)
                        this.$root.$on('windowScroll', this.onScroll)
                        this.$root.$on('windowResize', this.onWindowResize)
                    }
                })
            })
        },

        beforeDestroy() {
            this.error = null
            this.$root.$off('viewScroll', this.onScroll)
            this.$root.$off('windowScroll', this.onScroll)
            this.$root.$off('windowResize', this.onWindowResize)

            this.$store.dispatch('pluginStore/cancelRequests')
        }
    }
</script>
