import {BaseElementSelectInput} from '@/modules/element-select-input/base-element-select-input';

declare const Craft: any;
declare const Garnish: any;
declare const $: any;

/**
 * AssetSelectInput — a port of `Craft.AssetSelectInput` onto
 * {@link BaseElementSelectInput}. Extends the base element select with:
 * - An upload button + hidden file input wired to `Craft.createUploader`
 * - A `ProgressBar` overlay during uploads
 * - Shift-Space keyboard shortcut to open the file preview modal
 *
 * Notes:
 * - `keydown` on the elements container is a native event — `addListener` works.
 * - Uploader events (`fileuploadstart` etc.) are bound directly on the options
 *   object passed to `Craft.createUploader`, not via Garnish.
 * - The upload button's `click` handler uses jQuery `.on()` because the file
 *   input is replaced after each upload (see the comment in `_attachUploader`).
 */
export class AssetSelectInput extends BaseElementSelectInput {
    $uploadBtn: any = null;
    $fileInput: any = null;
    uploader: any = null;
    progressBar: any = null;
    openPreviewTimeout: ReturnType<typeof setTimeout> | null = null;

    constructor(settings?: any) {
        super(settings);
        if (new.target === AssetSelectInput) {
            this.init(settings);
        }
    }

    override init(...initArgs: any[]): void {
        super.init(...initArgs);

        if (this.settings.canUpload) {
            this._attachUploader();
        }

        this.updateAddElementsBtn();

        this.addListener(this.$elementsContainer[0], 'keydown', ((
            ev: KeyboardEvent
        ) => {
            if (ev.keyCode === Garnish.SPACE_KEY && ev.shiftKey) {
                this.openPreview();
                ev.stopPropagation();
            }
        }) as any);
    }

    override elementSelectSettings(): any {
        return Object.assign(super.elementSelectSettings(), {
            makeFocusable: true,
        });
    }

    clearOpenPreviewTimeout(): void {
        if (this.openPreviewTimeout) {
            clearTimeout(this.openPreviewTimeout);
            this.openPreviewTimeout = null;
        }
    }

    openPreview($element?: any): void {
        if (Craft.PreviewFileModal.openInstance) {
            Craft.PreviewFileModal.openInstance.hide();
        } else {
            if (!$element) {
                $element = this.$elements
                    .filter(':focus')
                    .add(this.$elements.has(':focus'));
            }
            if ($element.length) {
                Craft.PreviewFileModal.showForAsset(
                    $element,
                    this.elementSelect
                );
            }
        }
    }

    _attachUploader(): void {
        this.progressBar = new Craft.ProgressBar(
            $('<div class="progress-shade"></div>').appendTo(this.$container)
        );

        if (this.$addElementBtn) {
            this.$uploadBtn = $('<button/>', {
                type: 'button',
                class: 'btn dashed',
                'data-icon': 'upload',
                'aria-label':
                    this.settings.limit == 1
                        ? Craft.t('app', 'Upload a file')
                        : Craft.t('app', 'Upload files'),
                'aria-describedby': this.settings.describedBy,
                text:
                    this.settings.limit == 1
                        ? Craft.t('app', 'Upload a file')
                        : Craft.t('app', 'Upload files'),
            }).insertAfter(this.$addElementBtn);

            this.$fileInput = $('<input/>', {
                type: 'file',
                class: 'hidden',
                multiple: this.settings.limit != 1,
            }).insertAfter(this.$uploadBtn);

            // Trigger resize in case the field is inside an element editor HUD
            $(window).trigger('resize');
        }

        const options: any = {
            dropZone: this.$container,
            fileInput: this.$fileInput,
        };

        if (typeof this.settings.criteria.kind !== 'undefined') {
            options.allowedKinds = this.settings.criteria.kind;
        }

        options.canAddMoreFiles = this.canAddMoreFiles.bind(this);

        options.events = {};
        options.events.fileuploadstart = this._onUploadStart.bind(this);
        options.events.fileuploadprogressall =
            this._onUploadProgress.bind(this);
        options.events.fileuploaddone = this._onUploadComplete.bind(this);
        options.events.fileuploadfail = this._onUploadFailure.bind(this);

        this.uploader = Craft.createUploader(
            this.settings.fsType,
            this.$container,
            options
        );

        const params: any = {fieldId: this.settings.fieldId};
        if (this.settings.sourceElementId) {
            params.elementId = this.settings.sourceElementId;
        }
        if (this.settings.criteria.siteId) {
            params.siteId = this.settings.criteria.siteId;
        }
        this.uploader.setParams(params);

        if (this.$uploadBtn) {
            // We can't store a reference to the file input — it's replaced with a new
            // input after each upload: https://stackoverflow.com/a/25034721/1688568
            this.$uploadBtn.on('click', () => {
                this.$uploadBtn.next('input[type=file]').trigger('click');
            });
        }
    }

