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