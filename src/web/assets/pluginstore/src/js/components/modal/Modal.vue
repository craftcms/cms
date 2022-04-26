<template>
  <div class="hidden">
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
  @import '../../../../../../../../packages/craftcms-sass/mixins';

  #pluginstore-modal {
    @apply .absolute .pin-t .pin-l;
    max-width: 850px;
    max-height: 650px;
    z-index: 20000;

    .pluginstore-modal-flex {
      @apply .absolute .pin .flex .flex-col;

      header {
        .btn-left {
          @apply .absolute;
          top: 28px;
          @include left(24px);
        }

        h1 {
          @apply .text-center;
        }
      }

      .pluginstore-modal-main {
        @apply .relative .flex .flex-grow .mb-0 .min-h-0;

        .pluginstore-modal-content {
          @apply .overflow-auto .flex-grow;
          padding: 24px;
        }
      }
    }
  }
</style>
