import Garnish from './Garnish.js';

const uiLayerManager = new Garnish.UiLayerManager();

/**
 * @deprecated
 * Assign these here to avoid circular dependencies
 */
Object.assign(Garnish, {
  escManager: new Garnish.EscManager(),
  uiLayerManager,
  /**
   * @deprecated Use uiLayerManager instead
   */
  shortcutManager: uiLayerManager,
});

export default Garnish;
