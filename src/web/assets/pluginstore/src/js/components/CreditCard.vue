<template>
    <div class="credit-card">

        <div class="multitext">
            <div class="multitextrow">
                <text-field id="card-number" placeholder="Card Number" v-model="value.number"></text-field>
            </div>
            <div class="multitextrow">
                <text-input id="card-expiry" placeholder="MM/YY" v-model="value.expiry"></text-input>
                <text-input id="card-cvc" placeholder="CVC" v-model="value.cvc"></text-input>
            </div>
        </div>
    </div>
</template>

<script>
    import TextField from './fields/TextField';
    import TextInput from './inputs/TextInput';

    export default {

        props: ['value'],

        components: {
            TextField,
            TextInput,
        },

    }
</script>
