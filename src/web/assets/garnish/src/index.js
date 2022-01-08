import Garnish from './Garnish.js';

/**
 * @deprecated
 * Assign these here to avoid circular dependencies
 */
 Object.assign(Garnish, {
  Menu: Garnish.CustomSelect,
  escManager: new Garnish.EscManager(),
  shortcutManager: new Garnish.ShortcutManager(),
});

export default Garnish;
