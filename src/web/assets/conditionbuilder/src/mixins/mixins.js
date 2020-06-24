export default {
    methods: {
        getGroupContainerClasses(depth) {
            if (depth > 1) {
                return [
                    'tw-border-gray-200',
                    'tw-border-t',
                    'tw-border-b',
                    'tw-border-solid',
                    'tw-mt-2'
                ];
            }

            return [];
        },
        getGroupClasses(depth) {
            if (depth > 1) {
                return [
                    'tw-border-' + this.getDepthColor(depth, -1),
                    'tw-border-l-4',
                    'tw-border-solid',
                    'tw-pt-2',
                    'tw-pb-2',
                    'tw-pl-3',
                    'tw-border-r-0'
                ];
            }

            return [];
        },
        getRuleInputDepthBorderClass(depth) {
            return [
                'tw-border-' + this.getDepthColor(depth, -1),
                'tw-border-0',
                'tw-rounded',
                'tw-border-l-4',
                'tw-border-solid'
            ];
        },
        getGroupInputDepthBorderClass(depth) {
            return [
                'tw-border-' + this.getDepthColor(depth, 0),
                'tw-border-0',
                'tw-rounded',
                'tw-border-l-4',
                'tw-border-solid'
            ];
        },
        getDepthColor(depth, offset) {
            let depthClasses = [
                'green-400',
                'blue-400',
                'purple-400',
                'green-400',
                'blue-400',
                'purple-400',
            ];

            return [depthClasses[depth + offset]];
        }
    }
}