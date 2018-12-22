<template>
    <div class="field first">
        <div v-if="label" class="heading">
            <label :id="id+'-label'" :for="id">{{ label }}</label>
        </div>
        <div class="input ltr">
            <textarea-input :id="id" :placeholder="placeholder" :value="value" :cols="cols" @input="$emit('input', $event)"></textarea-input>
        </div>
    </div>
</template>

<script>
    import TextareaInput from '../inputs/TextareaInput'

    export default {

        props: ['label', 'id', 'placeholder', 'value', 'cols'],

        components: {
            TextareaInput,
        },

    }
</script>