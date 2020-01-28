<template>
  <a
    :title="'Delete'|t('app')"
    v-on:click.prevent="handleClick"
    class="delete icon"
    :class="{disabled}"
    role="button"
    href="#"></a>
</template>

<script>
    /* global Craft */
    import axios from 'axios'
    export default {
        name: 'AdminTableDeleteButton',

        props: {
            id: [Number, String],
            name: String,
            confirmationMessage: String,
            actionUrl: String,
            successMessage: String,
            disabled: Boolean,
        },

        data() {
            return {
            }
        },

        computed: {
            success() {
                var successMessage = this.successMessage !== undefined ? Craft.t('site', this.successMessage, {name: this.name}) : Craft.t('app', '“{name}” deleted.', {name: this.name});
                return Craft.escapeHtml(successMessage);
            },
            confirm() {
                var confirmationMessage = this.confirmationMessage !== undefined ? Craft.t('site', this.confirmationMessage, {name: this.name}) : Craft.t('app', 'Are you sure you want to delete “{name}”?', {name: this.name});
                return Craft.escapeHtml(confirmationMessage);
            }
        },

        methods: {
            confirmDelete: function() {
                return confirm(this.confirm);
            },
            handleClick() {
                if (!this.disabled && this.confirmDelete()) {
                  axios.post(Craft.getActionUrl(this.actionUrl), {id: this.id}, {
                        headers: {
                            'X-CSRF-Token': Craft.csrfTokenValue
                        }
                    }).then(response => {
                        if (response.data && response.data.success !== undefined && response.data.success) {
                            Craft.cp.displayNotice(this.success);
                            this.$emit('reload');
                        }
                    });
                }
            }
        }
    }
</script>

<style scoped>

</style>