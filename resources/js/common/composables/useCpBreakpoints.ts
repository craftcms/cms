import {useBreakpoints, breakpointsTailwind} from '@vueuse/core';

export const useCpBreakpoints = () => useBreakpoints(breakpointsTailwind);

export const cpBreakpoints = useCpBreakpoints();
