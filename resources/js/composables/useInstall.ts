import {computed, ref} from 'vue';
import {useStepper} from '@vueuse/core';
import {usePage} from '@inertiajs/vue3';
import {t} from '@craftcms/cp';

type InstallScreens =
  | 'start'
  | 'license'
  | 'db'
  | 'account'
  | 'site'
  | 'installing'
  | 'complete';

export const useInstall = () => {
  const {props} = usePage<{
    screen: InstallScreens;
    showDbScreen: boolean;
    licenseHtml: string;
    useEmailAsUsername: boolean;
  }>();

  const possibleSteps = ref<Record<string, any>>({
    start: {},
    license: {
      id: 'license',
      label: 'License',
    },
    account: {
      id: 'account',
      label: 'Account',
      action: '/admin/actions/install/validate-account',
      heading: t('app', 'Create your account'),
    },
    db: {
      id: 'db',
      label: 'Database',
      action: '/admin/actions/install/validate-db',
      heading: t('app', 'Connect to your database'),
      hidden: !props.showDbScreen,
    },
    site: {
      id: 'site',
      label: 'Site',
      action: '/admin/actions/install/validate-site',
      heading: t('app', 'Set up your site'),
    },
    installing: {
      label: 'Installing',
      id: 'installing',
    },
    complete: {},
  });

  const steps = computed(() => {
    return Object.keys(possibleSteps.value).reduce<Record<string, any>>(
      (acc, key) => {
        const step = possibleSteps.value[key];
        const hidden = step.hidden ?? false;

        if (!hidden) {
          acc[key] = step;
        }

        return acc;
      },
      {}
    );
  });

  const dotSteps = computed(() => {
    return Object.keys(steps.value).reduce<Record<string, any>>((acc, key) => {
      const step = steps.value[key];
      if (step.label ?? false) {
        acc[key] = step;
      }

      return acc;
    }, {});
  });

  const stepper = useStepper(steps);
  const currentId = computed(
    () => stepper.stepNames.value[stepper.index.value]
  );

  return {
    ...stepper,
    currentId,
    dotSteps,
  };
};
