import {ConfigService} from '@craftcms/ui/services/Config';

/** Whether the control panel's language reads right to left. */
export function isRtl(): boolean {
  return ConfigService.getInstance().get('orientation') === 'rtl';
}

/** The physical side lines of text start on. */
export function startSide(): 'left' | 'right' {
  return isRtl() ? 'right' : 'left';
}
