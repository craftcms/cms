export class AnimationBlocker {
  static extensions: string[] = ['.gif', '.webp'];
  #imageAddedObserver = AnimationBlocker.createImageAddedObserver();

  constructor() {
    AnimationBlocker.hideAllAnimations();
  }

  /**
   * Creates a MutationObserver to watch for added images
   * @private
   */
  private static createImageAddedObserver(): MutationObserver {
    const targetNode: HTMLBodyElement = document.querySelector('body')!;

    // Options for the observer (which mutations to observe)
    const config = {attributes: true, childList: true, subtree: true};

    // Callback function to execute when mutations are observed
    const callback = (mutationList: Array<MutationRecord>) => {
      for (const mutation of mutationList) {
        if (mutation.type === 'childList') {
          if (mutation.addedNodes) {
            for (const node of mutation.addedNodes) {
              if (node.nodeName === 'IMG') {
                if (this.couldBeAnimated(node as HTMLImageElement)) {
                  this.hideAnimation(node as HTMLImageElement);
                }
              }
            }
          }

          if (mutation.removedNodes.length > 0) {
            mutation.removedNodes.forEach((removedNode) => {
              if (removedNode.nodeName === 'IMG') {
                if (this.couldBeAnimated(removedNode as HTMLImageElement)) {
                  this.removeBlockerUI(mutation.target as HTMLElement);
                }
              }
            });
          }
        }
      }
    };

    // Create an observer instance linked to the callback function
    const observer = new MutationObserver(callback);

    // Start observing the target node for configured mutations
    observer.observe(targetNode, config);

    return observer;
  }

  /**
   * Waits for an image to load
   * @param image
   * @private
   */
  private static async waitForImage(image: HTMLImageElement): Promise<void> {
    return new Promise((res) => {
      if (image.complete) {
        return res();
      }
      image.onload = () => res();
      image.onerror = () => res();
    });
  }

  /**
   * Checks if the image's size has changed
   * @param image
   * @private
   */
  private static imageSizeChanged(
    image: HTMLImageElement
  ): boolean | undefined {
    const width: number = image.clientWidth;
    const height: number = image.clientHeight;
    const prevWidth: string | undefined = image.dataset.width;
    const prevHeight: string | undefined = image.dataset.height;

    if (!prevWidth || !prevHeight) return;

    return (
      width !== parseInt(prevWidth, 10) || height !== parseInt(prevHeight, 10)
    );
  }

  /**
   * Creates a canvas to cover the image
   * @param image
   * @private
   */
  private static createCover(image: HTMLImageElement): void {
    if (this.getCover(image)) return;

    const width = image.clientWidth;
    const height = image.clientHeight;
    const parent = image.parentElement!;

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    canvas.setAttribute('aria-hidden', 'true');
    canvas.setAttribute('role', 'presentation');
    canvas.setAttribute('data-image-cover', 'true');
    canvas.style.position = 'absolute';
    canvas.style.top = '50%';
    canvas.style.left = '50%';
    canvas.style.transform = 'translate(-50%, -50%)';

    // Draw first frame on canvas
    const ctx = canvas.getContext('2d') as CanvasRenderingContext2D;
    ctx.drawImage(image, 0, 0, width, height);
    parent.style.position = 'relative';
    parent.insertBefore(canvas, image);
  }

  /**
   * Gets the canvas cover
   * @param image
   * @private
   */
  private static getCover(image: HTMLImageElement): HTMLCanvasElement {
    return image.parentElement?.querySelector(
      '[data-image-cover]'
    ) as HTMLCanvasElement;
  }

  /**
   * Removes the canvas cover
   * @param image
   * @private
   */
  private static removeCover(image: HTMLImageElement) {
    const cover = this.getCover(image);
    cover.parentElement!.removeChild(cover);
  }

  private static removeBlockerUI(mutationTarget: HTMLElement) {
    mutationTarget.querySelector('[data-image-cover]')?.remove();
  }

  /**
   * Hides the animation of an image by drawing the first frame on a canvas
   * @param image
   */
  private static async hideAnimation(image: HTMLImageElement): Promise<void> {
    // If image already has an animation controller, return
    if (image.dataset.animationController) return;

    // Wait until it's completely loaded
    await this.waitForImage(image);
    const parent = image.parentElement!;
    let canvas = parent.querySelector('[data-image-cover]');
    const width = image.clientWidth;
    const height = image.clientHeight;

    // Store state on image
    image.setAttribute('data-animation-state', 'paused');

    if (!canvas) {
      image.setAttribute('data-width', width.toString());
      image.setAttribute('data-height', height.toString());
      this.createCover(image);
    } else if (canvas && this.imageSizeChanged(image)) {
      // Replace canvas
      this.removeCover(image);
      this.createCover(image);
    }

    image.dataset.animationController = JSON.stringify(this);
  }

  /**
   * Hides the animation of all images on the page
   */
  private static hideAllAnimations(): void {
    const images: HTMLImageElement[] = this.getAllPotentiallyAnimated();
    for (let i = 0; i < images.length; i++) {
      this.hideAnimation(images[i]);
    }
  }

  /**
   * Filters a NodeList of images to only include those that could be animated
   */
  private static filterImages(
    images: NodeListOf<HTMLImageElement>
  ): HTMLImageElement[] {
    return Array.from(images).filter((image) => this.couldBeAnimated(image));
  }

  /**
   * Checks a given image's src and srcset for common animation extensions
   * @param image
   */
  static couldBeAnimated(image: HTMLImageElement): boolean {
    const src = image.src;
    const srcset = image.srcset;

    const hasExpectedExtension = this.extensions.some(
      (extension) => src.includes(extension) || srcset.includes(extension)
    );

    return hasExpectedExtension || image.hasAttribute('data-animated');
  }

  static getAllPotentiallyAnimated(): HTMLImageElement[] {
    const allImages: NodeListOf<HTMLImageElement> =
      document.querySelectorAll('img');
    return this.filterImages(allImages);
  }
}
