# Garnish Library Dependency Graph & jQuery Migration Analysis

## Executive Summary

This document provides a comprehensive mapping of the Garnish JavaScript library's internal module dependencies, jQuery coupling levels, and external API usage. It serves as a foundation for planning the jQuery → vanilla JavaScript migration.

**Key Findings:**
- **23 modules total** (excluding lib/ and index/entry files)
- **Core foundation cluster**: Base, BaseDrag, Base.js, Garnish.js
- **High jQuery coupling** (26+ uses): Select (41), DragSort (21), BaseDrag (28), HUD (15)
- **Most-used public APIs**: Base, Modal, DragSort, HUD, DisclosureMenu
- **Leaf modules** (easier to migrate first): EscManager, DragMove, CheckboxSelect, MultiFunctionBtn

---

## Module Inventory & Analysis

### Foundation Layer

#### 1. **lib/Base.js** - Prototypal Inheritance System
- **Lines:** 160 (relatively small)
- **jQuery Coupling:** 0 uses
- **Difficulty to Remove jQuery:** N/A (no jQuery)
- **Role:** Provides the `.extend()` mechanism for all class definitions; foundational metaprogramming
- **Key Exports:** `Base.extend()`, `Base.implement()`
- **Consumers:** All other modules that extend Base
- **Migration Notes:** This is pure JavaScript and requires no changes.

---

#### 2. **Base.js** - Garnish Base Class
- **Lines:** 193
- **jQuery Coupling:** 10 uses
  - `$.noop` (default callbacks)
  - `$.extend` (merge settings)
  - `$.trim` (string utilities)
  - `$.proxy` (function binding)
  - jQuery event triggering utilities
- **DOM Manipulation:** NO
- **Event Handling:** YES (custom events via `trigger()`, instance-level event handlers)
- **Difficulty to Remove jQuery:** **LOW**
  - `$.noop` → Replace with `() => {}`
  - `$.extend` → Replace with `Object.assign()` or spread operator
  - `$.trim` → Replace with `String.trim()`
  - `$.proxy` → Replace with `.bind()`
- **Critical Methods:**
  - `constructor()` - Unique namespace assignment for events
  - `on()`, `off()`, `once()`, `trigger()` - Custom event system
  - `addListener()`, `removeListener()` - jQuery event delegation wrappers
  - `setSettings()` - Configuration merging
- **Dependencies:** `lib/Base.js`, Garnish (for constants)
- **Downstream:** ALL other modules
- **Migration Priority:** **TIER 1** (foundational)

---

#### 3. **Garnish.js** - Global Namespace & Utilities
- **Lines:** 1,212 (largest single module)
- **jQuery Coupling:** Extensive
  - `$(window)`, `$(document)`, `$(document.body)` - Global references
  - `$.extend` - Configuration merging
  - `$.event.special` - Custom event registration (jQuery internals)
  - `$.each` - Iteration
  - `$.makeArray` - Array conversion
  - `$.inArray` - Array searching
  - `$.data` - Element data storage
  - `.velocity()` - Animation library (external)
- **DOM Manipulation:** YES
  - jQuery selectors throughout (`.find()`, `.filter()`, etc.)
  - CSS operations (`.css()`, `.addClass()`, `.removeClass()`)
  - Attribute manipulation (`.attr()`, `.prop()`)
  - Focus/scroll management
- **Event Handling:** YES
  - Custom events: `activate`, `textchange`, `resize`
  - `$.event.special` hooks (deep jQuery integration)
  - Global event delegation
- **Difficulty to Remove jQuery:** **HIGH**
  - `.velocity()` → Requires replacement with CSS transitions or another animation library
  - `$.event.special` → Requires native event system rearchitecture
  - Global caching of `$win`, `$doc`, `$bod` → Straightforward but widespread
  - Selector utilities heavily rely on jQuery
- **Critical Public API:**
  - **Constants:** All keycode constants (RETURN_KEY, ESC_KEY, etc.), axis constants, CSS classes
  - **Global References:** `$win`, `$doc`, `$bod`, `$scrollContainer`
  - **Utility Methods:** `getOffset()`, `hitTest()`, `isCursorOver()`, `scrollContainerToElement()`, `shake()`
  - **DOM Utilities:** `copyTextStyles()`, `hasAttr()`, `getElement()`
  - **Input Utilities:** `findInputs()`, `getPostData()`, `getInputPostVal()`
  - **ARIA/A11y:** `addModalAttributes()`, `hideModalBackgroundLayers()`, `trapFocusWithin()`, focus management
  - **Animation:** `requestAnimationFrame`, `cancelAnimationFrame` wrappers
  - **Accessibility:** `isKeyboardFocusable()`, `firstFocusableElement()`, `setFocusWithin()`
  - **Event Listener Management:** `on()`, `off()`, `once()` (class-level, not instance)
