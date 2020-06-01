<template>
  <form ref="form" method="post">
    <input type="hidden" :name="tokenName" :value="tokenValue">
    <input type="hidden" name="action" :value="action">
    <input type="hidden" :name="param" :value="value">
    <input type="hidden" name="ids[]" v-for="(id, index) in ids" :key="index" :value="id">

    <div ref="button" class="btn menubtn" :data-icon="icon">{{label}}</div>
    <div class="menu" v-if="actions.length">
      <ul class="padded">
        <li v-for="(act,index) in actions" :key="index">
          <a href="#" :class="{ error: act.error !== undefined && act.error, disabled: (act.allowMultiple !== undefined && !act.allowMultiple && hasMultipleSelected) }" :data-param="act.param" :data-value="act.value" :data-ajax="act.ajax" @click.prevent="!(act.allowMultiple !== undefined && !act.allowMultiple && hasMultipleSelected) ? handleClick(act.param, act.value, act.action, act.ajax) : null">
            <span v-if="act.status" :class="'status ' + act.status"></span>{{act.label}}
          </a>
        </li>
      </ul>
    </div>
  </form>
</template>

<script>
    /* global Craft, $ */
    export default {
        name: 'AdminTableActionButton',

        props: {
            action: String,
            actions: Array,
            enabled: Boolean,
            ids: Array,
            label: String,
            icon: String,
        },

        data() {
            return {
                button: null,
                tokenName: Craft.csrfTokenName,
                tokenValue: Craft.csrfTokenValue,
                param: '',
                value: ''
            }
        },

        methods: {
            handleClick(param, value, action, ajax) {
                if (ajax) {
                    let data = {
                        ids: this.ids
                    };
                    data[param] = value;

                    Craft.postActionRequest(action, data, response => {
                        if (response.success) {
                            Craft.cp.displayNotice(Craft.escapeHtml(Craft.t('app', 'Updated.')));
                        }

                        this.$emit('reload');
                    });
                } else {
                    this.action = action;
                    this.param = param;
                    this.value = value;

                    this.$nextTick(() => {
                        this.$refs.form.submit();
                    });
                }
            }
        },

        computed: {
            hasMultipleSelected() {
                return (this.ids.length > 1);
            }
        },

        watch: {
            enabled() {
                if (this.enabled) {
                    this.button.removeClass('disabled');
                    this.button.data('menubtn').enable();
                } else {
                    this.button.addClass('disabled');
                    this.button.data('menubtn').disable();
                }
            }
        },

        mounted() {
            this.$nextTick(() => {
                Craft.initUiElements(this.$refs.form);
                this.button = $(this.$refs.button);
                this.button.data('menubtn').disable();
                this.button.addClass('disabled');
            });
        }
    }
</script>

<style scoped>

</style>