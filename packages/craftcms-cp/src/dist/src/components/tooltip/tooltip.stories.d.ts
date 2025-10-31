import {StoryObj} from '@storybook/web-components-vite';

declare const meta: {
    title: string;
    component: string;
    args: {
        placement: string;
        content: string;
    };
    parameters: {
        layout: string;
    };
    render: (args: any) => import('lit-html').TemplateResult<1>;
};
export default meta;
type Story = StoryObj<any>;
export declare const Playground: Story;
