declare module 'inputmask' {
  export default class Inputmask {
    constructor(options: Record<string, unknown>);
    mask(input: HTMLInputElement): void;
  }
}
