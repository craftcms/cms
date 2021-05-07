class Email extends AuthenticationStep
{
    private $email = $('#email');
    protected stepType = "craft\\authentication\\type\\Email";

    constructor()
    {
        super();

        this.$email.parents('.authentication-chain').data('handler', this.prepareData.bind(this));
        this.$email.on('input', this.onInput.bind(this));
    }

    protected validate()
    {
        const emailAddress = this.$email.val() as string;
        if (emailAddress.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
        }

        return true;
    }

    protected returnFormData(): AuthenticationRequest
    {
        return {
            "email": this.$email.val(),
        };
    }
}

new Email();
