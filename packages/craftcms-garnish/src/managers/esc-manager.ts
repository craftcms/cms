/**
 * ESC key manager.
 *
 * @deprecated Use UiLayerManager instead. Kept for parity.
 */

import {Base} from '../base';
import {bod} from '../globals';
import {ESC_KEY} from '../constants';

type EscHandlerFn = (ev: KeyboardEvent) => void;

interface EscRegistration {
  obj: {
    [key: string]: unknown;
    trigger?: (type: string) => void;
  };
  func: EscHandlerFn | string;
}

export class EscManager extends Base {
  private handlers: EscRegistration[] = [];

  constructor() {
    super();

    this.addListener(bod, 'keyup', (ev) => {
      const keyEvent = ev as unknown as KeyboardEvent;
      if (keyEvent.keyCode === ESC_KEY) {
        this.escapeLatest(keyEvent);
      }
    });
  }

  register(obj: EscRegistration['obj'], func: EscHandlerFn | string): void {
    this.handlers.push({obj, func});
  }

  unregister(obj: EscRegistration['obj']): void {
    for (let i = this.handlers.length - 1; i >= 0; i--) {
      if (this.handlers[i]!.obj === obj) {
        this.handlers.splice(i, 1);
      }
    }
  }

  escapeLatest(ev: KeyboardEvent): void {
    if (!this.handlers.length) {
      return;
    }

    const handler = this.handlers.pop()!;

    let func: EscHandlerFn;
    if (typeof handler.func === 'function') {
      func = handler.func;
    } else {
      func = handler.obj[handler.func] as EscHandlerFn;
    }

    func.call(handler.obj, ev);

    if (typeof handler.obj.trigger === 'function') {
      handler.obj.trigger('escape');
    }
  }
}
