import {ControllerElement} from '@/common/web-components';
import {AssetSelectInput} from '@/modules/asset-select-input/asset-select-input';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';

declare const Craft: any;

type ElementSelectInputConstructor = new (
    settings?: Record<string, any>
) => BaseElementSelectInput;

export default class CraftElementSelectInput extends ControllerElement<BaseElementSelectInput> {
    protected readonly rootSelector = ':scope > .elements';
    protected readonly defaultInputClass: ElementSelectInputConstructor =
        BaseElementSelectInput;

    protected create(): BaseElementSelectInput {
        Craft.initUiElements(this);

        const Input = this.#inputClass();

        return new Input({...this.jsonAttr('settings'), id: this.id});
    }

    get selectedIds(): number[] {
        return (
            this.instance?.getSelectedElementIds() ??
            [
                ...this.querySelectorAll<HTMLElement>(
                    ':scope > .elements .element'
                ),
            ]
                .map((element) => Number(element.dataset.id))
                .filter(Number.isFinite)
        );
    }

    #inputClass(): ElementSelectInputConstructor {
        const name = this.getAttribute('input-class');
        if (!name) {
            return this.defaultInputClass;
        }

        const Input = this.#globalInputClass(name);

        if (typeof Input !== 'function') {
            throw new Error(`Unknown element select input class [${name}].`);
        }

        return Input;
    }

    #globalInputClass(name: string): ElementSelectInputConstructor | undefined {
        return name
            .split('.')
            .reduce<any>((value, segment) => value?.[segment], window);
    }
}

export class CraftEntrySelectInput extends CraftElementSelectInput {
    protected override readonly defaultInputClass = EntrySelectInput;
}

export class CraftAssetSelectInput extends CraftElementSelectInput {
    protected override readonly defaultInputClass = AssetSelectInput;
}

declare global {
    interface HTMLElementTagNameMap {
        'craft-asset-select-input': CraftAssetSelectInput;
        'craft-element-select-input': CraftElementSelectInput;
        'craft-entry-select-input': CraftEntrySelectInput;
    }
}
