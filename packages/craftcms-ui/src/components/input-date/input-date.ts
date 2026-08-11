import CraftInput from '../input/input.js';

export default class CraftInputDate extends CraftInput {
  constructor() {
    super();
    this.type = 'date';
  }
}

if (!customElements.get('craft-input-date')) {
  customElements.define('craft-input-date', CraftInputDate);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-date': CraftInputDate;
  }
}
