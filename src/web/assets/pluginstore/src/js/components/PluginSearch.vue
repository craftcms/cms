<template>
    <div>
        <div class="toolbar">
            <div class="flex">
                <form @submit.prevent="search()" class="flex-grow texticon search icon clearable">
                    <input class="text fullwidth" id="sQuery" name="sQuery" type="text" :placeholder="'Search plugins'|t('app')" v-model="sQuery">
                    <div class="clear" :class="{ hidden: sQuery.length == 0 }" @click="sQuery = ''" title="Clear"></div>
                </form>

                <template v-if="sort">
                    <sort-menu-btn :attributes="sortMenuBtnAttributes" :value="sort" @update:value="val => $emit('update:sort', val)"></sort-menu-btn>
                </template>

                <div class="spinner" v-bind:class="{ invisible: !showSpinner }"></div>
            </div>
        </div>
    </div>
</template>

<script>
    import SortMenuBtn from './SortMenuBtn'

    export default {

        components: {
            SortMenuBtn,
        },

        props: ['sort'],

        data() {
            return {
                sQuery: '',
                showSpinner: false,

                selectedAttribute: null,
                selectedDirection: null,

                sortMenuBtnAttributes: null,
            }
        },

        methods: {
            search() {
                this.$store.commit('app/updateSearchQuery', this.sQuery)
                this.$router.push({path: '/search'})
            }
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