- **Dependencies:** Base (for constants)
- **Downstream:** ALL modules (used globally)
- **Migration Priority:** **TIER 1** (foundational, but complex)

---

#### 4. **index.js** - Entry Point & Initialization
- **Lines:** 18
- **jQuery Coupling:** 0 uses directly
- **Role:** Initializes singleton instances (escManager, uiLayerManager) and re-exports Garnish
- **Critical:** Re-assigns `Garnish.escManager` and `Garnish.uiLayerManager` to avoid circular dependencies
- **Migration Priority:** **TIER 2** (depends on UiLayerManager, EscManager)

---

## Component Modules Analysis

### UI Container & Overlay Management

#### 5. **UiLayerManager.js** - Layer & Focus Management
- **Lines:** 181
- **jQuery Coupling:** 6 uses
  - `$.isPlainObject` - Parameter type checking
  - `$(container)` - jQuery wrapper
  - `$.each` - Iteration
  - jQuery selectors for layer finding
- **DOM Manipulation:** Minimal (mostly reference storage)
- **Event Handling:** YES - Keyboard shortcuts/keydown events
- **Difficulty to Remove jQuery:** **LOW**
- **Key Methods:**
  - `addLayer()`, `removeLayer()` - Layer stack management
  - `registerShortcut()`, `unregisterShortcut()` - Keyboard shortcut system
  - `triggerShortcut()` - Event dispatch
- **Dependencies:** Base, Garnish
- **Downstream:** Modal, HUD, DisclosureMenu, CustomSelect, ContextMenu
- **Public API Usage:** `new Garnish.UiLayerManager()` (instantiated once), `.registerShortcut()`, `.addLayer()`, `.removeLayer()`
- **Migration Priority:** **TIER 2**

---

#### 6. **Modal.js** - Centered Modal Dialog
- **Lines:** 451
- **jQuery Coupling:** 13 uses
  - `$('<div/>')` - Element creation
  - `.find()`, `.data()`, `.appendTo()` - DOM traversal
  - `.offset()`, `.outerWidth()`, `.outerHeight()` - Dimension queries
  - `.velocity()` - Fade in/out animations
  - `.hasClass()`, `.addClass()`, `.removeClass()` - Class manipulation
- **DOM Manipulation:** YES (creates modal container, shade, resizing elements)
- **Event Handling:** YES - Window resize, drag interactions
- **Difficulty to Remove jQuery:** **MEDIUM**
  - `.velocity()` animations → CSS transitions
  - Dimension calculations → `getBoundingClientRect()`
  - Element creation → `document.createElement()`
- **Key Methods:**
  - `show()`, `hide()`, `quickShow()`, `quickHide()` - Visibility toggling
  - `updateSizeAndPosition()` - Responsive sizing
  - `getWidth()`, `getHeight()` - Dimension getters
- **Dependencies:** Base, Garnish, DragMove (optional, if resizable), BaseDrag (for resize handle)
- **Downstream:** Many utilities and feature modules
- **Public API Usage:** `new Garnish.Modal()` (13 usages)
- **Migration Priority:** **TIER 2** (high usage, medium complexity)

---

#### 7. **HUD.js** - Floating Popover/Tooltip
- **Lines:** 764
- **jQuery Coupling:** 15 uses
  - `.offset()`, `.scrollParent()`, `.outerWidth/Height()` - Layout calculations
  - `.find()`, `.filter()`, `.not()` - DOM queries
  - `.appendTo()`, `.insertBefore()`, `.insertAfter()` - DOM insertion
  - `.css()` - Inline styling
  - `.velocity()` - Animations
- **DOM Manipulation:** YES (creates HUD container, form, header/footer)
- **Event Handling:** YES - Window resize, scroll, focus trap
- **Difficulty to Remove jQuery:** **MEDIUM**
  - Similar to Modal (animations, layout calculations)
  - Layout logic (orientation detection) is core complexity
- **Key Methods:**
  - `show()`, `hide()`, `toggle()` - Visibility
  - `updateBody()` - Content updates
  - `updateSizeAndPosition()` - Smart positioning (bottom/top/left/right)
  - `submit()` - Form submission callback
- **Dependencies:** Base, Garnish, UiLayerManager
- **Downstream:** Dashboard, tablesettings, and many feature modules
- **Public API Usage:** `new Garnish.HUD()` (5 direct instantiations, but 21 total references)
- **Migration Priority:** **TIER 2** (high usage, medium complexity)

---

#### 8. **DisclosureMenu.js** - Dropdown/Popover Menu
- **Lines:** 1,008 (largest UI component)
- **jQuery Coupling:** 20 uses
  - `.css()`, `.velocity()` - Animations and styling
  - `.find()`, `.closest()`, `.filter()`, `.parent()` - DOM traversal
  - `.attr()` - Attribute manipulation
  - `.hasClass()`, `.addClass()`, `.removeClass()` - Class toggling
