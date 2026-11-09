/** global: Craft */
/** global: Garnish */

/**
 * Trigger settings for the built-in element actions, keyed by the
 * `legacyTrigger.handler` the server sends with each action's config.
 * Each factory receives the rest of `legacyTrigger`.
 */
const handlers = {
  delete: ({withDescendants, hardDelete, confirmationMessage}) => ({
    validateSelection: (selectedItems, elementIndex) =>
      everyElement(selectedItems, (element) =>
        Garnish.hasAttr(element, 'data-deletable')
      ) && elementIndex.settings.canDeleteElements(selectedItems),
    activate: (selectedItems, elementIndex) =>
      deleteElements(selectedItems, elementIndex, {
        withDescendants,
        hardDelete,
        confirmationMessage,
      }),
  }),

  deleteAssets: ({hardDelete, confirmationMessage}) => ({
    requireId: false,
    validateSelection: (selectedItems, elementIndex) =>
      everyElement(selectedItems, (element) => {
        if (!Garnish.hasAttr(element, 'data-is-folder')) {
          return Garnish.hasAttr(element, 'data-deletable');
        }

        // Only one folder at a time
        if (selectedItems.length !== 1) {
          return false;
        }

        const sourcePath = element.data('source-path') || [];
        return (
          sourcePath.length !== 0 &&
          !!sourcePath[sourcePath.length - 1].canDelete
        );
      }) && elementIndex.settings.canDeleteElements(selectedItems),
    activate: async (selectedItems, elementIndex) => {
      const element = selectedItems.find('.element:first');

      if (Garnish.hasAttr(element, 'data-is-folder')) {
        const sourcePath = element.data('source-path');
        await elementIndex.deleteFolder(sourcePath[sourcePath.length - 1]);
        elementIndex.updateElements();
        return;
      }

      await deleteElements(selectedItems, elementIndex, {
        hardDelete,
        confirmationMessage,
      });
    },
  }),

  duplicate: ({attribute}) => ({
    validateSelection: (selectedItems, elementIndex) =>
      everyElement(selectedItems, (element) =>
        Garnish.hasAttr(element, attribute)
      ) && elementIndex.settings.canDuplicateElements(selectedItems),
    beforeActivate: async (selectedItems, elementIndex) => {
      await elementIndex.onBeforeDuplicateElements(selectedItems);
    },
    afterActivate: async (selectedItems, elementIndex) => {
      await elementIndex.onDuplicateElements(selectedItems);
    },
  }),

  deleteForSite: () => ({
    validateSelection: (selectedItems, elementIndex) =>
      everyElement(selectedItems, (element) =>
        Garnish.hasAttr(element, 'data-deletable-for-site')
      ) && elementIndex.settings.canDeleteElements(selectedItems),
  }),

  restore: ({attribute}) => ({
    validateSelection: (selectedItems) =>
      everyElement(selectedItems, (element) =>
        Garnish.hasAttr(element, attribute)
      ),
  }),

  setStatus: () => ({
    validateSelection: (selectedItems) =>
      everyElement(
        selectedItems,
        (element) =>
          Garnish.hasAttr(element, 'data-savable') &&
          !Garnish.hasAttr(element, 'data-disallow-status')
      ),
  }),

  suspendUsers: ({currentUserId}) => ({
    bulk: true,
    validateSelection: (selectedItems) =>
      everyElement(
        selectedItems,
        (element) =>
          Garnish.hasAttr(element, 'data-can-suspend') &&
          !Garnish.hasAttr(element, 'data-suspended') &&
          element.data('id') != currentUserId
      ),
  }),

  unsuspendUsers: () => ({
    bulk: true,
    validateSelection: (selectedItems) =>
      everyElement(
        selectedItems,
        (element) =>
          Garnish.hasAttr(element, 'data-can-suspend') &&
          Garnish.hasAttr(element, 'data-suspended')
      ),
  }),
};

function everyElement(selectedItems, test) {
  for (let i = 0; i < selectedItems.length; i++) {
    if (!test(selectedItems.eq(i).find('.element'))) {
      return false;
    }
  }

  return true;
}

async function deleteElements(selectedItems, elementIndex, settings) {
  await elementIndex.onBeforeDeleteElements(selectedItems);
  elementIndex.setIndexBusy();
  const elementIds = elementIndex.getSelectedElementIds();

  new Craft.ElementDeletionManager(elementIndex.elementType, elementIds, {
    siteId: elementIndex.siteId,
    ownerId: elementIndex.settings.criteria?.ownerId,
    ...settings,
    onLoadBlockers: () => {
      elementIndex.setIndexAvailable();
    },
    onSuccess: async () => {
      elementIndex.updateElements(true, true);
      await elementIndex.onDeleteElements(selectedItems);
    },
  });
}

/**
 * Creates the triggers for the actions that came with a built-in trigger.
 *
 * @param {Object[]} actions
 */
export function createActionTriggers(actions) {
  for (const action of actions) {
    const {handler, ...params} = action.legacyTrigger ?? {};

    if (!handlers[handler]) {
      continue;
    }

    new Craft.ElementActionTrigger({
      type: action.type,
      triggerId: action.triggerId,
      ...handlers[handler](params),
    });
  }
}
