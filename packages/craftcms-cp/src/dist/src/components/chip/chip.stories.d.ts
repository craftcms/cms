import {StoryObj} from '@storybook/web-components-vite';

declare const meta: {
    title: string;
    component: string;
    argTypes: {};
    render: (args: any) => import('lit-html').TemplateResult<1>;
};
export default meta;
type Story = StoryObj<any>;
export declare const Default: Story;
export declare const PrefixAndSuffix: Story;
export declare const PrefixOnly: Story;
export declare const SuffixOnly: Story;