- **DOM Manipulation:** YES (creates menu structure, search input, items)
- **Event Handling:** YES - Click, keydown (arrow keys, search), focus management
- **Difficulty to Remove jQuery:** **MEDIUM-HIGH**
  - Complex focus and visibility logic
  - Search functionality with DOM filtering
  - `.velocity()` animations
  - Deep DOM manipulation for item creation
- **Key Methods:**
  - `show()`, `hide()` - Visibility
  - `addItem()`, `addGroup()`, `removeItem()` - Content management
  - `createItem()` - Dynamic item creation
  - `setContainerPosition()` - Smart positioning
  - `handleKeypress()` - Keyboard navigation
- **Dependencies:** Base, Garnish, UiLayerManager
- **Downstream:** High-level UI features
- **Public API Usage:** `new Garnish.DisclosureMenu()` (12 usages)
- **Migration Priority:** **TIER 2-3** (high usage, complex)

---

### Drag & Drop System

#### 9. **BaseDrag.js** - Base Drag Handler
- **Lines:** 583
- **jQuery Coupling:** 28 uses (high)
  - `.scrollParent()`, `.offset()`, `.outerWidth/Height()` - Layout info
  - `.find()`, `.filter()`, `.index()` - DOM traversal
  - `.data()`, `.removeData()` - Element data storage
  - `$.inArray`, `$.makeArray` - Array utilities
  - `$.isPlainObject` - Type checking
- **DOM Manipulation:** NO (only reads properties)
- **Event Handling:** YES - mousedown, mousemove, mouseup
- **Difficulty to Remove jQuery:** **MEDIUM**
  - `.scrollParent()` → Manual parent traversal with scroll checks
  - `.offset()` → `getBoundingClientRect()` + scroll offset
  - `.data()` → `WeakMap` or element properties
  - Everything else is straightforward
- **Key Methods:**
  - `startDragging()`, `stopDragging()`, `drag()` - Lifecycle
  - `addItems()`, `removeItems()` - Item management
  - `allowDragging()` - Override hook
  - `getPrevItem()`, `getNextItem()` - Item navigation
- **Dependencies:** Base, Garnish
- **Downstream:** Drag, DragSort, DragDrop, Modal (resize handle)
- **Migration Priority:** **TIER 1-2** (foundational for drag system)

---

#### 10. **Drag.js** - Element Dragging with Helpers
- **Lines:** 462
- **jQuery Coupling:** 8 uses
  - `.outerWidth/Height()` - Dimension queries
  - `.clone()`, `.addClass()`, `.appendTo()` - Helper element creation
  - `.offset()` - Position calculation
  - `.velocity()` - Animation for return-to-source
- **DOM Manipulation:** YES (creates helper clones)
- **Event Handling:** NO (delegated to BaseDrag)
- **Difficulty to Remove jQuery:** **MEDIUM**
  - `.velocity()` → CSS transitions
  - `.clone()` → `element.cloneNode(true)`
  - Layout calculations → Native APIs
- **Key Methods:**
  - `setDraggee()`, `appendDraggee()` - Draggee management
  - `returnHelpersToDraggees()`, `fadeOutHelpers()` - Animation
  - `findDraggee()`, `getHelperTarget*()` - Helper positioning
- **Dependencies:** Base, BaseDrag, Garnish
- **Downstream:** DragSort, DragDrop, Modal (resize)
- **Migration Priority:** **TIER 2**

---

#### 11. **DragDrop.js** - Drag with Drop Targets
- **Lines:** 116
- **jQuery Coupling:** 5 uses
  - `$(element)` - jQuery wrapper
  - `.addClass()`, `.removeClass()` - Class manipulation
  - `$.extend` - Settings merging
  - `$.noop` - Default callback
- **DOM Manipulation:** Minimal
- **Event Handling:** NO (delegated to Drag)
- **Difficulty to Remove jQuery:** **LOW**
- **Key Methods:**
  - `onDrag()` - Override to check drop targets
  - `updateDropTargets()` - Target list refresh
- **Dependencies:** Drag, Garnish
- **Downstream:** Feature modules
- **Migration Priority:** **TIER 2**

---

#### 12. **DragSort.js** - Sortable List Reordering
- **Lines:** 697
- **jQuery Coupling:** 21 uses
  - `.insertBefore()`, `.insertAfter()`, `.prependTo()` - DOM insertion
  - `.offset()`, `.outerWidth/Height()` - Layout calculations
  - `.index()` - Position finding
  - `.find()`, `.not()`, `.filter()` - DOM traversal
  - `.contains()` - Element containment checks
