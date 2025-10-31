import {StoryObj} from '@storybook/web-components-vite';

declare const meta: {
    title: string;
    component: string;
    argTypes: {};
    render: ({ active, indicator }: any) => import('lit-html').TemplateResult<1>;
};
export default meta;
type Story = StoryObj<any>;
export declare const Default: Story;
export declare const Active: Story;
export declare const WithIndicator: Story;
export declare const WithChildren: Story;
