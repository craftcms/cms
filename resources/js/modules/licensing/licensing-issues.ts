import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {sendApiRequest} from '@craftcms/ui/utilities/api/apiClient';
import {t} from '@craftcms/ui/utilities/translate';

/**
 * The licensing issues screen: its Refresh button, and the countdown to the
 * link that continues to the control panel (shunning the issues for a while).
 */
export function bootLicensingIssues(): void {
  const timer = document.querySelector<HTMLElement>(
    '[data-licensing-continue]'
  );

  if (!timer) {
    return;
  }

  const refreshButton = document.getElementById('refresh-btn') as
    | (HTMLElement & {loading?: boolean})
    | null;
  refreshButton?.addEventListener('click', async () => {
    refreshButton.loading = true;
    try {
      await sendApiRequest('GET', 'ping');
      location.reload();
    } finally {
      refreshButton.loading = false;
    }
  });

  let duration = Number(timer.dataset.licensingContinue);
  const hash = timer.dataset.licensingHash;

  const tick = async () => {
    if (duration) {
      timer.textContent = t(
        'Continue to the control panel in {duration, number} {duration, plural, =1{second} other{seconds}}…',
        {duration}
      );
      setTimeout(() => {
        duration--;
        void tick();
      }, 1000);
      return;
    }

    await actionClient.post('app/set-license-shun-cookie', {hash});

    const link = document.createElement('a');
    link.className = 'go';
    link.href = document.location.href;
    link.textContent = t('Continue to the control panel');
    timer.removeAttribute('role');
    timer.setAttribute('aria-live', 'polite');
    timer.replaceChildren(link);
  };

  void tick();
}
