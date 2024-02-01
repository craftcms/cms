<template>
  <a
    :title="deleteTitle"
    v-on:click.prevent="handleClick"
    class="delete icon"
    :class="{disabled}"
    role="button"
    href="#"
  ></a>
</template>

<script>
  /* global Craft */
  export default {
    name: 'AdminTableDeleteButton',

    props: {
      actionUrl: String,
      before: Function,
      confirmationMessage: String,
      deleteTitle: {
        type: String,
        default: Craft.escapeHtml(Craft.t('app', 'Delete')),
      },
      disabled: Boolean,
      failMessage: String,
      id: [Number, String],
      name: String,
      successMessage: String,
    },

    data() {
      return {};
    },

    computed: {
      success() {
        var successMessage = this.successMessage
          ? Craft.t('site', this.successMessage, {name: this.name})
          : Craft.t('app', '“{name}” deleted.', {name: this.name});
        return Craft.escapeHtml(successMessage);
      },
      confirm() {
        var confirmationMessage = this.confirmationMessage
          ? Craft.t('site', this.confirmationMessage, {name: this.name})
          : Craft.t('app', 'Are you sure you want to delete “{name}”?', {
              name: this.name,
            });
        return Craft.escapeHtml(confirmationMessage);
      },
      failed() {
        var failMessage = this.failMessage
          ? Craft.t('site', this.failMessage, {name: this.name})
          : Craft.t('app', 'Couldn’t delete “{name}”.', {name: this.name});
        return Craft.escapeHtml(failMessage);
      },
    },

    methods: {
      confirmDelete: function () {
        return confirm(this.confirm);
      },
      handleClick() {
        let _this = this;

        if (_this.disabled) {
          return;
        }

        _this.$emit('loading');

        _this.before(_this.id).then((continueDelete) => {
          if (continueDelete && _this.confirmDelete()) {
            Craft.sendActionRequest('POST', _this.actionUrl, {
              data: {id: _this.id},
            })
              .then(() => {
                Craft.cp.displayNotice(_this.success);
                _this.$emit('reload');
              })
              .catch(() => {
                Craft.cp.displayError(_this.failed);
                _this.$emit('finishloading');
              });
          } else {
            _this.$emit('finishloading');
          }
        });
      },
    },
  };
</script>

<style scoped></style>
