import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {isHttpError} from '@craftcms/ui/utilities/api/http';
import {t} from '@craftcms/ui/utilities/translate';
import {escapeHtml} from '@/common/craft/attributes';
import {getUrl} from '@/common/craft/url';

interface UpdaterOption {
  label: string;
  submit?: boolean;
  email?: string;
  subject?: string;
  url?: string;
  [key: string]: unknown;
}

export interface UpdaterState {
  data?: string;
  status?: string;
  error?: string;
  errorDetails?: string;
  nextAction?: string;
  options?: UpdaterOption[];
  finished?: boolean;
  returnUrl?: string;
}

/**
 * Runs the updater screen (updates, migrations, composer installs): posts
 * each step's action, shows its status, and offers the options a step asks
 * the user to choose from.
 */
export class Updater {
  #data: string | undefined;

  constructor(
    private actionPrefix: string,
    private graphic: HTMLElement,
    private status: HTMLElement
  ) {}

  setState(state: UpdaterState): void {
    this.graphic.classList.add('spinner');
    this.graphic.classList.remove('error');

    // Options don't carry the data along
    if (state.data) {
      this.#data = state.data;
    }

    if (state.status) {
      this.#showStatus(state.status);
    } else if (state.error) {
      this.graphic.classList.remove('spinner');
      this.graphic.classList.add('error');
      this.#showStatus(state.error);
      if (state.errorDetails) {
        const details = document.createElement('div');
        details.id = 'error';
        details.className = 'code';
        details.tabIndex = 0;
        details.innerHTML = parseStatus(state.errorDetails);
        this.status.append(details);
      }
    }

    if (state.nextAction) {
      void this.#post(state.nextAction);
    } else if (state.options) {
      this.#showOptions(state);
    } else if (state.finished) {
      this.#finish(state.returnUrl);
    }
  }

  #showStatus(status: string): void {
    this.status.innerHTML = parseStatus(status);
  }

  async #post(action: string): Promise<void> {
    try {
      const {data} = await actionClient.post<UpdaterState>(
        `${this.actionPrefix}/${action}`,
        {data: this.#data}
      );
      this.setState(data);
    } catch (error) {
      this.#fatalError(error);
    }
  }

  #showOptions(state: UpdaterState): void {
    const buttons = document.createElement('div');
    buttons.id = 'options';
    buttons.className = 'buttons';

    for (const option of state.options ?? []) {
      const button = document.createElement('a');
      button.className = 'btn big';
      button.textContent = option.label;
      button.classList.toggle('submit', !!option.submit);

      if (option.email) {
        button.href = emailLink(state, option);
      } else if (option.url) {
        button.href = option.url;
        button.target = '_blank';
      } else {
        button.setAttribute('role', 'button');
        button.addEventListener('click', () =>
          this.setState(option as UpdaterState)
        );
      }

      buttons.append(button);
    }

    this.status.append(buttons);
  }

  #finish(returnUrl?: string): void {
    this.graphic.classList.remove('spinner');
    this.graphic.classList.add('success');

    setTimeout(() => {
      window.location.href = getUrl(returnUrl || 'dashboard');
    }, 750);
  }

  #fatalError(error: unknown): void {
    const response = isHttpError(error) ? error.response : undefined;
    const body =
      typeof response?.data === 'string'
        ? response.data
        : JSON.stringify(response?.data ?? '');

    this.setState({
      error: t('A fatal error has occurred:'),
      errorDetails: `${t('Status:')} ${response?.statusText ?? ''}\n\n${t('Response:')} ${body}\n\n`,
      options: [
        {
          label: t('Troubleshoot'),
          url: 'https://craftcms.com/knowledge-base/failed-updates',
        },
        {label: t('Send for help'), email: 'support@craftcms.com'},
      ],
    });

    // Take the site out of maintenance mode
    void actionClient
      .post(`${this.actionPrefix}/finish`, {data: this.#data})
      .catch(() => {});
  }
}

function parseStatus(status: string): string {
  return `<p>${escapeHtml(status)
    .replace(/\n{2,}/g, '</p><p>')
    .replace(/\n/g, '<br>')
    .replace(/`(.*?)`/g, '<code>$1</code>')}</p>`;
}

function emailLink(state: UpdaterState, option: UpdaterOption): string {
  let body = 'Describe what happened here.';
  if (state.errorDetails) {
    body += `\n\n-----------------------------------------------------------\n\n${state.errorDetails}`;
  }

  return `mailto:${option.email}?subject=${encodeURIComponent(option.subject || 'Craft update failure')}&body=${encodeURIComponent(body)}`;
}

/** Starts the updater on the page that has one. */
export function bootUpdater(): void {
  const root = document.querySelector<HTMLElement>('[data-updater]');
  const graphic = root?.querySelector<HTMLElement>('#graphic');
  const status = root?.querySelector<HTMLElement>('#status');

  if (!root || !graphic || !status) {
    return;
  }

  new Updater(root.dataset.updater ?? '', graphic, status).setState(
    JSON.parse(root.dataset.updaterState ?? '{}')
  );
}
