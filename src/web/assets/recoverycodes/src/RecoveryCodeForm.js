(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.RecoveryCodeForm = Garnish.Base.extend(
    {
      init: function (form, onSuccess, showError) {
        const codeInput = form.querySelector('input.auth-recovery-code');
        const submitBtn = form.querySelector('button.submit');

        this.addListener(codeInput, 'input', (ev) => {
          if (codeInput.value.replace(/-/g, '').length === 12) {
            form.requestSubmit();
          }
        });

        this.addListener(form, 'submit', (ev) => {
          ev.preventDefault();

          if (submitBtn.classList.contains('loading')) {
            return;
          }

          submitBtn.classList.add('loading');

          Craft.sendActionRequest('POST', 'auth/verify-recovery-code', {
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
      METHOD: 'craft\\auth\\methods\\RecoveryCodes',
    }
  );

  Craft.registerAuthFormHandler(
    Craft.RecoveryCodeForm.METHOD,
    Craft.RecoveryCodeForm
  );
})(jQuery);