- **DOM Manipulation:** YES (moves items during drag)
- **Event Handling:** NO (delegated to Drag)
- **Difficulty to Remove jQuery:** **MEDIUM**
  - Layout calculations → Native APIs
  - DOM insertion → Native APIs
  - Coordinate calculations → Pure math
- **Key Methods:**
  - `onDragStart()`, `onDrag()`, `onDragStop()` - Lifecycle
  - `_getClosestItem()` - Hit detection (complex algorithm)
  - `_updateInsertion()` - Visual feedback
  - `_getItemMidpoint()` - Layout calculations (with caching optimization)
- **Dependencies:** Drag, Garnish
- **Downstream:** Component lists, matrix fields
- **Public API Usage:** `new Garnish.DragSort()` (12 usages)
- **Migration Priority:** **TIER 2** (high usage)

---

#### 13. **DragMove.js** - Simple Drag-to-Move
- **Lines:** 15
- **jQuery Coupling:** 0 uses
- **DOM Manipulation:** YES (sets `left`, `top` CSS)
- **Event Handling:** NO (delegated to BaseDrag)
- **Difficulty to Remove jQuery:** N/A
- **Role:** Trivial subclass that just applies mouse deltas to element position
- **Dependencies:** BaseDrag
- **Downstream:** Modal (if draggable), windows
- **Migration Priority:** **TIER 3** (minimal code, no jQuery)

---

### Menu & Selection Systems

#### 14. **Select.js** - Multi-Item Selection Interface
- **Lines:** 1,018 (largest module besides Garnish.js)
- **jQuery Coupling:** 41 uses (highest)
  - `.find()`, `.filter()`, `.index()`, `.not()`, `.eq()`, `.slice()` - DOM traversal
  - `.offset()`, `.outerWidth/Height()` - Layout calculations
  - `.addClass()`, `.removeClass()`, `.attr()` - DOM attribute manipulation
  - `$.inArray`, `$.makeArray` - Array utilities
  - `.data()` - Element data storage
- **DOM Manipulation:** Minimal (only class/attribute manipulation)
- **Event Handling:** YES - Click, shift-click, keyboard (arrows, Ctrl+A, space)
- **Difficulty to Remove jQuery:** **MEDIUM-HIGH**
  - Extensive DOM traversal logic that depends on jQuery
  - Layout calculations → Native APIs
  - Array utilities → Native alternatives
  - Event system remains jQuery-delegated
- **Key Methods:**
  - `selectItem()`, `selectRange()`, `selectAll()` - Selection operations
  - `addItems()`, `removeItems()` - Item management
  - `getClosestItem()` - Spatial querying (complex algorithm)
  - `focusItem()`, `setFocusableItem()` - Focus management
  - `onMouseDown()`, `onMouseUp()`, `onKeyDown()` - Event handlers
- **Dependencies:** Base, Garnish
- **Downstream:** CustomSelect, SelectMenu, MenuBtn, and form inputs
- **Public API Usage:** `new Garnish.Select()` (6 usages)
- **Migration Priority:** **TIER 2-3** (high usage, high coupling)

---

#### 15. **CustomSelect.js** - Dropdown Menu Interface
- **Lines:** 333
- **jQuery Coupling:** 5 uses
  - `.find()`, `.attr()`, `.data()` - DOM queries
  - `.appendTo()` - DOM insertion
  - `.offset()`, `.outerWidth/Height()`, `.scrollLeft/Top()` - Layout
  - `.css()` - Styling
  - `.velocity()` - Animations
- **DOM Manipulation:** YES (menu positioning and layout)
- **Event Handling:** YES - Click, hover state management
- **Difficulty to Remove jQuery:** **MEDIUM**
  - Layout calculations → Native APIs
  - `.velocity()` → CSS transitions
- **Key Methods:**
  - `addOptions()` - Option registration
  - `show()`, `hide()` - Visibility
  - `setPositionRelativeToAnchor()` - Smart positioning logic
  - `selectOption()` - Selection callback
  - `_alignLeft/Right/Center()` - Positioning helpers
- **Dependencies:** Base, Garnish, UiLayerManager
- **Downstream:** MenuBtn, form controls
- **Public API Usage:** Referenced as `.Menu` (deprecated alias), extends to SelectMenu
- **Migration Priority:** **TIER 2-3**

---

#### 16. **SelectMenu.js** - Extends CustomSelect
- **Lines:** 83
- **jQuery Coupling:** 2 uses
  - `$.extend` - Settings merging
- **DOM Manipulation:** NO (delegated to CustomSelect)
- **Event Handling:** NO
- **Difficulty to Remove jQuery:** **LOW**
- **Role:** Thin wrapper adding `.sel` class management for radio-button-style selections
- **Dependencies:** CustomSelect, Garnish
- **Migration Priority:** **TIER 3**

---

