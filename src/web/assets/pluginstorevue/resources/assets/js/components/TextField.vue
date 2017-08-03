<template>
    <div class="field first" id="name-field">
        <div class="heading">
            <label :id="id+'-label'" :for="id">{{ label }}</label>
        </div>
        <div class="input ltr">
            <input class="text fullwidth" type="text" :id="id" :name="id" :placeholder="placeholder" autocomplete="off" :value="value" @input="updateValue">
        </div>
    </div>
</template>

<script>
    export default {
        name: 'textField',
        props: ['label', 'id', 'placeholder', 'value'],
        methods: {
            updateValue($event) {
                this.$emit('input', $event.target.value)
            }
        }

    }
</script>