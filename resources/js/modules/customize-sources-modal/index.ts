import {
  CustomizeSourcesModal,
  PageSettingsModal,
  SourceDrag,
  Page,
  BaseSource,
  Source,
  CustomSource,
  Heading,
} from './customize-sources-modal';

export {
  CustomizeSourcesModal,
  PageSettingsModal,
  SourceDrag,
  Page,
  BaseSource,
  Source,
  CustomSource,
  Heading,
};

declare const window: any;

export function registerCraftGlobals(): void {
  const C = window.Craft;
  C.CustomizeSourcesModal = CustomizeSourcesModal;
  (C.CustomizeSourcesModal as any).PageSettingsModal = PageSettingsModal;
  (C.CustomizeSourcesModal as any).SourceDrag = SourceDrag;
  (C.CustomizeSourcesModal as any).Page = Page;
  (C.CustomizeSourcesModal as any).BaseSource = BaseSource;
  (C.CustomizeSourcesModal as any).Source = Source;
  (C.CustomizeSourcesModal as any).CustomSource = CustomSource;
  (C.CustomizeSourcesModal as any).Heading = Heading;
}