#### 17. **MenuBtn.js** - Button That Opens Menu
- **Lines:** 444
- **jQuery Coupling:** 5 uses
  - `.data()`, `.attr()` - Element data/attributes
  - `.addClass()`, `.removeClass()`, `.filter()` - Class manipulation
  - `.contains()` - Element containment
  - MutationObserver usage
- **DOM Manipulation:** Minimal
- **Event Handling:** YES - Click, keydown (search, navigation)
- **Difficulty to Remove jQuery:** **LOW**
  - Most operations are straightforward replacements
- **Key Methods:**
  - `showMenu()`, `hideMenu()` - Menu visibility
  - `onKeyDown()` - Keyboard navigation & search
  - `focusOption()`, `moveFocusUp/Down()` - Focus management
  - `onMouseDown()` - Toggle menu
- **Dependencies:** Base, Garnish, CustomSelect (composed)
- **Downstream:** Feature modules
- **Public API Usage:** `new Garnish.MenuBtn()` (6 usages)
- **Migration Priority:** **TIER 2**

---

#### 18. **ContextMenu.js** - Right-Click Context Menu
- **Lines:** 171
- **jQuery Coupling:** 8 uses
  - `$('<div/>')`, `$('<ul/>')`, `$('<li/>')`, `$('<a/>')` - Element creation
  - `.appendTo()` - DOM insertion
  - `.css()` - Positioning
  - `.mousedown()` - Event binding (not `.on()`, direct method)
  - `.hide()`, `.show()` - Visibility
- **DOM Manipulation:** YES (builds menu structure dynamically)
- **Event Handling:** YES - contextmenu and mousedown
- **Difficulty to Remove jQuery:** **MEDIUM**
  - DOM creation → `document.createElement()`
  - Event binding → `.addEventListener()`
- **Key Methods:**
  - `buildMenu()` - Dynamic menu construction
  - `showMenu()`, `hideMenu()` - Visibility
  - `enable()`, `disable()` - Toggle event listeners
- **Dependencies:** Base, Garnish, UiLayerManager
- **Downstream:** Specialized feature modules
- **Migration Priority:** **TIER 3**

---

### Form & Text Input Components

#### 19. **NiceText.js** - Textarea with Auto-Height & Char Count
- **Lines:** 343
- **jQuery Coupling:** 7 uses
  - `.val()` - Input value access
  - `.attr()`, `.removeAttr()` - Attribute manipulation
  - `.css()` - Styling
  - `.insertBefore()`, `.appendTo()` - DOM insertion
  - `.height()`, `.width()` - Dimension queries
  - `.velocity()` - Fade animations
- **DOM Manipulation:** YES (creates hint container, char counter)
- **Event Handling:** YES - textchange (custom event), keydown
- **Difficulty to Remove jQuery:** **LOW-MEDIUM**
  - Layout calculations → Native APIs
  - `.velocity()` → CSS transitions
- **Key Methods:**
  - `initialize()` - Setup (called lazily on first visibility)
  - `updateHeight()` - Auto-resize logic
  - `showHint()`, `hideHint()` - Hint visibility
  - `updateCharsLeft()` - Character counter update
  - `getHeightForValue()` - Complex text measurement
- **Dependencies:** Base, Garnish
- **Downstream:** Forms, content editors
- **Public API Usage:** `new Garnish.NiceText()` (3 usages)
- **Migration Priority:** **TIER 2-3**

---

#### 20. **MixedInput.js** - Hybrid Text/Tag Input
- **Lines:** 424
- **jQuery Coupling:** 5 uses
  - `.attr()`, `.val()`, `.css()`, `.prop()` - Element properties
  - `.insertBefore()`, `.append()`, `.remove()` - DOM manipulation
  - `$.inArray` - Array searching
- **DOM Manipulation:** YES (creates text input elements dynamically)
- **Event Handling:** YES - Click, focus, keydown (navigation)
- **Difficulty to Remove jQuery:** **LOW**
  - Mostly straightforward element operations
- **Key Methods:**
  - `addElement()`, `addTextElement()` - Add input elements
  - `removeElement()` - Remove elements
  - `setFocus()`, `focusPreviousElement()`, `focusNextElement()` - Focus management
  - `setCaretPos()` - Cursor positioning
- **Nested Class:** `TextElement` - Helper for text input lifecycle
- **Dependencies:** Base, Garnish
- **Downstream:** Advanced form inputs
- **Public API Usage:** `new Garnish.MixedInput()` (1 usage)
- **Migration Priority:** **TIER 3**

---

### Specialized Components

#### 21. **CheckboxSelect.js** - "Select All" Checkbox Group
- **Lines:** 97
- **jQuery Coupling:** 2 uses
  - `.find()`, `.filter()`, `.not()` - DOM selection
  - `.prop()` - Property manipulation
