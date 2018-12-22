<template>
    <div class="field first">
        <div v-if="label" class="heading">
            <label :id="id+'-label'" :for="id">{{ label }}</label>
        </div>

        <text-input :id="id" :placeholder="placeholder" :value="value" @input="$emit('input', $event)" :size="size" :errors="errors" :success="success"></text-input>
    </div>
</template>

<script>
    import TextInput from '../inputs/TextInput'
    export default {

        props: ['label', 'id', 'placeholder', 'value', 'size', 'errors', 'success'],

        components: {
            TextInput,
        },

    }
</script>