import {Meta, StoryObj} from '@storybook/web-components-vite';
import {default as CraftAvatar} from './avatar.js';

declare const args: Partial<unknown> & {
    [key: string]: any;
};
declare const meta: Meta<CraftAvatar>;
export default meta;
type Story = StoryObj<CraftAvatar & typeof args>;
export declare const Default: Story;
export declare const CustomColors: Story;
export declare const CustomSize: Story;
