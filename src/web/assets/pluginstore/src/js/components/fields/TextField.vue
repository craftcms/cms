<template>
    <div class="field first">
        <div v-if="label" class="heading">
            <label :id="id+'-label'" :for="id">{{ label }}</label>
        </div>
        <div class="input ltr" :class="{'errors': errors }">
            <text-input :id="id" :placeholder="placeholder" :value="value" @input="$emit('input', $event)" />
        </div>
    </div>
</template>

<script>
    import TextInput from '../inputs/TextInput';

    export default {

        props: ['label', 'id', 'placeholder', 'value', 'errors'],

        components: {
            TextInput,
        },

    }
</script>