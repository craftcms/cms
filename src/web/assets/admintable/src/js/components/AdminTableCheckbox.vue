<template>
    <div
        class="checkbox"
        :class="{
          checked: isChecked,
          'table-disabled-checkbox': !status
        }"
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
            checks: Array,
            status: {
                type: Boolean,
                default: true,
            }
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
                if (!this.status) {
                    return;
                }

                if (this.isChecked) {
                    this.$emit('removeCheck', this.id);
                } else {
                    this.$emit('addCheck', this.id);
                }
            }
        },
    }
</script>

<style scoped>
  .table-disabled-checkbox {
      opacity: 0.25;
  }
</style>