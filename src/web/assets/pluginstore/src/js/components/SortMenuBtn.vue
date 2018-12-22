<template>
    <div>
        <div class="btn menubtn sortmenubtn" :data-icon="value.direction">{{ menuLabel }}</div>
        <div class="menu">
            <ul class="padded sort-attributes">
                <li v-for="label, key in attributes"><a @click="selectAttribute(key)" :class="{sel: value.attribute == key}">{{ label }}</a></li>
            </ul>
            <hr>
            <ul class="padded sort-directions">
                <li v-for="label, key in directions"><a @click="selectDirection(key)" :class="{sel: value.direction == key}">{{ label }}</a></li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {

        props: ['attributes', 'value'],

        data() {
            return {
                defaultDirection: 'asc',
                directions: null,
            }
        },

        computed: {

            menuLabel() {
                if (this.attributes) {
                    return this.attributes[this.value.attribute]
                }
            }

        },

        methods: {

            selectAttribute(attribute) {
                this.$emit('update:value', {attribute: attribute, direction: this.value.direction})
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

            if (!this.value.direction) {
                this.$emit('update:value', {
                    attribute: this.value.attribute,
                    direction: this.defaultDirection
                })
            }

            Craft.initUiElements()
        },

    }
</script>