export interface ImageTransform {
  id: number | null;
  uid: string | null;
  name: string | null;
  handle: string | null;
  width: number | string | null;
  height: number | string | null;
  mode: string;
  position: string;
  quality: number | string | null;
  interlace: string;
  format: string | null;
  fill: string | null;
  upscale: boolean;
  parameterChangeTime: any[];
}

export interface ExistingImageTransform extends Omit<ImageTransform, 'id'> {
  id: number;
  uid: string;
  handle: string;
  name: string;
}
