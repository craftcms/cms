<template>
    <div class="credit-card">
        <text-fieldtype label="Card Number" id="card-number" v-model="value.number"></text-fieldtype>
        <text-fieldtype label="Card Expiry" id="card-expiry" placeholder="MM/YY"  v-model="value.expiry"></text-fieldtype>
        <text-fieldtype label="Card CVC" id="card-cvc" placeholder="XXX"  v-model="value.cvc"></text-fieldtype>
    </div>
</template>

<script>
    import TextFieldtype from './fieldtypes/TextFieldtype';

    export default {
        props: ['value'],

        components: {
            TextFieldtype,
        },
    }
</script>