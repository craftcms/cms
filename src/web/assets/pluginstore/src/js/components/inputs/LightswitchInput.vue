<template>
    <div>
        <label :for="id" class="pluginstore-lightswitch">
            <input :id="id" type="checkbox" :checked="value" @change="$emit('input', $event.target.checked)" />
            <div class="slider round"></div>
        </label>
    </div>
</template>

<script>
    export default {

        props: ['id', 'value'],

        methods: {
            turnOn() {
            }
        }

    }
</script>

<style lang="scss" scoped>
    .pluginstore-lightswitch {
        @apply .tw-relative .tw-block .tw-select-none;
        width: 34px;
        height: 22px;

        input {
            @apply .tw-absolute .tw-opacity-0;
        }

        .slider {
            @apply .tw-absolute .tw-pin .tw-cursor-pointer;
            background-color: #9da5af;
            -webkit-transition: .4s;
            transition: .4s;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1);
        }

        .slider:before {
            @apply .tw-absolute .tw-bg-white;
            content: "";
            height: 20px;
            width: 20px;
            left: 1px;
            bottom: 1px;
            -webkit-transition: .1s;
            transition: .1s;
        }

        input:checked + .slider {
            background-color: #00b007;
        }

        input:focus + .slider,
        input:active + .slider {
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(51, 170, 255, 0.5), 0 0 2px #33aaff;
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
    }
</style>