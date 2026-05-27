export function guardWebAwesomeCustomElements() {
  if (window.__craftWebAwesomeDefineGuard) {
    return;
  }

  window.__craftWebAwesomeDefineGuard = true;

  const define = CustomElementRegistry.prototype.define;

  CustomElementRegistry.prototype.define = function (
    name,
    constructor,
    options
  ) {
    if (name.startsWith('wa-') && this.get(name)) {
      return;
    }

    return define.call(this, name, constructor, options);
  };
}

declare global {
  interface Window {
    __craftWebAwesomeDefineGuard?: boolean;
  }
}
