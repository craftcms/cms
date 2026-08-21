import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/empty/empty';
import '@craftcms/ui/components/spinner/spinner';
import {t} from '@craftcms/ui';

class CraftContentBlockInput extends HTMLElement {
    private listener?: AbortController;

    connectedCallback(): void {
        this.listener?.abort();
        this.listener = new AbortController();
        this.addEventListener('click', this.onClick, {
            signal: this.listener.signal,
        });
    }

    disconnectedCallback(): void {
        this.listener?.abort();
    }

    private onClick = (event: Event): void => {
        const target = event.target as Element;

        if (target.closest('[data-content-block-add]')) {
            this.add();
        } else if (target.closest('[data-content-block-remove]')) {
            this.clear();
        }
    };

    private add(): void {
        const empty = this.querySelector('craft-empty');

        if (!empty) {
            return;
        }

        const pane = document.createElement('div');
        pane.className = 'pane';
        pane.dataset.contentBlock = '';
        const spinner = document.createElement('craft-spinner');
        spinner.setAttribute('label', t('Loading'));
        pane.append(spinner, this.removeButton());
        empty.replaceWith(pane);
        this.changed();
    }

    private clear(): void {
        const pane = this.querySelector('[data-content-block]');

        if (!pane) {
            return;
        }

        const empty = document.createElement('craft-empty');
        empty.setAttribute('label', this.getAttribute('empty-label')!);
        const add = document.createElement('craft-button');
        add.dataset.contentBlockAdd = '';
        add.setAttribute('icon', 'plus');
        add.textContent = this.getAttribute('add-label')!;
        empty.append(add);
        pane.replaceWith(empty);
        this.changed();
    }

    private removeButton(): HTMLElement {
        const button = document.createElement('craft-button');
        button.dataset.contentBlockRemove = '';
        button.setAttribute('icon', 'trash');
        button.textContent = this.getAttribute('clear-label')!;

        return button;
    }

    private changed(): void {
        this.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
    }
}

if (!customElements.get('craft-content-block-input')) {
    customElements.define('craft-content-block-input', CraftContentBlockInput);
}

declare global {
    interface HTMLElementTagNameMap {
        'craft-content-block-input': CraftContentBlockInput;
    }
}
