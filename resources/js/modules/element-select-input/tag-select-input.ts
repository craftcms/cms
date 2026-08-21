import {bod, DOWN_KEY, RETURN_KEY, UP_KEY} from '@craftcms/garnish';
import {BaseElementSelectInput} from './base-element-select-input';

declare const Craft: any;
declare const Garnish: any;
declare const $: any;

const DEFAULTS = {
    tagGroupId: null as number | null,
};

/**
 * TagSelectInput — a port of `Craft.TagSelectInput` onto
 * {@link BaseElementSelectInput}. Replaces the "add element" button with an
 * inline text input that searches for existing tags or offers to create a new
 * one. Uses `Garnish.Menu` (aliased to `CustomSelect` by the compat layer) for
 * the search dropdown.
 *
 * Notes:
 * - The search input uses addListener for native events (input, keydown, focus,
 *   blur). The mousedown listener on the menu uses addListener too (native).
 * - `Garnish.Menu` is used rather than importing `CustomSelect` directly, to
 *   remain consistent with the legacy code and to rely on the compat alias.
 */
export class TagSelectInput extends BaseElementSelectInput {
    static override defaults: Record<string, any> = DEFAULTS;

    override searchTimeout: ReturnType<typeof setTimeout> | null = null;
    override searchMenu: any = null;

    override $container: any = null;
    override $elementsContainer: any = null;
    override $elements: any = null;
    $addTagInput: any = null;
    override $spinner: any = null;

    _ignoreBlur = false;

    constructor(...restArgs: any[]) {
        let settings = restArgs[0];
        // Legacy compat: positional arguments (id, name, tagGroupId, sourceElementId)
        if (!$.isPlainObject(settings)) {
            const argNames = ['id', 'name', 'tagGroupId', 'sourceElementId'];
            const normalized: Record<string, any> = {};
            for (let i = 0; i < argNames.length; i++) {
                if (typeof restArgs[i] !== 'undefined') {
                    normalized[argNames[i]!] = restArgs[i];
                } else {
                    break;
                }
            }
            settings = normalized;
        }

        settings = Object.assign({}, TagSelectInput.defaults, settings);

        super(settings);
        if (new.target === TagSelectInput) {
            this.init(settings);
        }
    }

    override init(settings?: any): void {
        super.init(settings);

        this.$addTagInput = this.$container.children('.add').children('.text');
        this.$spinner = this.$addTagInput.next();

        this.addListener(this.$addTagInput[0], 'input', () => {
            if (this.searchTimeout) clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(this.searchForTags.bind(this), 500);
        });

        this.addListener(this.$addTagInput[0], 'keydown', ((
            ev: KeyboardEvent
        ) => {
            if (ev.keyCode === RETURN_KEY) {
                ev.preventDefault();
            }

            switch (ev.keyCode) {
                case RETURN_KEY: {
                    ev.preventDefault();
                    if (this.searchMenu) {
                        this.selectTag(
                            this.searchMenu.$options.filter('.hover')
                        );
                    }
                    return;
                }
                case DOWN_KEY: {
                    ev.preventDefault();
                    if (this.searchMenu) {
                        const $hover =
                            this.searchMenu.$options.filter('.hover');
                        if ($hover.length) {
                            const $next = $hover
                                .parent()
                                .nextAll()
                                .find('.menu-item:not(.disabled)')
                                .first();
                            if ($next.length) this.focusOption($next);
                        } else {
                            this.focusOption(this.searchMenu.$options.eq(0));
                        }
                    }
                    return;
                }
                case UP_KEY: {
                    ev.preventDefault();
                    if (this.searchMenu) {
                        const $hover =
                            this.searchMenu.$options.filter('.hover');
                        if ($hover.length) {
                            const $prev = $hover
                                .parent()
                                .prevAll()
                                .find('.menu-item:not(.disabled)')
                                .last();
                            if ($prev.length) this.focusOption($prev);
                        } else {
                            this.focusOption(
                                this.searchMenu.$options.eq(
                                    this.searchMenu.$options.length - 1
                                )
                            );
                        }
                    }
                    return;
                }
            }
        }) as any);

        this.addListener(this.$addTagInput[0], 'focus', () => {
            if (this.searchMenu) this.searchMenu.show();
        });

        this.addListener(this.$addTagInput[0], 'blur', () => {
            if (this._ignoreBlur) {
                this._ignoreBlur = false;
                return;
            }
            setTimeout(() => {
                if (this.searchMenu) this.searchMenu.hide();
            }, 1);
        });
    }

    get fieldName(): string | null {
        const $legend = this.$container.closest('fieldset').find('legend');
        if ($legend.length === 0) return null;
        return $legend[0].innerText;
    }

    override focusOption($option: any): void {
        this.searchMenu.$options.removeClass('hover');
        this.searchMenu.$ariaOptions.attr('aria-selected', 'false');
        $option.addClass('hover');
        this.$addTagInput.attr(
            'aria-activedescendant',
            $option.parent('li').attr('id')
        );
    }

    // Tags have no "add" button — the text input IS the add mechanism.
    override getAddElementsBtn(): any {
        return $([]);
    }

    override getElementSortAxis(): string | null {
        if (this.$container.parents('.inline-editing').length === 1) {
            return 'y';
        }
        return 'x';
    }

