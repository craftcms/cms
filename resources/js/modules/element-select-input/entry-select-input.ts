import {BaseElementSelectInput} from './base-element-select-input';

declare const Craft: any;

/**
 * EntrySelectInput — a port of `Craft.EntrySelectInput` onto
 * {@link BaseElementSelectInput}. Adds the ability to create new entries inline:
 * posts to `entries/create`, then opens the new entry in an element editor
 * slideout for the user to complete before the entry is added to the field.
 */
export class EntrySelectInput extends BaseElementSelectInput {
    constructor(settings?: any) {
        super(settings);
        if (new.target === EntrySelectInput) {
            this.init(settings);
        }
    }

    get section(): any {
        if (!this.settings.sectionId) return null;
        return Craft.publishableSections.find(
            (s: any) => s.id === this.settings.sectionId
        );
    }

    override canCreateElements(): boolean {
        return !!this.section;
    }

    override async createElement(title: string): Promise<number | null> {
        const response = await Craft.sendActionRequest(
            'POST',
            'entries/create',
            {
                data: {
                    siteId: this.settings.criteria.siteId,
                    section: this.section.handle,
                    authorId: Craft.userId,
                    title,
                },
            }
        );

        const entry = response.data.entry;

        try {
            await this.showElementEditor(entry);
        } catch {
            return null;
        }

        return entry.id;
    }

    showElementEditor(entry: any): Promise<void> {
        return new Promise((resolve, reject) => {
            const slideout = Craft.createElementEditor(
                'CraftCms\\Cms\\Entry\\Elements\\Entry',
                {
                    siteId: this.settings.criteria.siteId,
                    elementId: entry.id,
                    draftId: entry.draftId,
                    params: {fresh: 1},
                }
            );

            let submitted = false;

            slideout.on('submit', () => {
                submitted = true;
                resolve();
            });

            slideout.on('close', () => {
                if (!submitted) {
                    reject();
                }
            });
        });
    }
}
