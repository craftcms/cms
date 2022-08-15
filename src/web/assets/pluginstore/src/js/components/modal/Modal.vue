<template>
  <div class="tw-hidden">
    <div
      ref="pluginstoremodal"
      id="pluginstore-modal"
      class="pluginstore-modal modal"
      :class="'step-' + modalStep"
    >
      <cart
        v-if="modalStep === 'cart'"
        @continue-shopping="$root.closeModal()"
      ></cart>
    </div>
  </div>
</template>

<script>
  /* global Garnish */

  import {mapState} from 'vuex';
  import Cart from './steps/Cart';

  export default {
    components: {
      Cart,
    },

    props: ['pluginId', 'show'],

    data() {
      return {
        modal: null,
      };
    },

    computed: {
      modalStep() {
        return this.$root.modalStep;
      },
    },

    watch: {
      show(show) {
        if (show) {
          this.modal.show();
        } else {
          this.modal.hide();
        }
      },
    },

    mounted() {
      let $this = this;

      this.modal = new Garnish.Modal(this.$refs.pluginstoremodal, {
        autoShow: false,
        resizable: true,
        onHide() {
          $this.$emit('update:show', false);
        },
      });
    },
  };
</script>

<style lang="scss">
  @import '@craftcms/sass/mixins';

  #pluginstore-modal {
    @apply tw-absolute tw-top-0 tw-left-0;
    max-width: 850px;
    max-height: 650px;
    z-index: 100;

    .pluginstore-modal-flex {
      @apply tw-absolute tw-inset-0 tw-flex tw-flex-col;

      header {
        .btn-left {
          @apply tw-absolute;
          top: 28px;
          @include left(24px);
        }

        h1 {
          @apply tw-text-center;
        }
      }

      .pluginstore-modal-main {
        @apply tw-relative tw-flex tw-flex-grow tw-mb-0 tw-min-h-0;

        .pluginstore-modal-content {
          @apply tw-overflow-auto tw-flex-grow;
          padding: 24px;
        }
      }
    }
  }
</style>
