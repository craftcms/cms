<template>
    <div>
        <sort-menu-btn :attributes="sortMenuBtnAttributes" :value.sync="options"></sort-menu-btn>
    </div>
</template>

<script>
    import SortMenuBtn from './SortMenuBtn'

    export default {
        props: ['sortingOptions'],

        components: {
            SortMenuBtn,
        },

        data() {
            return {
                selectedAttribute: null,
                selectedDirection: null,
                sortMenuBtnAttributes: null,
                options: {
                    attribute: 'activeInstalls',
                    direction: 'desc',
                },
            }
        },

        watch: {
            options() {
                this.$emit('update:sortingOptions', this.options)
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
