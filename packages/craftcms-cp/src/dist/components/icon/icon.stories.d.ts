import {StoryObj} from '@storybook/web-components-vite';

declare const meta: {
    title: string;
    component: string;
    args: {
        iconId: string;
    };
    render: ({ iconId }: any) => import('lit-html').TemplateResult<1>;
};
export default meta;
type Story = StoryObj<any>;
export declare const Default: Story;
