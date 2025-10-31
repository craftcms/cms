import {StoryObj} from '@storybook/web-components-vite';

declare const meta: {
    title: string;
    component: string;
    argTypes: {
        state: {
            control: {
                type: "select";
            };
            options: string[];
            defaultValue: null;
        };
    };
    play: ({ canvas, userEvent }: import('storybook/internal/csf').PlayFunctionContext<import('@storybook/web-components').WebComponentsRenderer, any>) => Promise<void>;
    render: (args: any) => import('lit-html').TemplateResult<1>;
};
export default meta;
type Story = StoryObj<any>;
export declare const Basic: Story;
export declare const Expanded: Story;
export declare const Persistant: Story;
