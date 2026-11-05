import type {TextExpanderTriggers} from '@craftcms/ui';

export interface SystemMessageData {
  key: string;
  heading: string;
  subject: string;
  body: string;
  language: string;
  /** Offers the message's Twig variables while editing its subject and body. */
  textExpanderTriggers: TextExpanderTriggers;
}
