<template>
    <div
      class="checkbox"
      :class="{ checked: isChecked }"
      v-on:click.prevent="handleClick"
      :title="title"></div>
</template>

<script>
    /* global Craft */
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
            },
            title() {
                return Craft.escapeHtml(Craft.t('app', 'Select'));
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