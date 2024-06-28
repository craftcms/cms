type AuthFormHandler = new (
  container: HTMLElement,
  onSuccess: () => any,
  showError: (error: string) => void
) => void;

const authFormHandlers: {
  [key: string]: AuthFormHandler;
} = {};

function isJqueryObject(item: unknown): item is JQuery {
  return item instanceof jQuery;
}

export function createAuthFormHandler(
  method: string,
  container: JQuery | HTMLElement,
  onSuccess: () => void,
  showError: (error: string) => void
) {
  if (typeof authFormHandlers[method] === 'undefined') {
    throw `No authentication form has been registered for the method "${method}".`;
  }

  if (isJqueryObject(container)) {
    if (!container.length) {
      throw 'No form element specified.';
    }
    container = container[0];
  }

  // @TODO
  // if (!showError) {
  //   showError = (error) => {
  //     Craft.cp.displayError(error);
  //   };
  // }

  return new authFormHandlers[method](container, onSuccess, showError);
}

export function registerAuthFormHandler(
  method: string,
  func: AuthFormHandler
): void {
  if (typeof authFormHandlers[method] !== 'undefined') {
    throw `An authentication form handler has already been registered for the method “${method}”.`;
  }

  authFormHandlers[method] = func;
}
