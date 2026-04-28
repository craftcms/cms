<template>
  <div class="c-lightswitch">
    <div class="c-lightswitch-input">
      <label :for="id" class="lightswitch" :class="{disabled: disabled}">
        <input
          :id="id"
          type="checkbox"
          :value="value"
          :checked="checked"
          :disabled="disabled"
          @input="onInput"
        />
        <div class="slider round" />
      </label>
    </div>
  </div>
</template>

<script>
  export default {
    model: {
      prop: 'checked',
      event: 'input',
    },

    props: {
      checked: {
        type: Boolean,
        default: null,
      },
      disabled: {
        type: Boolean,
        default: null,
      },
      id: {
        type: String,
        default: function () {
          return 'c-lightswitch-id-' + Math.random().toString(36).substr(2, 9);
        },
      },
      value: {
        type: String,
        default: null,
      },
    },

    emits: ['update:checked', 'input'],

    methods: {
      onInput($event) {
        this.$emit('update:checked', $event.target.checked);
        this.$emit('input', $event.target.checked);
      },
    },
  };
</script>

<style>
  .c-lightswitch {
    .c-lightswitch-input {
      label {
        @apply tw-relative tw-block tw-select-none;
        width: 34px;
        height: 22px;

        input {
          @apply tw-absolute tw-opacity-0;
        }

        .slider {
          @apply tw-absolute tw-inset-0 tw-cursor-pointer tw-bg-gray-400;
          -webkit-transition: 0.4s;
          transition: 0.4s;
        }

        .slider:before {
          @apply tw-absolute tw-bg-white;
          content: '';
          height: 20px;
          width: 20px;
          left: 1px;
          bottom: 1px;
          -webkit-transition: 0.1s;
          transition: 0.1s;

          -webkit-transform: translateX(0px);
          -ms-transform: translateX(0px);
          transform: translateX(0px);
        }

        input:checked + .slider {
          background-color: #38c172;
        }

        input:focus + .slider {
          @apply tw-ring-2 tw-ring-blue-500 tw-ring-opacity-100;
        }

        input:checked + .slider:before {
          -webkit-transform: translateX(12px);
          -ms-transform: translateX(12px);
          transform: translateX(12px);
        }

        .slider.round {
          border-radius: 34px;
        }

        .slider.round:before {
          border-radius: 50%;
        }

        &.disabled {
          opacity: 0.4;

          .slider {
            @apply tw-cursor-default;
          }
        }
      }
    }
  }
</style>
