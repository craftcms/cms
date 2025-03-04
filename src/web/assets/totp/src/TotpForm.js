(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.TotpForm = Garnish.Base.extend(
    {
      init: function (form, onSuccess, showError) {
        const codeInput = form.querySelector('input.auth-totp-code');
        const submitBtn = form.querySelector('button.submit');

        this.addListener(codeInput, 'input', (ev) => {
          if (codeInput.value.length === 6) {
            form.requestSubmit();
          }
        });

        this.addListener(form, 'submit', (ev) => {
          ev.preventDefault();

          if (submitBtn.classList.contains('loading')) {
            return;
          }

          submitBtn.classList.add('loading');
          Craft.cp.announce(Craft.t('app', 'Loading'));

          Craft.sendActionRequest('POST', 'auth/verify-totp', {
            data: {
              code: codeInput.value,
            },
          })
            .then(() => {
              onSuccess();
            })
            .catch((e) => {
              showError(e?.response?.data?.message);
            })
            .finally(() => {
              submitBtn.classList.remove('loading');
            });
        });
      },
    },
    {
      METHOD: 'craft\\auth\\methods\\TOTP',
    }
  );

  Craft.registerAuthFormHandler(Craft.TotpForm.METHOD, Craft.TotpForm);
})(jQuery);
