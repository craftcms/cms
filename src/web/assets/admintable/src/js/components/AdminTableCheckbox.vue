<template>
    <div
      class="checkbox"
      :class="{ checked: isChecked }"
      v-on:click.prevent="handleClick"
      :title="'Select'|t('app')"></div>
</template>

<script>
    export default {
        name: 'AdminTableCheckbox',
        props: {
            id: Number,
            selectAll: Boolean,
            checks: Array
        },

        data() {
            return {
            }
        },

        computed: {
            isChecked() {
                return this.checks.indexOf(this.id) !== -1
            }
        },

        methods: {
            handleClick() {
                if (this.isChecked) {
                    this.$emit('removeCheck', this.id);
                } else {
                    this.$emit('addCheck', this.id);
                }
            }
        }
    }
</script>

<style scoped>

</style>