import {Base} from '@craftcms/garnish';

// Mostly Craft.* runtime seams (the still-legacy element index + prompt handler
// + progress bar); the only jQuery is via the objects Craft.ui/Craft.cp return
// (the undo button + notice close button).
declare const Craft: any;

/**
 * AssetMover — a port of `Craft.AssetMover` onto `@craftcms/garnish` `Base`.
 * Moves assets/folders in batches with conflict prompts, a progress bar, and an
 * undo affordance. Booted by the asset index (`new Craft.AssetMover()`), so
 * exposed on `window.Craft`.
 */
export class AssetMover extends Base {
  // The number of selected items that can be moved before we ask for confirmation.
  static countLimit = 50;
  // The total size of the selected assets that can be moved before we confirm (50MB).
  static sizeLimit = 50000000;

  undoMovedAssets: any = null;
  undoMovedFolders: any = null;
  conflictCount = 0;

  constructor() {
    super();
    if (new.target === AssetMover) {
      this.init();
    }
  }

  init(): void {
    this.undoMovedAssets = {
      assetIds: [],
      targetFolderId: null,
    };
    this.undoMovedFolders = {
      folderIds: [],
      targetFolderId: null,
    };
  }

  async getMoveParams(folderIds: any, assetIds: any): Promise<any> {
    let proceed = true;
    let confirmed = false;
    let response;

    try {
      // get total size of elements that should be moved
      response = await Craft.sendActionRequest('POST', 'assets/move-info', {
        data: {folderIds, assetIds},
      });
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
      return;
    }

    if (
      response.data.count > AssetMover.countLimit ||
      response.data.totalSize > AssetMover.sizeLimit
    ) {
      proceed = confirm(
        Craft.t('app', 'Are you sure you want to move the selected items?')
      );
      confirmed = true;
    }

    return {proceed, confirmed};
  }

  successNotice(moveParams: any, message: any, allowUndo = true): void {
    let details = null;
    let $undoBtn = null;

    if (allowUndo && this.conflictCount === 0) {
      $undoBtn = Craft.ui.createButton({
        label: Craft.t('app', 'Undo'),
        spinner: true,
      });
    }

    // if we didn't reach the limit, so the move was not confirmed, we want to give the option to undo
    if (!moveParams.confirmed && allowUndo) {
      details = {
        details: $undoBtn,
      };
    }

    const notice = Craft.cp.displayNotice(message, details);

    if (allowUndo) {
      $undoBtn?.on('activate', async () => {
        // ask to confirm if they want to undo
        if (
          confirm(Craft.t('app', 'Are you sure you want to undo the move?'))
        ) {
          // if yes, undo
          const totalFoldersMoved = await this.moveFolders(
            this.undoMovedFolders.folderIds,
            this.undoMovedFolders.targetFolderId
          );
          const totalAssetsMoved = await this.moveAssets(
            this.undoMovedAssets.assetIds,
            this.undoMovedAssets.targetFolderId
          );
          const totalItemsMoved = totalFoldersMoved + totalAssetsMoved;
          if (totalItemsMoved) {
            this.successNotice(
              moveParams,
              Craft.t('app', 'Move reverted.'),
              false
            );
            Craft.elementIndex.updateElements(true);
          }
        }
        // if no, just close the notice;
        notice.$closeBtn.trigger('click');
      });
    }
  }

  moveAssets(assetIds: any, targetFolderId: any, currentFolderId?: any): any {
    const requests = assetIds.map((assetId: any) => {
      return {
        for: 'asset',
        action: 'assets/move-asset',
        params: {
          assetId,
          folderId: targetFolderId,
          initialFolderId: currentFolderId,
        },
      };
    });
    return this._batchMoveRequests(requests, {
      conflictChoices: [
        {value: 'keepBoth', title: Craft.t('app', 'Keep both')},
        {value: 'replace', title: Craft.t('app', 'Replace it')},
      ],
      handleConflictChoice: function (prompt: any) {
        const params: any = {
          folderId: prompt.request.params.folderId,
          assetId: prompt.assetId,
          initialFolderId: prompt.request.params.initialFolderId,
        };
        switch (prompt.choice) {
          case 'replace':
            params.force = true;
            break;
          case 'keepBoth':
            params.filename = prompt.suggestedFilename;
            break;
        }
        return {action: 'assets/move-asset', params};
      },
    });
  }

