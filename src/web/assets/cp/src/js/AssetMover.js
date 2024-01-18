/** global: Craft */
/** global: Garnish */
/**
 * Asset mover class
 */
Craft.AssetMover = Garnish.Base.extend({
  moveAssets: function (assetIds, targetFolderId) {
    const requests = assetIds.map((assetId) => {
      return {
        for: 'asset',
        action: 'assets/move-asset',
        params: {
          assetId,
          folderId: targetFolderId,
        },
      };
    });
    return this._batchMoveRequests(requests, {
      conflictChoices: [
        {
          value: 'keepBoth',
          title: Craft.t('app', 'Keep both'),
        },
        {
          value: 'replace',
          title: Craft.t('app', 'Replace it'),
        },
      ],
      handleConflictChoice: function (prompt) {
        const params = {
          folderId: prompt.request.params.folderId,
          assetId: prompt.assetId,
        };
        switch (prompt.choice) {
          case 'replace':
            params.force = true;
            break;
          case 'keepBoth':
            params.filename = prompt.suggestedFilename;
            break;
        }
        return {
          action: 'assets/move-asset',
          params,
        };
      },
    });
  },

  moveFolders: function (folderIds, targetFolderId) {
    return new Promise((resolve, reject) => {
      const transferList = [];
      const folderIdsToDelete = [];

      const requests = folderIds.map((folderId) => {
        return {
          for: 'folder',
          action: 'assets/move-folder',
          params: {
            folderId,
            parentId: targetFolderId,
          },
          onSuccess: (response) => {
            if (response.transferList.length) {
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
        handleConflictChoice: function (prompt) {
          const params = {
            folderId: prompt.folderId,
            parentId: prompt.parentId,
          };
          switch (prompt.choice) {
            case 'replace':
              params.force = true;
              break;
            case 'merge':
              params.merge = true;
              break;
          }
          return {
            action: 'assets/move-folder',
            params,
          };
        },
      })
        .then((totalMoved) => {
          this._processTransferList(transferList).then(() => {
            this._deleteFolders(folderIdsToDelete).then(() => {
              resolve(totalMoved);
            });
          });
        })
        .catch(reject);
    });
  },

  _processTransferList: function (transferList) {
    return this._batchRequests(
      transferList.map((params) => {
        return {
          action: 'assets/move-asset',
          params,
        };
      })
    );
  },

  _deleteFolders: function (folderIds) {
    return this._batchRequests(
      folderIds.map((folderId) => {
        return {
          action: 'assets/delete-folder',
          params: {folderId},
        };
      })
    );
  },

  _batchMoveRequests: function (requests, settings) {
    return new Promise((resolve) => {
      let totalMoved = 0;

      this._batchRequests(requests).then((responses) => {
        Craft.elementIndex.promptHandler.resetPrompts();

        // Loop through all the responses
        for (const response of responses) {
          if (response.success) {
            totalMoved++;
          }

          // Push prompt into prompt array
          if (response.conflict) {
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

        Craft.elementIndex.promptHandler.showBatchPrompts((prompts) => {
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
            (nextTotalMoved) => {
              resolve(totalMoved + nextTotalMoved);
            }
          );
        });
      });
    });
  },

  _batchRequests: function (requests) {
    return new Promise((resolve) => {
      if (!requests.length) {
        resolve([]);
        return;
      }

      Craft.elementIndex.setIndexBusy();
      Craft.elementIndex._positionProgressBar();
      Craft.elementIndex.progressBar.resetProgressBar();
      Craft.elementIndex.progressBar.setItemCount(requests.length);
      Craft.elementIndex.progressBar.showProgressBar();

      const responses = [];
      let activeRequests = requests.length;

      for (const request of requests) {
        Craft.sendActionRequest('POST', request.action, {
          data: request.params,
        })
          .then((response) => {
            responses.push(
              Object.assign(
                {
                  success: true,
                },
                response.data,
                {request}
              )
            );
            if (request.onSuccess) {
              request.onSuccess(response.data);
            }
          })
          .catch((failure) => {
            if (failure.response && failure.response.data) {
              responses.push(
                Object.assign(
                  {
                    success: false,
                  },
                  failure.response.data,
                  {request}
                )
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
  },
});
