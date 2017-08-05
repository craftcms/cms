<template>
    <div class="field first">
        <div class="heading">
            <label :id="id+'-label'" :for="id">{{ label }}</label>
        </div>
        <div class="input ltr">
            <input class="text fullwidth" type="text" :id="id" :placeholder="placeholder" autocomplete="off" :value="value" @input="$emit('input', $event.target.value)">
        </div>
    </div>
</template>

<script>
    export default {
        props: ['label', 'id', 'placeholder', 'value'],
    }
</script>