import {StoryObj} from '@storybook/web-components-vite';

declare const meta: {
    title: string;
    component: string;
    argTypes: {
        width: {};
        size: {
            control: {
                type: "text";
            };
            defaultValue: null;
        };
    };
    render: ({ size, width, message }: any) => import('lit-html').TemplateResult<1>;
};
export default meta;
type Story = StoryObj<any>;
export declare const Basic: Story;
export declare const Size: Story;
export declare const Message: Story;
