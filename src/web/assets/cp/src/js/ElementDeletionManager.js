/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

/**
 * Element Deletion Manager
 *
 * Shows the deletion blockers for elements, then deletes them.
 *
 * @since 5.10.0
 */
Craft.ElementDeletionManager = Craft.DeletionBlockersModal.extend(
  {
    elementType: null,
    elementIds: null,
    hardDelete: false,
    succeeded: false,

    init: function (elementType, elementIds, settings) {
      this.elementType = elementType;
      this.elementIds = elementIds;
      this.setSettings(settings, Craft.ElementDeletionManager.defaults);
      this.blockers = [];
      this.run();
    },

    run: async function () {
      const data = await this.loadBlockers();

      this.settings.onLoadBlockers();

      if (!data.blockers.length) {
        await this.confirmAndDelete(data.totalElements);
        return;
      }

      await this.showModal(data);
    },

    getBlockersAction: function () {
      return 'delete-elements/deletion-blockers';
    },

    getParams: function () {
      return {
        elementType: this.elementType,
        elementIds: this.elementIds,
        siteId: this.settings.siteId,
        ownerId: this.settings.ownerId,
        withDescendants: this.settings.withDescendants,
        hardDelete: this.settings.hardDelete,
      };
    },

    getHeadingHtml: function (data) {
      return Craft.t(
        'app',
        'Before deleting {label}, please address the following {numBlockers, plural, =1{issue} other{issues}}:',
        {
          numBlockers: data.blockers.length,
          label: data.elementPreview,
        }
      );
    },

    getSubmitLabel: function (data) {
      return Craft.t('app', 'Delete {type}', {
        type:
          this.elementIds.length === 1
            ? Craft.elementTypeNames[this.elementType][2]
            : Craft.elementTypeNames[this.elementType][3],
      });
    },

    onSubmit: async function (data) {
      // The blocker modal is the confirmation, unless there's a custom message or a permanent-deletion warning to show
      await this.confirmAndDelete(
        data.totalElements,
        !this.settings.confirmationMessage && !this.settings.hardDelete
      );
    },

    onHide: function () {
      if (!this.succeeded) {
        this.settings.onCancel();
      }
    },

    confirmAndDelete: async function (totalElements, confirmed = false) {
      if (!confirmed && !confirm(this.getConfirmationMessage(totalElements))) {
        this.settings.onCancel();
        return;
      }

      this.$submitBtn?.addClass('loading');
      let data = null;
      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'delete-elements/delete',
          {
            data: this.getParams(),
          }
        );
        data = response.data;
      } catch (e) {
        return;
      } finally {
        this.$submitBtn?.removeClass('loading');
      }

      this.succeeded = true;
      this.settings.onSuccess();
      this.modal?.hide();

      if (data) {
        if (data.showAsFailure) {
          Craft.cp.displayError(data.message);
        } else {
          Craft.cp.displaySuccess(data.message);
        }
      }
    },

    getConfirmationMessage: function (totalElements) {
      if (this.settings.confirmationMessage) {
        return this.settings.confirmationMessage;
      }

      const elementTypeName =
        totalElements === 1
          ? Craft.elementTypeNames[this.elementType][2]
          : Craft.elementTypeNames[this.elementType][3];

      if (this.settings.hardDelete) {
        return Craft.t(
          'app',
          'Are you sure you want to permanently delete {numElements, plural, =1{this} other{these}} {type}?',
          {
            type: elementTypeName,
            numElements: totalElements,
          }
        );
      }

      return Craft.t(
        'app',
        'Are you sure you want to delete {numElements, plural, =1{this} other{these}} {type}?',
        {
          type: elementTypeName,
          numElements: totalElements,
        }
      );
    },
  },
  {
    defaults: {
      siteId: null,
      ownerId: null,
      withDescendants: false,
      hardDelete: false,
      confirmationMessage: null,
      onLoadBlockers: $.noop,
      onSuccess: $.noop,
      onCancel: $.noop,
    },
  }
);

// Kept for backwards compatibility
Craft.ElementDeletionManager.Blocker = Craft.DeletionBlockersModal.Blocker;
