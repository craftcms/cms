import {computed, ref} from 'vue';
import {useStepper} from '@vueuse/core';
import {t} from '@craftcms/cp';

export const useInstall = () => {
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
      heading: t('Create your account'),
    },
    db: {
      id: 'db',
      label: 'Database',
      action: '/admin/actions/install/validate-db',
      heading: t('Connect to your database'),
    },
    site: {
      id: 'site',
      label: 'Site',
      action: '/admin/actions/install/validate-site',
      heading: t('Set up your site'),
      submitLabel: t('Finish up'),
    },
    installing: {
      label: 'Installing',
      id: 'installing',
    },
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
    possibleSteps,
    currentId,
    dotSteps,
  };
};
