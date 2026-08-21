import type {
    ComboboxItem,
    ComboboxOption,
} from '@craftcms/ui/components/combobox/combobox';
import CraftCombobox from '@craftcms/ui/components/combobox/combobox';
import {property} from 'lit/decorators.js';
import {openSlideout} from '@/common/slideouts';

type FilesystemSaveData = {
    filesystem: {
        name: string;
        handle: string;
    };
};

export default class CraftFilesystemSelect extends CraftCombobox {
    @property({type: String, attribute: 'create-url'}) createUrl = '';

    private creating = false;
    private listener?: AbortController;

    override connectedCallback(): void {
        super.connectedCallback();
        this.listener?.abort();
        this.listener = new AbortController();
        this.addEventListener('model-value-changed', this.onModelValueChanged, {
            signal: this.listener.signal,
        });
    }

    override disconnectedCallback(): void {
        this.listener?.abort();
        super.disconnectedCallback();
    }

    private onModelValueChanged = (event: Event): void => {
        if (
            (event as CustomEvent).detail?.initialize ||
            this.modelValue !== '__add__' ||
            this.creating
        ) {
            return;
        }

        event.stopImmediatePropagation();

        if (!this.createUrl) {
            throw new Error('Filesystem select create URL is required.');
        }

        this.creating = true;

        queueMicrotask(() => {
            this.modelValue = '';
            this._inputNode.value = '';
            this._notifyModelValueChanged();
        });

        void openSlideout(this.createUrl, {
            opener: this,
            onSaved: ({data}) => {
                const filesystem = (data as FilesystemSaveData).filesystem;
                const option = {
                    label: filesystem.name,
                    value: filesystem.handle,
                    data: {hint: filesystem.handle},
                } satisfies ComboboxOption;

                this.options = this.options.map((item) =>
                    insertBeforeCreateOption(item, option)
                );
                void this.updateComplete.then(() => {
                    const selectedOption = Array.from(
                        this._listboxNode.querySelectorAll('craft-option')
                    ).find(
                        (item) =>
                            String(item.choiceValue) === String(option.value)
                    );

                    if (!selectedOption) {
                        throw new Error(
                            'Created filesystem option was not rendered.'
                        );
                    }

                    this.modelValue = option.value;
                    this._setTextboxValue(
                        this._getTextboxValueFromOption(selectedOption)
                    );
                    this._notifyModelValueChanged();
                });
            },
        }).finally(() => {
            this.creating = false;
        });
    };
}

function insertBeforeCreateOption(
    item: ComboboxItem,
    option: ComboboxOption
): ComboboxItem {
    if (item.type !== 'optgroup') {
        return item;
    }

    const actionIndex = item.options.findIndex(
        ({value}) => value === '__add__'
    );

    return actionIndex === -1
        ? item
        : {
              ...item,
              options: [
                  ...item.options.slice(0, actionIndex),
                  option,
                  ...item.options.slice(actionIndex),
              ],
          };
}

if (!customElements.get('craft-filesystem-select')) {
    customElements.define('craft-filesystem-select', CraftFilesystemSelect);
}

declare global {
    interface HTMLElementTagNameMap {
        'craft-filesystem-select': CraftFilesystemSelect;
    }
}
