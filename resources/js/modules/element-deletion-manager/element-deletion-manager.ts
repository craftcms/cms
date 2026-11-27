import {t} from '@craftcms/ui/utilities/translate';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {elementTypeName} from '@/common/craft/element-types';
import {showError, showSuccess} from '@/modules/messages/messages';
import {
  Blocker,
  DeletionBlockersModal,
} from '@/modules/deletion-blockers-modal/deletion-blockers-modal';

const DEFAULTS = {
  siteId: null,
  ownerId: null,
  withDescendants: false,
  hardDelete: false,
  confirmationMessage: null,
  onLoadBlockers: () => {},
  onSuccess: () => {},
  onCancel: () => {},
};

/**
 * Fetches elements' deletion blockers. If there are none, it asks the user to
 * confirm and deletes the elements. Otherwise it lists the blockers in a
 * {@link DeletionBlockersModal}. The element index and PHP code create it, so
 * `globals.ts` registers it on `window.Craft`.
 */
export class ElementDeletionManager extends DeletionBlockersModal {
  elementType: any = null;
  elementIds: any = null;
  hardDelete = false;
  succeeded = false;

  constructor(elementType?: any, elementIds?: any, settings?: any) {
    super();
    if (new.target === ElementDeletionManager) {
      this.init(elementType, elementIds, settings);
    }
  }

  override init(elementType: any, elementIds: any, settings: any): void {
    this.elementType = elementType;
    this.elementIds = elementIds;
    this.setSettings(settings, DEFAULTS);
    this.blockers = [];
    void this.run();
  }

  override async run(): Promise<boolean> {
    const data = await this.loadBlockers();

    this.settings.onLoadBlockers();

    if (!data.blockers.length) {
      await this.confirmAndDelete(data.totalElements);
      return true;
    }

    await this.showModal(data);
    return true;
  }

  override getBlockersAction(): string {
    return 'delete-elements/deletion-blockers';
  }

  override getParams(): any {
    return {
      elementType: this.elementType,
      elementIds: this.elementIds,
      siteId: this.settings.siteId,
      ownerId: this.settings.ownerId,
      withDescendants: this.settings.withDescendants,
      hardDelete: this.settings.hardDelete,
    };
  }

  override renderHeading(heading: HTMLElement, data: any): void {
    heading.innerHTML = t(
      'Before deleting {label}, please address the following {numBlockers, plural, =1{issue} other{issues}}:',
      {numBlockers: data.blockers.length, label: data.elementPreview}
    );
  }

  override getSubmitLabel(): string {
    return t('Delete {type}', {
      type: elementTypeName(this.elementType, this.elementIds.length !== 1),
    });
  }

  override async onSubmit(data: any): Promise<void> {
    // The blockers modal already asked the user, so only confirm again for a
    // custom message or a permanent deletion
    await this.confirmAndDelete(
      data.totalElements,
      !this.settings.confirmationMessage && !this.settings.hardDelete
    );
  }

  override onHide(): void {
    if (!this.succeeded) {
      this.settings.onCancel();
    }
  }

  async confirmAndDelete(totalElements: any, confirmed = false): Promise<void> {
    if (!confirmed && !confirm(this.getConfirmationMessage(totalElements))) {
      this.settings.onCancel();
      return;
    }

    if (this.submitButton) {
      this.submitButton.loading = true;
    }
    let data: any = null;
    try {
      const response = await actionClient.post(
        'delete-elements/delete',
        this.getParams()
      );
      data = response.data;
    } catch {
      return;
    } finally {
      if (this.submitButton) {
        this.submitButton.loading = false;
      }
    }

    this.succeeded = true;
    this.settings.onSuccess();
    this.hideModal();

    if (data) {
      if (data.showAsFailure) {
        showError(data.message);
      } else {
        showSuccess(data.message);
      }
    }
  }

  getConfirmationMessage(totalElements: any): string {
    if (this.settings.confirmationMessage) {
      return this.settings.confirmationMessage;
    }

    const typeName = elementTypeName(this.elementType, totalElements !== 1);

    if (this.settings.hardDelete) {
      return t(
        'Are you sure you want to permanently delete {numElements, plural, =1{this} other{these}} {type}?',
        {type: typeName, numElements: totalElements}
      );
    }

    return t(
      'Are you sure you want to delete {numElements, plural, =1{this} other{these}} {type}?',
      {type: typeName, numElements: totalElements}
    );
  }
}

// Lets legacy code create blockers with `new Craft.ElementDeletionManager.Blocker()`.
Object.assign(ElementDeletionManager, {Blocker});