- **DOM Manipulation:** NO
- **Event Handling:** YES - Change events
- **Difficulty to Remove jQuery:** **LOW**
- **Key Methods:**
  - `onAllChange()` - Toggle all items when master checkbox changes
  - `isAllChecked()` - State query
- **Dependencies:** Base, Garnish
- **Downstream:** Filter UIs
- **Public API Usage:** `new Garnish.CheckboxSelect()` (2 usages)
- **Migration Priority:** **TIER 3**

---

#### 22. **MultiFunctionBtn.js** - Button with Loading/Success States
- **Lines:** 125
- **jQuery Coupling:** 2 uses
  - `.data()`, `.find()` - DOM queries
  - `.addClass()`, `.removeClass()` - Class toggling
- **DOM Manipulation:** Minimal
- **Event Handling:** NO
- **Difficulty to Remove jQuery:** **LOW**
- **Key Methods:**
  - `busyEvent()`, `successEvent()`, `failureEvent()` - State changes
  - `updateMessages()` - ARIA live region updates
- **Dependencies:** Base, Garnish
- **Downstream:** Action buttons
- **Public API Usage:** `new Garnish.MultiFunctionBtn()` (1 usage)
- **Migration Priority:** **TIER 3**

---

#### 23. **EscManager.js** - ESC Key Handler (Deprecated)
- **Lines:** 55
- **jQuery Coupling:** 0 uses
- **DOM Manipulation:** NO
- **Event Handling:** YES - Keyup for ESC key
- **Difficulty to Remove jQuery:** N/A
- **Role:** Legacy handler for ESC key (superseded by UiLayerManager)
- **Status:** Deprecated; use UiLayerManager instead
- **Dependencies:** Base, Garnish
- **Migration Priority:** **TIER 4** (deprecated, minimal usage)

---

#### 24. **icons/ResizeHandle.js** - SVG Icon
- **Lines:** 4
- **jQuery Coupling:** 0 uses
- **Role:** Simple SVG string export
- **Migration Priority:** **TIER 4** (no migration needed)

---

## Dependency Graph (Topological Ordering)

### Leaves First (Easiest to Migrate First)

```
TIER 4 (No dependencies, no jQuery):
  - lib/Base.js
  - icons/ResizeHandle.js
  - EscManager.js
  - DragMove.js

TIER 3 (Leaf components, low jQuery):
  - SelectMenu.js (extends CustomSelect)
  - CheckboxSelect.js
  - MultiFunctionBtn.js
  - MixedInput.js
  - ContextMenu.js
  - DragDrop.js

TIER 2 (Mid-complexity, moderate jQuery):
  - NiceText.js
  - UiLayerManager.js
  - MenuBtn.js
  - CustomSelect.js
  - Modal.js
  - HUD.js
  - Drag.js
  - BaseDrag.js
  - DragSort.js
  - DisclosureMenu.js

TIER 1 (Foundational, highest priority):
  - Base.js (used by all others)
  - Garnish.js (global utilities, constants, event system)
```

### Dependency Graph (Text Form)

```
lib/Base.js
  ↓
Base.js (→ Garnish constants)
  ↓
├─→ UiLayerManager.js
│     ↓
│     ├─→ Modal.js
│     ├─→ HUD.js
│     ├─→ CustomSelect.js
│     │     ├─→ SelectMenu.js
│     │     └─→ MenuBtn.js
│     └─→ ContextMenu.js
│
├─→ BaseDrag.js (→ Garnish constants)
│     ├─→ Drag.js
│     │     ├─→ DragSort.js (uses DragSort-specific logic)
│     │     └─→ DragDrop.js
│     ├─→ DragMove.js (trivial)
│     └─→ Modal.js (for resize handle)
│
├─→ Select.js (high jQuery coupling)
│
├─→ NiceText.js
│
├─→ MixedInput.js (→ Base utility)
│
├─→ CheckboxSelect.js
│
└─→ MultiFunctionBtn.js

Garnish.js (entry point, includes all above)
  ↓
index.js (instantiates singletons, re-exports)
```

---

## jQuery Coupling Summary Table

