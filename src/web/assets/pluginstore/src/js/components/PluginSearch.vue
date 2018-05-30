<template>
    <div>
        <div class="toolbar">
            <div class="flex">
                <div class="flex-grow texticon search icon clearable">
                    <input class="text fullwidth" id="searchQuery" name="searchQuery" type="text" :placeholder="'Search plugins'|t('app')" v-model="searchQuery">
                    <div class="clear" :class="{ hidden: searchQuery.length == 0 }" @click="searchQuery = ''" title="Clear"></div>
                </div>

                <template v-if="sort">
                    <sort-menu-btn :attributes="sortMenuBtnAttributes" :value="sort" @update:value="val => $emit('update:sort', val)" />
                </template>

                <div class="spinner" v-bind:class="{ invisible: !showSpinner }"></div>
            </div>
        </div>

        <plugin-grid :plugins="pluginsToRender" :columns="4"></plugin-grid>
    </div>
</template>

<script>
    import filter from 'lodash/filter'
    import includes from 'lodash/includes'

    export default {

        components: {
            PluginGrid: require('./PluginGrid'),
            SortMenuBtn: require('./SortMenuBtn'),
        },

        props: ['plugins', 'sort'],

        data() {
            return {
                searchQuery: '',
                showSpinner: false,

                selectedAttribute: null,
                selectedDirection: null,

                sortMenuBtnAttributes: null,
            }
        },

        computed: {

            pluginsToRender() {
                let self = this

                let searchQuery = this.searchQuery

                if (!searchQuery) {
                    this.$emit('hideResults')
                    return []
                }

                this.$emit('showResults')

                return filter(this.plugins, o => {
                    if (o.packageName && includes(o.packageName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.name && includes(o.name.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.shortDescription && includes(o.shortDescription.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.description && includes(o.description.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.developerName && includes(o.developerName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.developerUrl && includes(o.developerUrl.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.keywords.length > 0) {
                        for (let i = 0; i < o.keywords.length; i++) {
                            if (includes(o.keywords[i].toLowerCase(), searchQuery.toLowerCase())) {
                                return true
                            }
                        }
                    }
                })
            },

        },

        mounted() {
            this.sortMenuBtnAttributes = {
                activeInstalls: this.$options.filters.t("Popularity", 'app'),
                lastUpdate: this.$options.filters.t("Last Update", 'app'),
                name: this.$options.filters.t("Name", 'app'),
                price: this.$options.filters.t("Price", 'app'),
            }
        }

    }
</script>
