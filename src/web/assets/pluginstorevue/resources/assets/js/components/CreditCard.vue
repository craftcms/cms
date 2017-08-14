<template>
    <div class="credit-card">

        <div class="multitext">
            <div class="multitextrow">
                <text-fieldtype id="card-number" placeholder="Card Number" v-model="value.number"></text-fieldtype>
            </div>
            <div class="multitextrow">
                <text-input id="card-expiry" placeholder="MM/YY" v-model="value.expiry"></text-input>
                <text-input id="card-cvc" placeholder="CVC" v-model="value.cvc"></text-input>
            </div>
        </div>
    </div>
</template>

<script>
    import TextFieldtype from './fieldtypes/TextFieldtype';
    import TextInput from './inputs/TextInput';

    export default {
        props: ['value'],

        components: {
            TextFieldtype,
            TextInput,
        },
    }
</script>