| Module | Lines | jQuery Uses | Difficulty | Key Issues |
|--------|-------|------------|------------|-----------|
| lib/Base.js | 160 | 0 | **NONE** | Pure JS, foundation |
| Base.js | 193 | 10 | **LOW** | $.noop, $.extend, $.trim, $.proxy |
| Garnish.js | 1,212 | ~50 | **HIGH** | $.event.special, .velocity(), selectors |
| index.js | 18 | 0 | **NONE** | Entry point only |
| UiLayerManager.js | 181 | 6 | **LOW** | $.isPlainObject, basic selectors |
| Modal.js | 451 | 13 | **MEDIUM** | .velocity(), offset(), dimension queries |
| HUD.js | 764 | 15 | **MEDIUM** | .velocity(), smart positioning, layout |
| DisclosureMenu.js | 1,008 | 20 | **MEDIUM-HIGH** | Complex DOM, .velocity(), filtering |
| BaseDrag.js | 583 | 28 | **MEDIUM** | .scrollParent(), .offset(), .data() |
| Drag.js | 462 | 8 | **MEDIUM** | .clone(), .velocity() animations |
| DragDrop.js | 116 | 5 | **LOW** | Basic utilities |
| DragSort.js | 697 | 21 | **MEDIUM** | Layout calculations, DOM insertion |
| DragMove.js | 15 | 0 | **NONE** | Trivial subclass |
| Select.js | 1,018 | 41 | **MEDIUM-HIGH** | Most jQuery-heavy; extensive DOM ops |
| CustomSelect.js | 333 | 5 | **MEDIUM** | Layout, .velocity() |
| SelectMenu.js | 83 | 2 | **LOW** | Thin wrapper |
| MenuBtn.js | 444 | 5 | **LOW** | Mostly native-friendly ops |
| ContextMenu.js | 171 | 8 | **MEDIUM** | DOM creation, event binding |
| NiceText.js | 343 | 7 | **LOW-MEDIUM** | Dimension queries, .velocity() |
| MixedInput.js | 424 | 5 | **LOW** | Straightforward DOM ops |
| CheckboxSelect.js | 97 | 2 | **LOW** | Minimal jQuery usage |
| MultiFunctionBtn.js | 125 | 2 | **LOW** | Class manipulation only |
| EscManager.js | 55 | 0 | **NONE** | Deprecated, pure JS |
| icons/ResizeHandle.js | 4 | 0 | **NONE** | SVG string constant |

---

## External API Consumer Analysis

### Highest-Priority Modules (Most Used by Consumers)

**Rank 1: `Garnish.Base` (96 usages)**
- Used as the superclass for virtually all Craft feature modules
- Consumer pattern: `Craft.SomeFeature = Garnish.Base.extend({...})`
- **Criticality:** ABSOLUTE (cannot remove without breaking everything)

**Rank 2: `Garnish.Modal` (35 usages)**
- Modals for settings, confirmations, editors
- Usage: `new Garnish.Modal(container, settings)`
- **Criticality:** VERY HIGH

**Rank 3: `Garnish.DragSort` (16+ instantiations, 36+ references)**
- Sortable lists, matrix fields, component reordering
- Usage: `new Garnish.DragSort(items, settings)`
- **Criticality:** VERY HIGH

**Rank 4: `Garnish.HUD` (5+ instantiations, 21+ references)**
- Floating popovers for quick edits, widget managers
- Usage: `new Garnish.HUD(trigger, content, settings)`
- **Criticality:** HIGH

**Rank 5: `Garnish.DisclosureMenu` (12+ instantiations, 19+ references)**
- Dropdown menus for navigation, actions
- Usage: `new Garnish.DisclosureMenu(trigger, settings)`
- **Criticality:** HIGH

**Rank 6: `Garnish.Select` (6+ instantiations, 9+ references)**
- Item selection UI for lists, filters
- Usage: `new Garnish.Select(container, items, settings)`
- **Criticality:** HIGH

**Rank 7: `Garnish.MenuBtn` (6 instantiations)**
- Action buttons with dropdown menus
- Usage: `new Garnish.MenuBtn(button, menu, settings)`
- **Criticality:** MEDIUM-HIGH

### Key Constants Used by Consumers

- **Keycodes:** `ESC_KEY`, `RETURN_KEY`, `TAB_KEY`, `SPACE_KEY`, `UP_KEY`, `DOWN_KEY`, `LEFT_KEY`, `RIGHT_KEY`, `HOME_KEY`, `END_KEY`, `BACKSPACE_KEY`, `DELETE_KEY`, `S_KEY`, `PAGE_UP_KEY`, `PAGE_DOWN_KEY`, `SHIFT_KEY`
- **Axes:** `X_AXIS`, `Y_AXIS`
- **ARIA Classes:** `JS_ARIA_CLASS`, `JS_ARIA_TRUE_CLASS`, `JS_ARIA_FALSE_CLASS`
- **Timing:** `FX_DURATION`

### Key Utility Methods Used

- `Garnish.hitTest(x, y, elem)` - Hit detection for drag operations
- `Garnish.scrollContainerToElement(container, elem)` - Scroll to view
- `Garnish.shake(elem)` - Error shake animation
- `Garnish.getOffset(elem)` - Position relative to scroll container
- `Garnish.copyTextStyles(source, target)` - Style copying for text inputs
- `Garnish.getPostData(container)` - Form serialization
- Focus management: `setFocusWithin()`, `trapFocusWithin()`, `getKeyboardFocusableElements()`
- `Garnish.uiLayerManager` - Global singleton for modal management and shortcuts

