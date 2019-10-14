<template>
  <a
    :title="'Delete'|t('app')"
    v-on:click.prevent="handleClick"
    class="delete icon"
    role="button"
    href="#"></a>
</template>

<script>
    /* global Craft */
    import axios from 'axios'
    export default {
        name: 'AdminTableDeleteButton',

        props: {
            id: Number,
            name: String,
            actionUrl: String
        },

        data() {
            return {

            }
        },

        methods: {
            confirmDelete: function(name) {
                return confirm(Craft.t('app', 'Are you sure you want to delete “{name}”?', {name: name}));
            },
            handleClick() {
                if (this.confirmDelete(this.name)) {
                    axios.post(Craft.getActionUrl(this.actionUrl), {id: this.id}, {
                        headers: {
                            'X-CSRF-Token': Craft.csrfTokenValue
                        }
                    }).then(response => {
                        if (response.data && response.data.success !== undefined && response.data.success) {
                            Craft.cp.displayNotice(Craft.t('app', '“{name}” deleted.', {name: this.name}));
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