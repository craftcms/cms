<template>
  <form ref="adminMenuBtn" method="post">
    <input type="hidden" :name="tokenName" :value="tokenValue">
    <input type="hidden" name="action" :value="action">
    <input type="hidden" :name="param" :value="value">
    <input type="hidden" name="ids[]" v-for="(id, index) in ids" :key="index" :value="id">

    <div class="btn menubtn" :data-icon="icon">{{label}}</div>
    <div class="menu" v-if="actions.length">
      <ul class="padded">
        <li v-for="(act,index) in actions" :key="index" v-once>
          <a href="#" :class="{ error: act.error !== undefined && act.error }" :data-param="act.param" :data-value="act.value" :data-ajax="act.ajax" @click.prevent="handleClick(act.param, act.value, act.action, act.ajax)">
            <span v-if="act.status" :class="'status ' + act.status"></span>{{act.label}}
          </a>
        </li>
      </ul>
    </div>
  </form>
</template>

<script>
    /* global Craft */
    export default {
        name: 'AdminTableActionButton',

        props: {
            label: String,
            icon: String,
            action: String,
            actions: Array,
            ids: Array
        },

        data() {
            return {
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
                            Craft.cp.displayNotice(Craft.t('app', 'Updated.'));
                        }

                        this.$emit('reload');
                    });
                } else {
                    this.action = action;
                    this.param = param;
                    this.value = value;

                    this.$nextTick(() => {
                        this.$refs.adminMenuBtn.submit();
                    });
                }
            }
        },

        created() {

        },

        mounted() {
            this.$nextTick(() => {
                Craft.initUiElements(this.$refs.adminMenuBtn);
            });
        }
    }
</script>

<style scoped>

</style>