---

## Migration Strategy Recommendation

### Phase 1: Foundation (Weeks 1-2)
1. **lib/Base.js** - No changes needed
2. **Base.js** - Replace jQuery utilities
   - `$.noop` → `() => {}`
   - `$.extend` → `Object.assign()`
   - `$.trim` → `.trim()`
   - `$.proxy` → `.bind()`
3. **EscManager.js** - No changes needed
4. **DragMove.js** - No changes needed

### Phase 2: Core Infrastructure (Weeks 2-3)
1. **Garnish.js** - LARGEST effort
   - Refactor `$.event.special` custom events to native CustomEvent
   - Replace global `$(window)`, `$(document)`, `$(document.body)` with direct references
   - Replace `.scrollParent()` with manual traversal
   - Replace `.velocity()` calls with CSS transitions or Animate API
   - Refactor DOM utility methods to use native APIs
2. **UiLayerManager.js** - Relatively light refactoring
3. **index.js** - No changes (depends on above)

### Phase 3: Drag System (Weeks 3-4)
1. **BaseDrag.js** - Replace jQuery offset/dimension methods
2. **Drag.js** - Update for BaseDrag changes, replace `.velocity()`
3. **DragDrop.js** - Light refactoring
4. **DragSort.js** - Update for Drag changes
5. **DragMove.js** - Already pure

### Phase 4: Modal & Overlay (Weeks 4-5)
1. **Modal.js** - Replace layout calculations, animations
2. **HUD.js** - Similar to Modal, plus positioning logic
3. **UiLayerManager.js** (if not done in Phase 2)

### Phase 5: Menu & Selection (Weeks 5-6)
1. **Select.js** - MOST jQuery-heavy; careful refactoring
2. **CustomSelect.js** - Update for Garnish changes
3. **SelectMenu.js** - Light updates
4. **MenuBtn.js** - Update for CustomSelect, Base changes
5. **ContextMenu.js** - Replace DOM creation

### Phase 6: Text & Utility (Weeks 6-7)
1. **NiceText.js** - Replace dimension queries, .velocity()
2. **MixedInput.js** - Update for Base changes
3. **CheckboxSelect.js** - Minimal changes
4. **MultiFunctionBtn.js** - Minimal changes

### Phase 7: Integration & Testing (Week 8)
1. Update all entry points (index.js, Garnish.js exports)
2. Integration tests with consumer code
3. Accessibility testing (focus management is critical)
4. Performance testing (drag operations are compute-intensive)

---

## Critical Dependencies to Watch

1. **`.scrollParent()` Usage** - Appears in BaseDrag, Select, HUD, Modal
   - No native equivalent; requires manual DOM traversal
   - Need to implement/share utility function

2. **`.velocity()` Animations** - Appears in Garnish, Modal, HUD, Drag, CustomSelect, NiceText
   - Replace with CSS transitions or requestAnimationFrame
   - Consider animation library (Animate.css, WAAPI)

3. **`$.event.special` Custom Events** - Only in Garnish.js (activate, textchange, resize)
   - Replace with native CustomEvent
   - Consider using native events directly if semantics match

4. **Element `.data()` Storage** - Used in BaseDrag, Select, Modal, CustomSelect
   - Replace with WeakMap or element properties
   - Use `element._garnish` namespace to avoid conflicts

5. **Array Utilities** - `$.inArray`, `$.makeArray`, `$.contains`
   - `.indexOf()` and `.includes()`
   - `Array.from()`
   - `.contains()` → Manual check or native `.closest()`

6. **Focus Management** - Critical for accessibility
   - Select.js, MenuBtn.js, HUD.js, Modal.js
   - Native focus API exists but jQuery's `:focusable` selector has custom logic
   - Need shared utility for finding focusable elements

---

## Additional Notes

### Performance Considerations
- DragSort's `_getClosestItem()` is performance-critical (large datasets)
- Already has optimization for pre-calculated midpoints
- Pure JavaScript may be faster than jQuery for this algorithm

### Accessibility
- Multiple modules handle focus trapping and management
- ARIA attributes are set/read extensively
- Careful testing needed post-migration

### Browser Compatibility
- Code uses modern JavaScript features (arrow functions, Object.assign, const/let)
- Assume modern browser baseline (ES6+)
- No need to support IE11

---

## Document Metadata

- **Generated:** 2025-06-17
- **Framework Version:** Craft 6 (ES6 modules)
- **jQuery Baseline:** 3.x
- **Scope:** `/packages/craftcms-legacy/garnish/src/` (23 modules)
- **External Consumer Directories:**
  - `/packages/craftcms-legacy/` (feature modules)
  - `/resources/js/` (CP interface)