    override enableAddElementsBtn(): void {
        if (this.$uploadBtn) {
            this.$uploadBtn.removeClass('hidden');
        }
        super.enableAddElementsBtn();
    }

    override disableAddElementsBtn(): void {
        if (this.$uploadBtn) {
            this.$uploadBtn.addClass('hidden');
        }
        super.disableAddElementsBtn();
    }

    selectUploadedFile(element: any): void {
        if (!this.canAddMoreElements()) return;

        const $newElement = element.$element;
        $newElement.appendTo(this.$elementsContainer);

        const margin = -($newElement.outerWidth() + 10);
        this.$addElementBtn.css('margin-' + Craft.left, margin + 'px');

        const animateCss: any = {};
        animateCss['margin-' + Craft.left] = 0;
        this.$addElementBtn.velocity(animateCss, 'fast');

        this.addElements($newElement);

        delete (this as any).modal;
    }

    _onUploadStart(): void {
        this.progressBar.$progressBar.css({
            top: Math.round(this.$container.outerHeight() / 2) - 6,
        });
        this.$container.addClass('uploading');
        this.progressBar.resetProgressBar();
        this.progressBar.showProgressBar();
    }

    _onUploadProgress(event: any, data: any = null): void {
        data = event instanceof CustomEvent ? event.detail : data;
        const progress = Math.round(
            Math.min(data.loaded / data.total, 1) * 100
        );
        this.progressBar.setProgressPercentage(progress);
    }

    _onUploadComplete(event: any, data: any = null): void {
        const result =
            event instanceof CustomEvent ? event.detail : data.result;

        Craft.sendActionRequest('POST', 'app/render-elements', {
            data: {
                elements: [
                    {
                        type: 'CraftCms\\Cms\\Asset\\Elements\\Asset',
                        id: result.assetId,
                        siteId: this.settings.criteria.siteId,
                        instances: [
                            {
                                context: 'field',
                                ui: [
                                    'list',
                                    'list-inline',
                                    'large',
                                    'thumbs',
                                ].includes(this.settings.viewMode)
                                    ? 'chip'
                                    : 'card',
                                size: ['large', 'thumbs'].includes(
                                    this.settings.viewMode
                                )
                                    ? 'large'
                                    : 'small',
                                showActionMenu: this.settings.showActionMenu,
                            },
                        ],
                    },
                ],
            },
        })
            .then(async ({data}: any) => {
                const elementInfo = Craft.getElementInfo(
                    data.elements[result.assetId][0]
                );
                this.selectElements([elementInfo]);

                await Craft.appendHeadHtml(data.headHtml);
                await Craft.appendBodyHtml(data.bodyHtml);

                if (this.uploader.isLastUpload()) {
                    this.progressBar.hideProgressBar();
                    this.$container.removeClass('uploading');
                    this.$container.trigger('change');
                }
            })
            .catch((error: any) => {
                if (error?.response) {
                    Craft.cp.displayError(error.response.data.message);
                } else {
                    Craft.cp.displayError();
                    throw error;
                }
            });

        Craft.cp.runQueue();
    }

    _onUploadFailure(event: any, data: any = null): void {
        const response =
            event instanceof CustomEvent
                ? event.detail
                : data?.jqXHR?.responseJSON;

        let {message, filename} = response || {};
        const {errors} = response || {};

        filename = filename || data?.files?.[0]?.name;

        const errorMessages: string[] = errors
            ? (Object.values(errors).flat() as string[])
            : [];

        if (!message) {
            if (errorMessages.length) {
                message = errorMessages.join('\n');
            } else if (filename) {
                message = Craft.t('app', 'Upload failed for “{filename}”.', {
                    filename,
                });
            } else {
                message = Craft.t('app', 'Upload failed.');
            }
        }

        Craft.cp.displayError(message);
        this.progressBar.hideProgressBar();
        this.$container.removeClass('uploading');
    }

    canAddMoreFiles(slotsTaken: number): boolean {
        return (
            !this.settings.limit ||
            this.$elements.length + slotsTaken < this.settings.limit
        );
    }
}
