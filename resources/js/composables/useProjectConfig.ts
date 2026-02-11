import {ref, type Ref} from 'vue';
import {router} from '@inertiajs/vue3';
import axios from 'axios';
import {t} from '@craftcms/cp';
import ProjectConfigController, {
  download,
} from '@actions/Utilities/ProjectConfigController';

export interface ProjectConfigProps {
  readOnly: boolean;
  invert: boolean;
  yamlExists: boolean;
  areChangesPending: boolean;
  entireConfig: string;
}

interface UseProjectConfigReturn {
  isDownloading: Ref<boolean>;
  isDiscarding: Ref<boolean>;
  discardChanges: () => void;
  downloadConfig: () => Promise<void>;
}

export function useProjectConfig(
  props: ProjectConfigProps
): UseProjectConfigReturn {
  const isDownloading = ref(false);
  const isDiscarding = ref(false);

  function discardChanges(): void {
    if (
      !confirm(
        t(
          'Are you sure you want to discard the pending project config YAML changes?'
        )
      )
    ) {
      return;
    }

    isDiscarding.value = true;
    router.post(
      ProjectConfigController.discard().url,
      {},
      {
        onFinish: () => {
          isDiscarding.value = false;
        },
      }
    );
  }

  async function downloadConfig(): Promise<void> {
    isDownloading.value = true;
    try {
      const response = await axios.get(download().url, {
        responseType: 'blob',
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'project.zip');
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Download failed:', error);
    } finally {
      isDownloading.value = false;
    }
  }

  return {
    isDownloading,
    isDiscarding,
    discardChanges,
    downloadConfig,
  };
}
