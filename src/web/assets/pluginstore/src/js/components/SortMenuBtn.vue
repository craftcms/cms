<template>
    <div ref="sortMenuBtn">
        <div class="btn menubtn sortmenubtn" :data-icon="value.direction">{{ menuLabel }}</div>
        <div class="menu">
            <ul class="padded sort-attributes">
                <li v-for="(label, key) in attributes" :key="key"><a @click="selectAttribute(key)" :class="{sel: value.attribute == key}">{{ label }}</a></li>
            </ul>
            <hr>
            <ul class="padded sort-directions">
                <li v-for="(label, key) in directions" :key="key"><a @click="selectDirection(key)" :class="{sel: value.direction == key}">{{ label }}</a></li>
            </ul>
        </div>
    </div>
</template>

<script>
    /* global Craft */
    import {mapState} from 'vuex'

    export default {
        props: ['attributes', 'value'],

        data() {
            return {
                defaultDirection: 'asc',
                directions: {},
            }
        },

        computed: {
            ...mapState({
                sortOptions: state => state.pluginStore.sortOptions,
            }),

            menuLabel() {
                if (this.attributes) {
                    return this.attributes[this.value.attribute]
                }

                return null
            }
        },

        methods: {
            selectAttribute(attribute) {
                const direction = this.sortOptions[attribute] ? this.sortOptions[attribute] : this.value.direction

                this.$emit('update:value', {attribute, direction})
            },

            selectDirection(direction) {
                this.$emit('update:value', {attribute: this.value.attribute, direction: direction})
            }
        },

        mounted() {
            this.directions = {
                asc: this.$options.filters.t("Ascending", 'app'),
                desc: this.$options.filters.t("Descending", 'app'),
            }

            this.$nextTick(() => {
                if (!this.value.direction) {
                    this.$emit('update:value', {
                        attribute: this.value.attribute,
                        direction: this.defaultDirection
                    })
                }

                Craft.initUiElements(this.$refs.sortMenuBtn)
            })
        },
    }
</script>
