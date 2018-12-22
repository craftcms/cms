<template>
    <div class="input" :class="{'errors': errors }">
        <div class="select">
            <select v-model="selectedOption" @change="onChange">
                <option v-for="option, optionKey in this.options" :value="option.value" :key="optionKey">{{ option.label }}</option>
            </select>
        </div>
    </div>
</template>


<script>
    export default {
        data() {
            return {
                selectedOption: null,
            }
        },

        props: ['options', 'value', 'errors'],

        methods: {
            onChange($event) {
                this.$emit('input', $event.target.value)
            },
        },

        mounted() {
            this.selectedOption = this.value
        },

        watch: {
            value: function (newValue) {
                this.selectedOption = newValue
            }
        }
    }
</script>
