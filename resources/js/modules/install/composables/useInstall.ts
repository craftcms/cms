import {computed, ref} from 'vue';
import {useStepper} from '@vueuse/core';
import {t} from '@craftcms/ui';
import {
  validateAccount,
  validateDb,
  validateSite,
} from '@actions/InstallController';

interface InstallStep {
  id?: string;
  label?: string;
  action?: string;
  heading?: string;
  submitLabel?: string;
  hidden?: boolean;
}

type InstallSteps = Record<string, InstallStep>;

export const useInstall = () => {
  const possibleSteps = ref<InstallSteps>({
    start: {},
    license: {
      id: 'license',
      label: 'License',
    },
    account: {
      id: 'account',
      label: 'Account',
      action: validateAccount().url,
      heading: t('Create your account'),
    },
    db: {
      id: 'db',
      label: 'Database',
      action: validateDb().url,
      heading: t('Connect to your database'),
    },
    site: {
      id: 'site',
      label: 'Site',
      action: validateSite().url,
      heading: t('Set up your site'),
      submitLabel: t('Finish up'),
    },
    installing: {
      label: 'Installing',
      id: 'installing',
    },
  });

  const steps = computed(() => {
    return Object.entries(possibleSteps.value).reduce<InstallSteps>(
      (acc, [key, step]) => {
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
    return Object.entries(steps.value).reduce<InstallSteps>(
      (acc, [key, step]) => {
        if (step.label ?? false) {
          acc[key] = step;
        }

        return acc;
      },
      {}
    );
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