  moveFolders(folderIds: any, targetFolderId: any, currentFolderId?: any): any {
    return new Promise((resolve, reject) => {
      const transferList: any[] = [];
      let folderIdsToDelete: any[] = [];
      const folderIdsWithFailedAssets: any[] = [];

      const requests = folderIds.map((folderId: any) => {
        return {
          for: 'folder',
          action: 'assets/move-folder',
          params: {
            folderId,
            parentId: targetFolderId,
            initialParentId: currentFolderId,
          },
          onSuccess: (response: any) => {
            if (response.transferList.length) {
              response.transferList.forEach(
                (item: any) => (item['oldFolderId'] = folderId)
              );
              transferList.push(...response.transferList);
            }
            folderIdsToDelete.push(folderId);
          },
        };
      });

      this._batchMoveRequests(requests, {
        conflictChoices: [
          {
            value: 'replace',
            title: Craft.t(
              'app',
              'Replace the folder (all existing files will be deleted)'
            ),
          },
          {
            value: 'merge',
            title: Craft.t(
              'app',
              'Merge the folder (any conflicting files will be replaced)'
            ),
          },
        ],
        handleConflictChoice: function (prompt: any) {
          const params: any = {
            folderId: prompt.folderId,
            parentId: prompt.parentId,
            initialParentId: prompt.request.params.initialParentId,
          };
          switch (prompt.choice) {
            case 'replace':
              params.force = true;
              break;
            case 'merge':
              params.merge = true;
              break;
          }
          return {action: 'assets/move-folder', params};
        },
      })
        .then((totalMoved: any) => {
          this._processTransferList(transferList).then((response: any) => {
            response.forEach((asset: any) => {
              if (asset.error?.length) {
                const oldFolderId = asset.request.params.oldFolderId;

                // store the old folder ID of the asset that failed to move
                if (!folderIdsWithFailedAssets.includes(oldFolderId)) {
                  folderIdsWithFailedAssets.push(oldFolderId);
                }

                // if the old folder ID of the asset that failed to move is due for deletion, prevent it
                if (folderIdsToDelete.includes(oldFolderId)) {
                  folderIdsToDelete = folderIdsToDelete.filter(
                    (item) => item !== oldFolderId
                  );
                }
              }
            });

            // adjust number of moved folders
            totalMoved = totalMoved - folderIdsWithFailedAssets.length;
            this._deleteFolders(folderIdsToDelete).then(() => {
              resolve(totalMoved);
            });
          });
        })
        .catch(reject);
    });
  }

  _processTransferList(transferList: any): any {
    return this._batchRequests(
      transferList.map((params: any) => {
        return {action: 'assets/move-asset', params};
      })
    );
  }

  _deleteFolders(folderIds: any): any {
    return this._batchRequests(
      folderIds.map((folderId: any) => {
        return {action: 'assets/delete-folder', params: {folderId}};
      })
    );
  }

  _batchMoveRequests(requests: any, settings: any): any {
    return new Promise((resolve) => {
      let totalMoved = 0;

      this._batchRequests(requests).then((responses: any) => {
        Craft.elementIndex.promptHandler.resetPrompts();

        // Loop through all the responses
        for (const response of responses) {
          if (response.success) {
            if (response.newFolderId) {
              this.undoMovedFolders.folderIds.push(response.newFolderId);
              if (this.undoMovedFolders.targetFolderId === null) {
                this.undoMovedFolders.targetFolderId =
                  response.request.params.initialParentId;
              }
            } else {
              this.undoMovedAssets.assetIds.push(
                response.request.params.assetId
              );
              if (this.undoMovedAssets.targetFolderId === null) {
                this.undoMovedAssets.targetFolderId =
                  response.request.params.initialFolderId;
              }
            }
            totalMoved++;
          }

          // Push prompt into prompt array
          if (response.conflict) {
            this.conflictCount++;
            Craft.elementIndex.promptHandler.addPrompt(
              Object.assign({}, response, {
                prompt: {
                  message: response.conflict,
                  choices: settings.conflictChoices,
                },
              })
            );
          }

          if (response.error) {
            Craft.cp.displayError(response.error);
          }
        }

        if (!Craft.elementIndex.promptHandler.getPromptCount()) {
          resolve(totalMoved);
          return;
        }

        Craft.elementIndex.promptHandler.showBatchPrompts((prompts: any) => {
          Craft.elementIndex.promptHandler.resetPrompts();
          const nextRequests = [];
          for (const prompt of prompts) {
            if (prompt.choice === 'cancel') {
              continue;
            }
            if (settings.handleConflictChoice) {
              const nextRequest = settings.handleConflictChoice(prompt);
              if (prompt.request && prompt.request.onSuccess) {
                nextRequest.onSuccess = prompt.request.onSuccess;
              }
              nextRequests.push(nextRequest);
            }
          }
          this._batchMoveRequests(nextRequests, settings).then(
            (nextTotalMoved: any) => {
              resolve(totalMoved + nextTotalMoved);
            }
          );
        });
      });
    });
  }

  _batchRequests(requests: any): any {
    return new Promise((resolve) => {
      if (!requests.length) {
        resolve([]);
        return;
      }

      Craft.elementIndex.setIndexBusy(false);
      Craft.elementIndex._positionProgressBar();
      Craft.elementIndex.progressBar.resetProgressBar();
      Craft.elementIndex.progressBar.setItemCount(requests.length);
      Craft.elementIndex.progressBar.showProgressBar();

      const responses: any[] = [];
      let activeRequests = requests.length;

      for (const request of requests) {
        Craft.sendActionRequest('POST', request.action, {
          data: request.params,
        })
          .then((response: any) => {
            responses.push(
              Object.assign({success: true}, response.data, {request})
            );
            if (request.onSuccess) {
              request.onSuccess(response.data);
            }
          })
          .catch((failure: any) => {
            if (failure.response && failure.response.data) {
              responses.push(
                Object.assign({success: false}, failure.response.data, {
                  request,
                })
              );
            }
          })
          .finally(() => {
            // Was that the last one?
            if (--activeRequests === 0) {
              Craft.elementIndex.setIndexAvailable();
              Craft.elementIndex.progressBar.hideProgressBar();
              // If assets were just merged we should get the reference tags updated right away
              Craft.cp.runQueue();
              resolve(responses);
            } else {
              Craft.elementIndex.progressBar.incrementProcessedItemCount(1);
              Craft.elementIndex.progressBar.updateProgressBar();
            }
          });
      }
    });
  }
}
