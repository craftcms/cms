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
            checked: Boolean,
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
        },

        created() {},

        watch: {
            selectAll() {
                if (this.selectAll) {
                    this.$emit('addCheck', this.id);
                } else {
                    this.$emit('removeCheck', this.id);
                }
                // this.isChecked = this.selectAll === false || this.selectAll === true ? this.selectAll : this.isChecked
            }
        }
    }
</script>

<style scoped>

</style>