    /** Tag-specific search that queries tags rather than generic elements. */
    override async searchForTags(): Promise<void> {
        if (this.searchMenu) this.killSearchMenu();

        const val = this.$addTagInput.val();

        if (!val) {
            this.$spinner.addClass('hidden');
            return;
        }

        this.$spinner.removeClass('hidden');
        Craft.cp.announce(Craft.t('app', 'Loading'));

        const excludeIds: number[] = [];
        for (let i = 0; i < this.$elements.length; i++) {
            const id = $(this.$elements[i]).data('id');
            if (id) excludeIds.push(id);
        }

        if (
            this.settings.sourceElementId &&
            !this.settings.allowSelfRelations
        ) {
            excludeIds.push(this.settings.sourceElementId);
        }

        try {
            const response = await Craft.sendActionRequest(
                'POST',
                'tags/search-for-tags',
                {
                    data: {
                        search: val,
                        tagGroupId: this.settings.tagGroupId,
                        excludeIds,
                    },
                }
            );

            if (this.searchMenu) this.killSearchMenu();
            this.$spinner.addClass('hidden');
            Craft.cp.announce(Craft.t('app', 'Loading complete'));

            const fieldName = this.fieldName;
            const $menu = $('<div class="menu tagmenu"/>');
            if (fieldName !== null) $menu.attr('aria-label', fieldName);
            $menu.appendTo($(bod));
            const $ul = $('<ul/>').appendTo($menu);

            for (const tag of response.data.tags) {
                const $li = $('<li/>').appendTo($ul);
                const label = `${Craft.t('app', 'Existing {type}', {type: Craft.t('app', 'Tag')})}: ${tag.title}`;
                $li.attr('aria-label', label);

                $('<div class="menu-item" data-icon="tag"/>')
                    .appendTo($li)
                    .text(tag.title)
                    .data('id', tag.id)
                    .addClass(tag.exclude ? 'disabled' : '');
            }

            if (!response.data.exactMatch) {
                const $li = $('<li/>').appendTo($ul);
                const label = `${Craft.t('app', 'Create {type}', {type: Craft.t('app', 'Tag')})}: ${val}`;
                $li.attr('aria-label', label);

                $('<div class="menu-item" data-icon="plus"/>')
                    .appendTo($li)
                    .text(val);
            }

            $ul.find('.menu-item:not(.disabled):first').addClass('hover');

            // Use Garnish.Menu which the compat layer aliases to CustomSelect.
            this.searchMenu = new Garnish.Menu($menu, {
                anchor: this.$addTagInput,
                onOptionSelect: this.selectTag.bind(this),
            });

            this.$addTagInput.attr('aria-controls', this.searchMenu.menuId);

            this.searchMenu.on('show', () => {
                this.$addTagInput.attr('aria-expanded', 'true');
                this.focusSelectedOption();
            });

            this.searchMenu.on('hide', () => {
                this.$addTagInput.attr('aria-expanded', 'false');
                this.$addTagInput.removeAttr('aria-activedescendant');
            });

            this.addListener($menu[0], 'mousedown', () => {
                this._ignoreBlur = true;
            });

            this.searchMenu.show();
        } catch {
            if (this.searchMenu) this.killSearchMenu();
            this.$spinner.addClass('hidden');
            Craft.cp.announce(Craft.t('app', 'Loading complete'));
        }
    }

    override focusSelectedOption(): void {
        const $option = this.searchMenu.$options.filter('.hover:first');
        if ($option.length) {
            this.focusOption($option);
        } else {
            this.focusFirstOption();
        }
    }

    override focusFirstOption(): void {
        this.focusOption(this.searchMenu.$options.first());
    }

    selectTag(option: any): void {
        const $option = $(option);
        if ($option.hasClass('disabled')) return;

        const $li = $('<li/>');
        if (this.settings.defaultPlacement === 'beginning') {
            $li.prependTo(this.$elementsContainer);
        } else {
            $li.appendTo(this.$elementsContainer);
        }

        const id = $option.data('id');
        const title = $option.text();

        const $element = $('<div/>', {
            class: 'chip element small removable',
            'data-id': id,
            'data-site-id': this.settings.targetSiteId,
            'data-label': title,
            'data-editable': '1',
        }).appendTo($li);

        const $chipContent = $('<div/>', {class: 'chip-content'}).appendTo(
            $element
        );
        const $titleContainer = $('<craft-truncate/>', {
            class: 'label',
        }).appendTo($chipContent);

        $('<span/>', {class: 'label-link', text: title}).appendTo(
            $titleContainer
        );
        $('<div/>', {class: 'chip-actions'}).appendTo($chipContent);

        const $input = $('<input/>', {
            type: 'hidden',
            name: this.settings.name + '[]',
            value: id,
        }).appendTo($chipContent);

        this.$elements = this.$elements.add($element);
        this.addElements($element);

        this.killSearchMenu();
        this.$addTagInput.val('');
        this.$addTagInput.focus();

        if (!id) {
            $element.addClass('loading disabled');

            Craft.sendActionRequest('POST', 'tags/create-tag', {
                data: {groupId: this.settings.tagGroupId, title},
            })
                .then((response: any) => {
                    $element.attr('data-id', response.data.id);
                    $input.val(response.data.id);
                    $element.removeClass('loading disabled');
                })
                .catch((e: any) => {
                    this.removeElement($element);
                    Craft.cp.displayError(e?.response?.data?.message);
                });
        }
    }

    override killSearchMenu(): void {
        this.searchMenu.hide();
        this.searchMenu.destroy();
        this.searchMenu = null;
    }
}
