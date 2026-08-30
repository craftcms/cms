declare module 'autosize' {
  interface Autosize {
    (el: HTMLTextAreaElement): void;
    update(el: HTMLTextAreaElement): void;
    destroy(el: HTMLTextAreaElement): void;
  }

  const autosize: Autosize;
  export default autosize;
}
