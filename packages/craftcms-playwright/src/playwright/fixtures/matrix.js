class Matrix {
  constructor() {
    this.blockActionBtnLocator = ' > .actions > .menubtn.action-btn';
    this.cardActionBtnLocator = '.card-actions > .action-btn';
  }

  async getBlockActionMenuId(element) {
    return element
      .locator(this.blockActionBtnLocator)
      .getAttribute('aria-controls');
  }

  async getCardActionMenuId(element) {
    return element
      .locator(this.cardActionBtnLocator)
      .getAttribute('aria-controls');
  }

  async openBlockActionMenu(element) {
    element.locator(this.blockActionBtnLocator).click();
  }

  async openCardActionMenu(element) {
    element.locator(this.cardActionBtnLocator).click();
  }

  async openNestedElementIndexActionMenu(page, matrixContainerLocator) {
    page
      .locator(matrixContainerLocator + '-actions-container')
      .getByTitle('Actions')
      .click();
  }

  getElementActionElement(element, actionsMenuId, actionName) {
    return element
      .locator('#' + actionsMenuId)
      .getByRole('button', {name: actionName, exact: true});
  }

  getElementIndexActionElement(element, matrixContainerLocator, actionName) {
    return element
      .getByRole('option', {name: actionName})
      .locator(
        matrixContainerLocator +
          '-craft-elements-actions-' +
          actionName +
          '-actiontrigger'
      );
  }

  getNewNestedEntryBtn(page) {
    return page.locator('#content').getByRole('button', {name: 'New entry'});
  }

  getEditNestedEntryBtn(page, id) {
    return page.locator('#' + id).getByRole('button', {name: 'Edit entry'});
  }
}

module.exports = {Matrix};
