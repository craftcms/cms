<template>
  <form ref="form" method="post">
    <input type="hidden" :name="tokenName" :value="tokenValue" />
    <input type="hidden" name="action" :value="action" />
    <input type="hidden" :name="param" :value="value" v-if="param" />
    <input
      type="hidden"
      name="ids[]"
      v-for="(id, index) in ids"
      :key="index"
      :value="id"
    />

    <component
      :is="isMenuButton ? 'div' : 'button'"
      ref="button"
      class="btn"
      :class="menuBtnClasses"
      :data-icon="icon"
      :disabled="buttonDisabled"
      :type="enabled && !isMenuButton && !ajax ? 'submit' : null"
      v-on="
        enabled && !isMenuButton && ajax
          ? {click: handleClick(param, value, action, ajax, handleClick)}
          : {}
      "
      >{{ label }}</component
    >
    <div class="menu" v-if="isMenuButton">
      <template v-for="(actList, ind) in actionsList">
        <hr
          v-if="
            actionsList.length > 1 && ind === actionsList.length - 1 && ind != 0
          "
          :key="ind"
        />
        <ul class="padded" :key="ind">
          <li v-for="(act, index) in actList" :key="index">
            <a
              href="#"
              :class="{
                ...(act.class ? act.class : {}),
                ...{
                  error: act.error,
                  disabled:
                    typeof act.allowMultiple !== 'undefined' &&
                    !act.allowMultiple &&
                    hasMultipleSelected,
                },
              }"
              :data-param="act.param"
              :data-value="act.value"
              :data-ajax="act.ajax"
              @click.prevent="
                !(
                  typeof act.allowMultiple !== 'undefined' &&
                  !act.allowMultiple &&
                  hasMultipleSelected
                )
                  ? handleClick(
                      act.param,
                      act.value,
                      act.action,
                      act.ajax,
                      act.handleClick
                    )
                  : null
              "
            >
              <span v-if="act.status" :class="'status ' + act.status"></span
              >{{ act.label }}
            </a>
          </li>
        </ul>
        <hr
          v-if="
            actionsList.length > 1 && ind != actionsList.length - 1 && ind != 0
          "
          :key="ind"
        />
      </template>
    </div>
  </form>
</template>

<script>
  /* global Craft, $ */
  export default {
    name: 'AdminTableActionButton',

    props: {
      action: String,
      actions: {
        type: Array,
        default: () => [],
      },
      ajax: {
        type: Boolean,
        default: false,
      },
      allowMultiple: {
        type: Boolean,
        default: true,
      },
      handleClick: {
        type: Boolean,
        default: true,
      },
      menuBtnClass: {
        type: String,
        default: '',
      },
      enabled: Boolean,
      ids: Array,
      label: String,
      icon: String,
      error: {
        type: Boolean,
        default: false,
      },
    },

    data() {
      return {
        button: null,
        buttonDisabled: false,
        tokenName: Craft.csrfTokenName,
        tokenValue: Craft.csrfTokenValue,
        param: '',
        value: '',
      };
    },

    methods: {
      handleClick(param, value, action, ajax, handleClick) {
        this.$emit('click', param, value, action, ajax);

        // Is the action button the one to deal with the click?
        if (typeof handleClick !== 'undefined' && !handleClick) {
          return;
        }

        if (ajax) {
          let data = {
            ids: this.ids,
          };
          data[param] = value;

          Craft.sendActionRequest('POST', action, {data})
            .then((response) => {
              Craft.cp.displayNotice(
                Craft.escapeHtml(Craft.t('app', 'Updated.'))
              );
            })
            .finally(() => {
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
      },

      enableButton() {
        if (this.isMenuButtonInitialised) {
          this.button.data('menubtn').enable();
        } else {
          this.buttonDisabled = false;
        }
      },

      disableButton() {
        if (this.isMenuButtonInitialised) {
          this.button.data('menubtn').disable();
        } else {
          this.buttonDisabled = true;
        }
      },
    },

    computed: {
      actionsList() {
        if (!this.actions.length) {
          return [];
        }

        let actionsList = [];
        let _tmpActionsList = [];

        this.actions.forEach((item) => {
          if (Object.keys(item).indexOf('separator') >= 0 && item.separator) {
            actionsList.push(_tmpActionsList);
            _tmpActionsList = [];
          }

          _tmpActionsList.push(item);
        });

        if (_tmpActionsList.length) {
          actionsList.push(_tmpActionsList);
        }

        return actionsList;
      },

      hasMultipleSelected() {
        return this.ids.length > 1;
      },

      isMenuButtonInitialised() {
        return this.isMenuButton && this.button.data('menubtn');
      },

      isMenuButton() {
        if (!this.button) {
          return false;
        }

        if (!this.actions.length) {
          return false;
        }

        return true;
      },

      menuBtnClasses() {
        let menuBtnClasses = [];

        if (this.isMenuButton) {
          menuBtnClasses.push('menubtn');
        }

        if (this.error) {
          menuBtnClasses.push('error');
        }

        if (!this.enabled || this.buttonDisabled) {
          menuBtnClasses.push('disabled');
        }

        if (this.menuBtnClass) {
          menuBtnClasses.push(this.menuBtnClass);
        }

        return menuBtnClasses;
      },
    },

    watch: {
      enabled() {
        if (this.enabled) {
          this.enableButton();
        } else {
          this.disableButton();
        }
      },

      hasMultipleSelected(val) {
        // Logic specifically for handling single buttons and not menu buttons
        if (val && !this.actions.length && !this.allowMultiple) {
          this.buttonDisabled = true;
        } else {
          this.buttonDisabled = false;
        }
      },
    },

    mounted() {
      this.$nextTick(() => {
        Craft.initUiElements(this.$refs.form);
        this.button = $(this.$refs.button);
        this.disableButton();
      });
    },
  };
</script>

<style scoped></style>
