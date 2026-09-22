import {actionClient} from '@craftcms/ui';
import {router} from '@inertiajs/vue3';
import {store} from '@/routes/craft/actions/craft/cp/uploads';
import ResolveUploadConflictController from '@actions/Assets/ResolveUploadConflictController';
import {deleteAsset} from '@actions/Assets/ActionController';
import {UploadQueue, type UploadJob, uploadErrorMessage} from './upload-queue';
import {
  type AssetUploadDestination,
  type UploadConflictChoice,
  showUploadConflict,
  createUploadCompleteNotification,
} from './upload-notification';

declare const Craft: any;

type UploadResult = CraftCms.Cms.Asset.Data.UploadResult;

interface UploadBatch {
  completed: number;
  notification?: ReturnType<typeof createUploadCompleteNotification>;
}

interface AssetUploadContext {
  destination: AssetUploadDestination;
  batch: UploadBatch;
  resolving: boolean;
  closePrompt?: () => void;
}

type QueuedAssetUpload = UploadJob<UploadResult, AssetUploadContext>;

/** Adds Assets index conflicts, grouped results, and folder changes to the shared queue. */
export class AssetUploadQueue extends UploadQueue<
  UploadResult,
  AssetUploadContext
> {
  private batches = new WeakMap<object, UploadBatch>();
  private completedBatches = new Set<UploadBatch>();
  private changes = new Map<number, number>();
  private nextRevision = 0;
  private stopNavigation?: () => void;

  enqueue(
    file: File,
    destination: AssetUploadDestination,
    selection: object
  ): void {
    if (!this.stopNavigation) {
      const beforeUnload = (event: BeforeUnloadEvent) => {
        if (this.hasPending) {
          event.preventDefault();
          event.returnValue = '';
        }
      };
      window.addEventListener('beforeunload', beforeUnload);
      const stop = router.on('navigate', (event) => {
        if (event.detail.page.component.startsWith('auth/')) {
          void this.cancelAll();
        }
      });
      this.stopNavigation = () => {
        window.removeEventListener('beforeunload', beforeUnload);
        stop();
      };
    }

    let batch = this.batches.get(selection);
    if (!batch) {
      batch = {completed: 0};
      this.batches.set(selection, batch);
    }
    this.add(
      file,
      {
        url: store.url(),
        parameters: {folderId: destination.folderId},
        csrfToken: Craft.csrfTokenValue,
      },
      {destination: {...destination}, batch, resolving: false}
    );
  }

  revision(folderId: number): number {
    return this.changes.get(folderId) ?? 0;
  }

  acknowledge(folderId: number, revision: number): void {
    if (this.revision(folderId) === revision) {
      this.changes.delete(folderId);
    }
  }

  override async cancelAll(): Promise<void> {
    this.stopNavigation?.();
    this.stopNavigation = undefined;
    for (const job of this.jobs) {
      job.context.closePrompt?.();
    }
    this.batches = new WeakMap();
    this.changes.clear();
    for (const batch of this.completedBatches) {
      batch.notification?.close();
    }
    this.completedBatches.clear();
    await super.cancelAll();
    this.changes.clear();
  }

  override retry(job: QueuedAssetUpload): void {
    if (job.result?.conflict) {
      this.prompt(job);
    } else {
      super.retry(job);
    }
  }

  protected override succeeded(
    job: QueuedAssetUpload,
    result: UploadResult
  ): void {
    job.result = result;
    this.changed(job);
    if (result.conflict) {
      job.notification.updateState('conflict', result.conflict);
    } else {
      this.uploaded(job);
    }
  }

  private prompt(job: QueuedAssetUpload): void {
    if (!this.jobs.has(job) || job.context.resolving || !job.result?.conflict) {
      return;
    }
    job.context.closePrompt?.();
    job.context.closePrompt = showUploadConflict(
      job.result.conflict,
      (choice) => {
        this.resolve(job, choice).catch((error: Error) =>
          Craft.cp.displayError(error.message)
        );
      }
    );
  }

  private async resolve(
    job: QueuedAssetUpload,
    choice: UploadConflictChoice
  ): Promise<void> {
    if (!this.jobs.has(job) || job.context.resolving) {
      return;
    }
    if (choice === 'cancel') {
      await this.cancel(job);
      return;
    }
    job.context.resolving = true;
    job.notification.updateState('completing');
    try {
      if (choice === 'replace') {
        await actionClient.post(ResolveUploadConflictController.url(), {
          sourceAssetId: job.result!.assetId,
          ...(job.result!.conflictingAssetId
            ? {assetId: job.result!.conflictingAssetId}
            : {targetFilename: job.result!.filename}),
        });
      }
      if (this.jobs.has(job)) {
        this.changed(job);
        this.uploaded(job);
      }
    } catch (error) {
      if (this.jobs.has(job)) {
        job.notification.updateState('conflict', uploadErrorMessage(error));
      }
    } finally {
      job.context.resolving = false;
    }
  }

  override async cancel(job: QueuedAssetUpload): Promise<void> {
    if (
      !this.jobs.has(job) ||
      job.context.resolving ||
      job.upload.state === 'completing'
    ) {
      return;
    }
    if (job.result?.conflict) {
      job.context.resolving = true;
      job.notification.updateState('completing');
      try {
        await actionClient.post(deleteAsset.url(), {
          assetId: job.result.assetId,
        });
        if (this.jobs.has(job)) {
          this.changed(job);
        }
      } catch (error) {
        job.notification.updateState('conflict', uploadErrorMessage(error));
        throw error;
      } finally {
        job.context.resolving = false;
      }
    }
    job.context.closePrompt?.();
    await super.cancel(job);
  }

  private uploaded(job: QueuedAssetUpload): void {
    super.complete(job);
    const {batch, destination} = job.context;
    batch.completed++;
    if (!batch.notification) {
      batch.notification = createUploadCompleteNotification(destination, () => {
        batch.notification = undefined;
        this.completedBatches.delete(batch);
      });
      this.completedBatches.add(batch);
    }
    batch.notification.update(batch.completed);
    Craft.cp.runQueue();
  }

  private changed(job: QueuedAssetUpload): void {
    this.changes.set(job.context.destination.folderId, ++this.nextRevision);
    this.dispatchEvent(
      new CustomEvent('change', {detail: job.context.destination.folderId})
    );
  }
}

export const assetUploadQueue = new AssetUploadQueue();
