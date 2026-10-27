import {actionClient, t} from '@craftcms/ui';
import {router} from '@inertiajs/vue3';
import {store} from '@/routes/craft/actions/craft/cp/uploads';
import ResolveUploadConflictController from '@actions/Assets/ResolveUploadConflictController';
import {deleteAsset} from '@actions/Assets/ActionController';
import {UploadQueue, type UploadJob, uploadErrorMessage} from './upload-queue';
import {imageCompletionData} from './image-completion-data';
import type {ConflictResolution} from '@/modules/assets/assetMover';
import {
  type AssetUploadDestination,
  createUploadConflictDetails,
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
  /**
   * Why the uploaded asset is waiting on a choice: its filename conflicts with
   * another file's, or it looks like a copy of other images.
   */
  held?: 'conflict' | 'duplicates';
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
        completionData: imageCompletionData,
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
    this.batches = new WeakMap();
    this.changes.clear();
    for (const batch of this.completedBatches) {
      batch.notification?.close();
    }
    this.completedBatches.clear();
    await super.cancelAll();
    this.changes.clear();
  }

  protected override succeeded(
    job: QueuedAssetUpload,
    result: UploadResult
  ): void {
    job.result = result;
    this.changed(job);
    if (result.conflict) {
      this.hold(job, 'conflict');
    } else {
      this.holdIfDuplicated(job);
    }
  }

  /** Holds an upload that looks like a copy of other images, or completes it. */
  private holdIfDuplicated(job: QueuedAssetUpload): void {
    if (job.result?.duplicates?.length) {
      this.hold(job, 'duplicates');
    } else {
      this.uploaded(job);
    }
  }

  /** Shows the choices for a held upload, with `error` in place of its message. */
  private hold(
    job: QueuedAssetUpload,
    reason: 'conflict' | 'duplicates',
    error?: string
  ): void {
    job.context.held = reason;
    const onChoice = (choice: ConflictResolution) => {
      this.resolve(job, choice).catch((error: Error) =>
        Craft.cp.displayError(error.message)
      );
    };
    if (reason === 'conflict') {
      job.notification.showDetails(
        createUploadConflictDetails(error ?? job.result!.conflict!, onChoice)
      );
      return;
    }
    const duplicates = job.result!.duplicates!;
    job.notification.showDetails(
      createUploadConflictDetails(
        error ??
          t('“{filename}” looks like an image you already have.', {
            filename: job.upload.file.name,
          }),
        onChoice,
        {
          duplicates,
          navigate: (url) => router.visit(url),
          // Only one image for it to replace
          replaceable: duplicates.length === 1,
        }
      )
    );
  }

  private async resolve(
    job: QueuedAssetUpload,
    choice: ConflictResolution
  ): Promise<void> {
    if (!this.jobs.has(job) || job.context.resolving) {
      return;
    }
    if (choice === 'cancel') {
      await this.cancel(job);
      return;
    }
    const reason = job.context.held!;
    job.context.resolving = true;
    job.notification.updateState('completing');
    try {
      if (choice === 'replace') {
        await actionClient.post(ResolveUploadConflictController.url(), {
          sourceAssetId: job.result!.assetId,
          ...(reason === 'duplicates'
            ? {assetId: job.result!.duplicates![0]!.id}
            : job.result!.conflictingAssetId
              ? {assetId: job.result!.conflictingAssetId}
              : {targetFilename: job.result!.filename}),
        });
      }
      if (this.jobs.has(job)) {
        this.changed(job);
        job.context.held = undefined;
        // Keeping both files still leaves the new one looking like a copy
        if (reason === 'conflict' && choice === 'keepBoth') {
          this.holdIfDuplicated(job);
        } else {
          this.uploaded(job);
        }
      }
    } catch (error) {
      if (this.jobs.has(job)) {
        this.hold(job, reason, uploadErrorMessage(error));
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
    const reason = job.context.held;
    if (reason) {
      job.context.resolving = true;
      job.notification.updateState('completing');
      try {
        await actionClient.post(deleteAsset.url(), {
          assetId: job.result!.assetId,
        });
        if (this.jobs.has(job)) {
          this.changed(job);
        }
      } catch (error) {
        this.hold(job, reason, uploadErrorMessage(error));
        throw error;
      } finally {
        job.context.resolving = false;
      }
    }
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
