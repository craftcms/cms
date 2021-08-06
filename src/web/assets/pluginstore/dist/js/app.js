/******/ (function(modules) { // webpackBootstrap
/******/ 	// install a JSONP callback for chunk loading
/******/ 	function webpackJsonpCallback(data) {
/******/ 		var chunkIds = data[0];
/******/ 		var moreModules = data[1];
/******/ 		var executeModules = data[2];
/******/
/******/ 		// add "moreModules" to the modules object,
/******/ 		// then flag all "chunkIds" as loaded and fire callback
/******/ 		var moduleId, chunkId, i = 0, resolves = [];
/******/ 		for(;i < chunkIds.length; i++) {
/******/ 			chunkId = chunkIds[i];
/******/ 			if(Object.prototype.hasOwnProperty.call(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 				resolves.push(installedChunks[chunkId][0]);
/******/ 			}
/******/ 			installedChunks[chunkId] = 0;
/******/ 		}
/******/ 		for(moduleId in moreModules) {
/******/ 			if(Object.prototype.hasOwnProperty.call(moreModules, moduleId)) {
/******/ 				modules[moduleId] = moreModules[moduleId];
/******/ 			}
/******/ 		}
/******/ 		if(parentJsonpFunction) parentJsonpFunction(data);
/******/
/******/ 		while(resolves.length) {
/******/ 			resolves.shift()();
/******/ 		}
/******/
/******/ 		// add entry modules from loaded chunk to deferred list
/******/ 		deferredModules.push.apply(deferredModules, executeModules || []);
/******/
/******/ 		// run deferred modules when all chunks ready
/******/ 		return checkDeferredModules();
/******/ 	};
/******/ 	function checkDeferredModules() {
/******/ 		var result;
/******/ 		for(var i = 0; i < deferredModules.length; i++) {
/******/ 			var deferredModule = deferredModules[i];
/******/ 			var fulfilled = true;
/******/ 			for(var j = 1; j < deferredModule.length; j++) {
/******/ 				var depId = deferredModule[j];
/******/ 				if(installedChunks[depId] !== 0) fulfilled = false;
/******/ 			}
/******/ 			if(fulfilled) {
/******/ 				deferredModules.splice(i--, 1);
/******/ 				result = __webpack_require__(__webpack_require__.s = deferredModule[0]);
/******/ 			}
/******/ 		}
/******/
/******/ 		return result;
/******/ 	}
/******/ 	function hotDisposeChunk(chunkId) {
/******/ 		delete installedChunks[chunkId];
/******/ 	}
/******/ 	var parentHotUpdateCallback = window["webpackHotUpdate"];
/******/ 	window["webpackHotUpdate"] = // eslint-disable-next-line no-unused-vars
/******/ 	function webpackHotUpdateCallback(chunkId, moreModules) {
/******/ 		hotAddUpdateChunk(chunkId, moreModules);
/******/ 		if (parentHotUpdateCallback) parentHotUpdateCallback(chunkId, moreModules);
/******/ 	} ;
/******/
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	function hotDownloadUpdateChunk(chunkId) {
/******/ 		var script = document.createElement("script");
/******/ 		script.charset = "utf-8";
/******/ 		script.src = __webpack_require__.p + "" + chunkId + "." + hotCurrentHash + ".hot-update.js";
/******/ 		if (null) script.crossOrigin = null;
/******/ 		document.head.appendChild(script);
/******/ 	}
/******/
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	function hotDownloadManifest(requestTimeout) {
/******/ 		requestTimeout = requestTimeout || 10000;
/******/ 		return new Promise(function(resolve, reject) {
/******/ 			if (typeof XMLHttpRequest === "undefined") {
/******/ 				return reject(new Error("No browser support"));
/******/ 			}
/******/ 			try {
/******/ 				var request = new XMLHttpRequest();
/******/ 				var requestPath = __webpack_require__.p + "" + hotCurrentHash + ".hot-update.json";
/******/ 				request.open("GET", requestPath, true);
/******/ 				request.timeout = requestTimeout;
/******/ 				request.send(null);
/******/ 			} catch (err) {
/******/ 				return reject(err);
/******/ 			}
/******/ 			request.onreadystatechange = function() {
/******/ 				if (request.readyState !== 4) return;
/******/ 				if (request.status === 0) {
/******/ 					// timeout
/******/ 					reject(
/******/ 						new Error("Manifest request to " + requestPath + " timed out.")
/******/ 					);
/******/ 				} else if (request.status === 404) {
/******/ 					// no update available
/******/ 					resolve();
/******/ 				} else if (request.status !== 200 && request.status !== 304) {
/******/ 					// other failure
/******/ 					reject(new Error("Manifest request to " + requestPath + " failed."));
/******/ 				} else {
/******/ 					// success
/******/ 					try {
/******/ 						var update = JSON.parse(request.responseText);
/******/ 					} catch (e) {
/******/ 						reject(e);
/******/ 						return;
/******/ 					}
/******/ 					resolve(update);
/******/ 				}
/******/ 			};
/******/ 		});
/******/ 	}
/******/
/******/ 	var hotApplyOnUpdate = true;
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	var hotCurrentHash = "e23f1d824159f3fd7874";
/******/ 	var hotRequestTimeout = 10000;
/******/ 	var hotCurrentModuleData = {};
/******/ 	var hotCurrentChildModule;
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	var hotCurrentParents = [];
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	var hotCurrentParentsTemp = [];
/******/
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	function hotCreateRequire(moduleId) {
/******/ 		var me = installedModules[moduleId];
/******/ 		if (!me) return __webpack_require__;
/******/ 		var fn = function(request) {
/******/ 			if (me.hot.active) {
/******/ 				if (installedModules[request]) {
/******/ 					if (installedModules[request].parents.indexOf(moduleId) === -1) {
/******/ 						installedModules[request].parents.push(moduleId);
/******/ 					}
/******/ 				} else {
/******/ 					hotCurrentParents = [moduleId];
/******/ 					hotCurrentChildModule = request;
/******/ 				}
/******/ 				if (me.children.indexOf(request) === -1) {
/******/ 					me.children.push(request);
/******/ 				}
/******/ 			} else {
/******/ 				console.warn(
/******/ 					"[HMR] unexpected require(" +
/******/ 						request +
/******/ 						") from disposed module " +
/******/ 						moduleId
/******/ 				);
/******/ 				hotCurrentParents = [];
/******/ 			}
/******/ 			return __webpack_require__(request);
/******/ 		};
/******/ 		var ObjectFactory = function ObjectFactory(name) {
/******/ 			return {
/******/ 				configurable: true,
/******/ 				enumerable: true,
/******/ 				get: function() {
/******/ 					return __webpack_require__[name];
/******/ 				},
/******/ 				set: function(value) {
/******/ 					__webpack_require__[name] = value;
/******/ 				}
/******/ 			};
/******/ 		};
/******/ 		for (var name in __webpack_require__) {
/******/ 			if (
/******/ 				Object.prototype.hasOwnProperty.call(__webpack_require__, name) &&
/******/ 				name !== "e" &&
/******/ 				name !== "t"
/******/ 			) {
/******/ 				Object.defineProperty(fn, name, ObjectFactory(name));
/******/ 			}
/******/ 		}
/******/ 		fn.e = function(chunkId) {
/******/ 			if (hotStatus === "ready") hotSetStatus("prepare");
/******/ 			hotChunksLoading++;
/******/ 			return __webpack_require__.e(chunkId).then(finishChunkLoading, function(err) {
/******/ 				finishChunkLoading();
/******/ 				throw err;
/******/ 			});
/******/
/******/ 			function finishChunkLoading() {
/******/ 				hotChunksLoading--;
/******/ 				if (hotStatus === "prepare") {
/******/ 					if (!hotWaitingFilesMap[chunkId]) {
/******/ 						hotEnsureUpdateChunk(chunkId);
/******/ 					}
/******/ 					if (hotChunksLoading === 0 && hotWaitingFiles === 0) {
/******/ 						hotUpdateDownloaded();
/******/ 					}
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 		fn.t = function(value, mode) {
/******/ 			if (mode & 1) value = fn(value);
/******/ 			return __webpack_require__.t(value, mode & ~1);
/******/ 		};
/******/ 		return fn;
/******/ 	}
/******/
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	function hotCreateModule(moduleId) {
/******/ 		var hot = {
/******/ 			// private stuff
/******/ 			_acceptedDependencies: {},
/******/ 			_declinedDependencies: {},
/******/ 			_selfAccepted: false,
/******/ 			_selfDeclined: false,
/******/ 			_selfInvalidated: false,
/******/ 			_disposeHandlers: [],
/******/ 			_main: hotCurrentChildModule !== moduleId,
/******/
/******/ 			// Module API
/******/ 			active: true,
/******/ 			accept: function(dep, callback) {
/******/ 				if (dep === undefined) hot._selfAccepted = true;
/******/ 				else if (typeof dep === "function") hot._selfAccepted = dep;
/******/ 				else if (typeof dep === "object")
/******/ 					for (var i = 0; i < dep.length; i++)
/******/ 						hot._acceptedDependencies[dep[i]] = callback || function() {};
/******/ 				else hot._acceptedDependencies[dep] = callback || function() {};
/******/ 			},
/******/ 			decline: function(dep) {
/******/ 				if (dep === undefined) hot._selfDeclined = true;
/******/ 				else if (typeof dep === "object")
/******/ 					for (var i = 0; i < dep.length; i++)
/******/ 						hot._declinedDependencies[dep[i]] = true;
/******/ 				else hot._declinedDependencies[dep] = true;
/******/ 			},
/******/ 			dispose: function(callback) {
/******/ 				hot._disposeHandlers.push(callback);
/******/ 			},
/******/ 			addDisposeHandler: function(callback) {
/******/ 				hot._disposeHandlers.push(callback);
/******/ 			},
/******/ 			removeDisposeHandler: function(callback) {
/******/ 				var idx = hot._disposeHandlers.indexOf(callback);
/******/ 				if (idx >= 0) hot._disposeHandlers.splice(idx, 1);
/******/ 			},
/******/ 			invalidate: function() {
/******/ 				this._selfInvalidated = true;
/******/ 				switch (hotStatus) {
/******/ 					case "idle":
/******/ 						hotUpdate = {};
/******/ 						hotUpdate[moduleId] = modules[moduleId];
/******/ 						hotSetStatus("ready");
/******/ 						break;
/******/ 					case "ready":
/******/ 						hotApplyInvalidatedModule(moduleId);
/******/ 						break;
/******/ 					case "prepare":
/******/ 					case "check":
/******/ 					case "dispose":
/******/ 					case "apply":
/******/ 						(hotQueuedInvalidatedModules =
/******/ 							hotQueuedInvalidatedModules || []).push(moduleId);
/******/ 						break;
/******/ 					default:
/******/ 						// ignore requests in error states
/******/ 						break;
/******/ 				}
/******/ 			},
/******/
/******/ 			// Management API
/******/ 			check: hotCheck,
/******/ 			apply: hotApply,
/******/ 			status: function(l) {
/******/ 				if (!l) return hotStatus;
/******/ 				hotStatusHandlers.push(l);
/******/ 			},
/******/ 			addStatusHandler: function(l) {
/******/ 				hotStatusHandlers.push(l);
/******/ 			},
/******/ 			removeStatusHandler: function(l) {
/******/ 				var idx = hotStatusHandlers.indexOf(l);
/******/ 				if (idx >= 0) hotStatusHandlers.splice(idx, 1);
/******/ 			},
/******/
/******/ 			//inherit from previous dispose call
/******/ 			data: hotCurrentModuleData[moduleId]
/******/ 		};
/******/ 		hotCurrentChildModule = undefined;
/******/ 		return hot;
/******/ 	}
/******/
/******/ 	var hotStatusHandlers = [];
/******/ 	var hotStatus = "idle";
/******/
/******/ 	function hotSetStatus(newStatus) {
/******/ 		hotStatus = newStatus;
/******/ 		for (var i = 0; i < hotStatusHandlers.length; i++)
/******/ 			hotStatusHandlers[i].call(null, newStatus);
/******/ 	}
/******/
/******/ 	// while downloading
/******/ 	var hotWaitingFiles = 0;
/******/ 	var hotChunksLoading = 0;
/******/ 	var hotWaitingFilesMap = {};
/******/ 	var hotRequestedFilesMap = {};
/******/ 	var hotAvailableFilesMap = {};
/******/ 	var hotDeferred;
/******/
/******/ 	// The update info
/******/ 	var hotUpdate, hotUpdateNewHash, hotQueuedInvalidatedModules;
/******/
/******/ 	function toModuleId(id) {
/******/ 		var isNumber = +id + "" === id;
/******/ 		return isNumber ? +id : id;
/******/ 	}
/******/
/******/ 	function hotCheck(apply) {
/******/ 		if (hotStatus !== "idle") {
/******/ 			throw new Error("check() is only allowed in idle status");
/******/ 		}
/******/ 		hotApplyOnUpdate = apply;
/******/ 		hotSetStatus("check");
/******/ 		return hotDownloadManifest(hotRequestTimeout).then(function(update) {
/******/ 			if (!update) {
/******/ 				hotSetStatus(hotApplyInvalidatedModules() ? "ready" : "idle");
/******/ 				return null;
/******/ 			}
/******/ 			hotRequestedFilesMap = {};
/******/ 			hotWaitingFilesMap = {};
/******/ 			hotAvailableFilesMap = update.c;
/******/ 			hotUpdateNewHash = update.h;
/******/
/******/ 			hotSetStatus("prepare");
/******/ 			var promise = new Promise(function(resolve, reject) {
/******/ 				hotDeferred = {
/******/ 					resolve: resolve,
/******/ 					reject: reject
/******/ 				};
/******/ 			});
/******/ 			hotUpdate = {};
/******/ 			for(var chunkId in installedChunks)
/******/ 			// eslint-disable-next-line no-lone-blocks
/******/ 			{
/******/ 				hotEnsureUpdateChunk(chunkId);
/******/ 			}
/******/ 			if (
/******/ 				hotStatus === "prepare" &&
/******/ 				hotChunksLoading === 0 &&
/******/ 				hotWaitingFiles === 0
/******/ 			) {
/******/ 				hotUpdateDownloaded();
/******/ 			}
/******/ 			return promise;
/******/ 		});
/******/ 	}
/******/
/******/ 	// eslint-disable-next-line no-unused-vars
/******/ 	function hotAddUpdateChunk(chunkId, moreModules) {
/******/ 		if (!hotAvailableFilesMap[chunkId] || !hotRequestedFilesMap[chunkId])
/******/ 			return;
/******/ 		hotRequestedFilesMap[chunkId] = false;
/******/ 		for (var moduleId in moreModules) {
/******/ 			if (Object.prototype.hasOwnProperty.call(moreModules, moduleId)) {
/******/ 				hotUpdate[moduleId] = moreModules[moduleId];
/******/ 			}
/******/ 		}
/******/ 		if (--hotWaitingFiles === 0 && hotChunksLoading === 0) {
/******/ 			hotUpdateDownloaded();
/******/ 		}
/******/ 	}
/******/
/******/ 	function hotEnsureUpdateChunk(chunkId) {
/******/ 		if (!hotAvailableFilesMap[chunkId]) {
/******/ 			hotWaitingFilesMap[chunkId] = true;
/******/ 		} else {
/******/ 			hotRequestedFilesMap[chunkId] = true;
/******/ 			hotWaitingFiles++;
/******/ 			hotDownloadUpdateChunk(chunkId);
/******/ 		}
/******/ 	}
/******/
/******/ 	function hotUpdateDownloaded() {
/******/ 		hotSetStatus("ready");
/******/ 		var deferred = hotDeferred;
/******/ 		hotDeferred = null;
/******/ 		if (!deferred) return;
/******/ 		if (hotApplyOnUpdate) {
/******/ 			// Wrap deferred object in Promise to mark it as a well-handled Promise to
/******/ 			// avoid triggering uncaught exception warning in Chrome.
/******/ 			// See https://bugs.chromium.org/p/chromium/issues/detail?id=465666
/******/ 			Promise.resolve()
/******/ 				.then(function() {
/******/ 					return hotApply(hotApplyOnUpdate);
/******/ 				})
/******/ 				.then(
/******/ 					function(result) {
/******/ 						deferred.resolve(result);
/******/ 					},
/******/ 					function(err) {
/******/ 						deferred.reject(err);
/******/ 					}
/******/ 				);
/******/ 		} else {
/******/ 			var outdatedModules = [];
/******/ 			for (var id in hotUpdate) {
/******/ 				if (Object.prototype.hasOwnProperty.call(hotUpdate, id)) {
/******/ 					outdatedModules.push(toModuleId(id));
/******/ 				}
/******/ 			}
/******/ 			deferred.resolve(outdatedModules);
/******/ 		}
/******/ 	}
/******/
/******/ 	function hotApply(options) {
/******/ 		if (hotStatus !== "ready")
/******/ 			throw new Error("apply() is only allowed in ready status");
/******/ 		options = options || {};
/******/ 		return hotApplyInternal(options);
/******/ 	}
/******/
/******/ 	function hotApplyInternal(options) {
/******/ 		hotApplyInvalidatedModules();
/******/
/******/ 		var cb;
/******/ 		var i;
/******/ 		var j;
/******/ 		var module;
/******/ 		var moduleId;
/******/
/******/ 		function getAffectedStuff(updateModuleId) {
/******/ 			var outdatedModules = [updateModuleId];
/******/ 			var outdatedDependencies = {};
/******/
/******/ 			var queue = outdatedModules.map(function(id) {
/******/ 				return {
/******/ 					chain: [id],
/******/ 					id: id
/******/ 				};
/******/ 			});
/******/ 			while (queue.length > 0) {
/******/ 				var queueItem = queue.pop();
/******/ 				var moduleId = queueItem.id;
/******/ 				var chain = queueItem.chain;
/******/ 				module = installedModules[moduleId];
/******/ 				if (
/******/ 					!module ||
/******/ 					(module.hot._selfAccepted && !module.hot._selfInvalidated)
/******/ 				)
/******/ 					continue;
/******/ 				if (module.hot._selfDeclined) {
/******/ 					return {
/******/ 						type: "self-declined",
/******/ 						chain: chain,
/******/ 						moduleId: moduleId
/******/ 					};
/******/ 				}
/******/ 				if (module.hot._main) {
/******/ 					return {
/******/ 						type: "unaccepted",
/******/ 						chain: chain,
/******/ 						moduleId: moduleId
/******/ 					};
/******/ 				}
/******/ 				for (var i = 0; i < module.parents.length; i++) {
/******/ 					var parentId = module.parents[i];
/******/ 					var parent = installedModules[parentId];
/******/ 					if (!parent) continue;
/******/ 					if (parent.hot._declinedDependencies[moduleId]) {
/******/ 						return {
/******/ 							type: "declined",
/******/ 							chain: chain.concat([parentId]),
/******/ 							moduleId: moduleId,
/******/ 							parentId: parentId
/******/ 						};
/******/ 					}
/******/ 					if (outdatedModules.indexOf(parentId) !== -1) continue;
/******/ 					if (parent.hot._acceptedDependencies[moduleId]) {
/******/ 						if (!outdatedDependencies[parentId])
/******/ 							outdatedDependencies[parentId] = [];
/******/ 						addAllToSet(outdatedDependencies[parentId], [moduleId]);
/******/ 						continue;
/******/ 					}
/******/ 					delete outdatedDependencies[parentId];
/******/ 					outdatedModules.push(parentId);
/******/ 					queue.push({
/******/ 						chain: chain.concat([parentId]),
/******/ 						id: parentId
/******/ 					});
/******/ 				}
/******/ 			}
/******/
/******/ 			return {
/******/ 				type: "accepted",
/******/ 				moduleId: updateModuleId,
/******/ 				outdatedModules: outdatedModules,
/******/ 				outdatedDependencies: outdatedDependencies
/******/ 			};
/******/ 		}
/******/
/******/ 		function addAllToSet(a, b) {
/******/ 			for (var i = 0; i < b.length; i++) {
/******/ 				var item = b[i];
/******/ 				if (a.indexOf(item) === -1) a.push(item);
/******/ 			}
/******/ 		}
/******/
/******/ 		// at begin all updates modules are outdated
/******/ 		// the "outdated" status can propagate to parents if they don't accept the children
/******/ 		var outdatedDependencies = {};
/******/ 		var outdatedModules = [];
/******/ 		var appliedUpdate = {};
/******/
/******/ 		var warnUnexpectedRequire = function warnUnexpectedRequire() {
/******/ 			console.warn(
/******/ 				"[HMR] unexpected require(" + result.moduleId + ") to disposed module"
/******/ 			);
/******/ 		};
/******/
/******/ 		for (var id in hotUpdate) {
/******/ 			if (Object.prototype.hasOwnProperty.call(hotUpdate, id)) {
/******/ 				moduleId = toModuleId(id);
/******/ 				/** @type {TODO} */
/******/ 				var result;
/******/ 				if (hotUpdate[id]) {
/******/ 					result = getAffectedStuff(moduleId);
/******/ 				} else {
/******/ 					result = {
/******/ 						type: "disposed",
/******/ 						moduleId: id
/******/ 					};
/******/ 				}
/******/ 				/** @type {Error|false} */
/******/ 				var abortError = false;
/******/ 				var doApply = false;
/******/ 				var doDispose = false;
/******/ 				var chainInfo = "";
/******/ 				if (result.chain) {
/******/ 					chainInfo = "\nUpdate propagation: " + result.chain.join(" -> ");
/******/ 				}
/******/ 				switch (result.type) {
/******/ 					case "self-declined":
/******/ 						if (options.onDeclined) options.onDeclined(result);
/******/ 						if (!options.ignoreDeclined)
/******/ 							abortError = new Error(
/******/ 								"Aborted because of self decline: " +
/******/ 									result.moduleId +
/******/ 									chainInfo
/******/ 							);
/******/ 						break;
/******/ 					case "declined":
/******/ 						if (options.onDeclined) options.onDeclined(result);
/******/ 						if (!options.ignoreDeclined)
/******/ 							abortError = new Error(
/******/ 								"Aborted because of declined dependency: " +
/******/ 									result.moduleId +
/******/ 									" in " +
/******/ 									result.parentId +
/******/ 									chainInfo
/******/ 							);
/******/ 						break;
/******/ 					case "unaccepted":
/******/ 						if (options.onUnaccepted) options.onUnaccepted(result);
/******/ 						if (!options.ignoreUnaccepted)
/******/ 							abortError = new Error(
/******/ 								"Aborted because " + moduleId + " is not accepted" + chainInfo
/******/ 							);
/******/ 						break;
/******/ 					case "accepted":
/******/ 						if (options.onAccepted) options.onAccepted(result);
/******/ 						doApply = true;
/******/ 						break;
/******/ 					case "disposed":
/******/ 						if (options.onDisposed) options.onDisposed(result);
/******/ 						doDispose = true;
/******/ 						break;
/******/ 					default:
/******/ 						throw new Error("Unexception type " + result.type);
/******/ 				}
/******/ 				if (abortError) {
/******/ 					hotSetStatus("abort");
/******/ 					return Promise.reject(abortError);
/******/ 				}
/******/ 				if (doApply) {
/******/ 					appliedUpdate[moduleId] = hotUpdate[moduleId];
/******/ 					addAllToSet(outdatedModules, result.outdatedModules);
/******/ 					for (moduleId in result.outdatedDependencies) {
/******/ 						if (
/******/ 							Object.prototype.hasOwnProperty.call(
/******/ 								result.outdatedDependencies,
/******/ 								moduleId
/******/ 							)
/******/ 						) {
/******/ 							if (!outdatedDependencies[moduleId])
/******/ 								outdatedDependencies[moduleId] = [];
/******/ 							addAllToSet(
/******/ 								outdatedDependencies[moduleId],
/******/ 								result.outdatedDependencies[moduleId]
/******/ 							);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 				if (doDispose) {
/******/ 					addAllToSet(outdatedModules, [result.moduleId]);
/******/ 					appliedUpdate[moduleId] = warnUnexpectedRequire;
/******/ 				}
/******/ 			}
/******/ 		}
/******/
/******/ 		// Store self accepted outdated modules to require them later by the module system
/******/ 		var outdatedSelfAcceptedModules = [];
/******/ 		for (i = 0; i < outdatedModules.length; i++) {
/******/ 			moduleId = outdatedModules[i];
/******/ 			if (
/******/ 				installedModules[moduleId] &&
/******/ 				installedModules[moduleId].hot._selfAccepted &&
/******/ 				// removed self-accepted modules should not be required
/******/ 				appliedUpdate[moduleId] !== warnUnexpectedRequire &&
/******/ 				// when called invalidate self-accepting is not possible
/******/ 				!installedModules[moduleId].hot._selfInvalidated
/******/ 			) {
/******/ 				outdatedSelfAcceptedModules.push({
/******/ 					module: moduleId,
/******/ 					parents: installedModules[moduleId].parents.slice(),
/******/ 					errorHandler: installedModules[moduleId].hot._selfAccepted
/******/ 				});
/******/ 			}
/******/ 		}
/******/
/******/ 		// Now in "dispose" phase
/******/ 		hotSetStatus("dispose");
/******/ 		Object.keys(hotAvailableFilesMap).forEach(function(chunkId) {
/******/ 			if (hotAvailableFilesMap[chunkId] === false) {
/******/ 				hotDisposeChunk(chunkId);
/******/ 			}
/******/ 		});
/******/
/******/ 		var idx;
/******/ 		var queue = outdatedModules.slice();
/******/ 		while (queue.length > 0) {
/******/ 			moduleId = queue.pop();
/******/ 			module = installedModules[moduleId];
/******/ 			if (!module) continue;
/******/
/******/ 			var data = {};
/******/
/******/ 			// Call dispose handlers
/******/ 			var disposeHandlers = module.hot._disposeHandlers;
/******/ 			for (j = 0; j < disposeHandlers.length; j++) {
/******/ 				cb = disposeHandlers[j];
/******/ 				cb(data);
/******/ 			}
/******/ 			hotCurrentModuleData[moduleId] = data;
/******/
/******/ 			// disable module (this disables requires from this module)
/******/ 			module.hot.active = false;
/******/
/******/ 			// remove module from cache
/******/ 			delete installedModules[moduleId];
/******/
/******/ 			// when disposing there is no need to call dispose handler
/******/ 			delete outdatedDependencies[moduleId];
/******/
/******/ 			// remove "parents" references from all children
/******/ 			for (j = 0; j < module.children.length; j++) {
/******/ 				var child = installedModules[module.children[j]];
/******/ 				if (!child) continue;
/******/ 				idx = child.parents.indexOf(moduleId);
/******/ 				if (idx >= 0) {
/******/ 					child.parents.splice(idx, 1);
/******/ 				}
/******/ 			}
/******/ 		}
/******/
/******/ 		// remove outdated dependency from module children
/******/ 		var dependency;
/******/ 		var moduleOutdatedDependencies;
/******/ 		for (moduleId in outdatedDependencies) {
/******/ 			if (
/******/ 				Object.prototype.hasOwnProperty.call(outdatedDependencies, moduleId)
/******/ 			) {
/******/ 				module = installedModules[moduleId];
/******/ 				if (module) {
/******/ 					moduleOutdatedDependencies = outdatedDependencies[moduleId];
/******/ 					for (j = 0; j < moduleOutdatedDependencies.length; j++) {
/******/ 						dependency = moduleOutdatedDependencies[j];
/******/ 						idx = module.children.indexOf(dependency);
/******/ 						if (idx >= 0) module.children.splice(idx, 1);
/******/ 					}
/******/ 				}
/******/ 			}
/******/ 		}
/******/
/******/ 		// Now in "apply" phase
/******/ 		hotSetStatus("apply");
/******/
/******/ 		if (hotUpdateNewHash !== undefined) {
/******/ 			hotCurrentHash = hotUpdateNewHash;
/******/ 			hotUpdateNewHash = undefined;
/******/ 		}
/******/ 		hotUpdate = undefined;
/******/
/******/ 		// insert new code
/******/ 		for (moduleId in appliedUpdate) {
/******/ 			if (Object.prototype.hasOwnProperty.call(appliedUpdate, moduleId)) {
/******/ 				modules[moduleId] = appliedUpdate[moduleId];
/******/ 			}
/******/ 		}
/******/
/******/ 		// call accept handlers
/******/ 		var error = null;
/******/ 		for (moduleId in outdatedDependencies) {
/******/ 			if (
/******/ 				Object.prototype.hasOwnProperty.call(outdatedDependencies, moduleId)
/******/ 			) {
/******/ 				module = installedModules[moduleId];
/******/ 				if (module) {
/******/ 					moduleOutdatedDependencies = outdatedDependencies[moduleId];
/******/ 					var callbacks = [];
/******/ 					for (i = 0; i < moduleOutdatedDependencies.length; i++) {
/******/ 						dependency = moduleOutdatedDependencies[i];
/******/ 						cb = module.hot._acceptedDependencies[dependency];
/******/ 						if (cb) {
/******/ 							if (callbacks.indexOf(cb) !== -1) continue;
/******/ 							callbacks.push(cb);
/******/ 						}
/******/ 					}
/******/ 					for (i = 0; i < callbacks.length; i++) {
/******/ 						cb = callbacks[i];
/******/ 						try {
/******/ 							cb(moduleOutdatedDependencies);
/******/ 						} catch (err) {
/******/ 							if (options.onErrored) {
/******/ 								options.onErrored({
/******/ 									type: "accept-errored",
/******/ 									moduleId: moduleId,
/******/ 									dependencyId: moduleOutdatedDependencies[i],
/******/ 									error: err
/******/ 								});
/******/ 							}
/******/ 							if (!options.ignoreErrored) {
/******/ 								if (!error) error = err;
/******/ 							}
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 			}
/******/ 		}
/******/
/******/ 		// Load self accepted modules
/******/ 		for (i = 0; i < outdatedSelfAcceptedModules.length; i++) {
/******/ 			var item = outdatedSelfAcceptedModules[i];
/******/ 			moduleId = item.module;
/******/ 			hotCurrentParents = item.parents;
/******/ 			hotCurrentChildModule = moduleId;
/******/ 			try {
/******/ 				__webpack_require__(moduleId);
/******/ 			} catch (err) {
/******/ 				if (typeof item.errorHandler === "function") {
/******/ 					try {
/******/ 						item.errorHandler(err);
/******/ 					} catch (err2) {
/******/ 						if (options.onErrored) {
/******/ 							options.onErrored({
/******/ 								type: "self-accept-error-handler-errored",
/******/ 								moduleId: moduleId,
/******/ 								error: err2,
/******/ 								originalError: err
/******/ 							});
/******/ 						}
/******/ 						if (!options.ignoreErrored) {
/******/ 							if (!error) error = err2;
/******/ 						}
/******/ 						if (!error) error = err;
/******/ 					}
/******/ 				} else {
/******/ 					if (options.onErrored) {
/******/ 						options.onErrored({
/******/ 							type: "self-accept-errored",
/******/ 							moduleId: moduleId,
/******/ 							error: err
/******/ 						});
/******/ 					}
/******/ 					if (!options.ignoreErrored) {
/******/ 						if (!error) error = err;
/******/ 					}
/******/ 				}
/******/ 			}
/******/ 		}
/******/
/******/ 		// handle errors in accept handlers and self accepted module load
/******/ 		if (error) {
/******/ 			hotSetStatus("fail");
/******/ 			return Promise.reject(error);
/******/ 		}
/******/
/******/ 		if (hotQueuedInvalidatedModules) {
/******/ 			return hotApplyInternal(options).then(function(list) {
/******/ 				outdatedModules.forEach(function(moduleId) {
/******/ 					if (list.indexOf(moduleId) < 0) list.push(moduleId);
/******/ 				});
/******/ 				return list;
/******/ 			});
/******/ 		}
/******/
/******/ 		hotSetStatus("idle");
/******/ 		return new Promise(function(resolve) {
/******/ 			resolve(outdatedModules);
/******/ 		});
/******/ 	}
/******/
/******/ 	function hotApplyInvalidatedModules() {
/******/ 		if (hotQueuedInvalidatedModules) {
/******/ 			if (!hotUpdate) hotUpdate = {};
/******/ 			hotQueuedInvalidatedModules.forEach(hotApplyInvalidatedModule);
/******/ 			hotQueuedInvalidatedModules = undefined;
/******/ 			return true;
/******/ 		}
/******/ 	}
/******/
/******/ 	function hotApplyInvalidatedModule(moduleId) {
/******/ 		if (!Object.prototype.hasOwnProperty.call(hotUpdate, moduleId))
/******/ 			hotUpdate[moduleId] = modules[moduleId];
/******/ 	}
/******/
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// object to store loaded and loading chunks
/******/ 	// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 	// Promise = chunk loading, 0 = chunk loaded
/******/ 	var installedChunks = {
/******/ 		0: 0
/******/ 	};
/******/
/******/ 	var deferredModules = [];
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {},
/******/ 			hot: hotCreateModule(moduleId),
/******/ 			parents: (hotCurrentParentsTemp = hotCurrentParents, hotCurrentParents = [], hotCurrentParentsTemp),
/******/ 			children: []
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, hotCreateRequire(moduleId));
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "http://localhost:8085/";
/******/
/******/ 	// __webpack_hash__
/******/ 	__webpack_require__.h = function() { return hotCurrentHash; };
/******/
/******/ 	var jsonpArray = window["webpackJsonp"] = window["webpackJsonp"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// add entry module to deferred list
/******/ 	deferredModules.push([224,1]);
/******/ 	// run deferred modules when ready
/******/ 	return checkDeferredModules();
/******/ })
/************************************************************************/
/******/ ([
/* 0 */,
/* 1 */
/***/ (function(module, exports) {

module.exports = Vuex;

/***/ }),
/* 2 */
/***/ (function(module, exports) {

module.exports = axios;

/***/ }),
/* 3 */,
/* 4 */
/***/ (function(module, exports) {

module.exports = Vue;

/***/ }),
/* 5 */,
/* 6 */,
/* 7 */,
/* 8 */,
/* 9 */,
/* 10 */,
/* 11 */,
/* 12 */,
/* 13 */,
/* 14 */,
/* 15 */,
/* 16 */,
/* 17 */,
/* 18 */,
/* 19 */,
/* 20 */,
/* 21 */,
/* 22 */,
/* 23 */,
/* 24 */,
/* 25 */,
/* 26 */,
/* 27 */,
/* 28 */,
/* 29 */
/***/ (function(module, exports) {

module.exports = VueRouter;

/***/ }),
/* 30 */,
/* 31 */,
/* 32 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(83);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("61b75688", content, true, {});

/***/ }),
/* 33 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(85);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("6e795e51", content, true, {});

/***/ }),
/* 34 */,
/* 35 */,
/* 36 */,
/* 37 */,
/* 38 */,
/* 39 */,
/* 40 */,
/* 41 */,
/* 42 */,
/* 43 */,
/* 44 */,
/* 45 */,
/* 46 */,
/* 47 */,
/* 48 */,
/* 49 */,
/* 50 */,
/* 51 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(172);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("a0f46d96", content, true, {});

/***/ }),
/* 52 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(174);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("62ad747b", content, true, {});

/***/ }),
/* 53 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(176);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("52221f5c", content, true, {});

/***/ }),
/* 54 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(178);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("689f0f20", content, true, {});

/***/ }),
/* 55 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(180);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("2116628e", content, true, {});

/***/ }),
/* 56 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(182);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("4470bdf5", content, true, {});

/***/ }),
/* 57 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(184);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("392feff6", content, true, {});

/***/ }),
/* 58 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(186);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("14532fb2", content, true, {});

/***/ }),
/* 59 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(188);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("612c8ef3", content, true, {});

/***/ }),
/* 60 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(190);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("75e4e996", content, true, {});

/***/ }),
/* 61 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(192);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("0a6e2855", content, true, {});

/***/ }),
/* 62 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(194);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("c4694d96", content, true, {});

/***/ }),
/* 63 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(196);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("816959c6", content, true, {});

/***/ }),
/* 64 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(198);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("a9735f56", content, true, {});

/***/ }),
/* 65 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(200);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("122307b7", content, true, {});

/***/ }),
/* 66 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(202);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("0252f397", content, true, {});

/***/ }),
/* 67 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(204);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("474975cf", content, true, {});

/***/ }),
/* 68 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(206);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("2db8db0a", content, true, {});

/***/ }),
/* 69 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(208);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("0b5087d4", content, true, {});

/***/ }),
/* 70 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(210);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("018a01f5", content, true, {});

/***/ }),
/* 71 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(212);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("1f2bf7b0", content, true, {});

/***/ }),
/* 72 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(214);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("5dd48ac8", content, true, {});

/***/ }),
/* 73 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(219);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("03c845bc", content, true, {});

/***/ }),
/* 74 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(221);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("7470997c", content, true, {});

/***/ }),
/* 75 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(223);
if(content.__esModule) content = content.default;
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(3).default
var update = add("6b4b9c36", content, true, {});

/***/ }),
/* 76 */,
/* 77 */,
/* 78 */,
/* 79 */,
/* 80 */,
/* 81 */,
/* 82 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_EditionBadge_vue_vue_type_style_index_0_id_79f9118a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(32);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_EditionBadge_vue_vue_type_style_index_0_id_79f9118a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_EditionBadge_vue_vue_type_style_index_0_id_79f9118a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 83 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 84 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginCard_vue_vue_type_style_index_0_id_4baf6246_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(33);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginCard_vue_vue_type_style_index_0_id_4baf6246_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginCard_vue_vue_type_style_index_0_id_4baf6246_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 85 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 86 */,
/* 87 */,
/* 88 */,
/* 89 */,
/* 90 */,
/* 91 */,
/* 92 */,
/* 93 */,
/* 94 */,
/* 95 */,
/* 96 */,
/* 97 */,
/* 98 */,
/* 99 */,
/* 100 */,
/* 101 */,
/* 102 */,
/* 103 */,
/* 104 */,
/* 105 */,
/* 106 */,
/* 107 */,
/* 108 */,
/* 109 */,
/* 110 */,
/* 111 */,
/* 112 */,
/* 113 */,
/* 114 */,
/* 115 */,
/* 116 */,
/* 117 */,
/* 118 */,
/* 119 */,
/* 120 */,
/* 121 */,
/* 122 */,
/* 123 */,
/* 124 */,
/* 125 */,
/* 126 */,
/* 127 */,
/* 128 */,
/* 129 */,
/* 130 */,
/* 131 */,
/* 132 */,
/* 133 */,
/* 134 */,
/* 135 */,
/* 136 */,
/* 137 */,
/* 138 */,
/* 139 */,
/* 140 */,
/* 141 */,
/* 142 */,
/* 143 */,
/* 144 */,
/* 145 */,
/* 146 */,
/* 147 */,
/* 148 */,
/* 149 */,
/* 150 */,
/* 151 */,
/* 152 */,
/* 153 */,
/* 154 */,
/* 155 */,
/* 156 */,
/* 157 */,
/* 158 */,
/* 159 */,
/* 160 */,
/* 161 */,
/* 162 */,
/* 163 */,
/* 164 */,
/* 165 */,
/* 166 */,
/* 167 */,
/* 168 */,
/* 169 */,
/* 170 */,
/* 171 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_InfoHud_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(51);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_InfoHud_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_InfoHud_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 172 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 173 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_LicenseStatus_vue_vue_type_style_index_0_id_94a8be66_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(52);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_LicenseStatus_vue_vue_type_style_index_0_id_94a8be66_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_LicenseStatus_vue_vue_type_style_index_0_id_94a8be66_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 174 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 175 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CmsEdition_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(53);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CmsEdition_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CmsEdition_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 176 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 177 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CmsEditions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(54);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CmsEditions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CmsEditions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 178 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 179 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_id_vue_vue_type_style_index_0_id_5488eebc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(55);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_id_vue_vue_type_style_index_0_id_5488eebc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_id_vue_vue_type_style_index_0_id_5488eebc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 180 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 181 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_StatusMessage_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(56);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_StatusMessage_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_StatusMessage_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 182 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 183 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_plugin_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(57);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_plugin_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_plugin_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 184 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 185 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_buy_all_trials_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(58);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_buy_all_trials_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_buy_all_trials_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 186 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 187 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_ChangelogRelease_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(59);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_ChangelogRelease_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_ChangelogRelease_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 188 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 189 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginChangelog_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(60);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginChangelog_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginChangelog_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 190 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 191 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginActions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(61);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginActions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginActions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 192 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 193 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginEdition_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(62);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginEdition_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginEdition_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 194 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 195 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginEditions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(63);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginEditions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginEditions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 196 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 197 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginScreenshots_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(64);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginScreenshots_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_PluginScreenshots_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 198 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 199 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_index_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(65);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_index_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_index_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 200 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 201 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Cart_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(66);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Cart_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Cart_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 202 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 203 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Modal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(67);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Modal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Modal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 204 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 205 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CategorySelector_vue_vue_type_style_index_0_id_756ced68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(68);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CategorySelector_vue_vue_type_style_index_0_id_756ced68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_CategorySelector_vue_vue_type_style_index_0_id_756ced68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 206 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 207 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_5e5fb078_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(69);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_5e5fb078_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_5e5fb078_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 208 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 209 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_ScreenshotModal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(70);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_ScreenshotModal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_ScreenshotModal_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 210 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 211 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(71);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 212 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 213 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_1_style_scss_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(72);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_1_style_scss_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_1_style_scss_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 214 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 215 */,
/* 216 */,
/* 217 */,
/* 218 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Btn_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(73);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Btn_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Btn_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 219 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 220 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Dropdown_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(74);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Dropdown_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Dropdown_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 221 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 222 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Spinner_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(75);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Spinner_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_mini_css_extract_plugin_dist_loader_js_ref_2_1_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_dist_cjs_js_ref_2_3_node_modules_sass_loader_dist_cjs_js_ref_2_4_node_modules_vue_loader_lib_index_js_vue_loader_options_Spinner_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),
/* 223 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 224 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external "Vue"
var external_Vue_ = __webpack_require__(4);
var external_Vue_default = /*#__PURE__*/__webpack_require__.n(external_Vue_);

// EXTERNAL MODULE: external "axios"
var external_axios_ = __webpack_require__(2);
var external_axios_default = /*#__PURE__*/__webpack_require__.n(external_axios_);

// EXTERNAL MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/accounting/accounting.js
var accounting = __webpack_require__(28);
var accounting_default = /*#__PURE__*/__webpack_require__.n(accounting);

// CONCATENATED MODULE: ./js/filters/currency.js

/**
 * Formats a value as a currency value
 */

function currency(value) {
  var precision = 2;
  var floatValue = parseFloat(value); // Auto precision

  if (Math.round(floatValue) === floatValue) {
    precision = 0;
  }

  if (floatValue < 0) {
    return '-' + accounting_default.a.formatMoney(floatValue * -1, '$', precision);
  }

  return accounting_default.a.formatMoney(floatValue, '$', precision);
}
// CONCATENATED MODULE: ./js/filters/craft.js
/* global Craft */
function escapeHtml(str) {
  return Craft.escapeHtml(str);
}
function t(message, category, params) {
  return Craft.t(category, message, params);
}
function formatDate(date) {
  return Craft.formatDate(date);
}
function formatNumber(number) {
  var format = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : ',.0f';
  return Craft.formatNumber(number, format);
}
// EXTERNAL MODULE: external "VueRouter"
var external_VueRouter_ = __webpack_require__(29);
var external_VueRouter_default = /*#__PURE__*/__webpack_require__.n(external_VueRouter_);

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/index.vue?vue&type=template&id=16b1afbb&
var pagesvue_type_template_id_16b1afbb_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-container"},[(!_vm.loading)?[_vm._l((_vm.featuredSections),function(featuredSection,key){return _c('div',{key:'featuredSection-' + key},[_c('div',{staticClass:"tw-flex tw-items-baseline tw-justify-between",class:{'tw-mt-8': key > 0}},[_c('h2',[_vm._v(_vm._s(featuredSection.title))]),_vm._v(" "),_c('router-link',{staticClass:"tw-right",attrs:{"to":'/featured/'+featuredSection.slug}},[_vm._v(_vm._s(_vm._f("t")("See all",'app')))])],1),_vm._v(" "),_c('plugin-grid',{attrs:{"plugins":featuredSection.plugins,"auto-limit":true}})],1)}),_vm._v(" "),(_vm.activeTrialPlugins.length > 0 || _vm.activeTrialsError)?[_c('h2',[_vm._v(_vm._s(_vm._f("t")("Active Trials",'app')))]),_vm._v(" "),(_vm.activeTrialPlugins.length > 0)?[_c('plugin-grid',{attrs:{"plugins":_vm.activeTrialPlugins,"trialMode":true}})]:_vm._e(),_vm._v(" "),(_vm.activeTrialsError)?[_c('div',{staticClass:"tw-mb-8"},[_c('p',{staticClass:"error"},[_vm._v(_vm._s(_vm.activeTrialsError))])])]:_vm._e()]:_vm._e()]:[_c('spinner')]],2)}
var staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/index.vue?vue&type=template&id=16b1afbb&

// EXTERNAL MODULE: external "Vuex"
var external_Vuex_ = __webpack_require__(1);
var external_Vuex_default = /*#__PURE__*/__webpack_require__.n(external_Vuex_);

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginGrid.vue?vue&type=template&id=e13be788&
var PluginGridvue_type_template_id_e13be788_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',[(_vm.plugins && _vm.plugins.length > 0)?_c('div',{staticClass:"ps-grid-plugins"},_vm._l((_vm.computedPlugins),function(plugin,key){return _c('div',{key:key,staticClass:"ps-grid-box"},[_c('plugin-card',{attrs:{"plugin":plugin,"trialMode":_vm.trialMode}})],1)}),0):_vm._e()])}
var PluginGridvue_type_template_id_e13be788_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginGrid.vue?vue&type=template&id=e13be788&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginCard.vue?vue&type=template&id=4baf6246&scoped=true&
var PluginCardvue_type_template_id_4baf6246_scoped_true_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.plugin)?_c('router-link',{staticClass:"plugin-card tw-relative tw-flex tw-flex-no-wrap tw-items-start tw-py-6 tw-border-b tw-border-gray-200 tw-border-solid tw-no-underline hover:tw-no-underline tw-text-gray-800",attrs:{"to":'/' + _vm.plugin.handle,"title":_vm.plugin.name}},[_c('div',{staticClass:"plugin-icon tw-mr-4"},[(_vm.plugin.iconUrl)?_c('img',{attrs:{"src":_vm.plugin.iconUrl}}):_c('img',{attrs:{"src":_vm.defaultPluginSvg}})]),_vm._v(" "),_c('div',[_c('div',{staticClass:"plugin-details-header"},[_c('div',{staticClass:"plugin-name"},[_c('strong',[_vm._v(_vm._s(_vm.plugin.name))]),_vm._v(" "),(_vm.trialMode && _vm.activeTrialPluginEdition && _vm.plugin.editions.length > 1)?_c('edition-badge',{attrs:{"name":_vm.activeTrialPluginEdition.name}}):_vm._e()],1),_vm._v(" "),_c('div',[_vm._v(_vm._s(_vm.plugin.shortDescription))])]),_vm._v(" "),(_vm.plugin.abandoned)?[_c('div',{staticClass:"error"},[_vm._v(_vm._s(_vm._f("t")("Abandoned",'app')))])]:[_c('div',{staticClass:"light"},[_vm._v("\n              "+_vm._s(_vm.fullPriceLabel)+"\n            ")])],_vm._v(" "),(_vm.isPluginInstalled(_vm.plugin.handle))?_c('div',{staticClass:"installed",attrs:{"data-icon":"check"}}):_vm._e()],2)]):_vm._e()}
var PluginCardvue_type_template_id_4baf6246_scoped_true_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginCard.vue?vue&type=template&id=4baf6246&scoped=true&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/EditionBadge.vue?vue&type=template&id=79f9118a&scoped=true&
var EditionBadgevue_type_template_id_79f9118a_scoped_true_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"edition-badge",class:_vm.cssClass},[_c('div',{staticClass:"edition-badge-name"},[_vm._v(_vm._s(_vm.name))])])}
var EditionBadgevue_type_template_id_79f9118a_scoped_true_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/EditionBadge.vue?vue&type=template&id=79f9118a&scoped=true&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/EditionBadge.vue?vue&type=script&lang=js&
//
//
//
//
//
//
/* harmony default export */ var EditionBadgevue_type_script_lang_js_ = ({
  props: ['name', 'block', 'big'],
  computed: {
    cssClass: function cssClass() {
      var cssClasses = {};

      if (typeof this.block !== 'undefined') {
        cssClasses['is-block'] = true;
      }

      if (typeof this.big !== 'undefined') {
        cssClasses['is-big'] = true;
      }

      return cssClasses;
    }
  }
});
// CONCATENATED MODULE: ./js/components/EditionBadge.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_EditionBadgevue_type_script_lang_js_ = (EditionBadgevue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/EditionBadge.vue?vue&type=style&index=0&id=79f9118a&lang=scss&scoped=true&
var EditionBadgevue_type_style_index_0_id_79f9118a_lang_scss_scoped_true_ = __webpack_require__(82);

// EXTERNAL MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/runtime/componentNormalizer.js
var componentNormalizer = __webpack_require__(0);

// CONCATENATED MODULE: ./js/components/EditionBadge.vue






/* normalize component */

var component = Object(componentNormalizer["a" /* default */])(
  components_EditionBadgevue_type_script_lang_js_,
  EditionBadgevue_type_template_id_79f9118a_scoped_true_render,
  EditionBadgevue_type_template_id_79f9118a_scoped_true_staticRenderFns,
  false,
  null,
  "79f9118a",
  null
  
)

/* harmony default export */ var EditionBadge = (component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginCard.vue?vue&type=script&lang=js&
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* global Craft */


/* harmony default export */ var PluginCardvue_type_script_lang_js_ = ({
  props: ['plugin', 'trialMode'],
  components: {
    EditionBadge: EditionBadge
  },
  computed: _objectSpread(_objectSpread(_objectSpread({}, Object(external_Vuex_["mapState"])({
    defaultPluginSvg: function defaultPluginSvg(state) {
      return state.craft.defaultPluginSvg;
    }
  })), Object(external_Vuex_["mapGetters"])({
    isPluginInstalled: 'craft/isPluginInstalled',
    getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition'
  })), {}, {
    activeTrialPluginEdition: function activeTrialPluginEdition() {
      return this.getActiveTrialPluginEdition(this.plugin);
    },
    priceRange: function priceRange() {
      var editions = this.plugin.editions;
      var min = null;
      var max = null;

      for (var i = 0; i < editions.length; i++) {
        var edition = editions[i];
        var price = 0;

        if (edition.price) {
          price = parseInt(edition.price);
        }

        if (min === null) {
          min = price;
        }

        if (max === null) {
          max = price;
        }

        if (price < min) {
          min = price;
        }

        if (price > max) {
          max = price;
        }
      }

      return {
        min: min,
        max: max
      };
    },
    fullPriceLabel: function fullPriceLabel() {
      var _this$priceRange = this.priceRange,
          min = _this$priceRange.min,
          max = _this$priceRange.max;

      if (min !== max) {
        return "".concat(this.priceLabel(min), "\u2013").concat(this.priceLabel(max));
      }

      return this.priceLabel(min);
    }
  }),
  methods: {
    priceLabel: function priceLabel(price) {
      return price > 0 ? this.$options.filters.currency(price) : Craft.t('app', 'Free');
    }
  }
});
// CONCATENATED MODULE: ./js/components/PluginCard.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginCardvue_type_script_lang_js_ = (PluginCardvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/PluginCard.vue?vue&type=style&index=0&id=4baf6246&lang=scss&scoped=true&
var PluginCardvue_type_style_index_0_id_4baf6246_lang_scss_scoped_true_ = __webpack_require__(84);

// CONCATENATED MODULE: ./js/components/PluginCard.vue






/* normalize component */

var PluginCard_component = Object(componentNormalizer["a" /* default */])(
  components_PluginCardvue_type_script_lang_js_,
  PluginCardvue_type_template_id_4baf6246_scoped_true_render,
  PluginCardvue_type_template_id_4baf6246_scoped_true_staticRenderFns,
  false,
  null,
  "4baf6246",
  null
  
)

/* harmony default export */ var PluginCard = (PluginCard_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginGrid.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var PluginGridvue_type_script_lang_js_ = ({
  components: {
    PluginCard: PluginCard
  },
  props: ['plugins', 'trialMode', 'autoLimit'],
  data: function data() {
    return {
      winWidth: null
    };
  },
  computed: {
    computedPlugins: function computedPlugins() {
      var _this = this;

      return this.plugins.filter(function (plugin, key) {
        if (!_this.autoLimit || _this.autoLimit && key < _this.limit) {
          return true;
        }

        return false;
      });
    },
    limit: function limit() {
      var totalPlugins = this.plugins.length;

      if (this.winWidth < 1400) {
        totalPlugins = 4;
      }

      var remains = totalPlugins % (this.oddNumberOfColumns ? 3 : 2);
      return totalPlugins - remains;
    },
    oddNumberOfColumns: function oddNumberOfColumns() {
      if (this.winWidth < 1400 || this.winWidth >= 1824) {
        return false;
      }

      return true;
    }
  },
  methods: {
    onWindowResize: function onWindowResize() {
      this.winWidth = window.innerWidth;
    }
  },
  mounted: function mounted() {
    this.winWidth = window.innerWidth;
    this.$root.$on('windowResize', this.onWindowResize);
  },
  beforeDestroy: function beforeDestroy() {
    this.$root.$off('windowResize', this.onWindowResize);
  }
});
// CONCATENATED MODULE: ./js/components/PluginGrid.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginGridvue_type_script_lang_js_ = (PluginGridvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/PluginGrid.vue





/* normalize component */

var PluginGrid_component = Object(componentNormalizer["a" /* default */])(
  components_PluginGridvue_type_script_lang_js_,
  PluginGridvue_type_template_id_e13be788_render,
  PluginGridvue_type_template_id_e13be788_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginGrid = (PluginGrid_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/index.vue?vue&type=script&lang=js&
function pagesvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function pagesvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { pagesvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { pagesvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { pagesvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function pagesvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var pagesvue_type_script_lang_js_ = ({
  components: {
    PluginGrid: PluginGrid
  },
  data: function data() {
    return {
      activeTrialsError: null,
      activeTrialsLoaded: false,
      featuredSectionsLoaded: false,
      loading: false
    };
  },
  computed: pagesvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    activeTrialPlugins: function activeTrialPlugins(state) {
      return state.cart.activeTrialPlugins;
    },
    featuredSections: function featuredSections(state) {
      return state.pluginStore.featuredSections;
    }
  })),
  mounted: function mounted() {
    var _this = this;

    // reset variables
    this.$store.commit('cart/updateActiveTrialPlugins', []);
    this.$store.commit('pluginStore/updateFeaturedSections', []);
    this.activeTrialsLoaded = false;
    this.featuredSectionsLoaded = false; // start loading

    this.loading = true; // load featured sections

    this.$store.dispatch('pluginStore/getFeaturedSections').then(function () {
      _this.featuredSectionsLoaded = true;

      _this.$emit('dataLoaded');
    })["catch"](function () {
      _this.featuredSectionsLoaded = true;

      _this.$emit('dataLoaded');
    }); // load active trial plugins

    this.$store.dispatch('cart/getActiveTrials').then(function () {
      _this.activeTrialsLoaded = true;

      _this.$emit('dataLoaded');
    })["catch"](function () {
      _this.activeTrialsError = _this.$options.filters.t('Couldn’t load active trials.', 'app');
      _this.activeTrialsLoaded = true;

      _this.$emit('dataLoaded');
    }); // stop loading when all the loaded has finished loading

    this.$on('dataLoaded', function () {
      if (!_this.featuredSectionsLoaded || !_this.activeTrialsLoaded) {
        return null;
      }

      _this.loading = false;
    });
  }
});
// CONCATENATED MODULE: ./js/pages/index.vue?vue&type=script&lang=js&
 /* harmony default export */ var js_pagesvue_type_script_lang_js_ = (pagesvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/pages/index.vue





/* normalize component */

var pages_component = Object(componentNormalizer["a" /* default */])(
  js_pagesvue_type_script_lang_js_,
  pagesvue_type_template_id_16b1afbb_render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var pages = (pages_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/categories/_id.vue?vue&type=template&id=86d41062&
var _idvue_type_template_id_86d41062_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.category)?_c('div',{staticClass:"ps-container"},[_c('plugin-index',{attrs:{"action":"pluginStore/getPluginsByCategory","requestData":_vm.requestData,"plugins":_vm.plugins},scopedSlots:_vm._u([{key:"header",fn:function(){return [_c('h1',[_vm._v(_vm._s(_vm.category.title))])]},proxy:true}],null,false,3653016063)})],1):_vm._e()}
var _idvue_type_template_id_86d41062_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/categories/_id.vue?vue&type=template&id=86d41062&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginIndex.vue?vue&type=template&id=83ace6b6&
var PluginIndexvue_type_template_id_83ace6b6_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',[_c('div',{staticClass:"tw-border-b tw-border-solid tw-border-gray-200 tw-pb-4 tw-flex tw-justify-between tw-items-center"},[_vm._t("header"),_vm._v(" "),(!_vm.disableSorting)?[_c('plugin-index-sort',{attrs:{"loading":_vm.loading,"orderBy":_vm.orderBy,"direction":_vm.direction},on:{"update:orderBy":function($event){_vm.orderBy=$event},"update:order-by":function($event){_vm.orderBy=$event},"update:direction":function($event){_vm.direction=$event},"change":_vm.onOrderByChange}})]:_vm._e()],2),_vm._v(" "),_c('plugin-grid',{attrs:{"plugins":_vm.plugins}}),_vm._v(" "),(_vm.plugins.length === 0 && !_vm.loadingBottom && !_vm.loading)?_c('div',{staticClass:"tw-mt-4"},[_c('p',[_vm._v(_vm._s(_vm._f("t")("No results.",'app')))])]):_vm._e(),_vm._v(" "),(_vm.error)?_c('div',{staticClass:"tw-my-4 tw-text-red-500"},[_vm._v(_vm._s(_vm.error))]):_vm._e(),_vm._v(" "),(_vm.loadingBottom || (_vm.disableSorting && _vm.loading))?_c('spinner',{staticClass:"tw-my-4"}):_vm._e()],1)}
var PluginIndexvue_type_template_id_83ace6b6_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginIndex.vue?vue&type=template&id=83ace6b6&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginIndexSort.vue?vue&type=template&id=d8c02f4e&
var PluginIndexSortvue_type_template_id_d8c02f4e_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"tw-flex tw-self-end"},[(_vm.loading)?_c('spinner',{staticClass:"tw-mt-2 tw-mr-4"}):_vm._e(),_vm._v(" "),_c('sort-menu-btn',{attrs:{"attributes":_vm.sortMenuBtnAttributes,"value":_vm.options},on:{"update:value":function($event){_vm.options=$event}}})],1)}
var PluginIndexSortvue_type_template_id_d8c02f4e_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginIndexSort.vue?vue&type=template&id=d8c02f4e&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/SortMenuBtn.vue?vue&type=template&id=77b15f50&
var SortMenuBtnvue_type_template_id_77b15f50_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{ref:"sortMenuBtn"},[_c('div',{staticClass:"btn menubtn sortmenubtn",attrs:{"data-icon":_vm.value.direction}},[_vm._v(_vm._s(_vm.menuLabel))]),_vm._v(" "),_c('div',{staticClass:"menu"},[_c('ul',{staticClass:"padded sort-attributes"},_vm._l((_vm.attributes),function(label,key){return _c('li',{key:key},[_c('a',{class:{sel: _vm.value.attribute == key},on:{"click":function($event){return _vm.selectAttribute(key)}}},[_vm._v(_vm._s(label))])])}),0),_vm._v(" "),_c('hr'),_vm._v(" "),_c('ul',{staticClass:"padded sort-directions"},_vm._l((_vm.directions),function(label,key){return _c('li',{key:key},[_c('a',{class:{sel: _vm.value.direction == key},on:{"click":function($event){return _vm.selectDirection(key)}}},[_vm._v(_vm._s(label))])])}),0)])])}
var SortMenuBtnvue_type_template_id_77b15f50_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/SortMenuBtn.vue?vue&type=template&id=77b15f50&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/SortMenuBtn.vue?vue&type=script&lang=js&
function SortMenuBtnvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function SortMenuBtnvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { SortMenuBtnvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { SortMenuBtnvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { SortMenuBtnvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function SortMenuBtnvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* global Craft */

/* harmony default export */ var SortMenuBtnvue_type_script_lang_js_ = ({
  props: ['attributes', 'value'],
  data: function data() {
    return {
      defaultDirection: 'asc',
      directions: {}
    };
  },
  computed: SortMenuBtnvue_type_script_lang_js_objectSpread(SortMenuBtnvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    sortOptions: function sortOptions(state) {
      return state.pluginStore.sortOptions;
    }
  })), {}, {
    menuLabel: function menuLabel() {
      if (this.attributes) {
        return this.attributes[this.value.attribute];
      }

      return null;
    }
  }),
  methods: {
    selectAttribute: function selectAttribute(attribute) {
      var direction = this.sortOptions[attribute] ? this.sortOptions[attribute] : this.value.direction;
      this.$emit('update:value', {
        attribute: attribute,
        direction: direction
      });
    },
    selectDirection: function selectDirection(direction) {
      this.$emit('update:value', {
        attribute: this.value.attribute,
        direction: direction
      });
    }
  },
  mounted: function mounted() {
    var _this = this;

    this.directions = {
      asc: this.$options.filters.t("Ascending", 'app'),
      desc: this.$options.filters.t("Descending", 'app')
    };
    this.$nextTick(function () {
      if (!_this.value.direction) {
        _this.$emit('update:value', {
          attribute: _this.value.attribute,
          direction: _this.defaultDirection
        });
      }

      Craft.initUiElements(_this.$refs.sortMenuBtn);
    });
  }
});
// CONCATENATED MODULE: ./js/components/SortMenuBtn.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_SortMenuBtnvue_type_script_lang_js_ = (SortMenuBtnvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/SortMenuBtn.vue





/* normalize component */

var SortMenuBtn_component = Object(componentNormalizer["a" /* default */])(
  components_SortMenuBtnvue_type_script_lang_js_,
  SortMenuBtnvue_type_template_id_77b15f50_render,
  SortMenuBtnvue_type_template_id_77b15f50_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var SortMenuBtn = (SortMenuBtn_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginIndexSort.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//

/* harmony default export */ var PluginIndexSortvue_type_script_lang_js_ = ({
  props: ['loading', 'orderBy', 'direction'],
  components: {
    SortMenuBtn: SortMenuBtn
  },
  data: function data() {
    return {
      selectedAttribute: null,
      selectedDirection: null,
      sortMenuBtnAttributes: null,
      options: {
        attribute: null,
        direction: null
      }
    };
  },
  watch: {
    options: function options() {
      this.$emit('update:orderBy', this.options.attribute);
      this.$emit('update:direction', this.options.direction);
      this.$emit('change');
    }
  },
  mounted: function mounted() {
    this.options.attribute = this.orderBy;
    this.options.direction = this.direction;
    this.sortMenuBtnAttributes = {
      popularity: this.$options.filters.t("Popularity", 'app'),
      dateUpdated: this.$options.filters.t("Last Update", 'app'),
      name: this.$options.filters.t("Name", 'app')
    };
  }
});
// CONCATENATED MODULE: ./js/components/PluginIndexSort.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginIndexSortvue_type_script_lang_js_ = (PluginIndexSortvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/PluginIndexSort.vue





/* normalize component */

var PluginIndexSort_component = Object(componentNormalizer["a" /* default */])(
  components_PluginIndexSortvue_type_script_lang_js_,
  PluginIndexSortvue_type_template_id_d8c02f4e_render,
  PluginIndexSortvue_type_template_id_d8c02f4e_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginIndexSort = (PluginIndexSort_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginIndex.vue?vue&type=script&lang=js&
function PluginIndexvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function PluginIndexvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { PluginIndexvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { PluginIndexvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { PluginIndexvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function PluginIndexvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//



/* harmony default export */ var PluginIndexvue_type_script_lang_js_ = ({
  props: ['plugins', 'action', 'requestData', 'disableSorting'],
  components: {
    PluginGrid: PluginGrid,
    PluginIndexSort: PluginIndexSort
  },
  data: function data() {
    return {
      orderBy: null,
      direction: null,
      loading: false,
      loadingBottom: false,
      hasMore: false,
      page: 1,
      error: null
    };
  },
  computed: PluginIndexvue_type_script_lang_js_objectSpread(PluginIndexvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    sortOptions: function sortOptions(state) {
      return state.pluginStore.sortOptions;
    }
  })), {}, {
    requestActionData: function requestActionData() {
      return PluginIndexvue_type_script_lang_js_objectSpread(PluginIndexvue_type_script_lang_js_objectSpread({}, this.requestData), {}, {
        page: this.page,
        orderBy: this.orderBy,
        direction: this.direction
      });
    }
  }),
  methods: {
    onOrderByChange: function onOrderByChange() {
      this.error = null;
      this.requestPlugins(true);
    },
    onScroll: function onScroll() {
      var _this = this;

      this.$root.$off('viewScroll', this.onScroll);
      this.$root.$off('windowScroll', this.onScroll);

      if (this.loadingBottom === true && this.hasMore === true) {
        return null;
      }

      if (this.scrollDistFromBottom() < 300) {
        this.requestPlugins(false, function (responseData) {
          if (responseData.currentPage < responseData.total) {
            _this.$root.$on('viewScroll', _this.onScroll);

            _this.$root.$on('windowScroll', _this.onScroll);
          }
        });
      } else {
        this.$root.$on('viewScroll', this.onScroll);
        this.$root.$on('windowScroll', this.onScroll);
      }
    },
    onWindowResize: function onWindowResize() {
      if (!this.hasMore) {
        return null;
      }

      if (this.viewHasScrollbar()) {
        return null;
      }

      this.requestPlugins();
    },
    requestPlugins: function requestPlugins(dontAppendData, onAfterSuccess) {
      var _this2 = this;

      if (this.loading) {
        return null;
      }

      if (this.loadingBottom) {
        return null;
      }

      if (!dontAppendData && !this.hasMore) {
        return null;
      }

      if (dontAppendData) {
        this.page = 1;

        if (this.plugins.length > 0) {
          this.loading = true;
        } else {
          this.loadingBottom = true;
        }
      } else {
        this.loadingBottom = true;
      }

      this.$store.dispatch(this.action, PluginIndexvue_type_script_lang_js_objectSpread(PluginIndexvue_type_script_lang_js_objectSpread({}, this.requestActionData), {}, {
        appendData: !dontAppendData
      })).then(function (responseData) {
        if (responseData && responseData.error) {
          throw responseData.error;
        }

        _this2.loading = false;
        _this2.loadingBottom = false;

        if (responseData.currentPage < responseData.total) {
          _this2.hasMore = true;
          _this2.page++;

          if (!_this2.viewHasScrollbar()) {
            _this2.requestPlugins();
          }
        } else {
          _this2.hasMore = false;
        }

        if (typeof onAfterSuccess === 'function') {
          onAfterSuccess(responseData);
        }
      })["catch"](function (thrown) {
        var errorMsg = _this2.$options.filters.t("Couldn’t get plugins.", 'app');

        if (typeof thrown === 'string') {
          errorMsg = thrown;
        }

        _this2.error = errorMsg;
        _this2.loading = false;
        _this2.loadingBottom = false;
        throw thrown;
      });
    },
    scrollContainer: function scrollContainer() {
      return this.scrollMode() === 'view' ? document.getElementById('content').getElementsByClassName('ps-main')[0] : document.documentElement;
    },
    scrollDistFromBottom: function scrollDistFromBottom() {
      var $container = this.scrollContainer();
      var scrollTop = $container.scrollTop;
      var scrollHeight = $container.scrollHeight;
      var offsetHeight = window.outerHeight;

      if (this.scrollMode() === 'view') {
        offsetHeight = $container.offsetHeight;
      }

      return scrollHeight - Math.max(scrollTop + offsetHeight, 0);
    },
    scrollMode: function scrollMode() {
      if (window.innerWidth >= 975) {
        return 'view';
      }

      return 'window';
    },
    viewHasScrollbar: function viewHasScrollbar() {
      var $container = this.scrollContainer();

      if ($container.clientHeight < $container.scrollHeight) {
        return true;
      }

      return false;
    }
  },
  created: function created() {
    var keys = Object.keys(this.sortOptions);
    var firstOptionKey = keys[0];
    this.orderBy = firstOptionKey;
    this.direction = this.sortOptions[firstOptionKey];
  },
  mounted: function mounted() {
    var _this3 = this;

    this.$store.commit('pluginStore/updatePlugins', []);
    this.$nextTick(function () {
      _this3.requestPlugins(true, function (responseData) {
        if (responseData.currentPage < responseData.total) {
          _this3.$root.$on('viewScroll', _this3.onScroll);

          _this3.$root.$on('windowScroll', _this3.onScroll);

          _this3.$root.$on('windowResize', _this3.onWindowResize);
        }
      });
    });
  },
  beforeDestroy: function beforeDestroy() {
    this.error = null;
    this.$root.$off('viewScroll', this.onScroll);
    this.$root.$off('windowScroll', this.onScroll);
    this.$root.$off('windowResize', this.onWindowResize);
    this.$store.dispatch('pluginStore/cancelRequests');
  }
});
// CONCATENATED MODULE: ./js/components/PluginIndex.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginIndexvue_type_script_lang_js_ = (PluginIndexvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/PluginIndex.vue





/* normalize component */

var PluginIndex_component = Object(componentNormalizer["a" /* default */])(
  components_PluginIndexvue_type_script_lang_js_,
  PluginIndexvue_type_template_id_83ace6b6_render,
  PluginIndexvue_type_template_id_83ace6b6_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginIndex = (PluginIndex_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/categories/_id.vue?vue&type=script&lang=js&
function _idvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function _idvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { _idvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { _idvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { _idvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _idvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var _idvue_type_script_lang_js_ = ({
  components: {
    PluginIndex: PluginIndex
  },
  data: function data() {
    return {
      category: null
    };
  },
  computed: _idvue_type_script_lang_js_objectSpread(_idvue_type_script_lang_js_objectSpread(_idvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    plugins: function plugins(state) {
      return state.pluginStore.plugins;
    }
  })), Object(external_Vuex_["mapGetters"])({
    getCategoryById: 'pluginStore/getCategoryById'
  })), {}, {
    requestData: function requestData() {
      return {
        categoryId: this.category.id
      };
    }
  }),
  methods: _idvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapActions"])({
    getPluginsByCategory: 'pluginStore/getPluginsByCategory'
  })),
  mounted: function mounted() {
    var categoryId = this.$route.params.id;
    this.category = this.getCategoryById(categoryId);
  }
});
// CONCATENATED MODULE: ./js/pages/categories/_id.vue?vue&type=script&lang=js&
 /* harmony default export */ var categories_idvue_type_script_lang_js_ = (_idvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/pages/categories/_id.vue





/* normalize component */

var _id_component = Object(componentNormalizer["a" /* default */])(
  categories_idvue_type_script_lang_js_,
  _idvue_type_template_id_86d41062_render,
  _idvue_type_template_id_86d41062_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var _id = (_id_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/upgrade-craft.vue?vue&type=template&id=44309402&
var upgrade_craftvue_type_template_id_44309402_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-container"},[_c('div',{staticClass:"tw-border tw-border-solid"},[_vm._v("\n        hello world\n    ")]),_vm._v(" "),_c('h1',[_vm._v(_vm._s(_vm._f("t")("Upgrade Craft CMS",'app')))]),_vm._v(" "),_c('hr'),_vm._v(" "),(!_vm.loading)?[(_vm.errorMsg)?[(_vm.errorMsg)?_c('div',{staticClass:"error"},[_vm._v("\n                "+_vm._s(_vm.errorMsg)+"\n            ")]):_vm._e()]:[_c('cms-editions')]]:[_c('spinner')]],2)}
var upgrade_craftvue_type_template_id_44309402_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/upgrade-craft.vue?vue&type=template&id=44309402&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/CmsEditions.vue?vue&type=template&id=3ebb086d&
var CmsEditionsvue_type_template_id_3ebb086d_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"cms-editions"},_vm._l((_vm.cmsEditions),function(edition,key){return _c('cms-edition',{key:key,attrs:{"edition":edition}})}),1)}
var CmsEditionsvue_type_template_id_3ebb086d_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/upgradecraft/CmsEditions.vue?vue&type=template&id=3ebb086d&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/CmsEdition.vue?vue&type=template&id=44a74281&
var CmsEditionvue_type_template_id_44a74281_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"cms-editions-edition"},[_c('div',{staticClass:"description"},[_c('edition-badge',{attrs:{"name":_vm.edition.name,"block":"","big":""}}),_vm._v(" "),_c('p',{staticClass:"edition-description"},[_vm._v(_vm._s(_vm.editionDescription))]),_vm._v(" "),_c('div',{staticClass:"price"},[(_vm.edition.price && _vm.edition.price > 0)?[_vm._v("\n                "+_vm._s(_vm._f("currency")(_vm.edition.price))+"\n            ")]:[_vm._v("\n                "+_vm._s(_vm._f("t")("Free",'app'))+"\n            ")]],2),_vm._v(" "),(_vm.edition.price && _vm.edition.price > 0)?_c('p',{staticClass:"tw--mt-8 tw-py-6 tw-text-gray-600"},[_vm._v("\n            "+_vm._s(_vm._f("t")("Price includes 1 year of updates.",'app'))),_c('br'),_vm._v("\n            "+_vm._s(_vm._f("t")("{renewalPrice}/year per site for updates after that.",'app', {renewalPrice: _vm.$options.filters.currency(_vm.edition.renewalPrice)}))+"\n        ")]):_vm._e(),_vm._v(" "),_c('ul',_vm._l((_vm.features),function(feature,key){return _c('li',{key:key},[_c('icon',{attrs:{"icon":"check"}}),_vm._v(" "),_c('span',{staticClass:"tw-inline-block tw-mx-2"},[_vm._v(_vm._s(feature.name))]),_vm._v(" "),(feature.description)?_c('info-hud',[_vm._v("\n                    "+_vm._s(feature.description)+"\n                ")]):_vm._e()],1)}),0)],1),_vm._v(" "),_c('div',{staticClass:"cms-edition-actions"},[_c('status-badge',{attrs:{"edition":_vm.editionIndex}}),_vm._v(" "),_c('buy-btn',{attrs:{"edition":_vm.editionIndex,"edition-handle":_vm.edition.handle}})],1)])}
var CmsEditionvue_type_template_id_44a74281_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/upgradecraft/CmsEdition.vue?vue&type=template&id=44a74281&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/InfoHud.vue?vue&type=template&id=537ba79d&
var InfoHudvue_type_template_id_537ba79d_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"info-hud tw-flex tw-items-center"},[_c('v-popover',{attrs:{"placement":"right"}},[_c('icon',{attrs:{"icon":"info-circle"}}),_vm._v(" "),_c('template',{slot:"popover"},[_vm._t("default")],2)],2)],1)}
var InfoHudvue_type_template_id_537ba79d_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/InfoHud.vue?vue&type=template&id=537ba79d&

// EXTERNAL MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/v-tooltip/dist/v-tooltip.esm.js
var v_tooltip_esm = __webpack_require__(30);

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/InfoHud.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//


external_Vue_default.a.use(v_tooltip_esm["a" /* default */]);
v_tooltip_esm["a" /* default */].options.autoHide = false;
/* harmony default export */ var InfoHudvue_type_script_lang_js_ = ({});
// CONCATENATED MODULE: ./js/components/InfoHud.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_InfoHudvue_type_script_lang_js_ = (InfoHudvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/InfoHud.vue?vue&type=style&index=0&lang=scss&
var InfoHudvue_type_style_index_0_lang_scss_ = __webpack_require__(171);

// CONCATENATED MODULE: ./js/components/InfoHud.vue






/* normalize component */

var InfoHud_component = Object(componentNormalizer["a" /* default */])(
  components_InfoHudvue_type_script_lang_js_,
  InfoHudvue_type_template_id_537ba79d_render,
  InfoHudvue_type_template_id_537ba79d_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var InfoHud = (InfoHud_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/StatusBadge.vue?vue&type=template&id=657701ce&
var StatusBadgevue_type_template_id_657701ce_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"cms-edition-status-badge"},[(_vm.CraftEdition == _vm.edition)?[(_vm.licensedEdition >= _vm.edition)?[_c('license-status',{attrs:{"status":"installed","description":_vm._f("t")('Installed','app')}})]:[_c('license-status',{attrs:{"status":"installed","description":_vm._f("t")('Installed as a trial','app')}})]]:(_vm.licensedEdition == _vm.edition)?[_c('license-status',{attrs:{"status":"licensed","description":_vm._f("t")('Licensed','app')}})]:_vm._e()],2)}
var StatusBadgevue_type_template_id_657701ce_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/upgradecraft/StatusBadge.vue?vue&type=template&id=657701ce&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/LicenseStatus.vue?vue&type=template&id=94a8be66&scoped=true&
var LicenseStatusvue_type_template_id_94a8be66_scoped_true_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('btn',{class:'license-status ' + _vm.status,attrs:{"icon":"check","disabled":true,"block":"","large":"","outline":""}},[_vm._v("\n    "+_vm._s(_vm.description)+"\n")])}
var LicenseStatusvue_type_template_id_94a8be66_scoped_true_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/LicenseStatus.vue?vue&type=template&id=94a8be66&scoped=true&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/LicenseStatus.vue?vue&type=script&lang=js&
//
//
//
//
//
//
/* harmony default export */ var LicenseStatusvue_type_script_lang_js_ = ({
  props: ['status', 'description']
});
// CONCATENATED MODULE: ./js/components/LicenseStatus.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_LicenseStatusvue_type_script_lang_js_ = (LicenseStatusvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/LicenseStatus.vue?vue&type=style&index=0&id=94a8be66&lang=scss&scoped=true&
var LicenseStatusvue_type_style_index_0_id_94a8be66_lang_scss_scoped_true_ = __webpack_require__(173);

// CONCATENATED MODULE: ./js/components/LicenseStatus.vue






/* normalize component */

var LicenseStatus_component = Object(componentNormalizer["a" /* default */])(
  components_LicenseStatusvue_type_script_lang_js_,
  LicenseStatusvue_type_template_id_94a8be66_scoped_true_render,
  LicenseStatusvue_type_template_id_94a8be66_scoped_true_staticRenderFns,
  false,
  null,
  "94a8be66",
  null
  
)

/* harmony default export */ var LicenseStatus = (LicenseStatus_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/StatusBadge.vue?vue&type=script&lang=js&
function StatusBadgevue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function StatusBadgevue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { StatusBadgevue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { StatusBadgevue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { StatusBadgevue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function StatusBadgevue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var StatusBadgevue_type_script_lang_js_ = ({
  props: ['edition'],
  components: {
    LicenseStatus: LicenseStatus
  },
  computed: StatusBadgevue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    CraftEdition: function CraftEdition(state) {
      return state.craft.CraftEdition;
    },
    licensedEdition: function licensedEdition(state) {
      return state.craft.licensedEdition;
    }
  }))
});
// CONCATENATED MODULE: ./js/components/upgradecraft/StatusBadge.vue?vue&type=script&lang=js&
 /* harmony default export */ var upgradecraft_StatusBadgevue_type_script_lang_js_ = (StatusBadgevue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/upgradecraft/StatusBadge.vue





/* normalize component */

var StatusBadge_component = Object(componentNormalizer["a" /* default */])(
  upgradecraft_StatusBadgevue_type_script_lang_js_,
  StatusBadgevue_type_template_id_657701ce_render,
  StatusBadgevue_type_template_id_657701ce_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var StatusBadge = (StatusBadge_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/BuyBtn.vue?vue&type=template&id=3d28c2be&
var BuyBtnvue_type_template_id_3d28c2be_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',[(_vm.edition > _vm.licensedEdition)?[(!_vm.isCmsEditionInCart(_vm.editionHandle))?[_c('btn',{attrs:{"kind":"primary","block":"","large":""},on:{"click":function($event){return _vm.buyCraft(_vm.editionHandle)}}},[_vm._v(_vm._s(_vm._f("t")("Buy now",'app')))])]:[_c('btn',{attrs:{"block":"","large":"","submit":"","disabled":""}},[_vm._v(_vm._s(_vm._f("t")("Added to cart",'app')))])]]:_vm._e(),_vm._v(" "),(_vm.canTestEditions && _vm.edition != _vm.CraftEdition && _vm.edition > _vm.licensedEdition)?[_c('btn',{attrs:{"block":"","large":""},on:{"click":function($event){return _vm.installCraft(_vm.editionHandle)}}},[_vm._v(_vm._s(_vm._f("t")("Try for free",'app')))])]:_vm._e(),_vm._v(" "),(_vm.edition == _vm.licensedEdition && _vm.edition != _vm.CraftEdition)?[_c('btn',{attrs:{"block":"","large":""},on:{"click":function($event){return _vm.installCraft(_vm.editionHandle)}}},[_vm._v(_vm._s(_vm._f("t")("Reactivate",'app')))])]:_vm._e(),_vm._v(" "),(_vm.loading)?_c('spinner'):_vm._e()],2)}
var BuyBtnvue_type_template_id_3d28c2be_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/upgradecraft/BuyBtn.vue?vue&type=template&id=3d28c2be&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/BuyBtn.vue?vue&type=script&lang=js&
function BuyBtnvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function BuyBtnvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { BuyBtnvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { BuyBtnvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { BuyBtnvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function BuyBtnvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var BuyBtnvue_type_script_lang_js_ = ({
  props: ['edition', 'edition-handle'],
  data: function data() {
    return {
      loading: false
    };
  },
  computed: BuyBtnvue_type_script_lang_js_objectSpread(BuyBtnvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    canTestEditions: function canTestEditions(state) {
      return state.craft.canTestEditions;
    },
    CraftEdition: function CraftEdition(state) {
      return state.craft.CraftEdition;
    },
    licensedEdition: function licensedEdition(state) {
      return state.craft.licensedEdition;
    }
  })), Object(external_Vuex_["mapGetters"])({
    isCmsEditionInCart: 'cart/isCmsEditionInCart'
  })),
  methods: BuyBtnvue_type_script_lang_js_objectSpread(BuyBtnvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapActions"])({
    addToCart: 'cart/addToCart',
    getCraftData: 'craft/getCraftData',
    tryEdition: 'craft/tryEdition'
  })), {}, {
    buyCraft: function buyCraft(edition) {
      var _this = this;

      this.loading = true;
      var item = {
        type: 'cms-edition',
        edition: edition
      };
      this.addToCart([item]).then(function () {
        _this.loading = false;

        _this.$root.openModal('cart');
      })["catch"](function () {
        _this.loading = false;
      });
    },
    installCraft: function installCraft(edition) {
      var _this2 = this;

      this.loading = true;
      this.tryEdition(edition).then(function () {
        _this2.getCraftData().then(function () {
          _this2.loading = false;

          _this2.$root.displayNotice("Craft CMS edition changed.");
        });
      })["catch"](function () {
        _this2.loading = false;

        _this2.$root.displayError("Couldn’t change Craft CMS edition.");
      });
    }
  })
});
// CONCATENATED MODULE: ./js/components/upgradecraft/BuyBtn.vue?vue&type=script&lang=js&
 /* harmony default export */ var upgradecraft_BuyBtnvue_type_script_lang_js_ = (BuyBtnvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/upgradecraft/BuyBtn.vue





/* normalize component */

var BuyBtn_component = Object(componentNormalizer["a" /* default */])(
  upgradecraft_BuyBtnvue_type_script_lang_js_,
  BuyBtnvue_type_template_id_3d28c2be_render,
  BuyBtnvue_type_template_id_3d28c2be_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var BuyBtn = (BuyBtn_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/CmsEdition.vue?vue&type=script&lang=js&
function CmsEditionvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function CmsEditionvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { CmsEditionvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { CmsEditionvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { CmsEditionvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function CmsEditionvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//





/* harmony default export */ var CmsEditionvue_type_script_lang_js_ = ({
  props: ['edition'],
  components: {
    InfoHud: InfoHud,
    StatusBadge: StatusBadge,
    BuyBtn: BuyBtn,
    EditionBadge: EditionBadge
  },
  computed: CmsEditionvue_type_script_lang_js_objectSpread(CmsEditionvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapGetters"])({
    getCmsEditionFeatures: 'craft/getCmsEditionFeatures',
    getCmsEditionIndex: 'craft/getCmsEditionIndex'
  })), {}, {
    editionDescription: function editionDescription() {
      switch (this.edition.handle) {
        case 'solo':
          return this.$options.filters.t('For when you’re building a website for yourself or a friend.', 'app');

        case 'pro':
          return this.$options.filters.t('For when you’re building something professionally for a client or team.', 'app');

        default:
          return null;
      }
    },
    editionIndex: function editionIndex() {
      return this.getCmsEditionIndex(this.edition.handle);
    },
    features: function features() {
      return this.getCmsEditionFeatures(this.edition.handle);
    }
  })
});
// CONCATENATED MODULE: ./js/components/upgradecraft/CmsEdition.vue?vue&type=script&lang=js&
 /* harmony default export */ var upgradecraft_CmsEditionvue_type_script_lang_js_ = (CmsEditionvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/upgradecraft/CmsEdition.vue?vue&type=style&index=0&lang=scss&
var CmsEditionvue_type_style_index_0_lang_scss_ = __webpack_require__(175);

// CONCATENATED MODULE: ./js/components/upgradecraft/CmsEdition.vue






/* normalize component */

var CmsEdition_component = Object(componentNormalizer["a" /* default */])(
  upgradecraft_CmsEditionvue_type_script_lang_js_,
  CmsEditionvue_type_template_id_44a74281_render,
  CmsEditionvue_type_template_id_44a74281_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var CmsEdition = (CmsEdition_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/upgradecraft/CmsEditions.vue?vue&type=script&lang=js&
function CmsEditionsvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function CmsEditionsvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { CmsEditionsvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { CmsEditionsvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { CmsEditionsvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function CmsEditionsvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//


/* harmony default export */ var CmsEditionsvue_type_script_lang_js_ = ({
  components: {
    CmsEdition: CmsEdition
  },
  data: function data() {
    return {
      loading: false
    };
  },
  computed: CmsEditionsvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    cmsEditions: function cmsEditions(state) {
      return state.pluginStore.cmsEditions;
    }
  })),
  beforeDestroy: function beforeDestroy() {
    this.$store.dispatch('pluginStore/cancelRequests');
  }
});
// CONCATENATED MODULE: ./js/components/upgradecraft/CmsEditions.vue?vue&type=script&lang=js&
 /* harmony default export */ var upgradecraft_CmsEditionsvue_type_script_lang_js_ = (CmsEditionsvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/upgradecraft/CmsEditions.vue?vue&type=style&index=0&lang=scss&
var CmsEditionsvue_type_style_index_0_lang_scss_ = __webpack_require__(177);

// CONCATENATED MODULE: ./js/components/upgradecraft/CmsEditions.vue






/* normalize component */

var CmsEditions_component = Object(componentNormalizer["a" /* default */])(
  upgradecraft_CmsEditionsvue_type_script_lang_js_,
  CmsEditionsvue_type_template_id_3ebb086d_render,
  CmsEditionsvue_type_template_id_3ebb086d_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var CmsEditions = (CmsEditions_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/upgrade-craft.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var upgrade_craftvue_type_script_lang_js_ = ({
  components: {
    CmsEditions: CmsEditions
  },
  data: function data() {
    return {
      errorMsg: null,
      loading: true
    };
  },
  mounted: function mounted() {
    var _this = this;

    this.$store.dispatch('pluginStore/getCmsEditions').then(function () {
      _this.loading = false;
    })["catch"](function () {
      _this.loading = false;
      _this.errorMsg = _this.$options.filters.t("Couldn’t load CMS editions.", 'app');
    });
  }
});
// CONCATENATED MODULE: ./js/pages/upgrade-craft.vue?vue&type=script&lang=js&
 /* harmony default export */ var pages_upgrade_craftvue_type_script_lang_js_ = (upgrade_craftvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/pages/upgrade-craft.vue





/* normalize component */

var upgrade_craft_component = Object(componentNormalizer["a" /* default */])(
  pages_upgrade_craftvue_type_script_lang_js_,
  upgrade_craftvue_type_template_id_44309402_render,
  upgrade_craftvue_type_template_id_44309402_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var upgrade_craft = (upgrade_craft_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/developer/_id.vue?vue&type=template&id=5488eebc&scoped=true&
var _idvue_type_template_id_5488eebc_scoped_true_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-container"},[(!_vm.loading)?[_c('plugin-index',{attrs:{"action":"pluginStore/getPluginsByDeveloperId","requestData":_vm.requestData,"plugins":_vm.plugins},scopedSlots:_vm._u([{key:"header",fn:function(){return [(_vm.developer)?_c('div',{staticClass:"developer-card tw-flex tw-pb-2 tw-items-center"},[_c('div',{staticClass:"avatar tw-inline-block tw-overflow-hidden tw-rounded-full tw-bg-gray-500 tw-mr-6 tw-no-line-height"},[_c('img',{attrs:{"src":_vm.developer.photoUrl,"width":"120","height":"120"}})]),_vm._v(" "),_c('div',{staticClass:"tw-flex-1"},[_c('h1',{staticClass:"tw-text-lg tw-font-bold tw-mb-2"},[_vm._v(_vm._s(_vm.developer.developerName))]),_vm._v(" "),(_vm.developer.location)?_c('p',{staticClass:"tw-mb-1"},[_vm._v(_vm._s(_vm.developer.location))]):_vm._e(),_vm._v(" "),(_vm.developer.developerUrl)?_c('ul',[_c('li',{staticClass:"tw-mr-4 tw-inline-block"},[_c('btn',{attrs:{"href":_vm.developer.developerUrl,"block":""}},[_vm._v(_vm._s(_vm._f("t")("Website",'app')))])],1)]):_vm._e()])]):_vm._e()]},proxy:true}],null,false,3145547534)})]:[_c('spinner')]],2)}
var _idvue_type_template_id_5488eebc_scoped_true_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/developer/_id.vue?vue&type=template&id=5488eebc&scoped=true&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/developer/_id.vue?vue&type=script&lang=js&
function developer_idvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function developer_idvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { developer_idvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { developer_idvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { developer_idvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function developer_idvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var developer_idvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      loading: true
    };
  },
  components: {
    PluginIndex: PluginIndex
  },
  computed: developer_idvue_type_script_lang_js_objectSpread(developer_idvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    developer: function developer(state) {
      return state.pluginStore.developer;
    },
    plugins: function plugins(state) {
      return state.pluginStore.plugins;
    }
  })), {}, {
    requestData: function requestData() {
      return {
        developerId: this.$route.params.id
      };
    }
  }),
  mounted: function mounted() {
    var _this = this;

    var developerId = this.$route.params.id; // load developer details

    this.$store.dispatch('pluginStore/getDeveloper', developerId).then(function () {
      _this.loading = false;
    })["catch"](function () {
      _this.loading = false;
    });
  }
});
// CONCATENATED MODULE: ./js/pages/developer/_id.vue?vue&type=script&lang=js&
 /* harmony default export */ var pages_developer_idvue_type_script_lang_js_ = (developer_idvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/pages/developer/_id.vue?vue&type=style&index=0&id=5488eebc&lang=scss&scoped=true&
var _idvue_type_style_index_0_id_5488eebc_lang_scss_scoped_true_ = __webpack_require__(179);

// CONCATENATED MODULE: ./js/pages/developer/_id.vue






/* normalize component */

var developer_id_component = Object(componentNormalizer["a" /* default */])(
  pages_developer_idvue_type_script_lang_js_,
  _idvue_type_template_id_5488eebc_scoped_true_render,
  _idvue_type_template_id_5488eebc_scoped_true_staticRenderFns,
  false,
  null,
  "5488eebc",
  null
  
)

/* harmony default export */ var developer_id = (developer_id_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/featured/_handle.vue?vue&type=template&id=4b1cbe95&
var _handlevue_type_template_id_4b1cbe95_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-container"},[(!_vm.loading)?[_c('plugin-index',{attrs:{"action":"pluginStore/getPluginsByFeaturedSectionHandle","requestData":_vm.requestData,"plugins":_vm.plugins,"disableSorting":true},scopedSlots:_vm._u([{key:"header",fn:function(){return [(_vm.featuredSection)?[_c('h1',[_vm._v(_vm._s(_vm.featuredSection.title))])]:_vm._e()]},proxy:true}],null,false,780213750)})]:[_c('spinner')]],2)}
var _handlevue_type_template_id_4b1cbe95_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/featured/_handle.vue?vue&type=template&id=4b1cbe95&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/featured/_handle.vue?vue&type=script&lang=js&
function _handlevue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function _handlevue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { _handlevue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { _handlevue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { _handlevue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _handlevue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var _handlevue_type_script_lang_js_ = ({
  components: {
    PluginIndex: PluginIndex
  },
  data: function data() {
    return {
      loading: true,
      pluginsLoaded: false,
      sectionLoaded: false
    };
  },
  computed: _handlevue_type_script_lang_js_objectSpread(_handlevue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    featuredSection: function featuredSection(state) {
      return state.pluginStore.featuredSection;
    },
    plugins: function plugins(state) {
      return state.pluginStore.plugins;
    }
  })), {}, {
    requestData: function requestData() {
      return {
        featuredSectionHandle: this.$route.params.handle
      };
    }
  }),
  mounted: function mounted() {
    var _this = this;

    this.$store.commit('pluginStore/updatePlugins', []);
    var featuredSectionHandle = this.$route.params.handle; // retrieve featured section

    this.$store.dispatch('pluginStore/getFeaturedSectionByHandle', featuredSectionHandle).then(function () {
      _this.loading = false;
    })["catch"](function () {
      _this.loading = false;
    });
  }
});
// CONCATENATED MODULE: ./js/pages/featured/_handle.vue?vue&type=script&lang=js&
 /* harmony default export */ var featured_handlevue_type_script_lang_js_ = (_handlevue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/pages/featured/_handle.vue





/* normalize component */

var _handle_component = Object(componentNormalizer["a" /* default */])(
  featured_handlevue_type_script_lang_js_,
  _handlevue_type_template_id_4b1cbe95_render,
  _handlevue_type_template_id_4b1cbe95_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var _handle = (_handle_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/buy/_plugin.vue?vue&type=template&id=3dc84d6d&
var _pluginvue_type_template_id_3dc84d6d_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-container buy-plugin"},[(_vm.loading)?_c('status-message',{attrs:{"message":_vm.statusMessage}}):_vm._e()],1)}
var _pluginvue_type_template_id_3dc84d6d_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/buy/_plugin.vue?vue&type=template&id=3dc84d6d&

// CONCATENATED MODULE: ./js/api/pluginstore.js
/* global Craft */
 // create a cancel token for axios

var CancelToken = external_axios_default.a.CancelToken;
var cancelTokenSource = CancelToken.source();
/* harmony default export */ var pluginstore = ({
  /**
   * Cancel requests.
   */
  cancelRequests: function cancelRequests() {
    // cancel requests
    cancelTokenSource.cancel(); // create a new cancel token

    cancelTokenSource = CancelToken.source();
  },

  /**
   * Get plugin store data.
   *
   * @returns {AxiosPromise<any>}
   */
  getCoreData: function getCoreData() {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'plugin-store/core-data', {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get CMS editions.
   *
   * @returns {AxiosPromise<any>}
   */
  getCmsEditions: function getCmsEditions() {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'cms-editions', {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get developer.
   *
   * @param developerId
   * @returns {AxiosPromise<any>}
   */
  getDeveloper: function getDeveloper(developerId) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'developer/' + developerId, {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get featured section by handle.
   *
   * @param featuredSectionHandle
   * @returns {AxiosPromise<any>}
   */
  getFeaturedSectionByHandle: function getFeaturedSectionByHandle(featuredSectionHandle) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'plugin-store/featured-section/' + featuredSectionHandle, {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get featured sections.
   *
   * @returns {AxiosPromise<any>}
   */
  getFeaturedSections: function getFeaturedSections() {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'plugin-store/featured-sections', {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugin changelog.
   *
   * @param pluginId
   * @returns {AxiosPromise<any>}
   */
  getPluginChangelog: function getPluginChangelog(pluginId) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'plugin/' + pluginId + '/changelog', {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugin details.
   *
   * @param pluginId
   * @returns {AxiosPromise<any>}
   */
  getPluginDetails: function getPluginDetails(pluginId) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'plugin/' + pluginId, {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugin details by handle.
   *
   * @param pluginHandle
   * @returns {AxiosPromise<any>}
   */
  getPluginDetailsByHandle: function getPluginDetailsByHandle(pluginHandle) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'plugin-store/plugin/' + pluginHandle, {
        cancelToken: cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugins by category.
   *
   * @param categoryId
   * @param pluginIndexParams
   * @returns {AxiosPromise<any>}
   */
  getPluginsByCategory: function getPluginsByCategory(categoryId, pluginIndexParams) {
    var _this = this;

    return new Promise(function (resolve, reject) {
      var params = _this._getPluginIndexParams(pluginIndexParams);

      params.categoryId = categoryId;
      Craft.sendApiRequest('GET', 'plugin-store/plugins', {
        cancelToken: cancelTokenSource.token,
        params: params
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugins by developer ID.
   *
   * @param developerId
   * @param pluginIndexParams
   * @returns {AxiosPromise<any>}
   */
  getPluginsByDeveloperId: function getPluginsByDeveloperId(developerId, pluginIndexParams) {
    var _this2 = this;

    return new Promise(function (resolve, reject) {
      var params = _this2._getPluginIndexParams(pluginIndexParams);

      params.developerId = developerId;
      Craft.sendApiRequest('GET', 'plugin-store/plugins', {
        cancelToken: cancelTokenSource.token,
        params: params
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugins by featured section handle.
   *
   * @param featuredSectionHandle
   * @param pluginIndexParams
   * @returns {AxiosPromise<any>}
   */
  getPluginsByFeaturedSectionHandle: function getPluginsByFeaturedSectionHandle(featuredSectionHandle, pluginIndexParams) {
    var _this3 = this;

    return new Promise(function (resolve, reject) {
      var params = _this3._getPluginIndexParams(pluginIndexParams);

      Craft.sendApiRequest('GET', 'plugin-store/plugins-by-featured-section/' + featuredSectionHandle, {
        cancelToken: cancelTokenSource.token,
        params: params
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugins by handles.
   *
   * @param pluginHandles
   * @returns {AxiosPromise<any>}
   */
  getPluginsByHandles: function getPluginsByHandles(pluginHandles) {
    return new Promise(function (resolve, reject) {
      var pluginHandlesString;

      if (Array.isArray(pluginHandles)) {
        pluginHandlesString = pluginHandles.join(',');
      } else {
        pluginHandlesString = pluginHandles;
      }

      Craft.sendApiRequest('GET', 'plugin-store/plugins-by-handles', {
        cancelToken: cancelTokenSource.token,
        params: {
          pluginHandles: pluginHandlesString
        }
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugins by IDs.
   *
   * @param pluginIds
   * @returns {AxiosPromise<any>}
   */
  getPluginsByIds: function getPluginsByIds(pluginIds) {
    return new Promise(function (resolve, reject) {
      var pluginIdsString;

      if (Array.isArray(pluginIds)) {
        pluginIdsString = pluginIds.join(',');
      } else {
        pluginIdsString = pluginIds;
      }

      Craft.sendApiRequest('GET', 'plugins', {
        cancelToken: cancelTokenSource.token,
        params: {
          ids: pluginIdsString
        }
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Search plugins.
   *
   * @param searchQuery
   * @param pluginIndexParams
   * @returns {AxiosPromise<any>}
   */
  searchPlugins: function searchPlugins(searchQuery, pluginIndexParams) {
    var _this4 = this;

    return new Promise(function (resolve, reject) {
      var params = _this4._getPluginIndexParams(pluginIndexParams);

      params.searchQuery = searchQuery;
      Craft.sendApiRequest('GET', 'plugin-store/plugins', {
        cancelToken: cancelTokenSource.token,
        params: params
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get plugin index params.
   *
   * @param limit
   * @param offset
   * @param orderBy
   * @param direction
   * @returns {{offset: *, limit: *, orderBy: *, direction: *}}
   * @private
   */
  _getPluginIndexParams: function _getPluginIndexParams(_ref) {
    var perPage = _ref.perPage,
        page = _ref.page,
        orderBy = _ref.orderBy,
        direction = _ref.direction;

    if (!perPage) {
      perPage = 96;
    }

    if (!page) {
      page = 1;
    }

    return {
      perPage: perPage,
      page: page,
      orderBy: orderBy,
      direction: direction
    };
  }
});
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/StatusMessage.vue?vue&type=template&id=2bdc21d8&
var StatusMessagevue_type_template_id_2bdc21d8_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"status-message"},[_c('div',[(_vm.error)?[_c('icon',{attrs:{"icon":"exclamation-triangle"}})]:[_c('spinner',{attrs:{"size":"lg"}})],_vm._v(" "),_c('div',{staticClass:"message"},[_vm._v(_vm._s(_vm.message))])],2)])}
var StatusMessagevue_type_template_id_2bdc21d8_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/StatusMessage.vue?vue&type=template&id=2bdc21d8&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/StatusMessage.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ var StatusMessagevue_type_script_lang_js_ = ({
  props: ['message', 'error']
});
// CONCATENATED MODULE: ./js/components/StatusMessage.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_StatusMessagevue_type_script_lang_js_ = (StatusMessagevue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/StatusMessage.vue?vue&type=style&index=0&lang=scss&
var StatusMessagevue_type_style_index_0_lang_scss_ = __webpack_require__(181);

// CONCATENATED MODULE: ./js/components/StatusMessage.vue






/* normalize component */

var StatusMessage_component = Object(componentNormalizer["a" /* default */])(
  components_StatusMessagevue_type_script_lang_js_,
  StatusMessagevue_type_template_id_2bdc21d8_render,
  StatusMessagevue_type_template_id_2bdc21d8_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var StatusMessage = (StatusMessage_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/buy/_plugin.vue?vue&type=script&lang=js&
function _pluginvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function _pluginvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { _pluginvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { _pluginvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { _pluginvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _pluginvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//



/* harmony default export */ var _pluginvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      loading: false,
      statusMessage: null
    };
  },
  components: {
    StatusMessage: StatusMessage
  },
  methods: {
    buyPlugin: function buyPlugin(pluginHandle, editionHandle) {
      var _this = this;

      pluginstore.getPluginDetailsByHandle(pluginHandle).then(function (responseData) {
        var plugin = responseData;

        if (!_this.isPluginBuyable(plugin)) {
          _this.loading = false;

          _this.$router.push({
            path: '/'
          });

          return;
        }

        if (_this.isInCart(plugin)) {
          _this.$router.push({
            path: '/'
          });

          _this.$root.openModal('cart');
        } else {
          if (!editionHandle) {
            editionHandle = plugin.editions[0].handle;
          }

          var item = {
            type: 'plugin-edition',
            plugin: plugin.handle,
            edition: editionHandle
          };

          _this.$store.dispatch('cart/addToCart', [item]).then(function () {
            _this.loading = false;

            _this.$router.push({
              path: '/'
            });

            _this.$root.openModal('cart');
          })["catch"](function (error) {
            throw error;
          });
        }
      })["catch"](function (error) {
        throw error;
      });
    },
    isPluginBuyable: function isPluginBuyable(plugin) {
      var price = plugin.editions[0].price;

      if (price === null) {
        return false;
      }

      if (parseFloat(price) === 0) {
        return false;
      }

      if (!this.isPluginInstalled(plugin.handle)) {
        return true;
      }

      var pluginLicenseInfo = this.getPluginLicenseInfo(plugin.handle);

      if (!pluginLicenseInfo) {
        return false;
      }

      if (pluginLicenseInfo.licenseKey && pluginLicenseInfo.licenseKeyStatus !== 'trial' && pluginLicenseInfo.licenseIssues.indexOf('mismatched') === -1) {
        return false;
      }

      return true;
    }
  },
  computed: _pluginvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapGetters"])({
    isInCart: 'cart/isInCart',
    isPluginInstalled: 'craft/isPluginInstalled',
    getPluginLicenseInfo: 'craft/getPluginLicenseInfo'
  })),
  mounted: function mounted() {
    var _this2 = this;

    this.loading = true;
    this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app');
    var plugin = this.$route.params.plugin;
    var edition = this.$route.params.edition;

    if (this.$root.allDataLoaded) {
      this.buyPlugin(plugin, edition);
    } else {
      // wait for the cart to be ready
      this.$root.$on('allDataLoaded', function () {
        _this2.buyPlugin(plugin, edition);
      });
    }
  }
});
// CONCATENATED MODULE: ./js/pages/buy/_plugin.vue?vue&type=script&lang=js&
 /* harmony default export */ var buy_pluginvue_type_script_lang_js_ = (_pluginvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/pages/buy/_plugin.vue?vue&type=style&index=0&lang=scss&
var _pluginvue_type_style_index_0_lang_scss_ = __webpack_require__(183);

// CONCATENATED MODULE: ./js/pages/buy/_plugin.vue






/* normalize component */

var _plugin_component = Object(componentNormalizer["a" /* default */])(
  buy_pluginvue_type_script_lang_js_,
  _pluginvue_type_template_id_3dc84d6d_render,
  _pluginvue_type_template_id_3dc84d6d_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var _plugin = (_plugin_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/buy-all-trials.vue?vue&type=template&id=78dd4407&
var buy_all_trialsvue_type_template_id_78dd4407_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-container buy-plugin"},[(_vm.loading)?_c('status-message',{attrs:{"message":_vm.statusMessage}}):_vm._e()],1)}
var buy_all_trialsvue_type_template_id_78dd4407_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/buy-all-trials.vue?vue&type=template&id=78dd4407&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/buy-all-trials.vue?vue&type=script&lang=js&
function buy_all_trialsvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function buy_all_trialsvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { buy_all_trialsvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { buy_all_trialsvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { buy_all_trialsvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function buy_all_trialsvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//


/* harmony default export */ var buy_all_trialsvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      loading: false,
      statusMessage: null,
      activeTrialsLoaded: false,
      activeTrialsError: null
    };
  },
  components: {
    StatusMessage: StatusMessage
  },
  computed: buy_all_trialsvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapGetters"])({
    pendingActiveTrials: 'cart/pendingActiveTrials'
  })),
  methods: {
    buyAllTrials: function buyAllTrials() {
      var _this = this;

      // load active trial plugins
      this.$store.dispatch('cart/getActiveTrials').then(function () {
        _this.activeTrialsLoaded = true; // Add all trials to the cart

        _this.$store.dispatch('cart/addAllTrialsToCart').then(function () {
          _this.$root.displayNotice(_this.$options.filters.t('Active trials added to the cart.', 'app'));

          _this.$router.push({
            path: '/'
          });

          _this.$root.openModal('cart');
        })["catch"](function () {
          _this.$root.displayError(_this.$options.filters.t('Couldn’t add all items to the cart.', 'app'));

          _this.$router.push({
            path: '/'
          });
        });
      })["catch"](function () {
        _this.activeTrialsError = _this.$options.filters.t('Couldn’t load active trials.', 'app');
        _this.activeTrialsLoaded = true;
      });
    }
  },
  mounted: function mounted() {
    var _this2 = this;

    this.loading = true;
    this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app');

    if (this.$root.allDataLoaded) {
      this.buyAllTrials();
    } else {
      // wait for the cart to be ready
      this.$root.$on('allDataLoaded', function () {
        _this2.buyAllTrials();
      });
    }
  }
});
// CONCATENATED MODULE: ./js/pages/buy-all-trials.vue?vue&type=script&lang=js&
 /* harmony default export */ var pages_buy_all_trialsvue_type_script_lang_js_ = (buy_all_trialsvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/pages/buy-all-trials.vue?vue&type=style&index=0&lang=scss&
var buy_all_trialsvue_type_style_index_0_lang_scss_ = __webpack_require__(185);

// CONCATENATED MODULE: ./js/pages/buy-all-trials.vue






/* normalize component */

var buy_all_trials_component = Object(componentNormalizer["a" /* default */])(
  pages_buy_all_trialsvue_type_script_lang_js_,
  buy_all_trialsvue_type_template_id_78dd4407_render,
  buy_all_trialsvue_type_template_id_78dd4407_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var buy_all_trials = (buy_all_trials_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/tests.vue?vue&type=template&id=20582061&
var testsvue_type_template_id_20582061_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',[_c('h2',[_vm._v("Translations")]),_vm._v(" "),_c('p',[_vm._v(_vm._s(_vm._f("currency")(_vm.somePrice))+" per year for updates")]),_vm._v(" "),_c('p',[_vm._v(_vm._s(_vm._f("t")("{price} per year for updates",'app', { price: _vm.$root.$options.filters.currency(_vm.somePrice) })))]),_vm._v(" "),_vm._m(0),_vm._v(" "),_c('p',{domProps:{"innerHTML":_vm._s(_vm.craftTranslation)}}),_vm._v(" "),_c('h2',[_vm._v("Modal")]),_vm._v(" "),_c('p',[_c('a',{on:{"click":function($event){return _vm.openModal()}}},[_vm._v("Open Garnish Modal")])]),_vm._v(" "),_c('div',{staticClass:"tw-hidden"},[_c('div',{ref:"garnishmodalcontent",staticClass:"modal"},[_c('div',{staticClass:"body"},[_vm._v("\n                Hello World\n            ")])])])])}
var testsvue_type_template_id_20582061_staticRenderFns = [function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('p',[_vm._v("{{ \"Go to {link}\"|t('app', { link: '"),_c('a',{attrs:{"href":"#"}},[_vm._v("test")]),_vm._v("' }) }}")])}]


// CONCATENATED MODULE: ./js/pages/tests.vue?vue&type=template&id=20582061&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/tests.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* global Craft */

/* global Garnish */
/* harmony default export */ var testsvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      somePrice: '99.00',
      modal: null
    };
  },
  computed: {
    craftTranslation: function craftTranslation() {
      return Craft.t('app', 'Go to {link}', {
        link: '<a href="#">test</a>'
      });
    }
  },
  mounted: function mounted() {
    this.modal = new Garnish.Modal(this.$refs.garnishmodalcontent, {
      autoShow: false,
      resizable: true
    });
  },
  methods: {
    openModal: function openModal() {
      this.modal.show();
    }
  }
});
// CONCATENATED MODULE: ./js/pages/tests.vue?vue&type=script&lang=js&
 /* harmony default export */ var pages_testsvue_type_script_lang_js_ = (testsvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/pages/tests.vue





/* normalize component */

var tests_component = Object(componentNormalizer["a" /* default */])(
  pages_testsvue_type_script_lang_js_,
  testsvue_type_template_id_20582061_render,
  testsvue_type_template_id_20582061_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var tests = (tests_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/_not-found.vue?vue&type=template&id=13aa928c&
var _not_foundvue_type_template_id_13aa928c_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('status-message',{attrs:{"error":true,"message":_vm.message}})}
var _not_foundvue_type_template_id_13aa928c_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/_not-found.vue?vue&type=template&id=13aa928c&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/_not-found.vue?vue&type=script&lang=js&
//
//
//
//

/* harmony default export */ var _not_foundvue_type_script_lang_js_ = ({
  components: {
    StatusMessage: StatusMessage
  },
  computed: {
    message: function message() {
      return this.$options.filters.t("Page not found.", 'app');
    }
  }
});
// CONCATENATED MODULE: ./js/pages/_not-found.vue?vue&type=script&lang=js&
 /* harmony default export */ var pages_not_foundvue_type_script_lang_js_ = (_not_foundvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/pages/_not-found.vue





/* normalize component */

var _not_found_component = Object(componentNormalizer["a" /* default */])(
  pages_not_foundvue_type_script_lang_js_,
  _not_foundvue_type_template_id_13aa928c_render,
  _not_foundvue_type_template_id_13aa928c_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var _not_found = (_not_found_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/search.vue?vue&type=template&id=344d2972&
var searchvue_type_template_id_344d2972_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-container"},[_c('plugin-index',{ref:"pluginIndex",attrs:{"action":"pluginStore/searchPlugins","requestData":_vm.requestData,"plugins":_vm.plugins},scopedSlots:_vm._u([{key:"header",fn:function(){return [_c('h1',[_vm._v(_vm._s(_vm._f("t")("Showing results for “{searchQuery}”",'app', {searchQuery: _vm.searchQuery})))])]},proxy:true}])})],1)}
var searchvue_type_template_id_344d2972_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/search.vue?vue&type=template&id=344d2972&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/search.vue?vue&type=script&lang=js&
function searchvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function searchvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { searchvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { searchvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { searchvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function searchvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var searchvue_type_script_lang_js_ = ({
  components: {
    PluginIndex: PluginIndex
  },
  watch: {
    searchQuery: function searchQuery() {
      var _this = this;

      this.$router.push({
        path: '/'
      });
      this.$nextTick(function () {
        _this.$router.push({
          path: '/search'
        });
      });
    }
  },
  computed: searchvue_type_script_lang_js_objectSpread(searchvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    plugins: function plugins(state) {
      return state.pluginStore.plugins;
    },
    searchQuery: function searchQuery(state) {
      return state.app.searchQuery;
    }
  })), {}, {
    requestData: function requestData() {
      return {
        searchQuery: this.searchQuery
      };
    }
  }),
  mounted: function mounted() {
    if (!this.searchQuery) {
      this.$router.push({
        path: '/'
      });
      return null;
    }
  }
});
// CONCATENATED MODULE: ./js/pages/search.vue?vue&type=script&lang=js&
 /* harmony default export */ var pages_searchvue_type_script_lang_js_ = (searchvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/pages/search.vue





/* normalize component */

var search_component = Object(componentNormalizer["a" /* default */])(
  pages_searchvue_type_script_lang_js_,
  searchvue_type_template_id_344d2972_render,
  searchvue_type_template_id_344d2972_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var search = (search_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/_handle/index.vue?vue&type=template&id=66045728&
var _handlevue_type_template_id_66045728_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"plugin-details ps-container"},[(!_vm.loading && _vm.plugin)?[_c('div',{staticClass:"plugin-details-header tw-border-b tw-border-solid tw-border-gray-100 tw-flex tw-mb-6 tw-pb-6 tw-items-center"},[_c('div',{staticClass:"plugin-icon"},[(_vm.plugin.iconUrl)?_c('img',{attrs:{"src":_vm.plugin.iconUrl,"width":"100"}}):_c('img',{attrs:{"src":_vm.defaultPluginSvg,"width":"100"}})]),_vm._v(" "),_c('div',{staticClass:"description tw-flex-1"},[_c('h1',{staticClass:"ttw-ext-lg tw-font-bold tw-mb-2"},[_vm._v(_vm._s(_vm.plugin.name))]),_vm._v(" "),_c('p',{staticClass:"tw-mb-2 tw-text-gray-600"},[_vm._v(_vm._s(_vm.plugin.shortDescription))]),_vm._v(" "),_c('p',{staticClass:"tw-mb-2"},[_c('router-link',{attrs:{"to":'/developer/' + _vm.plugin.developerId,"title":_vm.plugin.developerName}},[_vm._v(_vm._s(_vm.plugin.developerName))])],1)]),_vm._v(" "),(_vm.actionsLoading)?_c('div',[_c('spinner')],1):_vm._e()]),_vm._v(" "),_c('div',{staticClass:"plugin-details-body"},[(!_vm.loading)?[(_vm.plugin.abandoned)?[_c('div',{staticClass:"error tw-mb-6 tw-px-4 tw-py-3 tw-rounded tw-border tw-border-solid tw-border-red-500 tw-flex tw-flex-nowrap tw-text-base"},[_c('svg',{staticClass:"tw-w-8 tw-h-8"},[_c('use',{attrs:{"xlink:href":_vm.alertIcon + '#alert'}})]),_vm._v(" "),_c('div',{staticClass:"tw-flex-1 tw-mb-0"},[_c('strong',[_vm._v(_vm._s(_vm._f("t")("This plugin is no longer maintained.",'app'))+" ")]),_vm._v(" "),(_vm.recommendedLabel)?_c('span',{domProps:{"innerHTML":_vm._s(_vm.recommendedLabel)}}):_vm._e()])])]:_vm._e(),_vm._v(" "),(_vm.plugin.screenshotUrls && _vm.plugin.screenshotUrls.length)?[_c('plugin-screenshots',{attrs:{"images":_vm.plugin.screenshotUrls}}),_vm._v(" "),_c('hr')]:_vm._e(),_vm._v(" "),_c('div',{staticClass:"lg:tw-flex"},[_c('div',{staticClass:"lg:tw-flex-1 lg:tw-pr-8 lg:tw-mr-4"},[(_vm.longDescription)?_c('div',{staticClass:"readable",domProps:{"innerHTML":_vm._s(_vm.longDescription)}}):(_vm.plugin.shortDescription)?_c('div',{staticClass:"readable",domProps:{"innerHTML":_vm._s(_vm.plugin.shortDescription)}}):_c('p',[_vm._v("No description.")])]),_vm._v(" "),_c('div',{staticClass:"lg:tw-pl-8 lg:tw-ml-4"},[_c('ul',[(_vm.plugin.documentationUrl)?_c('li',{staticClass:"tw-py-1"},[_c('a',{attrs:{"href":_vm.plugin.documentationUrl,"rel":"noopener","target":"_blank"}},[_c('icon',{attrs:{"icon":"book"}}),_vm._v(" "+_vm._s(_vm._f("t")("Documentation",'app'))+"\n                                    ")],1)]):_vm._e(),_vm._v(" "),_c('li',[_c('a',{attrs:{"href":_vm.plugin.repository,"rel":"noopener","target":"_blank"}},[_c('icon',{attrs:{"icon":"link"}}),_vm._v(" Repository")],1)])])])]),_vm._v(" "),_c('hr'),_vm._v(" "),_c('div',{staticClass:"tw-py-8"},[_c('plugin-editions',{attrs:{"plugin":_vm.plugin}}),_vm._v(" "),(_vm.licenseMismatched)?_c('div',{staticClass:"tw-mx-auto tw-max-w-sm tw-px-8"},[_c('div',{staticClass:"tw-flex tw-items-center"},[_c('svg',{staticClass:"tw-text-blue-500 tw-fill-current tw-w-8 tw-h-8 tw-mr-4 tw-flex tw-items-center",attrs:{"version":"1.1","xmlns":"http://www.w3.org/2000/svg","x":"0px","y":"0px","viewBox":"0 0 256 448","xml:space":"preserve"}},[_c('path',{attrs:{"fill":"currentColor","d":"M184,144c0,4.2-3.8,8-8,8s-8-3.8-8-8c0-17.2-26.8-24-40-24c-4.2,0-8-3.8-8-8s3.8-8,8-8C151.2,104,184,116.2,184,144z\nM224,144c0-50-50.8-80-96-80s-96,30-96,80c0,16,6.5,32.8,17,45c4.8,5.5,10.2,10.8,15.2,16.5C82,226.8,97,251.8,99.5,280h57\nc2.5-28.2,17.5-53.2,35.2-74.5c5-5.8,10.5-11,15.2-16.5C217.5,176.8,224,160,224,144z M256,144c0,25.8-8.5,48-25.8,67\ns-40,45.8-42,72.5c7.2,4.2,11.8,12.2,11.8,20.5c0,6-2.2,11.8-6.2,16c4,4.2,6.2,10,6.2,16c0,8.2-4.2,15.8-11.2,20.2\nc2,3.5,3.2,7.8,3.2,11.8c0,16.2-12.8,24-27.2,24c-6.5,14.5-21,24-36.8,24s-30.2-9.5-36.8-24c-14.5,0-27.2-7.8-27.2-24\nc0-4,1.2-8.2,3.2-11.8c-7-4.5-11.2-12-11.2-20.2c0-6,2.2-11.8,6.2-16c-4-4.2-6.2-10-6.2-16c0-8.2,4.5-16.2,11.8-20.5\nc-2-26.8-24.8-53.5-42-72.5S0,169.8,0,144C0,76,64.8,32,128,32S256,76,256,144z"}})]),_vm._v(" "),_c('div',[_c('div',{domProps:{"innerHTML":_vm._s(_vm.licenseMismatchedMessage)}})])])]):_vm._e()],1),_vm._v(" "),_c('hr'),_vm._v(" "),_c('div',{staticClass:"tw-max-w-xs tw-mx-auto tw-py-8"},[_c('h2',{staticClass:"tw-mt-0"},[_vm._v(_vm._s(_vm._f("t")("Package Name",'app')))]),_vm._v(" "),_c('p',[_vm._v(_vm._s(_vm._f("t")("Copy the package’s name for this plugin.",'app')))]),_vm._v(" "),_c('copy-package',{attrs:{"plugin":_vm.plugin}})],1),_vm._v(" "),_c('hr'),_vm._v(" "),_c('h2',{staticClass:"tw-mb-4"},[_vm._v(_vm._s(_vm._f("t")("Information",'app')))]),_vm._v(" "),_c('div',{staticClass:"plugin-infos"},[_c('ul',{staticClass:"plugin-meta"},[_c('li',[_c('span',[_vm._v(_vm._s(_vm._f("t")("Version",'app')))]),_vm._v(" "),_c('strong',[_vm._v(_vm._s(_vm.plugin.version))])]),_vm._v(" "),_c('li',[_c('span',[_vm._v(_vm._s(_vm._f("t")("Last update",'app')))]),_vm._v(" "),_c('strong',[_vm._v(_vm._s(_vm.lastUpdate))])]),_vm._v(" "),(_vm.plugin.activeInstalls > 0)?_c('li',[_c('span',[_vm._v(_vm._s(_vm._f("t")("Active installs",'app')))]),_vm._v(" "),_c('strong',[_vm._v(_vm._s(_vm._f("formatNumber")(_vm.plugin.activeInstalls)))])]):_vm._e(),_vm._v(" "),_c('li',[_c('span',[_vm._v(_vm._s(_vm._f("t")("Compatibility",'app')))]),_vm._v(" "),_c('strong',[_vm._v(_vm._s(_vm.plugin.compatibility))])]),_vm._v(" "),(_vm.pluginCategories && _vm.pluginCategories.length > 0)?_c('li',[_c('span',[_vm._v(_vm._s(_vm._f("t")("Categories",'app')))]),_vm._v(" "),_c('div',_vm._l((_vm.pluginCategories),function(category,key){return _c('div',{key:'plugin-category-' + key},[_c('strong',[_c('router-link',{attrs:{"to":'/categories/' + category.id,"title":category.title}},[_vm._v(_vm._s(category.title))])],1)])}),0)]):_vm._e(),_vm._v(" "),_c('li',[_c('span',[_vm._v(_vm._s(_vm._f("t")("License",'app')))]),_vm._v(" "),_c('strong',[_vm._v(_vm._s(_vm.licenseLabel))])])])]),_vm._v(" "),_c('p',[_c('a',{attrs:{"href":'mailto:issues@craftcms.com?subject=' + encodeURIComponent('Issue with ' + _vm.plugin.name) + '&body=' + encodeURIComponent('I would like to report the following issue with '+_vm.plugin.name+' (https://plugins.craftcms.com/' + _vm.plugin.handle + '):\n\n')}},[_c('icon',{staticClass:"mr-2",attrs:{"icon":"exclamation-circle"}}),_vm._v(_vm._s(_vm._f("t")("Report an issue",'app')))],1)]),_vm._v(" "),_c('hr'),_vm._v(" "),_c('plugin-changelog',{attrs:{"pluginId":_vm.plugin.id}})]:[_c('spinner')]],2)]:[_c('spinner')]],2)}
var _handlevue_type_template_id_66045728_staticRenderFns = []


// CONCATENATED MODULE: ./js/pages/_handle/index.vue?vue&type=template&id=66045728&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/CopyPackage.vue?vue&type=template&id=2b0b9100&
var CopyPackagevue_type_template_id_2b0b9100_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"tw-flex"},[_c('input',{ref:"input",staticClass:"tw-font-mono tw-border tw-border-r-0 tw-border-solid tw-border-gray-300 tw-w-full tw-py-2 tw-px-3 tw-rounded-l",attrs:{"readonly":"readonly"},domProps:{"value":_vm.plugin.packageName},on:{"focus":_vm.select}}),_vm._v(" "),_c('button',{staticClass:"tw-border tw-border-solid tw-border-gray-300 tw-py-2 tw-px-3 tw-rounded-r tw-bg-gray-50 hover:tw-bg-gray-100 active:tw-bg-gray-200 hover:tw-cursor-pointer",on:{"click":_vm.copy}},[_c('icon',{staticClass:"tw-text-gray-500",attrs:{"icon":"copy"}})],1)])}
var CopyPackagevue_type_template_id_2b0b9100_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/CopyPackage.vue?vue&type=template&id=2b0b9100&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/CopyPackage.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
/* harmony default export */ var CopyPackagevue_type_script_lang_js_ = ({
  props: ['plugin'],
  methods: {
    select: function select() {
      this.$refs.input.$refs.input.select();
    },
    copy: function copy() {
      this.select();
      window.document.execCommand('copy');
    }
  }
});
// CONCATENATED MODULE: ./js/components/CopyPackage.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_CopyPackagevue_type_script_lang_js_ = (CopyPackagevue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/CopyPackage.vue





/* normalize component */

var CopyPackage_component = Object(componentNormalizer["a" /* default */])(
  components_CopyPackagevue_type_script_lang_js_,
  CopyPackagevue_type_template_id_2b0b9100_render,
  CopyPackagevue_type_template_id_2b0b9100_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var CopyPackage = (CopyPackage_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginChangelog.vue?vue&type=template&id=60cae39e&
var PluginChangelogvue_type_template_id_60cae39e_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"plugin-changelog",class:{collapsed: !_vm.showMore}},[_c('h2',[_vm._v(_vm._s(_vm._f("t")("Changelog",'app')))]),_vm._v(" "),(_vm.loading)?[_c('spinner',{staticClass:"mt-4"})]:[_c('div',{staticClass:"releases"},[_vm._l((_vm.pluginChangelog),function(release,key){return [_c('changelog-release',{key:key,attrs:{"release":release}})]})],2),_vm._v(" "),_c('div',{staticClass:"more"},[(_vm.showMore === false)?_c('a',{staticClass:"c-btn",on:{"click":function($event){$event.preventDefault();_vm.showMore = true}}},[_vm._v(_vm._s(_vm._f("t")("More",'app')))]):_vm._e(),_vm._v(" "),(_vm.showMore === true)?_c('a',{staticClass:"c-btn",on:{"click":function($event){$event.preventDefault();_vm.showMore = false}}},[_vm._v(_vm._s(_vm._f("t")("Less",'app')))]):_vm._e()])]],2)}
var PluginChangelogvue_type_template_id_60cae39e_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginChangelog.vue?vue&type=template&id=60cae39e&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ChangelogRelease.vue?vue&type=template&id=665c1ea9&
var ChangelogReleasevue_type_template_id_665c1ea9_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.release)?_c('div',{staticClass:"changelog-release"},[_c('div',{staticClass:"version"},[_c('a',{staticClass:"anchor",attrs:{"href":'#' + _vm.release.version}},[_c('icon',{attrs:{"icon":"link"}})],1),_vm._v(" "),_c('h2',{attrs:{"id":_vm.release.version}},[_vm._v(_vm._s(_vm._f("t")("Version {version}",'app', {version: _vm.release.version})))]),_vm._v(" "),_c('div',{staticClass:"date"},[_vm._v(_vm._s(_vm.date))]),_vm._v(" "),(_vm.release.critical)?_c('div',{staticClass:"critical"},[_vm._v(_vm._s(_vm._f("t")('Critical','app')))]):_vm._e()]),_vm._v(" "),_c('div',{staticClass:"details readable",domProps:{"innerHTML":_vm._s(_vm.release.notes)}})]):_vm._e()}
var ChangelogReleasevue_type_template_id_665c1ea9_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ChangelogRelease.vue?vue&type=template&id=665c1ea9&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ChangelogRelease.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//

/* global Craft */
/* harmony default export */ var ChangelogReleasevue_type_script_lang_js_ = ({
  props: ['release'],
  computed: {
    date: function date() {
      return Craft.formatDate(this.release.date);
    }
  }
});
// CONCATENATED MODULE: ./js/components/ChangelogRelease.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_ChangelogReleasevue_type_script_lang_js_ = (ChangelogReleasevue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/ChangelogRelease.vue?vue&type=style&index=0&lang=scss&
var ChangelogReleasevue_type_style_index_0_lang_scss_ = __webpack_require__(187);

// CONCATENATED MODULE: ./js/components/ChangelogRelease.vue






/* normalize component */

var ChangelogRelease_component = Object(componentNormalizer["a" /* default */])(
  components_ChangelogReleasevue_type_script_lang_js_,
  ChangelogReleasevue_type_template_id_665c1ea9_render,
  ChangelogReleasevue_type_template_id_665c1ea9_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ChangelogRelease = (ChangelogRelease_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginChangelog.vue?vue&type=script&lang=js&
function PluginChangelogvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function PluginChangelogvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { PluginChangelogvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { PluginChangelogvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { PluginChangelogvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function PluginChangelogvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ var PluginChangelogvue_type_script_lang_js_ = ({
  props: ['pluginId'],
  data: function data() {
    return {
      showMore: false,
      loading: false
    };
  },
  components: {
    ChangelogRelease: ChangelogRelease
  },
  computed: PluginChangelogvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    pluginChangelog: function pluginChangelog(state) {
      return state.pluginStore.pluginChangelog;
    }
  })),
  mounted: function mounted() {
    var _this = this;

    this.loading = true;
    this.$store.dispatch('pluginStore/getPluginChangelog', this.pluginId).then(function () {
      _this.loading = false;
    });
  },
  destroyed: function destroyed() {
    this.$store.commit('pluginStore/updatePluginChangelog', null);
  }
});
// CONCATENATED MODULE: ./js/components/PluginChangelog.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginChangelogvue_type_script_lang_js_ = (PluginChangelogvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/PluginChangelog.vue?vue&type=style&index=0&lang=scss&
var PluginChangelogvue_type_style_index_0_lang_scss_ = __webpack_require__(189);

// CONCATENATED MODULE: ./js/components/PluginChangelog.vue






/* normalize component */

var PluginChangelog_component = Object(componentNormalizer["a" /* default */])(
  components_PluginChangelogvue_type_script_lang_js_,
  PluginChangelogvue_type_template_id_60cae39e_render,
  PluginChangelogvue_type_template_id_60cae39e_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginChangelog = (PluginChangelog_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginEditions.vue?vue&type=template&id=11a9e75b&
var PluginEditionsvue_type_template_id_11a9e75b_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"plugin-editions tw-mb-4"},_vm._l((_vm.plugin.editions),function(edition,key){return _c('plugin-edition',{key:key,attrs:{"plugin":_vm.plugin,"edition":edition}})}),1)}
var PluginEditionsvue_type_template_id_11a9e75b_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginEditions.vue?vue&type=template&id=11a9e75b&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginEdition.vue?vue&type=template&id=7cb7a1cc&
var PluginEditionvue_type_template_id_7cb7a1cc_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"plugin-editions-edition"},[_c('div',{staticClass:"description"},[(_vm.plugin.editions.length > 1)?_c('edition-badge',{attrs:{"name":_vm.edition.name,"block":"","big":""}}):_vm._e(),_vm._v(" "),_c('div',{staticClass:"price"},[(!_vm.isPluginEditionFree(_vm.edition))?[(_vm.licensedEdition && _vm.licensedEdition.handle !== _vm.edition.handle && _vm.licensedEdition.price > 0 && _vm.licenseValidOrAstray)?[_c('del',[_vm._v(_vm._s(_vm._f("currency")(_vm.edition.price)))]),_vm._v("\n                    "+_vm._s(_vm._f("currency")((_vm.edition.price - _vm.licensedEdition.price)))+"\n                ")]:[_vm._v("\n                    "+_vm._s(_vm._f("currency")(_vm.edition.price))+"\n                ")]]:[_vm._v("\n                "+_vm._s(_vm._f("t")("Free",'app'))+"\n            ")]],2),_vm._v(" "),(!_vm.isPluginEditionFree(_vm.edition))?_c('p',{staticClass:"tw--mt-8 tw-py-6 tw-text-gray-600"},[_vm._v("\n            "+_vm._s(_vm._f("t")("Price includes 1 year of updates.",'app'))),_c('br'),_vm._v("\n            "+_vm._s(_vm._f("t")("{renewalPrice}/year per site for updates after that.",'app', {renewalPrice: _vm.$options.filters.currency(_vm.edition.renewalPrice)}))+"\n        ")]):_vm._e(),_vm._v(" "),(_vm.plugin.editions.length > 1 && _vm.edition.features && _vm.edition.features.length > 0)?_c('ul',_vm._l((_vm.edition.features),function(feature,key){return _c('li',{key:key},[_c('icon',{attrs:{"icon":"check"}}),_vm._v("\n                "+_vm._s(feature.name)+"\n\n                "),(feature.description)?_c('info-hud',[_vm._v("\n                    "+_vm._s(feature.description)+"\n                ")]):_vm._e()],1)}),0):_vm._e()],1),_vm._v(" "),_c('plugin-actions',{attrs:{"plugin":_vm.plugin,"edition":_vm.edition}})],1)}
var PluginEditionvue_type_template_id_7cb7a1cc_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginEdition.vue?vue&type=template&id=7cb7a1cc&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginActions.vue?vue&type=template&id=61a38f3e&
var PluginActionsvue_type_template_id_61a38f3e_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.plugin)?_c('div',{staticClass:"plugin-actions"},[(!_vm.isPluginEditionFree)?[(_vm.isInCart(_vm.plugin, _vm.edition))?[(_vm.allowUpdates)?_c('btn',{attrs:{"kind":"primary","icon":"check","block":"","large":"","outline":""},on:{"click":function($event){return _vm.$root.openModal('cart')}}},[_vm._v(_vm._s(_vm._f("t")("Already in your cart",'app')))]):_vm._e()]:[(_vm.allowUpdates && _vm.isEditionMoreExpensiveThanLicensed)?_c('btn',{attrs:{"kind":"primary","loading":_vm.addToCartloading,"disabled":_vm.addToCartloading || !_vm.plugin.latestCompatibleVersion || _vm.licenseMismatched || _vm.plugin.abandoned,"block":"","large":""},on:{"click":function($event){return _vm.addEditionToCart(_vm.edition.handle)}}},[_vm._v(_vm._s(_vm._f("t")("Add to cart",'app')))]):(_vm.licensedEdition === _vm.edition.handle)?_c('btn',{attrs:{"kind":"primary","block":"","large":"","disabled":""}},[_vm._v(_vm._s(_vm._f("t")("Licensed",'app')))]):_vm._e()]]:_vm._e(),_vm._v(" "),(!_vm.isPluginInstalled || _vm.currentEdition !== _vm.edition.handle)?[(_vm.allowUpdates || _vm.isPluginInstalled)?_c('form',{attrs:{"method":"post"},on:{"submit":_vm.onSwitchOrInstallSubmit}},[_c('input',{attrs:{"type":"hidden","name":_vm.csrfTokenName},domProps:{"value":_vm.csrfTokenValue}}),_vm._v(" "),(_vm.isPluginInstalled)?[_c('input',{attrs:{"type":"hidden","name":"action","value":"plugins/switch-edition"}}),_vm._v(" "),_c('input',{attrs:{"type":"hidden","name":"pluginHandle"},domProps:{"value":_vm.plugin.handle}}),_vm._v(" "),_c('input',{attrs:{"type":"hidden","name":"edition"},domProps:{"value":_vm.edition.handle}})]:[_c('input',{attrs:{"type":"hidden","name":"action","value":"pluginstore/install"}}),_vm._v(" "),_c('input',{attrs:{"type":"hidden","name":"packageName"},domProps:{"value":_vm.plugin.packageName}}),_vm._v(" "),_c('input',{attrs:{"type":"hidden","name":"handle"},domProps:{"value":_vm.plugin.handle}}),_vm._v(" "),_c('input',{attrs:{"type":"hidden","name":"edition"},domProps:{"value":_vm.edition.handle}}),_vm._v(" "),_c('input',{attrs:{"type":"hidden","name":"version"},domProps:{"value":_vm.plugin.latestCompatibleVersion}})],_vm._v(" "),(_vm.isPluginEditionFree)?[_c('btn',{attrs:{"kind":"primary","type":"submit","loading":_vm.loading,"disabled":!_vm.plugin.latestCompatibleVersion,"block":"","large":""}},[_vm._v(_vm._s(_vm._f("t")("Install",'app')))])]:[((_vm.isEditionMoreExpensiveThanLicensed && _vm.currentEdition === _vm.edition.handle) || (_vm.licensedEdition === _vm.edition.handle && !_vm.currentEdition))?[_c('btn',{attrs:{"type":"submit","loading":_vm.loading,"disabled":!_vm.plugin.latestCompatibleVersion,"block":"","large":""}},[_vm._v(_vm._s(_vm._f("t")("Install",'app')))])]:(_vm.isEditionMoreExpensiveThanLicensed && _vm.currentEdition !== _vm.edition.handle)?[_c('btn',{attrs:{"type":"submit","disabled":(!((_vm.pluginLicenseInfo && _vm.pluginLicenseInfo.isInstalled && _vm.pluginLicenseInfo.isEnabled) || !_vm.pluginLicenseInfo)) || !_vm.plugin.latestCompatibleVersion,"loading":_vm.loading,"block":"","large":""}},[_vm._v(_vm._s(_vm._f("t")("Try",'app')))])]:(_vm.currentEdition && _vm.licensedEdition === _vm.edition.handle && _vm.currentEdition !== _vm.edition.handle)?[_c('btn',{attrs:{"type":"submit","loading":_vm.loading,"block":"","large":""}},[_vm._v(_vm._s(_vm._f("t")("Reactivate",'app')))])]:_vm._e()]],2):_vm._e()]:[(_vm.currentEdition !== _vm.licensedEdition && !_vm.isPluginEditionFree)?[_c('btn',{attrs:{"icon":"check","disabled":true,"large":"","block":""}},[_vm._v(" "+_vm._s(_vm._f("t")("Installed as a trial",'app')))])]:[_c('btn',{attrs:{"icon":"check","disabled":true,"block":"","large":""}},[_vm._v(" "+_vm._s(_vm._f("t")("Installed",'app')))])]],_vm._v(" "),(_vm.plugin.latestCompatibleVersion && _vm.plugin.latestCompatibleVersion != _vm.plugin.version)?[_c('div',{staticClass:"tw-text-gray-500 tw-mt-4 tw-px-8"},[_c('p',[_vm._v(_vm._s(_vm._f("t")("Only up to {version} is compatible with your version of Craft.",'app', {version: _vm.plugin.latestCompatibleVersion})))])])]:(!_vm.plugin.latestCompatibleVersion)?[_c('div',{staticClass:"tw-text-gray-500 tw-mt-4 tw-px-8"},[_c('p',[_vm._v(_vm._s(_vm._f("t")("This plugin isn’t compatible with your version of Craft.",'app')))])])]:(!_vm.isPluginEditionFree && _vm.plugin.abandoned)?[_c('div',{staticClass:"tw-text-gray-500 tw-mt-4 tw-px-8"},[_c('p',[_vm._v(_vm._s(_vm._f("t")("This plugin is no longer maintained.",'app')))])])]:_vm._e()],2):_vm._e()}
var PluginActionsvue_type_template_id_61a38f3e_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginActions.vue?vue&type=template&id=61a38f3e&

// CONCATENATED MODULE: ./js/mixins/licenses.js
/* harmony default export */ var licenses = ({
  computed: {
    licenseMismatched: function licenseMismatched() {
      return this.getLicenseMismatched(this.pluginLicenseInfo);
    },
    licenseValidOrAstray: function licenseValidOrAstray() {
      return this.getLicenseValidOrAstray(this.pluginLicenseInfo);
    }
  },
  methods: {
    getLicenseMismatched: function getLicenseMismatched(pluginLicenseInfo) {
      return pluginLicenseInfo && pluginLicenseInfo.licenseKeyStatus === 'mismatched';
    },
    getLicenseValidOrAstray: function getLicenseValidOrAstray(pluginLicenseInfo) {
      return pluginLicenseInfo.licenseKeyStatus === 'valid' || pluginLicenseInfo.licenseKeyStatus === 'astray';
    }
  }
});
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginActions.vue?vue&type=script&lang=js&
function PluginActionsvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function PluginActionsvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { PluginActionsvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { PluginActionsvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { PluginActionsvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function PluginActionsvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* global Craft */


/* harmony default export */ var PluginActionsvue_type_script_lang_js_ = ({
  mixins: [licenses],
  props: ['plugin', 'edition'],
  data: function data() {
    return {
      loading: false,
      addToCartloading: false
    };
  },
  computed: PluginActionsvue_type_script_lang_js_objectSpread(PluginActionsvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapGetters"])({
    getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
    isInCart: 'cart/isInCart'
  })), {}, {
    pluginLicenseInfo: function pluginLicenseInfo() {
      return this.getPluginLicenseInfo(this.plugin.handle);
    },
    isPluginEditionFree: function isPluginEditionFree() {
      return this.$store.getters['pluginStore/isPluginEditionFree'](this.edition);
    },
    isPluginInstalled: function isPluginInstalled() {
      return this.$store.getters['craft/isPluginInstalled'](this.plugin.handle);
    },
    isEditionMoreExpensiveThanLicensed: function isEditionMoreExpensiveThanLicensed() {
      // A plugin edition is buyable if it’s more expensive than the licensed one
      if (!this.edition) {
        return false;
      }

      if (this.pluginLicenseInfo) {
        var licensedEditionHandle = this.licensedEdition;
        var licensedEdition = this.plugin.editions.find(function (edition) {
          return edition.handle === licensedEditionHandle;
        });

        if (licensedEdition && this.edition.price && parseFloat(this.edition.price) <= parseFloat(licensedEdition.price)) {
          return false;
        }
      }

      return true;
    },
    licensedEdition: function licensedEdition() {
      if (!this.pluginLicenseInfo) {
        return null;
      }

      return this.pluginLicenseInfo.licensedEdition;
    },
    currentEdition: function currentEdition() {
      if (!this.pluginLicenseInfo) {
        return null;
      }

      return this.pluginLicenseInfo.edition;
    },
    allowUpdates: function allowUpdates() {
      return Craft.allowUpdates && Craft.allowAdminChanges;
    },
    csrfTokenName: function csrfTokenName() {
      return Craft.csrfTokenName;
    },
    csrfTokenValue: function csrfTokenValue() {
      return Craft.csrfTokenValue;
    }
  }),
  methods: {
    addEditionToCart: function addEditionToCart(editionHandle) {
      var _this = this;

      this.addToCartloading = true;
      var item = {
        type: 'plugin-edition',
        plugin: this.plugin.handle,
        edition: editionHandle
      };
      this.$store.dispatch('cart/addToCart', [item]).then(function () {
        _this.addToCartloading = false;

        _this.$root.openModal('cart');
      })["catch"](function () {
        _this.addToCartloading = false;
      });
    },
    onSwitchOrInstallSubmit: function onSwitchOrInstallSubmit($ev) {
      var _this2 = this;

      this.loading = true;

      if (this.isPluginInstalled) {
        // Switch (prevent form submit)
        $ev.preventDefault();
        this.$store.dispatch('craft/switchPluginEdition', {
          pluginHandle: this.plugin.handle,
          edition: this.edition.handle
        }).then(function () {
          _this2.loading = false;

          _this2.$root.displayNotice("Plugin edition changed.");
        });
        return false;
      } // Install (don’t prevent form submit)

    }
  }
});
// CONCATENATED MODULE: ./js/components/PluginActions.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginActionsvue_type_script_lang_js_ = (PluginActionsvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/PluginActions.vue?vue&type=style&index=0&lang=scss&
var PluginActionsvue_type_style_index_0_lang_scss_ = __webpack_require__(191);

// CONCATENATED MODULE: ./js/components/PluginActions.vue






/* normalize component */

var PluginActions_component = Object(componentNormalizer["a" /* default */])(
  components_PluginActionsvue_type_script_lang_js_,
  PluginActionsvue_type_template_id_61a38f3e_render,
  PluginActionsvue_type_template_id_61a38f3e_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginActions = (PluginActions_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginEdition.vue?vue&type=script&lang=js&
function PluginEditionvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function PluginEditionvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { PluginEditionvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { PluginEditionvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { PluginEditionvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function PluginEditionvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//





/* harmony default export */ var PluginEditionvue_type_script_lang_js_ = ({
  mixins: [licenses],
  props: ['plugin', 'edition'],
  components: {
    PluginActions: PluginActions,
    InfoHud: InfoHud,
    EditionBadge: EditionBadge
  },
  computed: PluginEditionvue_type_script_lang_js_objectSpread(PluginEditionvue_type_script_lang_js_objectSpread(PluginEditionvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    cart: function cart(state) {
      return state.cart.cart;
    }
  })), Object(external_Vuex_["mapGetters"])({
    isPluginEditionFree: 'pluginStore/isPluginEditionFree',
    getPluginEdition: 'pluginStore/getPluginEdition',
    getPluginLicenseInfo: 'craft/getPluginLicenseInfo'
  })), {}, {
    pluginLicenseInfo: function pluginLicenseInfo() {
      if (!this.plugin) {
        return null;
      }

      return this.getPluginLicenseInfo(this.plugin.handle);
    },
    licensedEdition: function licensedEdition() {
      if (!this.pluginLicenseInfo) {
        return null;
      }

      return this.getPluginEdition(this.plugin, this.pluginLicenseInfo.licensedEdition);
    }
  })
});
// CONCATENATED MODULE: ./js/components/PluginEdition.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginEditionvue_type_script_lang_js_ = (PluginEditionvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/PluginEdition.vue?vue&type=style&index=0&lang=scss&
var PluginEditionvue_type_style_index_0_lang_scss_ = __webpack_require__(193);

// CONCATENATED MODULE: ./js/components/PluginEdition.vue






/* normalize component */

var PluginEdition_component = Object(componentNormalizer["a" /* default */])(
  components_PluginEditionvue_type_script_lang_js_,
  PluginEditionvue_type_template_id_7cb7a1cc_render,
  PluginEditionvue_type_template_id_7cb7a1cc_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginEdition = (PluginEdition_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginEditions.vue?vue&type=script&lang=js&
//
//
//
//
//
//

/* harmony default export */ var PluginEditionsvue_type_script_lang_js_ = ({
  props: ['plugin'],
  components: {
    PluginEdition: PluginEdition
  }
});
// CONCATENATED MODULE: ./js/components/PluginEditions.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginEditionsvue_type_script_lang_js_ = (PluginEditionsvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/PluginEditions.vue?vue&type=style&index=0&lang=scss&
var PluginEditionsvue_type_style_index_0_lang_scss_ = __webpack_require__(195);

// CONCATENATED MODULE: ./js/components/PluginEditions.vue






/* normalize component */

var PluginEditions_component = Object(componentNormalizer["a" /* default */])(
  components_PluginEditionsvue_type_script_lang_js_,
  PluginEditionsvue_type_template_id_11a9e75b_render,
  PluginEditionsvue_type_template_id_11a9e75b_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginEditions = (PluginEditions_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginScreenshots.vue?vue&type=template&id=53684152&
var PluginScreenshotsvue_type_template_id_53684152_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"screenshots"},_vm._l((_vm.images),function(image,key){return _c('a',{key:key,staticClass:"screenshot",on:{"click":function($event){return _vm.zoomImage(key)}}},[_c('img',{attrs:{"src":image}})])}),0)}
var PluginScreenshotsvue_type_template_id_53684152_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginScreenshots.vue?vue&type=template&id=53684152&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginScreenshots.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
/* harmony default export */ var PluginScreenshotsvue_type_script_lang_js_ = ({
  props: ['images'],
  methods: {
    zoomImage: function zoomImage(key) {
      this.$store.commit('app/updateScreenshotModalImages', this.images);
      this.$store.commit('app/updateShowingScreenshotModal', true);
      this.$store.commit('app/updateScreenshotModalImageKey', key);
    }
  }
});
// CONCATENATED MODULE: ./js/components/PluginScreenshots.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginScreenshotsvue_type_script_lang_js_ = (PluginScreenshotsvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/PluginScreenshots.vue?vue&type=style&index=0&lang=scss&
var PluginScreenshotsvue_type_style_index_0_lang_scss_ = __webpack_require__(197);

// CONCATENATED MODULE: ./js/components/PluginScreenshots.vue






/* normalize component */

var PluginScreenshots_component = Object(componentNormalizer["a" /* default */])(
  components_PluginScreenshotsvue_type_script_lang_js_,
  PluginScreenshotsvue_type_template_id_53684152_render,
  PluginScreenshotsvue_type_template_id_53684152_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginScreenshots = (PluginScreenshots_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/pages/_handle/index.vue?vue&type=script&lang=js&
function pages_handlevue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function pages_handlevue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { pages_handlevue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { pages_handlevue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { pages_handlevue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function pages_handlevue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* global Craft */






/* harmony default export */ var pages_handlevue_type_script_lang_js_ = ({
  mixins: [licenses],
  components: {
    CopyPackage: CopyPackage,
    PluginChangelog: PluginChangelog,
    PluginEditions: PluginEditions,
    PluginScreenshots: PluginScreenshots
  },
  data: function data() {
    return {
      actionsLoading: false,
      loading: false
    };
  },
  computed: pages_handlevue_type_script_lang_js_objectSpread(pages_handlevue_type_script_lang_js_objectSpread(pages_handlevue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    categories: function categories(state) {
      return state.pluginStore.categories;
    },
    defaultPluginSvg: function defaultPluginSvg(state) {
      return state.craft.defaultPluginSvg;
    },
    plugin: function plugin(state) {
      return state.pluginStore.plugin;
    },
    showingScreenshotModal: function showingScreenshotModal(state) {
      return state.app.showingScreenshotModal;
    },
    alertIcon: function alertIcon(state) {
      return state.craft.alertIcon;
    }
  })), Object(external_Vuex_["mapGetters"])({
    getPluginEdition: 'pluginStore/getPluginEdition',
    getPluginLicenseInfo: 'craft/getPluginLicenseInfo'
  })), {}, {
    longDescription: function longDescription() {
      if (this.plugin.longDescription && this.plugin.longDescription.length > 0) {
        return this.plugin.longDescription;
      }

      return null;
    },
    pluginCategories: function pluginCategories() {
      var _this = this;

      return this.categories.filter(function (c) {
        return _this.plugin.categoryIds.find(function (pc) {
          return pc == c.id;
        });
      });
    },
    licenseLabel: function licenseLabel() {
      switch (this.plugin.license) {
        case 'craft':
          return 'Craft';

        case 'mit':
          return 'MIT';
      }

      return null;
    },
    lastUpdate: function lastUpdate() {
      var date = new Date(this.plugin.lastUpdate.replace(/\s/, 'T'));
      return Craft.formatDate(date);
    },
    pluginLicenseInfo: function pluginLicenseInfo() {
      if (!this.plugin) {
        return null;
      }

      return this.getPluginLicenseInfo(this.plugin.handle);
    },
    licenseMismatchedMessage: function licenseMismatchedMessage() {
      return this.$options.filters.t('This license is tied to another Craft install. Visit {accountLink} to detach it, or buy a new license.', 'app', {
        accountLink: '<a href="https://id.craftcms.com" rel="noopener" target="_blank">id.craftcms.com</a>'
      });
    },
    recommendedLabel: function recommendedLabel() {
      if (!this.plugin.replacementHandle) {
        return null;
      }

      return this.$options.filters.t('The developer recommends using <a href="{url}">{name}</a> instead.', 'app', {
        name: this.plugin.replacementName,
        url: Craft.getCpUrl('plugin-store/' + this.plugin.replacementHandle)
      });
    }
  }),
  methods: pages_handlevue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapActions"])({
    addToCart: 'cart/addToCart'
  })),
  mounted: function mounted() {
    var _this2 = this;

    var pluginHandle = this.$route.params.handle;
    this.loading = true;
    this.$store.dispatch('pluginStore/getPluginDetailsByHandle', pluginHandle).then(function () {
      _this2.loading = false;
    })["catch"](function () {
      _this2.loading = false;
    });
  },
  beforeDestroy: function beforeDestroy() {
    this.$store.dispatch('pluginStore/cancelRequests');
  },
  beforeRouteLeave: function beforeRouteLeave(to, from, next) {
    if (this.showingScreenshotModal) {
      this.$store.commit('app/updateShowingScreenshotModal', false);
    } else {
      next();
    }
  }
});
// CONCATENATED MODULE: ./js/pages/_handle/index.vue?vue&type=script&lang=js&
 /* harmony default export */ var js_pages_handlevue_type_script_lang_js_ = (pages_handlevue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/pages/_handle/index.vue?vue&type=style&index=0&lang=scss&
var _handlevue_type_style_index_0_lang_scss_ = __webpack_require__(199);

// CONCATENATED MODULE: ./js/pages/_handle/index.vue






/* normalize component */

var pages_handle_component = Object(componentNormalizer["a" /* default */])(
  js_pages_handlevue_type_script_lang_js_,
  _handlevue_type_template_id_66045728_render,
  _handlevue_type_template_id_66045728_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var pages_handle = (pages_handle_component.exports);
// CONCATENATED MODULE: ./js/router/index.js













external_Vue_default.a.use(external_VueRouter_default.a);
/* harmony default export */ var router = (new external_VueRouter_default.a({
  base: window.pluginStoreAppBaseUrl,
  mode: 'history',
  scrollBehavior: function scrollBehavior() {
    return {
      x: 0,
      y: 0
    };
  },
  routes: [{
    path: '/',
    name: 'Index',
    component: pages
  }, {
    path: '/categories/:id',
    name: 'CategoriesId',
    component: _id
  }, {
    path: '/upgrade-craft',
    name: 'UpgradeCraft',
    component: upgrade_craft
  }, {
    path: '/developer/:id',
    name: 'DeveloperId',
    component: developer_id
  }, {
    path: '/featured/:handle',
    name: 'FeaturedHandle',
    component: _handle
  }, {
    path: '/buy/:plugin',
    name: 'BuyPlugin',
    component: _plugin
  }, {
    path: '/buy/:plugin/:edition',
    name: 'BuyPlugin',
    component: _plugin
  }, {
    path: '/buy-all-trials',
    name: 'BuyAllTrials',
    component: buy_all_trials
  }, {
    path: '/search',
    name: 'Search',
    component: search
  }, {
    path: '/tests',
    name: 'Tests',
    component: tests
  }, {
    path: '/:handle',
    name: 'PluginsHandle',
    component: pages_handle
  }, {
    path: '*',
    name: 'NotFound',
    component: _not_found
  }]
}));
// CONCATENATED MODULE: ./js/store/modules/app.js
/**
 * State
 */
var app_state = {
  searchQuery: '',
  showingScreenshotModal: false,
  screenshotModalImages: null,
  screenshotModalImageKey: 0
};
/**
 * Getters
 */

var app_getters = {};
/**
 * Actions
 */

var actions = {};
/**
 * Mutations
 */

var mutations = {
  updateSearchQuery: function updateSearchQuery(state, searchQuery) {
    state.searchQuery = searchQuery;
  },
  updateShowingScreenshotModal: function updateShowingScreenshotModal(state, show) {
    state.showingScreenshotModal = show;
  },
  updateScreenshotModalImages: function updateScreenshotModalImages(state, images) {
    state.screenshotModalImages = images;
  },
  updateScreenshotModalImageKey: function updateScreenshotModalImageKey(state, key) {
    state.screenshotModalImageKey = key;
  }
};
/* harmony default export */ var app = ({
  namespaced: true,
  state: app_state,
  getters: app_getters,
  actions: actions,
  mutations: mutations
});
// CONCATENATED MODULE: ./js/api/cart.js
/* global Craft */

/* harmony default export */ var api_cart = ({
  /**
   * Create cart.
   */
  createCart: function createCart(data) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('POST', 'carts', {
        data: data
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },

  /**
   * Get cart.
   */
  getCart: function getCart(orderNumber) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'carts/' + orderNumber).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },

  /**
   * Get order number.
   */
  getOrderNumber: function getOrderNumber(cb) {
    var orderNumber = localStorage.getItem('orderNumber');
    return cb(orderNumber);
  },

  /**
   * Reset order number.
   */
  resetOrderNumber: function resetOrderNumber() {
    localStorage.removeItem('orderNumber');
  },

  /**
   * Save order number.
   */
  saveOrderNumber: function saveOrderNumber(orderNumber) {
    localStorage.setItem('orderNumber', orderNumber);
  },

  /**
   * Save plugin license keys
   */
  savePluginLicenseKeys: function savePluginLicenseKeys(data) {
    return external_axios_default.a.post(Craft.getActionUrl('plugin-store/save-plugin-license-keys'), data, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue
      }
    });
  },

  /**
   * Update cart.
   */
  updateCart: function updateCart(orderNumber, data) {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('POST', 'carts/' + orderNumber, {
        data: data
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  }
});
// CONCATENATED MODULE: ./js/store/modules/cart.js
function cart_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function cart_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { cart_ownKeys(Object(source), true).forEach(function (key) { cart_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { cart_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function cart_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }






external_Vue_default.a.use(external_Vuex_default.a);
/**
 * State
 */

var cart_state = {
  activeTrialPlugins: [],
  cart: null,
  cartPlugins: [],
  selectedExpiryDates: {}
};
/**
 * Getters
 */

var cart_getters = {
  cartItems: function cartItems(state) {
    var cartItems = [];

    if (state.cart) {
      var lineItems = state.cart.lineItems;
      lineItems.forEach(function (lineItem) {
        var cartItem = {};
        cartItem.lineItem = lineItem;

        if (lineItem.purchasable.type === 'plugin-edition') {
          cartItem.plugin = state.cartPlugins.find(function (p) {
            return p.handle === lineItem.purchasable.plugin.handle;
          });
        }

        cartItems.push(cartItem);
      });
    }

    return cartItems;
  },
  cartItemsData: function cartItemsData(state) {
    return utils.getCartItemsData(state.cart);
  },
  isCmsEditionInCart: function isCmsEditionInCart(state) {
    return function (cmsEdition) {
      if (!state.cart) {
        return false;
      }

      return state.cart.lineItems.find(function (lineItem) {
        return lineItem.purchasable.type === 'cms-edition' && lineItem.purchasable.handle === cmsEdition;
      });
    };
  },
  isInCart: function isInCart(state) {
    return function (plugin, edition) {
      if (!state.cart) {
        return false;
      }

      return state.cart.lineItems.find(function (lineItem) {
        if (lineItem.purchasable.pluginId !== plugin.id) {
          return false;
        }

        if (edition && lineItem.purchasable.handle !== edition.handle) {
          return false;
        }

        return true;
      });
    };
  },
  getActiveTrialPluginEdition: function getActiveTrialPluginEdition(state, getters, rootState, rootGetters) {
    return function (plugin) {
      var pluginHandle = plugin.handle;
      var pluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'](pluginHandle);
      var pluginEdition = plugin.editions.find(function (edition) {
        return edition.handle === pluginLicenseInfo.edition;
      });

      if (!pluginEdition) {
        return null;
      }

      return pluginEdition;
    };
  },
  activeTrials: function activeTrials(state, getters, rootState, rootGetters) {
    var craftLogo = rootState.craft.craftLogo;
    var cmsEditions = rootState.pluginStore.cmsEditions;
    var licensedEdition = rootState.craft.licensedEdition;
    var CraftEdition = rootState.craft.CraftEdition;
    var getPluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'];
    var getCmsEditionIndex = rootGetters['craft/getCmsEditionIndex'];
    var getPluginEdition = rootGetters['pluginStore/getPluginEdition'];
    var trials = []; // CMS trial

    var cmsProEdition = cmsEditions.find(function (edition) {
      return edition.handle === 'pro';
    });
    var cmsProEditionIndex = getCmsEditionIndex(cmsProEdition.handle);

    if (cmsProEdition && licensedEdition < cmsProEditionIndex && licensedEdition < CraftEdition) {
      trials.push({
        type: 'cms-edition',
        name: 'Craft',
        iconUrl: craftLogo,
        editionHandle: 'pro',
        editionName: 'Pro',
        price: cmsProEdition.price,
        navigateTo: '/upgrade-craft'
      });
    } // Plugin trials


    var plugins = state.activeTrialPlugins;

    for (var i = 0; i < plugins.length; i++) {
      var plugin = plugins[i]; // license mismatched

      var pluginLicenseInfo = getPluginLicenseInfo(plugin.handle);
      var licenseMismatched = licenses.methods.getLicenseMismatched(pluginLicenseInfo); // plugin edition

      var activeTrialPluginEdition = getPluginEdition(plugin, pluginLicenseInfo.edition);

      if (!activeTrialPluginEdition) {
        continue;
      } // licensed edition


      var _licensedEdition = getPluginEdition(plugin, pluginLicenseInfo.licensedEdition); // license valid or astray


      var licenseValidOrAstray = licenses.methods.getLicenseValidOrAstray(pluginLicenseInfo); // navigate to

      var navigateTo = '/' + plugin.handle; // price & discount price

      var discountPrice = null;
      var price = activeTrialPluginEdition.price;

      if (_licensedEdition && _licensedEdition.handle !== activeTrialPluginEdition.handle && _licensedEdition.price > 0 && licenseValidOrAstray) {
        discountPrice = activeTrialPluginEdition.price - _licensedEdition.price;
      } // show edition badge


      var showEditionBadge = activeTrialPluginEdition && plugin.editions.length > 1; // plugin id

      var pluginId = plugin.id; // build trial row

      trials.push({
        type: 'plugin-edition',
        name: plugin.name,
        iconUrl: plugin.iconUrl,
        editionHandle: pluginLicenseInfo.edition,
        editionName: activeTrialPluginEdition.name,
        pluginHandle: plugin.handle,
        licenseMismatched: licenseMismatched,
        discountPrice: discountPrice,
        price: price,
        navigateTo: navigateTo,
        showEditionBadge: showEditionBadge,
        pluginId: pluginId
      });
    }

    return trials;
  },
  pendingActiveTrials: function pendingActiveTrials(state, getters) {
    var activeTrials = getters.activeTrials;
    var cart = state.cart;
    var isCmsEditionInCart = getters.isCmsEditionInCart; // filter out trials which are already in the cart

    return activeTrials.filter(function (activeTrial) {
      switch (activeTrial.type) {
        case 'cms-edition':
          if (isCmsEditionInCart(activeTrial.editionHandle)) {
            return false;
          }

          return true;

        case 'plugin-edition':
          return !cart.lineItems.find(function (item) {
            return item.purchasable.pluginId == activeTrial.pluginId;
          });

        default:
          return false;
      }
    });
  }
};
/**
 * Actions
 */

var cart_actions = {
  addToCart: function addToCart(_ref, newItems) {
    var state = _ref.state,
        dispatch = _ref.dispatch,
        rootGetters = _ref.rootGetters;
    return new Promise(function (resolve, reject) {
      var cart = JSON.parse(JSON.stringify(state.cart));
      var items = utils.getCartItemsData(cart);
      newItems.forEach(function (newItem) {
        var alreadyInCart = items.find(function (item) {
          return item.plugin === newItem.plugin;
        });

        if (!alreadyInCart) {
          var item = cart_objectSpread({}, newItem);

          item.expiryDate = '1y'; // Set default values

          item.autoRenew = false;

          switch (item.type) {
            case 'plugin-edition':
              {
                var pluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'](item.plugin); // Check that the current plugin license exists and is `valid`

                if (pluginLicenseInfo && pluginLicenseInfo.licenseKey && (pluginLicenseInfo.licenseKeyStatus === 'valid' || pluginLicenseInfo.licenseKeyStatus === 'trial')) {
                  // Check if the license has issues other than `wrong_edition` or `astray`
                  var hasIssues = false;

                  if (pluginLicenseInfo.licenseIssues.length > 0) {
                    pluginLicenseInfo.licenseIssues.forEach(function (issue) {
                      if (issue !== 'wrong_edition' && issue !== 'astray' && issue !== 'no_trials') {
                        hasIssues = true;
                      }
                    });
                  } // If we don’t have issues for this license, we can attach its key to the item


                  if (!hasIssues) {
                    item.licenseKey = pluginLicenseInfo.licenseKey;
                  }
                }

                item.cmsLicenseKey = window.cmsLicenseKey;
                break;
              }

            case 'cms-edition':
              {
                item.licenseKey = window.cmsLicenseKey;
                break;
              }
          }

          items.push(item);
        }
      });
      var data = {
        items: items
      };
      var cartNumber = cart.number;
      dispatch('updateCart', {
        cartNumber: cartNumber,
        data: data
      }).then(function (responseData) {
        if (typeof responseData.errors !== 'undefined') {
          return reject(responseData);
        }

        resolve(responseData);
      })["catch"](function (error) {
        return reject(error);
      });
    });
  },
  addAllTrialsToCart: function addAllTrialsToCart(_ref2) {
    var dispatch = _ref2.dispatch,
        getters = _ref2.getters;
    var items = [];
    getters.pendingActiveTrials.forEach(function (activeTrial) {
      var item = {
        type: activeTrial.type,
        edition: activeTrial.editionHandle
      };

      if (activeTrial.type === 'plugin-edition') {
        item.plugin = activeTrial.pluginHandle;
      }

      items.push(item);
    });
    return dispatch('addToCart', items);
  },
  createCart: function createCart(_ref3) {
    var dispatch = _ref3.dispatch,
        rootState = _ref3.rootState;
    return new Promise(function (resolve, reject) {
      var data = {
        email: rootState.craft.currentUser.email
      };
      api_cart.createCart(data).then(function (cartResponseData) {
        dispatch('updateCartPlugins', {
          cartResponseData: cartResponseData
        }).then(function () {
          dispatch('saveOrderNumber', {
            orderNumber: cartResponseData.cart.number
          });
          resolve(cartResponseData);
        })["catch"](function (error) {
          reject(error);
        });
      })["catch"](function (cartError) {
        reject(cartError);
      });
    });
  },
  getActiveTrials: function getActiveTrials(_ref4) {
    var dispatch = _ref4.dispatch;
    return new Promise(function (resolve, reject) {
      // get cms editions
      dispatch('pluginStore/getCmsEditions', null, {
        root: true
      }).then(function () {
        // get active trial plugins
        dispatch('getActiveTrialPlugins').then(function () {
          resolve();
        })["catch"](function (error) {
          reject(error);
        });
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getActiveTrialPlugins: function getActiveTrialPlugins(_ref5) {
    var commit = _ref5.commit,
        rootState = _ref5.rootState,
        rootGetters = _ref5.rootGetters;
    return new Promise(function (resolve, reject) {
      // get plugin license info and find active trial plugin handles
      var pluginHandles = [];
      var pluginLicenseInfo = rootState.craft.pluginLicenseInfo;

      for (var pluginHandle in pluginLicenseInfo) {
        if (Object.prototype.hasOwnProperty.call(pluginLicenseInfo, pluginHandle) && pluginLicenseInfo[pluginHandle].isEnabled) {
          pluginHandles.push(pluginHandle);
        }
      } // request plugins by plugin handle


      pluginstore.getPluginsByHandles(pluginHandles).then(function (responseData) {
        if (responseData && responseData.error) {
          throw responseData.error;
        }

        var data = responseData;
        var plugins = [];

        var _loop = function _loop(i) {
          var plugin = data[i];

          if (!plugin) {
            return "continue";
          }

          var info = pluginLicenseInfo[plugin.handle];

          if (!info) {
            return "continue";
          }

          if (info.licenseKey && info.edition === info.licensedEdition) {
            return "continue";
          }

          if (info.edition) {
            var pluginEdition = plugin.editions.find(function (edition) {
              return edition.handle === info.edition;
            });

            if (pluginEdition && rootGetters['pluginStore/isPluginEditionFree'](pluginEdition)) {
              return "continue";
            }
          }

          if (!rootGetters['craft/isPluginInstalled'](plugin.handle)) {
            return "continue";
          }

          plugins.push(plugin);
        };

        for (var i = 0; i < data.length; i++) {
          var _ret = _loop(i);

          if (_ret === "continue") continue;
        }

        commit('updateActiveTrialPlugins', plugins);
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getCart: function getCart(_ref6) {
    var dispatch = _ref6.dispatch;
    return new Promise(function (resolve, reject) {
      // retrieve the order number
      dispatch('getOrderNumber').then(function (orderNumber) {
        if (orderNumber) {
          // get cart by order number
          api_cart.getCart(orderNumber).then(function (cartResponseData) {
            dispatch('updateCartPlugins', {
              cartResponseData: cartResponseData
            }).then(function () {
              resolve(cartResponseData);
            })["catch"](function (error) {
              reject(error);
            });
          })["catch"](function () {
            // Cart already completed or has errors? Create a new one.
            dispatch('createCart').then(function (cartResponseData) {
              resolve(cartResponseData);
            })["catch"](function (cartError) {
              reject(cartError);
            });
          });
        } else {
          // No order number yet? Create a new cart.
          dispatch('createCart').then(function (cartResponseData) {
            resolve(cartResponseData);
          })["catch"](function (cartError) {
            reject(cartError);
          });
        }
      });
    });
  },
  getOrderNumber: function getOrderNumber(_ref7) {
    var state = _ref7.state;
    return new Promise(function (resolve, reject) {
      if (state.cart && state.cart.number) {
        var orderNumber = state.cart.number;
        resolve(orderNumber);
      } else {
        api_cart.getOrderNumber(function (orderNumber) {
          resolve(orderNumber);
        }, function (response) {
          reject(response);
        });
      }
    });
  },
  removeFromCart: function removeFromCart(_ref8, lineItemKey) {
    var dispatch = _ref8.dispatch,
        state = _ref8.state;
    return new Promise(function (resolve, reject) {
      var cart = state.cart;
      var items = utils.getCartItemsData(cart);
      items.splice(lineItemKey, 1);
      var data = {
        items: items
      };
      var cartNumber = cart.number;
      dispatch('updateCart', {
        cartNumber: cartNumber,
        data: data
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  resetCart: function resetCart(_ref9) {
    var commit = _ref9.commit,
        dispatch = _ref9.dispatch;
    return new Promise(function (resolve, reject) {
      commit('resetCart');
      dispatch('resetOrderNumber');
      dispatch('getCart').then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  resetOrderNumber: function resetOrderNumber() {
    api_cart.resetOrderNumber();
  },
  saveCart: function saveCart(_ref10, data) {
    var dispatch = _ref10.dispatch,
        state = _ref10.state;
    return new Promise(function (resolve, reject) {
      var cart = state.cart;
      var cartNumber = cart.number;
      dispatch('updateCart', {
        cartNumber: cartNumber,
        data: data
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  saveOrderNumber: function saveOrderNumber(context, _ref11) {
    var orderNumber = _ref11.orderNumber;
    api_cart.saveOrderNumber(orderNumber);
  },
  savePluginLicenseKeys: function savePluginLicenseKeys(_ref12, cart) {
    var rootGetters = _ref12.rootGetters;
    return new Promise(function (resolve, reject) {
      var pluginLicenseKeys = [];
      cart.lineItems.forEach(function (lineItem) {
        if (lineItem.purchasable.type === 'plugin-edition') {
          if (rootGetters['craft/isPluginInstalled'](lineItem.purchasable.plugin.handle)) {
            var licenseKey = lineItem.options.licenseKey;

            if (licenseKey.substr(0, 4) === 'new:') {
              licenseKey = licenseKey.substr(4);
            }

            pluginLicenseKeys.push({
              handle: lineItem.purchasable.plugin.handle,
              key: licenseKey
            });
          }
        }
      });
      var data = {
        pluginLicenseKeys: pluginLicenseKeys
      };
      api_cart.savePluginLicenseKeys(data).then(function (response) {
        resolve(response);
      })["catch"](function (error) {
        reject(error.response);
      });
    });
  },
  updateCart: function updateCart(_ref13, _ref14) {
    var dispatch = _ref13.dispatch;
    var cartNumber = _ref14.cartNumber,
        data = _ref14.data;
    return new Promise(function (resolve, reject) {
      api_cart.updateCart(cartNumber, data).then(function (cartResponseData) {
        if (cartResponseData && cartResponseData.errors) {
          reject({
            response: cartResponseData
          });
          return null;
        }

        dispatch('updateCartPlugins', {
          cartResponseData: cartResponseData
        }).then(function () {
          resolve(cartResponseData);
        })["catch"](function (error) {
          reject(error);
        });
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  updateCartPlugins: function updateCartPlugins(_ref15, _ref16) {
    var commit = _ref15.commit;
    var cartResponseData = _ref16.cartResponseData;
    return new Promise(function (resolve, reject) {
      var cart = cartResponseData.cart;
      var cartItemPluginIds = [];
      cart.lineItems.forEach(function (lineItem) {
        if (lineItem.purchasable.type === 'plugin-edition') {
          cartItemPluginIds.push(lineItem.purchasable.plugin.id);
        }
      });

      if (cartItemPluginIds.length > 0) {
        pluginstore.getPluginsByIds(cartItemPluginIds).then(function (pluginsResponseData) {
          commit('updateCart', {
            cartResponseData: cartResponseData
          });
          commit('updateCartPlugins', {
            pluginsResponseData: pluginsResponseData
          });
          resolve(pluginsResponseData);
        })["catch"](function (error) {
          reject(error);
        });
      } else {
        var pluginsResponseData = [];
        commit('updateCart', {
          cartResponseData: cartResponseData
        });
        commit('updateCartPlugins', {
          pluginsResponseData: pluginsResponseData
        });
        resolve(pluginsResponseData);
      }
    });
  },
  updateItem: function updateItem(_ref17, _ref18) {
    var dispatch = _ref17.dispatch,
        state = _ref17.state;
    var itemKey = _ref18.itemKey,
        item = _ref18.item;
    return new Promise(function (resolve, reject) {
      var cart = state.cart;
      var cartNumber = cart.number;
      var items = utils.getCartItemsData(cart);
      items[itemKey] = item;
      var data = {
        items: items
      };
      dispatch('updateCart', {
        cartNumber: cartNumber,
        data: data
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  }
};
/**
 * Mutations
 */

var cart_mutations = {
  resetCart: function resetCart(state) {
    state.cart = null;
  },
  updateActiveTrialPlugins: function updateActiveTrialPlugins(state, plugins) {
    state.activeTrialPlugins = plugins;
  },
  updateCart: function updateCart(state, _ref19) {
    var cartResponseData = _ref19.cartResponseData;
    state.cart = cartResponseData.cart;
    var selectedExpiryDates = {};
    state.cart.lineItems.forEach(function (lineItem, key) {
      selectedExpiryDates[key] = lineItem.options.expiryDate;
    });
    state.selectedExpiryDates = selectedExpiryDates;
  },
  updateCartPlugins: function updateCartPlugins(state, _ref20) {
    var pluginsResponseData = _ref20.pluginsResponseData;
    state.cartPlugins = pluginsResponseData;
  },
  updateSelectedExpiryDates: function updateSelectedExpiryDates(state, selectedExpiryDates) {
    state.selectedExpiryDates = selectedExpiryDates;
  }
};
/**
 * Utils
 */

var utils = {
  getCartData: function getCartData(cart) {
    var data = {
      email: cart.email,
      billingAddress: {
        firstName: cart.billingAddress.firstName,
        lastName: cart.billingAddress.lastName
      },
      items: []
    };
    data.items = this.getCartItemsData(cart);
    return data;
  },
  getCartItemsData: function getCartItemsData(cart) {
    if (!cart) {
      return [];
    }

    var lineItems = [];

    for (var i = 0; i < cart.lineItems.length; i++) {
      var lineItem = cart.lineItems[i];

      switch (lineItem.purchasable.type) {
        case 'plugin-edition':
          {
            var item = {
              type: lineItem.purchasable.type,
              plugin: lineItem.purchasable.plugin.handle,
              edition: lineItem.purchasable.handle,
              cmsLicenseKey: window.cmsLicenseKey,
              expiryDate: lineItem.options.expiryDate,
              autoRenew: lineItem.options.autoRenew
            };
            var licenseKey = lineItem.options.licenseKey;

            if (licenseKey && licenseKey.substr(0, 3) !== 'new') {
              item.licenseKey = licenseKey;
            }

            lineItems.push(item);
            break;
          }

        case 'cms-edition':
          {
            var _item = {
              type: lineItem.purchasable.type,
              edition: lineItem.purchasable.handle,
              expiryDate: lineItem.options.expiryDate,
              autoRenew: lineItem.options.autoRenew
            };
            var _licenseKey = lineItem.options.licenseKey;

            if (_licenseKey && _licenseKey.substr(0, 3) !== 'new') {
              _item.licenseKey = _licenseKey;
            }

            lineItems.push(_item);
            break;
          }
      }
    }

    return lineItems;
  }
};
/* harmony default export */ var modules_cart = ({
  namespaced: true,
  state: cart_state,
  getters: cart_getters,
  actions: cart_actions,
  mutations: cart_mutations
});
// CONCATENATED MODULE: ./js/store/modules/plugin-store.js
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }


/**
 * State
 */

var plugin_store_state = {
  categories: [],
  cmsEditions: null,
  developer: null,
  expiryDateOptions: [],
  featuredPlugins: [],
  featuredSection: null,
  featuredSections: [],
  plugin: null,
  pluginChangelog: null,
  // plugin index
  plugins: []
};
/**
 * Getters
 */

var plugin_store_getters = {
  getCategoryById: function getCategoryById(state) {
    return function (id) {
      return state.categories.find(function (c) {
        return c.id == id;
      });
    };
  },
  getPluginEdition: function getPluginEdition() {
    return function (plugin, editionHandle) {
      return plugin.editions.find(function (edition) {
        return edition.handle === editionHandle;
      });
    };
  },
  getPluginIndexParams: function getPluginIndexParams() {
    return function (context) {
      var perPage = context.perPage ? context.perPage : null;
      var page = context.page ? context.page : 1;
      var orderBy = context.orderBy;
      var direction = context.direction;
      return {
        perPage: perPage,
        page: page,
        orderBy: orderBy,
        direction: direction
      };
    };
  },
  isPluginEditionFree: function isPluginEditionFree() {
    return function (edition) {
      return edition.price === null;
    };
  }
};
/**
 * Actions
 */

var plugin_store_actions = {
  cancelRequests: function cancelRequests() {
    return pluginstore.cancelRequests();
  },
  getCoreData: function getCoreData(_ref) {
    var commit = _ref.commit;
    return new Promise(function (resolve, reject) {
      pluginstore.getCoreData().then(function (responseData) {
        commit('updateCoreData', {
          responseData: responseData
        });
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getCmsEditions: function getCmsEditions(_ref2, payload) {
    var commit = _ref2.commit;
    var force = payload && payload.force ? payload.force : false;
    return new Promise(function (resolve, reject) {
      if (plugin_store_state.cmsEditions && force !== true) {
        resolve();
        return;
      }

      pluginstore.getCmsEditions().then(function (responseData) {
        commit('updateCmsEditions', {
          responseData: responseData
        });
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getDeveloper: function getDeveloper(_ref3, developerId) {
    var commit = _ref3.commit;
    return pluginstore.getDeveloper(developerId).then(function (responseData) {
      commit('updateDeveloper', responseData);
    });
  },
  getFeaturedSectionByHandle: function getFeaturedSectionByHandle(_ref4, featuredSectionHandle) {
    var commit = _ref4.commit;
    return pluginstore.getFeaturedSectionByHandle(featuredSectionHandle).then(function (responseData) {
      commit('updateFeaturedSection', responseData);
    });
  },
  getFeaturedSections: function getFeaturedSections(_ref5) {
    var commit = _ref5.commit;
    return pluginstore.getFeaturedSections().then(function (responseData) {
      commit('updateFeaturedSections', responseData);
    });
  },
  getPluginChangelog: function getPluginChangelog(_ref6, pluginId) {
    var commit = _ref6.commit;
    return new Promise(function (resolve, reject) {
      pluginstore.getPluginChangelog(pluginId).then(function (responseData) {
        commit('updatePluginChangelog', responseData);
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getPluginDetails: function getPluginDetails(_ref7, pluginId) {
    var commit = _ref7.commit;
    return new Promise(function (resolve, reject) {
      pluginstore.getPluginDetails(pluginId).then(function (responseData) {
        commit('updatePluginDetails', responseData);
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getPluginDetailsByHandle: function getPluginDetailsByHandle(_ref8, pluginHandle) {
    var commit = _ref8.commit;
    return pluginstore.getPluginDetailsByHandle(pluginHandle).then(function (responseData) {
      commit('updatePluginDetails', responseData);
    });
  },
  getPluginsByCategory: function getPluginsByCategory(_ref9, context) {
    var getters = _ref9.getters,
        dispatch = _ref9.dispatch;
    return new Promise(function (resolve, reject) {
      var pluginIndexParams = getters['getPluginIndexParams'](context);
      pluginstore.getPluginsByCategory(context.categoryId, pluginIndexParams).then(function (responseData) {
        dispatch('updatePluginIndex', {
          context: context,
          responseData: responseData
        });
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getPluginsByDeveloperId: function getPluginsByDeveloperId(_ref10, context) {
    var getters = _ref10.getters,
        dispatch = _ref10.dispatch;
    return new Promise(function (resolve, reject) {
      var pluginIndexParams = getters['getPluginIndexParams'](context);
      pluginstore.getPluginsByDeveloperId(context.developerId, pluginIndexParams).then(function (responseData) {
        dispatch('updatePluginIndex', {
          context: context,
          responseData: responseData
        });
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getPluginsByFeaturedSectionHandle: function getPluginsByFeaturedSectionHandle(_ref11, context) {
    var getters = _ref11.getters,
        dispatch = _ref11.dispatch;
    return new Promise(function (resolve, reject) {
      var pluginIndexParams = getters['getPluginIndexParams'](context);
      return pluginstore.getPluginsByFeaturedSectionHandle(context.featuredSectionHandle, pluginIndexParams).then(function (responseData) {
        dispatch('updatePluginIndex', {
          context: context,
          responseData: responseData
        });
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  searchPlugins: function searchPlugins(_ref12, context) {
    var getters = _ref12.getters,
        dispatch = _ref12.dispatch;
    return new Promise(function (resolve, reject) {
      var pluginIndexParams = getters['getPluginIndexParams'](context);
      pluginstore.searchPlugins(context.searchQuery, pluginIndexParams).then(function (responseData) {
        dispatch('updatePluginIndex', {
          context: context,
          responseData: responseData
        });
        resolve(responseData);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  updatePluginIndex: function updatePluginIndex(_ref13, _ref14) {
    var commit = _ref13.commit;
    var context = _ref14.context,
        responseData = _ref14.responseData;

    if (context.appendData && context.appendData === true) {
      commit('appendPlugins', responseData.plugins);
    } else {
      commit('updatePlugins', responseData.plugins);
    }
  }
};
/**
 * Mutations
 */

var plugin_store_mutations = {
  appendPlugins: function appendPlugins(state, plugins) {
    state.plugins = [].concat(_toConsumableArray(state.plugins), _toConsumableArray(plugins));
  },
  updateCoreData: function updateCoreData(state, _ref15) {
    var responseData = _ref15.responseData;
    state.categories = responseData.categories;
    state.expiryDateOptions = responseData.expiryDateOptions;
    state.sortOptions = responseData.sortOptions;
  },
  updateCmsEditions: function updateCmsEditions(state, _ref16) {
    var responseData = _ref16.responseData;
    state.cmsEditions = responseData.editions;
  },
  updateDeveloper: function updateDeveloper(state, developer) {
    state.developer = developer;
  },
  updateFeaturedSection: function updateFeaturedSection(state, featuredSection) {
    state.featuredSection = featuredSection;
  },
  updateFeaturedSections: function updateFeaturedSections(state, featuredSections) {
    state.featuredSections = featuredSections;
  },
  updatePluginChangelog: function updatePluginChangelog(state, changelog) {
    state.pluginChangelog = changelog;
  },
  updatePluginDetails: function updatePluginDetails(state, pluginDetails) {
    state.plugin = pluginDetails;
  },
  updatePlugins: function updatePlugins(state, plugins) {
    state.plugins = plugins;
  }
};
/* harmony default export */ var plugin_store = ({
  namespaced: true,
  state: plugin_store_state,
  getters: plugin_store_getters,
  actions: plugin_store_actions,
  mutations: plugin_store_mutations
});
// CONCATENATED MODULE: ./js/api/craft.js
/* global Craft */
 // create a cancel token for axios

var craft_CancelToken = external_axios_default.a.CancelToken;
var craft_cancelTokenSource = craft_CancelToken.source(); // create an axios instance

var _axios = external_axios_default.a.create({
  cancelToken: craft_cancelTokenSource.token
});

/* harmony default export */ var craft = ({
  /**
   * Cancel requests.
   */
  cancelRequests: function cancelRequests() {
    // cancel requests
    craft_cancelTokenSource.cancel(); // create a new cancel token

    craft_cancelTokenSource = craft_CancelToken.source(); // update axios with the new cancel token

    _axios.defaults.cancelToken = craft_cancelTokenSource.token;
  },

  /**
   * Get Craft data.
   */
  getCraftData: function getCraftData() {
    return new Promise(function (resolve, reject) {
      _axios.get(Craft.getActionUrl('plugin-store/craft-data')).then(function (response) {
        resolve(response);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get Craft ID data.
   */
  getCraftIdData: function getCraftIdData(_ref) {
    var accessToken = _ref.accessToken;
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'account', {
        cancelToken: craft_cancelTokenSource.token,
        headers: {
          'Authorization': 'Bearer ' + accessToken
        }
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get countries.
   */
  getCountries: function getCountries() {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'countries', {
        cancelToken: craft_cancelTokenSource.token
      }).then(function (responseData) {
        resolve(responseData);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Get Plugin License Info.
   */
  getPluginLicenseInfo: function getPluginLicenseInfo() {
    return new Promise(function (resolve, reject) {
      Craft.sendApiRequest('GET', 'cms-licenses', {
        params: {
          include: 'plugins'
        }
      }).then(function (response) {
        _axios.post(Craft.getActionUrl('app/get-plugin-license-info'), {
          pluginLicenses: response.license.pluginLicenses || []
        }, {
          headers: {
            'X-CSRF-Token': Craft.csrfTokenValue
          }
        }).then(function (response) {
          resolve(response);
        })["catch"](function (error) {
          if (external_axios_default.a.isCancel(error)) {// request cancelled
          } else {
            reject(error);
          }
        });
      });
    });
  },

  /**
   * Switch plugin edition.
   */
  switchPluginEdition: function switchPluginEdition(pluginHandle, edition) {
    return new Promise(function (resolve, reject) {
      var data = 'pluginHandle=' + pluginHandle + '&edition=' + edition;

      _axios.post(Craft.getActionUrl('plugins/switch-edition'), data, {
        headers: {
          'X-CSRF-Token': Craft.csrfTokenValue
        }
      }).then(function (response) {
        Craft.clearCachedApiHeaders();
        resolve(response);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  },

  /**
   * Try edition.
   */
  tryEdition: function tryEdition(edition) {
    return new Promise(function (resolve, reject) {
      _axios.post(Craft.getActionUrl('app/try-edition'), 'edition=' + edition, {
        headers: {
          'X-CSRF-Token': Craft.csrfTokenValue
        }
      }).then(function (response) {
        Craft.clearCachedApiHeaders();
        resolve(response);
      })["catch"](function (error) {
        if (external_axios_default.a.isCancel(error)) {// request cancelled
        } else {
          reject(error);
        }
      });
    });
  }
});
// CONCATENATED MODULE: ./js/store/modules/craft.js

/**
 * State
 */

var craft_state = {
  canTestEditions: null,
  countries: null,
  craftId: null,
  craftLogo: null,
  currentUser: null,
  defaultPluginSvg: null,
  licensedEdition: null,
  pluginLicenseInfo: {},
  alertIcon: null,
  // Craft editions
  CraftEdition: null,
  CraftPro: null,
  CraftSolo: null
};
/**
 * Getters
 */

var craft_getters = {
  getCmsEditionFeatures: function getCmsEditionFeatures() {
    return function (editionHandle) {
      var features = {
        "solo": [{
          name: "Ultra-flexible content modeling",
          description: "Define custom content types, fields, and relations needed to perfectly contain your unique content requirements."
        }, {
          name: "Powerful front-end tools",
          description: "Develop custom front-end templates with Twig, or use Craft as a headless CMS."
        }, {
          name: "Multi-Site",
          description: "Run multiple related sites from a single installation, with shared content and user accounts."
        }, {
          name: "Localization",
          description: "Cater to distinct audiences from around the world with Craft’s best-in-class localization capabilities."
        }, {
          name: "Single admin account",
          description: "The Solo edition is limited to a single admin account."
        }],
        "pro": [{
          name: "Unlimited user accounts",
          description: "Create unlimited user accounts, user groups, user permissions, and public user registration."
        }, {
          name: "Enhanced content previewing",
          description: "Preview your content from multiple targets, including single-page applications."
        }, {
          name: "GraphQL API",
          description: "Make your content available to other applications with a self-generating GraphQL API."
        }, {
          name: "System branding",
          description: "Personalize the control panel for your brand."
        }, {
          name: "Basic developer support",
          description: "Get developer-to-developer support right from the Craft core development team."
        }]
      };

      if (!features[editionHandle]) {
        return null;
      }

      return features[editionHandle];
    };
  },
  getPluginLicenseInfo: function getPluginLicenseInfo(state) {
    return function (pluginHandle) {
      if (!state.pluginLicenseInfo) {
        return null;
      }

      if (!state.pluginLicenseInfo[pluginHandle]) {
        return null;
      }

      return state.pluginLicenseInfo[pluginHandle];
    };
  },
  isPluginInstalled: function isPluginInstalled(state) {
    return function (pluginHandle) {
      if (!state.pluginLicenseInfo) {
        return false;
      }

      if (!state.pluginLicenseInfo[pluginHandle]) {
        return false;
      }

      if (!state.pluginLicenseInfo[pluginHandle].isInstalled) {
        return false;
      }

      return true;
    };
  },
  getCmsEditionIndex: function getCmsEditionIndex(state) {
    return function (editionHandle) {
      switch (editionHandle) {
        case 'solo':
          return state.CraftSolo;

        case 'pro':
          return state.CraftPro;

        default:
          return null;
      }
    };
  }
};
/**
 * Actions
 */

var craft_actions = {
  cancelRequests: function cancelRequests() {
    return craft.cancelRequests();
  },
  getCraftData: function getCraftData(_ref) {
    var commit = _ref.commit;
    return new Promise(function (resolve, reject) {
      craft.getCraftData().then(function (response) {
        commit('updateCraftData', {
          response: response
        });
        craft.getCountries().then(function (responseData) {
          commit('updateCountries', {
            responseData: responseData
          });
          resolve();
        })["catch"](function (error) {
          reject(error);
        });
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getCraftIdData: function getCraftIdData(_ref2, _ref3) {
    var commit = _ref2.commit;
    var accessToken = _ref3.accessToken;
    return new Promise(function (resolve, reject) {
      craft.getCraftIdData({
        accessToken: accessToken
      }).then(function (responseData) {
        commit('updateCraftIdData', {
          responseData: responseData
        });
        resolve();
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  getPluginLicenseInfo: function getPluginLicenseInfo(_ref4) {
    var commit = _ref4.commit;
    return new Promise(function (resolve, reject) {
      craft.getPluginLicenseInfo().then(function (response) {
        commit('updatePluginLicenseInfo', {
          response: response
        });
        resolve(response);
      })["catch"](function (error) {
        reject(error);
      });
    });
  },
  switchPluginEdition: function switchPluginEdition(_ref5, _ref6) {
    var dispatch = _ref5.dispatch;
    var pluginHandle = _ref6.pluginHandle,
        edition = _ref6.edition;
    return new Promise(function (resolve, reject) {
      craft.switchPluginEdition(pluginHandle, edition).then(function (switchPluginEditionResponse) {
        dispatch('getPluginLicenseInfo').then(function (getPluginLicenseInfoResponse) {
          resolve({
            switchPluginEditionResponse: switchPluginEditionResponse,
            getPluginLicenseInfoResponse: getPluginLicenseInfoResponse
          });
        })["catch"](function (response) {
          return reject(response);
        });
      })["catch"](function (response) {
        return reject(response);
      });
    });
  },
  tryEdition: function tryEdition(context, edition) {
    return new Promise(function (resolve, reject) {
      craft.tryEdition(edition).then(function (response) {
        resolve(response);
      })["catch"](function (response) {
        reject(response);
      });
    });
  }
};
/**
 * Mutations
 */

var craft_mutations = {
  updateCraftData: function updateCraftData(state, _ref7) {
    var response = _ref7.response;
    state.canTestEditions = response.data.canTestEditions;
    state.craftLogo = response.data.craftLogo;
    state.currentUser = response.data.currentUser;
    state.defaultPluginSvg = response.data.defaultPluginSvg;
    state.licensedEdition = response.data.licensedEdition;
    state.alertIcon = response.data.alertIcon; // Craft editions

    state.CraftEdition = response.data.CraftEdition;
    state.CraftPro = response.data.CraftPro;
    state.CraftSolo = response.data.CraftSolo;
  },
  updateCraftIdData: function updateCraftIdData(state, _ref8) {
    var responseData = _ref8.responseData;
    state.craftId = responseData;
  },
  updateCountries: function updateCountries(state, _ref9) {
    var responseData = _ref9.responseData;
    state.countries = responseData.countries;
  },
  updateCraftId: function updateCraftId(state, craftId) {
    state.craftId = craftId;
  },
  updatePluginLicenseInfo: function updatePluginLicenseInfo(state, _ref10) {
    var response = _ref10.response;
    state.pluginLicenseInfo = response.data;
  }
};
/* harmony default export */ var modules_craft = ({
  namespaced: true,
  state: craft_state,
  getters: craft_getters,
  actions: craft_actions,
  mutations: craft_mutations
});
// CONCATENATED MODULE: ./js/store/index.js






external_Vue_default.a.use(external_Vuex_default.a);
/* harmony default export */ var store = (new external_Vuex_default.a.Store({
  strict: true,
  modules: {
    app: app,
    cart: modules_cart,
    pluginStore: plugin_store,
    craft: modules_craft
  }
}));
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/Modal.vue?vue&type=template&id=99e7d010&
var Modalvue_type_template_id_99e7d010_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"tw-hidden"},[_c('div',{ref:"pluginstoremodal",staticClass:"pluginstore-modal modal",class:'step-'+_vm.modalStep,attrs:{"id":"pluginstore-modal"}},[(_vm.modalStep === 'cart')?_c('cart',{on:{"continue-shopping":function($event){return _vm.$root.closeModal()}}}):_vm._e()],1)])}
var Modalvue_type_template_id_99e7d010_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/modal/Modal.vue?vue&type=template&id=99e7d010&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/steps/Cart.vue?vue&type=template&id=2d2314b3&
var Cartvue_type_template_id_2d2314b3_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('step',[_c('template',{slot:"header"},[_c('h1',[_vm._v(_vm._s(_vm._f("t")("Cart",'app')))])]),_vm._v(" "),_c('template',{slot:"main"},[(!_vm.activeTrialsLoading)?[_c('h2',[_vm._v(_vm._s(_vm._f("t")("Items in your cart",'app')))]),_vm._v(" "),(_vm.cart)?[(_vm.cartItems.length)?[_c('table',{staticClass:"cart-data tw-w-full"},[_c('thead',[_c('tr',[_c('th'),_vm._v(" "),_c('th',[_vm._v(_vm._s(_vm._f("t")("Item",'app')))]),_vm._v(" "),_c('th',[_vm._v(_vm._s(_vm._f("t")("Updates",'app')))]),_vm._v(" "),_c('th',{staticClass:"tw-w-10"})])]),_vm._v(" "),_vm._l((_vm.cartItems),function(item,itemKey){return _c('tbody',{key:'item' + itemKey},[_c('tr',{staticClass:"item-details"},[(item.lineItem.purchasable.type === 'cms-edition')?[_c('td',{staticClass:"thin"},[_c('div',{staticClass:"plugin-icon"},[_c('img',{attrs:{"src":_vm.craftLogo,"width":"40","height":"40"}})])]),_vm._v(" "),_c('td',{staticClass:"item-name"},[_c('strong',[_vm._v("Craft CMS")]),_vm._v(" "),_c('edition-badge',{attrs:{"name":item.lineItem.purchasable.name}})],1)]:(item.lineItem.purchasable.type === 'plugin-edition')?[_c('td',{staticClass:"thin"},[_c('div',{staticClass:"plugin-icon"},[(item.plugin.iconUrl)?_c('img',{attrs:{"src":item.plugin.iconUrl,"width":"40","height":"40"}}):_vm._e()])]),_vm._v(" "),_c('td',[_c('div',{staticClass:"item-name"},[_c('strong',[_vm._v(_vm._s(item.plugin.name))]),_vm._v(" "),(item.plugin.editions > 1)?_c('edition-badge',{attrs:{"name":item.lineItem.purchasable.name}}):_vm._e()],1)])]:_vm._e(),_vm._v(" "),_c('td',{staticClass:"expiry-date"},[(
                                        item.lineItem.purchasable.type === 'cms-edition'
                                        || (item.lineItem.purchasable.type === 'plugin-edition'
                                        && (
                                            item.lineItem.options.licenseKey.substr(0, 4) === 'new:'
                                            || (
                                                _vm.pluginLicenseInfo(item.plugin.handle) &&
                                                _vm.pluginLicenseInfo(item.plugin.handle).isTrial
                                            )
                                        )
                                    ))?[_c('dropdown',{attrs:{"options":_vm.itemExpiryDateOptions(itemKey)},on:{"input":function($event){return _vm.onSelectedExpiryDateChange(itemKey)}},model:{value:(_vm.selectedExpiryDates[itemKey]),callback:function ($$v) {_vm.$set(_vm.selectedExpiryDates, itemKey, $$v)},expression:"selectedExpiryDates[itemKey]"}})]:_vm._e(),_vm._v(" "),(_vm.itemLoading(itemKey))?_c('spinner'):_vm._e()],2),_vm._v(" "),_c('td',{staticClass:"price"},[_c('strong',[_vm._v(_vm._s(_vm._f("currency")(item.lineItem.price)))])])],2),_vm._v(" "),_vm._l((item.lineItem.adjustments),function(adjustment,adjustmentKey){return [_c('tr',{key:itemKey + 'adjustment-' + adjustmentKey,staticClass:"sub-item"},[_c('td',{staticClass:"blank-cell"}),_vm._v(" "),_c('td',{staticClass:"blank-cell"}),_vm._v(" "),_c('td',[(adjustment.sourceSnapshot.type === 'extendedUpdates')?[_vm._v("\n                                            "+_vm._s(_vm._f("t")("Updates until {date}",'app', {date: _vm.$options.filters.formatDate(adjustment.sourceSnapshot.expiryDate)}))+"\n                                        ")]:[_vm._v("\n                                            "+_vm._s(adjustment.name)+"\n                                        ")]],2),_vm._v(" "),_c('td',{staticClass:"price"},[_vm._v("\n                                        "+_vm._s(_vm._f("currency")(adjustment.amount))+"\n                                    ")])])]}),_vm._v(" "),_c('tr',{staticClass:"sub-item"},[_c('td',{staticClass:"blank-cell"}),_vm._v(" "),_c('td',{staticClass:"blank-cell"}),_vm._v(" "),_c('td',{staticClass:"empty-cell"}),_vm._v(" "),_c('td',{staticClass:"price"},[_c('div',{staticClass:"tw-w-16"},[(!_vm.removeFromCartLoading(itemKey))?[_c('a',{attrs:{"role":"button"},on:{"click":function($event){return _vm.removeFromCart(itemKey)}}},[_vm._v(_vm._s(_vm._f("t")("Remove",'app')))])]:[_c('spinner',{staticClass:"sm"})]],2)])])],2)}),_vm._v(" "),_c('tbody',[_c('tr',[_c('th',{staticClass:"total-price",attrs:{"colspan":"3"}},[_c('strong',[_vm._v(_vm._s(_vm._f("t")("Total Price",'app')))])]),_vm._v(" "),_c('td',{staticClass:"total-price"},[_c('strong',[_vm._v(_vm._s(_vm._f("currency")(_vm.cart.totalPrice)))])])])])],2),_vm._v(" "),_c('div',{staticClass:"tw-py-4 tw-flex"},[_c('btn',{attrs:{"kind":"primary","loading":_vm.loadingCheckout},on:{"click":function($event){return _vm.payment()}}},[_vm._v(_vm._s(_vm._f("t")("Checkout",'app')))])],1)]:_c('div',[_c('p',[_vm._v(_vm._s(_vm._f("t")("Your cart is empty.",'app'))+" "),_c('a',{on:{"click":function($event){return _vm.$emit('continue-shopping')}}},[_vm._v(_vm._s(_vm._f("t")("Continue shopping",'app')))])])])]:_vm._e(),_vm._v(" "),_c('active-trials')]:[_c('spinner')]],2)],2)}
var Cartvue_type_template_id_2d2314b3_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/modal/steps/Cart.vue?vue&type=template&id=2d2314b3&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/Step.vue?vue&type=template&id=45a38f0a&
var Stepvue_type_template_id_45a38f0a_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"pluginstore-modal-flex"},[(!!_vm.$slots['body'])?[_vm._t("body")]:[(!!_vm.$slots['header'])?_c('header',{staticClass:"header"},[_vm._t("header")],2):_vm._e(),_vm._v(" "),_c('div',{staticClass:"pluginstore-modal-main"},[_c('div',{staticClass:"pluginstore-modal-content"},[_vm._t("main")],2)])]],2)}
var Stepvue_type_template_id_45a38f0a_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/modal/Step.vue?vue&type=template&id=45a38f0a&

// CONCATENATED MODULE: ./js/components/modal/Step.vue

var script = {}


/* normalize component */

var Step_component = Object(componentNormalizer["a" /* default */])(
  script,
  Stepvue_type_template_id_45a38f0a_render,
  Stepvue_type_template_id_45a38f0a_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Step = (Step_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/steps/cart/ActiveTrials.vue?vue&type=template&id=b05a214e&
var ActiveTrialsvue_type_template_id_b05a214e_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.pendingActiveTrials && _vm.pendingActiveTrials.length > 0)?_c('div',{staticClass:"tw-border-t tw-border-solid tw-border-gray-300 tw-mt-6 tw-pt-6"},[(_vm.pendingActiveTrials.length > 1)?_c('div',{staticClass:"tw-right"},[_c('a',{on:{"click":function($event){return _vm.addAllTrialsToCart()}}},[_vm._v(_vm._s(_vm._f("t")("Add all to cart",'app')))])]):_vm._e(),_vm._v(" "),_c('h2',[_vm._v(_vm._s(_vm._f("t")("Active Trials",'app')))]),_vm._v(" "),_c('table',{staticClass:"cart-data"},_vm._l((_vm.pendingActiveTrials),function(activeTrial,key){return _c('tbody',{key:key},[_c('active-trials-table-row',{attrs:{"activeTrial":activeTrial}})],1)}),0)]):_vm._e()}
var ActiveTrialsvue_type_template_id_b05a214e_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/modal/steps/cart/ActiveTrials.vue?vue&type=template&id=b05a214e&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/steps/cart/ActiveTrialsTableRow.vue?vue&type=template&id=6d1074c9&
var ActiveTrialsTableRowvue_type_template_id_6d1074c9_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('tr',[_c('td',{staticClass:"thin"},[_c('div',{staticClass:"plugin-icon"},[(_vm.activeTrial.iconUrl)?_c('img',{attrs:{"src":_vm.activeTrial.iconUrl,"height":"40","width":"40"}}):_c('div',{staticClass:"default-icon"})])]),_vm._v(" "),_c('td',{staticClass:"item-name"},[_c('a',{attrs:{"title":_vm.activeTrial.name},on:{"click":function($event){$event.preventDefault();return _vm.navigateToPlugin.apply(null, arguments)}}},[_c('strong',[_vm._v(_vm._s(_vm.activeTrial.name))])]),_vm._v(" "),(_vm.activeTrial.editionName && _vm.activeTrial.showEditionBadge)?_c('edition-badge',{attrs:{"name":_vm.activeTrial.editionName}}):_vm._e()],1),_vm._v(" "),_c('td',[(_vm.activeTrial.price)?[(_vm.activeTrial.discountPrice)?[_c('del',{staticClass:"mr-1"},[_vm._v(_vm._s(_vm._f("currency")(_vm.activeTrial.price)))]),_vm._v(" "),_c('strong',[_vm._v(_vm._s(_vm._f("currency")((_vm.activeTrial.discountPrice))))])]:[_c('strong',[_vm._v(_vm._s(_vm._f("currency")(_vm.activeTrial.price)))])]]:_vm._e()],2),_vm._v(" "),_c('td',{staticClass:"tw-w-1/4"},[_c('div',{staticClass:"tw-text-right"},[(!_vm.addToCartLoading)?[_c('a',{class:{
                    'disabled hover:no-underline': _vm.activeTrial.licenseMismatched
                },attrs:{"loading":_vm.addToCartLoading},on:{"click":function($event){return _vm.addToCart()}}},[_vm._v(_vm._s(_vm._f("t")("Add to cart",'app')))])]:[_c('spinner',{attrs:{"size":"sm"}})]],2)])])}
var ActiveTrialsTableRowvue_type_template_id_6d1074c9_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/modal/steps/cart/ActiveTrialsTableRow.vue?vue&type=template&id=6d1074c9&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/steps/cart/ActiveTrialsTableRow.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var ActiveTrialsTableRowvue_type_script_lang_js_ = ({
  components: {
    EditionBadge: EditionBadge
  },
  props: ['activeTrial'],
  data: function data() {
    return {
      addToCartLoading: false
    };
  },
  methods: {
    addToCart: function addToCart() {
      var _this = this;

      this.addToCartLoading = true;
      var item = {
        type: this.activeTrial.type,
        edition: this.activeTrial.editionHandle
      };

      if (this.activeTrial.type === 'plugin-edition') {
        item.plugin = this.activeTrial.pluginHandle;
      }

      this.$store.dispatch('cart/addToCart', [item]).then(function () {
        _this.addToCartLoading = false;
      })["catch"](function (response) {
        _this.addToCartLoading = false;
        var errorMessage = response.errors && response.errors[0] && response.errors[0].message ? response.errors[0].message : 'Couldn’t add item to cart.';

        _this.$root.displayError(errorMessage);
      });
    },
    navigateToPlugin: function navigateToPlugin() {
      var path = this.activeTrial.navigateTo;
      this.$root.closeModal();

      if (this.$route.path !== path) {
        this.$router.push({
          path: path
        });
      }
    }
  }
});
// CONCATENATED MODULE: ./js/components/modal/steps/cart/ActiveTrialsTableRow.vue?vue&type=script&lang=js&
 /* harmony default export */ var cart_ActiveTrialsTableRowvue_type_script_lang_js_ = (ActiveTrialsTableRowvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/modal/steps/cart/ActiveTrialsTableRow.vue





/* normalize component */

var ActiveTrialsTableRow_component = Object(componentNormalizer["a" /* default */])(
  cart_ActiveTrialsTableRowvue_type_script_lang_js_,
  ActiveTrialsTableRowvue_type_template_id_6d1074c9_render,
  ActiveTrialsTableRowvue_type_template_id_6d1074c9_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ActiveTrialsTableRow = (ActiveTrialsTableRow_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/steps/cart/ActiveTrials.vue?vue&type=script&lang=js&
function ActiveTrialsvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function ActiveTrialsvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ActiveTrialsvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { ActiveTrialsvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ActiveTrialsvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function ActiveTrialsvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//



/* harmony default export */ var ActiveTrialsvue_type_script_lang_js_ = ({
  mixins: [licenses],
  components: {
    ActiveTrialsTableRow: ActiveTrialsTableRow
  },
  computed: ActiveTrialsvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapGetters"])({
    getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition',
    pendingActiveTrials: 'cart/pendingActiveTrials'
  })),
  methods: {
    addAllTrialsToCart: function addAllTrialsToCart() {
      var _this = this;

      this.$store.dispatch('cart/addAllTrialsToCart')["catch"](function () {
        _this.$root.displayError(_this.$options.filters.t('Couldn’t add all items to the cart.', 'app'));
      });
    }
  }
});
// CONCATENATED MODULE: ./js/components/modal/steps/cart/ActiveTrials.vue?vue&type=script&lang=js&
 /* harmony default export */ var cart_ActiveTrialsvue_type_script_lang_js_ = (ActiveTrialsvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/modal/steps/cart/ActiveTrials.vue





/* normalize component */

var ActiveTrials_component = Object(componentNormalizer["a" /* default */])(
  cart_ActiveTrialsvue_type_script_lang_js_,
  ActiveTrialsvue_type_template_id_b05a214e_render,
  ActiveTrialsvue_type_template_id_b05a214e_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ActiveTrials = (ActiveTrials_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/steps/Cart.vue?vue&type=script&lang=js&
function Cartvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function Cartvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { Cartvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { Cartvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { Cartvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function Cartvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* global Craft */




/* harmony default export */ var Cartvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      activeTrialsLoading: true,
      loadingItems: {},
      loadingRemoveFromCart: {},
      loadingCheckout: false
    };
  },
  components: {
    ActiveTrials: ActiveTrials,
    EditionBadge: EditionBadge,
    Step: Step
  },
  computed: Cartvue_type_script_lang_js_objectSpread(Cartvue_type_script_lang_js_objectSpread(Cartvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    activeTrialPlugins: function activeTrialPlugins(state) {
      return state.cart.activeTrialPlugins;
    },
    cart: function cart(state) {
      return state.cart.cart;
    },
    craftLogo: function craftLogo(state) {
      return state.craft.craftLogo;
    },
    expiryDateOptions: function expiryDateOptions(state) {
      return state.pluginStore.expiryDateOptions;
    }
  })), Object(external_Vuex_["mapGetters"])({
    cartItems: 'cart/cartItems',
    cartItemsData: 'cart/cartItemsData',
    getPluginLicenseInfo: 'craft/getPluginLicenseInfo'
  })), {}, {
    selectedExpiryDates: {
      get: function get() {
        return JSON.parse(JSON.stringify(this.$store.state.cart.selectedExpiryDates));
      },
      set: function set(newValue) {
        this.$store.commit('cart/updateSelectedExpiryDates', newValue);
      }
    }
  }),
  methods: Cartvue_type_script_lang_js_objectSpread(Cartvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapActions"])({
    removeFromCart: 'cart/removeFromCart'
  })), {}, {
    itemExpiryDateOptions: function itemExpiryDateOptions(itemKey) {
      var item = this.cartItems[itemKey];
      var renewalPrice = item.lineItem.purchasable.renewalPrice;
      var options = [];
      var selectedOption = 0;
      this.expiryDateOptions.forEach(function (option, key) {
        if (option === item.lineItem.options.expiryDate) {
          selectedOption = key;
        }
      });

      for (var i = 0; i < this.expiryDateOptions.length; i++) {
        var expiryDateOption = this.expiryDateOptions[i];
        var optionValue = expiryDateOption[0];
        var date = Craft.formatDate(expiryDateOption[1]);
        var label = this.$options.filters.t("Updates until {date}", 'app', {
          date: date
        });
        var price = renewalPrice * (i - selectedOption);

        if (price !== 0) {
          var sign = '';

          if (price > 0) {
            sign = '+';
          }

          price = this.$options.filters.currency(price);
          label = this.$options.filters.t("Updates until {date} ({sign}{price})", 'app', {
            date: date,
            sign: sign,
            price: price
          });
        }

        options.push({
          label: label,
          value: optionValue
        });
      }

      return options;
    },
    itemLoading: function itemLoading(itemKey) {
      if (!this.loadingItems[itemKey]) {
        return false;
      }

      return true;
    },
    onSelectedExpiryDateChange: function onSelectedExpiryDateChange(itemKey) {
      var _this = this;

      this.$set(this.loadingItems, itemKey, true);
      var item = this.cartItemsData[itemKey];
      item.expiryDate = this.selectedExpiryDates[itemKey];
      this.$store.dispatch('cart/updateItem', {
        itemKey: itemKey,
        item: item
      }).then(function () {
        _this.$delete(_this.loadingItems, itemKey);
      });
    },
    payment: function payment() {
      console.log('Redirect to Craft Console’s cart');
    },
    removeFromCart: function removeFromCart(itemKey) {
      var _this2 = this;

      this.$set(this.loadingRemoveFromCart, itemKey, true);
      this.$store.dispatch('cart/removeFromCart', itemKey).then(function () {
        _this2.$delete(_this2.loadingRemoveFromCart, itemKey);
      })["catch"](function (response) {
        _this2.$delete(_this2.loadingRemoveFromCart, itemKey);

        var errorMessage = response.errors && response.errors[0] && response.errors[0].message ? response.errors[0].message : 'Couldn’t remove item from cart.';

        _this2.$root.displayError(errorMessage);
      });
    },
    removeFromCartLoading: function removeFromCartLoading(itemKey) {
      if (!this.loadingRemoveFromCart[itemKey]) {
        return false;
      }

      return true;
    },
    pluginLicenseInfo: function pluginLicenseInfo(pluginHandle) {
      return this.getPluginLicenseInfo(pluginHandle);
    }
  }),
  mounted: function mounted() {
    var _this3 = this;

    this.$store.dispatch('cart/getActiveTrials').then(function () {
      _this3.activeTrialsLoading = false;
    })["catch"](function () {
      _this3.activeTrialsLoading = false;
    });
  }
});
// CONCATENATED MODULE: ./js/components/modal/steps/Cart.vue?vue&type=script&lang=js&
 /* harmony default export */ var steps_Cartvue_type_script_lang_js_ = (Cartvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/modal/steps/Cart.vue?vue&type=style&index=0&lang=scss&
var Cartvue_type_style_index_0_lang_scss_ = __webpack_require__(201);

// CONCATENATED MODULE: ./js/components/modal/steps/Cart.vue






/* normalize component */

var Cart_component = Object(componentNormalizer["a" /* default */])(
  steps_Cartvue_type_script_lang_js_,
  Cartvue_type_template_id_2d2314b3_render,
  Cartvue_type_template_id_2d2314b3_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Cart = (Cart_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/modal/Modal.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//

/* global Garnish */


/* harmony default export */ var Modalvue_type_script_lang_js_ = ({
  components: {
    Cart: Cart
  },
  props: ['pluginId', 'show'],
  data: function data() {
    return {
      modal: null
    };
  },
  computed: {
    modalStep: function modalStep() {
      return this.$root.modalStep;
    }
  },
  watch: {
    show: function show(_show) {
      if (_show) {
        this.modal.show();
      } else {
        this.modal.hide();
      }
    }
  },
  mounted: function mounted() {
    var $this = this;
    this.modal = new Garnish.Modal(this.$refs.pluginstoremodal, {
      autoShow: false,
      resizable: true,
      onHide: function onHide() {
        $this.$emit('update:show', false);
      }
    });
  }
});
// CONCATENATED MODULE: ./js/components/modal/Modal.vue?vue&type=script&lang=js&
 /* harmony default export */ var modal_Modalvue_type_script_lang_js_ = (Modalvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/modal/Modal.vue?vue&type=style&index=0&lang=scss&
var Modalvue_type_style_index_0_lang_scss_ = __webpack_require__(203);

// CONCATENATED MODULE: ./js/components/modal/Modal.vue






/* normalize component */

var Modal_component = Object(componentNormalizer["a" /* default */])(
  modal_Modalvue_type_script_lang_js_,
  Modalvue_type_template_id_99e7d010_render,
  Modalvue_type_template_id_99e7d010_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Modal = (Modal_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./App.vue?vue&type=template&id=1af68e1e&
var lib_vue_loader_options_Appvue_type_template_id_1af68e1e_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-wrapper"},[_c('transition',{attrs:{"name":"fade"}},[(_vm.showingScreenshotModal)?_c('screenshot-modal'):_vm._e()],1),_vm._v(" "),(_vm.$root.pluginStoreDataLoaded)?[_c('sidebar'),_vm._v(" "),_c('div',{staticClass:"ps-main",on:{"&scroll":function($event){return _vm.onViewScroll.apply(null, arguments)}}},[_c('router-view',{key:_vm.$route.fullPath})],1)]:[_c('status-message',{attrs:{"error":_vm.$root.pluginStoreDataError,"message":_vm.$root.statusMessage}})],_vm._v(" "),_c('modal',{attrs:{"show":_vm.$root.showModal,"plugin-id":_vm.$root.pluginId},on:{"update:show":function($event){return _vm.$set(_vm.$root, "showModal", $event)}}})],2)}
var lib_vue_loader_options_Appvue_type_template_id_1af68e1e_staticRenderFns = []


// CONCATENATED MODULE: ./App.vue?vue&type=template&id=1af68e1e&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/Sidebar.vue?vue&type=template&id=5e5fb078&scoped=true&
var Sidebarvue_type_template_id_5e5fb078_scoped_true_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"ps-sidebar"},[_c('plugin-search'),_vm._v(" "),_c('category-selector'),_vm._v(" "),_c('ul',{staticClass:"categories"},[(_vm.CraftEdition < _vm.CraftPro || _vm.licensedEdition < _vm.CraftPro)?_c('li',[_c('router-link',{attrs:{"to":"/upgrade-craft"}},[_c('img',{attrs:{"src":"data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+CiAgPGcgZmlsbD0ibm9uZSI+CiAgICA8cmVjdCB3aWR0aD0iNDAuOTA5IiBoZWlnaHQ9IjQwLjkwOSIgeD0iMjkuNTQ1IiB5PSIyOS41NDUiIGZpbGw9IiNGRkYiLz4KICAgIDxwYXRoIGZpbGw9IiNFNTQyMkIiIGQ9Ik04OS40NzM2ODQyLDAgTDEwLjUyNjMxNTgsMCBDNC42NzgzNjI1NywwIDAsNC42NzgzNjI1NyAwLDEwLjUyNjMxNTggTDAsODkuNDczNjg0MiBDMCw5NS4zMjE2Mzc0IDQuNjc4MzYyNTcsMTAwIDEwLjUyNjMxNTgsMTAwIEw4OS40NzM2ODQyLDEwMCBDOTUuMjA0Njc4NCwxMDAgMTAwLDk1LjMyMTYzNzQgMTAwLDg5LjQ3MzY4NDIgTDEwMCwxMC41MjYzMTU4IEMxMDAsNC42NzgzNjI1NyA5NS4zMjE2Mzc0LDAgODkuNDczNjg0MiwwIE02MCw1Ni42MDgxODcxIEw2NC42NzgzNjI2LDYxLjk4ODMwNDEgQzU5Ljc2NjA4MTksNjUuOTY0OTEyMyA1NC4xNTIwNDY4LDY4LjE4NzEzNDUgNDguNTM4MDExNyw2OC4xODcxMzQ1IEMzNy40MjY5MDA2LDY4LjE4NzEzNDUgMzAuNDA5MzU2Nyw2MC44MTg3MTM1IDMyLjA0Njc4MzYsNTAuNDA5MzU2NyBDMzMuNjg0MjEwNSw0MCA0My4xNTc4OTQ3LDMyLjYzMTU3ODkgNTQuMjY5MDA1OCwzMi42MzE1Nzg5IEM1OS42NDkxMjI4LDMyLjYzMTU3ODkgNjQuNjc4MzYyNiwzNC43MzY4NDIxIDY4LjE4NzEzNDUsMzguNTk2NDkxMiBMNjEuNjM3NDI2OSw0My45NzY2MDgyIEM1OS43NjYwODE5LDQxLjUyMDQ2NzggNTYuNjA4MTg3MSwzOS44ODMwNDA5IDUzLjA5OTQxNTIsMzkuODgzMDQwOSBDNDYuNDMyNzQ4NSwzOS44ODMwNDA5IDQxLjI4NjU0OTcsNDQuMjEwNTI2MyA0MC4yMzM5MTgxLDUwLjQwOTM1NjcgQzM5LjI5ODI0NTYsNTYuNjA4MTg3MSA0My4wNDA5MzU3LDYwLjkzNTY3MjUgNDkuODI0NTYxNCw2MC45MzU2NzI1IEM1My4wOTk0MTUyLDYwLjkzNTY3MjUgNTYuNjA4MTg3MSw1OS42NDkxMjI4IDYwLDU2LjYwODE4NzEgWiIvPgogIDwvZz4KPC9zdmc+Cg=="}}),_vm._v("\n                "+_vm._s(_vm._f("t")("Upgrade Craft CMS",'app'))+"\n            ")])],1):_vm._e(),_vm._v(" "),_vm._l((_vm.categories),function(category){return _c('li',{key:category.id},[_c('router-link',{attrs:{"to":'/categories/'+category.id}},[_c('img',{attrs:{"src":category.iconUrl}}),_vm._v("\n                "+_vm._s(category.title)+"\n            ")])],1)})],2)],1)}
var Sidebarvue_type_template_id_5e5fb078_scoped_true_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/Sidebar.vue?vue&type=template&id=5e5fb078&scoped=true&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/CategorySelector.vue?vue&type=template&id=756ced68&scoped=true&
var CategorySelectorvue_type_template_id_756ced68_scoped_true_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',[_c('a',{staticClass:"category-selector-btn",attrs:{"href":"#"},on:{"click":function($event){$event.preventDefault();_vm.showCategorySelector = !_vm.showCategorySelector}}},[_vm._v("All categories")]),_vm._v(" "),_c('div',{staticClass:"category-selector",class:{ hidden: !_vm.showCategorySelector }},[_c('div',{staticClass:"category-selector-header"},[_c('a',{attrs:{"href":"#"},on:{"click":function($event){$event.preventDefault();_vm.showCategorySelector = false}}},[_vm._v("Hide categories")])]),_vm._v(" "),_c('div',{staticClass:"category-selector-body"},[_c('ul',{staticClass:"categories"},[(_vm.CraftEdition < _vm.CraftPro || _vm.licensedEdition < _vm.CraftPro)?_c('li',[_c('router-link',{attrs:{"to":"/upgrade-craft"}},[_c('img',{attrs:{"src":"data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHdpZHRoPSIxMDBweCIgaGVpZ2h0PSIxMDBweCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+ICAgICAgICA8dGl0bGU+Y3JhZnQ8L3RpdGxlPiAgICA8ZGVzYz5DcmVhdGVkIHdpdGggU2tldGNoLjwvZGVzYz4gICAgPGRlZnM+PC9kZWZzPiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4gICAgICAgIDxnIGlkPSJjcmFmdCI+ICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbCIgZmlsbD0iI0RBNUE0NyIgY3g9IjUwIiBjeT0iNTAiIHI9IjUwIj48L2NpcmNsZT4gICAgICAgICAgICA8cGF0aCBkPSJNNjUuMTMxNDQwNCwzNC4yNjI5Njc5IEM2NS40MTUyMjQxLDM0LjQ3NTEzMDEgNjUuNjgyNzkxNywzNC42OTk0NTQ0IDY1Ljk0NDk1MzksMzQuOTI3ODMyOCBMNzAuMTgyNzkxNywzMS42MzA1MzU1IEw3MC4zMTUyMjQxLDMxLjQ2MDI2NTIgQzY5LjY2MDE5NjUsMzAuODAwOTk5IDY4Ljk1ODM2NzUsMzAuMTg5OTQ3IDY4LjIxNTIyNDEsMjkuNjMxODg2OSBDNTguNDg5NTQ4NSwyMi4zNTQ4NTk4IDQzLjc5MjI1MTIsMjUuNDAwODA1OCAzNS4zODgxOTcxLDM2LjQzNTk0MDkgQzI2Ljk4OTU0ODUsNDcuNDY5NzI0NyAyOC4wNjM4NzI4LDYyLjMxMDI2NTIgMzcuNzg4MTk3MSw2OS41ODk5OTUgQzQ1LjczMDA4OSw3NS41MzA1MzU1IDU2Ljk4Mjc5MTcsNzQuNTg3MjkyMyA2NS40MTkyNzgyLDY4LjAzNTk0MDkgTDY1LjQxMjUyMTQsNjguMDE5NzI0NyBMNjEuMzc3Mzg2Myw2NC44ODQ1ODk2IEM1NS4xMjQ2ODM2LDY4Ljg2ODM3MzMgNDcuMzY5Mjc4Miw2OS4xNTQ4NTk4IDQxLjc1ODQ2NzQsNjQuOTU3NTYyNSBDMzQuMjg1NDk0NCw1OS4zNjgzNzMzIDMzLjQ2MTE3MDEsNDcuOTY1NjcwNiAzOS45MTY1NzU1LDM5LjQ4OTk5NSBDNDYuMzY5Mjc4MiwzMS4wMTI5Njc5IDU3LjY1OTgxODcsMjguNjczNzc4OCA2NS4xMzAwODksMzQuMjYyOTY3OSBMNjUuMTMxNDQwNCwzNC4yNjI5Njc5IFoiIGlkPSJQYXRoIiBmaWxsPSIjRkZGRkZGIj48L3BhdGg+ICAgICAgICA8L2c+ICAgIDwvZz48L3N2Zz4="}}),_vm._v("\n                        "+_vm._s(_vm._f("t")("Upgrade Craft CMS",'app'))+"\n                    ")])],1):_vm._e(),_vm._v(" "),_vm._l((_vm.categories),function(category,key){return _c('li',{key:key},[_c('router-link',{attrs:{"to":'/categories/'+category.id},nativeOn:{"click":function($event){_vm.showCategorySelector = false}}},[_c('img',{attrs:{"src":category.iconUrl}}),_vm._v("\n                        "+_vm._s(category.title)+"\n                    ")])],1)})],2)])])])}
var CategorySelectorvue_type_template_id_756ced68_scoped_true_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/CategorySelector.vue?vue&type=template&id=756ced68&scoped=true&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/CategorySelector.vue?vue&type=script&lang=js&
function CategorySelectorvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function CategorySelectorvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { CategorySelectorvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { CategorySelectorvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { CategorySelectorvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function CategorySelectorvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var CategorySelectorvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      showCategorySelector: false
    };
  },
  computed: CategorySelectorvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    categories: function categories(state) {
      return state.pluginStore.categories;
    },
    CraftEdition: function CraftEdition(state) {
      return state.craft.CraftEdition;
    },
    CraftPro: function CraftPro(state) {
      return state.craft.CraftPro;
    },
    licensedEdition: function licensedEdition(state) {
      return state.craft.licensedEdition;
    }
  }))
});
// CONCATENATED MODULE: ./js/components/CategorySelector.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_CategorySelectorvue_type_script_lang_js_ = (CategorySelectorvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/CategorySelector.vue?vue&type=style&index=0&id=756ced68&lang=scss&scoped=true&
var CategorySelectorvue_type_style_index_0_id_756ced68_lang_scss_scoped_true_ = __webpack_require__(205);

// CONCATENATED MODULE: ./js/components/CategorySelector.vue






/* normalize component */

var CategorySelector_component = Object(componentNormalizer["a" /* default */])(
  components_CategorySelectorvue_type_script_lang_js_,
  CategorySelectorvue_type_template_id_756ced68_scoped_true_render,
  CategorySelectorvue_type_template_id_756ced68_scoped_true_staticRenderFns,
  false,
  null,
  "756ced68",
  null
  
)

/* harmony default export */ var CategorySelector = (CategorySelector_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginSearch.vue?vue&type=template&id=45840a3f&
var PluginSearchvue_type_template_id_45840a3f_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"tw-mb-4"},[_c('form',{on:{"submit":function($event){$event.preventDefault();return _vm.search()}}},[_c('div',{staticClass:"ps-search tw-relative tw-flex tw-w-full"},[_c('div',{staticClass:"tw-absolute tw-inset-y-0 tw-flex tw-items-center tw-pl-3 tw-z-10 tw-text-gray-500"},[_c('icon',{attrs:{"icon":"search"}})],1),_vm._v(" "),_c('input',{directives:[{name:"model",rawName:"v-model",value:(_vm.searchQuery),expression:"searchQuery"}],staticClass:"tw-flex-1 tw-w-full tw-pl-9 tw-pr-3 tw-py-2 tw-rounded tw-border-solid tw-border-gray-300 tw-text-sm",attrs:{"type":"text","id":"searchQuery","placeholder":_vm._f("t")('Search plugins','app'),"autocomplete":"off"},domProps:{"value":(_vm.searchQuery)},on:{"input":function($event){if($event.target.composing){ return; }_vm.searchQuery=$event.target.value}}})])])])}
var PluginSearchvue_type_template_id_45840a3f_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/PluginSearch.vue?vue&type=template&id=45840a3f&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/PluginSearch.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ var PluginSearchvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      searchQuery: ''
    };
  },
  methods: {
    search: function search() {
      if (this.searchQuery) {
        this.$store.commit('app/updateSearchQuery', this.searchQuery);
        this.$router.push({
          path: '/search'
        });
      }
    }
  }
});
// CONCATENATED MODULE: ./js/components/PluginSearch.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_PluginSearchvue_type_script_lang_js_ = (PluginSearchvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/PluginSearch.vue





/* normalize component */

var PluginSearch_component = Object(componentNormalizer["a" /* default */])(
  components_PluginSearchvue_type_script_lang_js_,
  PluginSearchvue_type_template_id_45840a3f_render,
  PluginSearchvue_type_template_id_45840a3f_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var PluginSearch = (PluginSearch_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/Sidebar.vue?vue&type=script&lang=js&
function Sidebarvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function Sidebarvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { Sidebarvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { Sidebarvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { Sidebarvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function Sidebarvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//



/* harmony default export */ var Sidebarvue_type_script_lang_js_ = ({
  components: {
    CategorySelector: CategorySelector,
    PluginSearch: PluginSearch
  },
  computed: Sidebarvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    categories: function categories(state) {
      return state.pluginStore.categories;
    },
    CraftEdition: function CraftEdition(state) {
      return state.craft.CraftEdition;
    },
    CraftPro: function CraftPro(state) {
      return state.craft.CraftPro;
    },
    licensedEdition: function licensedEdition(state) {
      return state.craft.licensedEdition;
    }
  }))
});
// CONCATENATED MODULE: ./js/components/Sidebar.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_Sidebarvue_type_script_lang_js_ = (Sidebarvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/Sidebar.vue?vue&type=style&index=0&id=5e5fb078&lang=scss&scoped=true&
var Sidebarvue_type_style_index_0_id_5e5fb078_lang_scss_scoped_true_ = __webpack_require__(207);

// CONCATENATED MODULE: ./js/components/Sidebar.vue






/* normalize component */

var Sidebar_component = Object(componentNormalizer["a" /* default */])(
  components_Sidebarvue_type_script_lang_js_,
  Sidebarvue_type_template_id_5e5fb078_scoped_true_render,
  Sidebarvue_type_template_id_5e5fb078_scoped_true_staticRenderFns,
  false,
  null,
  "5e5fb078",
  null
  
)

/* harmony default export */ var Sidebar = (Sidebar_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ScreenshotModal.vue?vue&type=template&id=7b1e94b5&
var ScreenshotModalvue_type_template_id_7b1e94b5_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{ref:"screenshotModal",attrs:{"id":"screenshot-modal"},on:{"keydown":function($event){if(!$event.type.indexOf('key')&&_vm._k($event.keyCode,"esc",27,$event.key,["Esc","Escape"])){ return null; }return _vm.close.apply(null, arguments)}}},[_c('a',{staticClass:"close",on:{"click":_vm.close}},[_vm._v("×")]),_vm._v(" "),(_vm.screenshotModalImages)?_c('div',{ref:"carousel",staticClass:"carousel"},[_c('swiper',{ref:"screenshotModalSwiper",attrs:{"options":_vm.swiperOption}},_vm._l((_vm.screenshotModalImages),function(imageUrl,key){return _c('swiper-slide',{key:key},[_c('div',{staticClass:"screenshot"},[_c('div',{staticClass:"swiper-zoom-container"},[_c('img',{attrs:{"src":imageUrl}})])])])}),1),_vm._v(" "),(_vm.screenshotModalImages.length > 1)?[_c('div',{staticClass:"swiper-button-prev"},[_c('icon',{attrs:{"icon":"chevron-left","size":"xl"}})],1),_vm._v(" "),_c('div',{staticClass:"swiper-button-next"},[_c('icon',{attrs:{"icon":"chevron-right","size":"xl"}})],1),_vm._v(" "),_c('div',{staticClass:"pagination-wrapper"},[_c('div',{staticClass:"pagination-content"},[_c('div',{class:'swiper-pagination',attrs:{"slot":"pagination"},slot:"pagination"})])])]:_vm._e()],2):_vm._e()])}
var ScreenshotModalvue_type_template_id_7b1e94b5_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ScreenshotModal.vue?vue&type=template&id=7b1e94b5&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ScreenshotModal.vue?vue&type=script&lang=js&
function ScreenshotModalvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function ScreenshotModalvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ScreenshotModalvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { ScreenshotModalvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ScreenshotModalvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function ScreenshotModalvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ var ScreenshotModalvue_type_script_lang_js_ = ({
  data: function data() {
    return {
      ratio: '4:3'
    };
  },
  computed: ScreenshotModalvue_type_script_lang_js_objectSpread(ScreenshotModalvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    screenshotModalImageKey: function screenshotModalImageKey(state) {
      return state.app.screenshotModalImageKey;
    },
    screenshotModalImages: function screenshotModalImages(state) {
      return state.app.screenshotModalImages;
    }
  })), {}, {
    swiper: function swiper() {
      return this.$refs.screenshotModalSwiper.swiper;
    },
    swiperOption: function swiperOption() {
      return {
        initialSlide: 0,
        loop: false,
        pagination: {
          el: '.swiper-pagination',
          clickable: true
        },
        keyboard: true,
        zoom: true,
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev'
        }
      };
    }
  }),
  methods: {
    close: function close() {
      this.$store.commit('app/updateShowingScreenshotModal', false);
    },
    handleEscapeKey: function handleEscapeKey(e) {
      if (e.keyCode === 27) {
        this.close();
      }
    },
    handleResize: function handleResize() {
      if (this.screenshotModalImages.length === 0) {
        return;
      }

      var ratio = this.ratio.split(':');
      var ratioWidth = ratio[0];
      var ratioHeight = ratio[1];
      var $carousel = this.$refs.carousel;
      var carouselWidth = $carousel.offsetWidth;
      var carouselHeight = $carousel.offsetHeight;
      var imageElements = $carousel.getElementsByTagName("img");
      var maxHeight;

      if (this.inline) {
        maxHeight = carouselWidth * ratioHeight / ratioWidth;
      } else {
        if (carouselWidth > carouselHeight) {
          maxHeight = carouselWidth * ratioHeight / ratioWidth;
        } else {
          maxHeight = carouselHeight * ratioWidth / ratioHeight;
        }

        if (carouselHeight > 0 && maxHeight > carouselHeight) {
          maxHeight = carouselHeight;
        }
      }

      for (var i = 0; i < imageElements.length; i++) {
        var imageElement = imageElements[i];
        imageElement.style.maxHeight = maxHeight + 'px';
      }
    }
  },
  mounted: function mounted() {
    this.swiper.slideTo(this.screenshotModalImageKey, 0);
    window.addEventListener('resize', this.handleResize);
    this.handleResize();
  },
  created: function created() {
    window.addEventListener('keydown', this.handleEscapeKey);
  },
  beforeDestroy: function beforeDestroy() {
    this.swiper.destroy(true, false);
    window.removeEventListener('resize', this.handleResize);
    window.removeEventListener('keydown', this.handleEscapeKey);
  }
});
// CONCATENATED MODULE: ./js/components/ScreenshotModal.vue?vue&type=script&lang=js&
 /* harmony default export */ var components_ScreenshotModalvue_type_script_lang_js_ = (ScreenshotModalvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/ScreenshotModal.vue?vue&type=style&index=0&lang=scss&
var ScreenshotModalvue_type_style_index_0_lang_scss_ = __webpack_require__(209);

// CONCATENATED MODULE: ./js/components/ScreenshotModal.vue






/* normalize component */

var ScreenshotModal_component = Object(componentNormalizer["a" /* default */])(
  components_ScreenshotModalvue_type_script_lang_js_,
  ScreenshotModalvue_type_template_id_7b1e94b5_render,
  ScreenshotModalvue_type_template_id_7b1e94b5_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ScreenshotModal = (ScreenshotModal_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./App.vue?vue&type=script&lang=js&
function lib_vue_loader_options_Appvue_type_script_lang_js_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function lib_vue_loader_options_Appvue_type_script_lang_js_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { lib_vue_loader_options_Appvue_type_script_lang_js_ownKeys(Object(source), true).forEach(function (key) { lib_vue_loader_options_Appvue_type_script_lang_js_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { lib_vue_loader_options_Appvue_type_script_lang_js_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function lib_vue_loader_options_Appvue_type_script_lang_js_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//





/* harmony default export */ var lib_vue_loader_options_Appvue_type_script_lang_js_ = ({
  components: {
    Sidebar: Sidebar,
    Modal: Modal,
    StatusMessage: StatusMessage,
    ScreenshotModal: ScreenshotModal
  },
  computed: lib_vue_loader_options_Appvue_type_script_lang_js_objectSpread({}, Object(external_Vuex_["mapState"])({
    showingScreenshotModal: function showingScreenshotModal(state) {
      return state.app.showingScreenshotModal;
    }
  })),
  methods: {
    onViewScroll: function onViewScroll($event) {
      this.$root.$emit('viewScroll', $event);
    }
  },
  mounted: function mounted() {
    var _this = this;

    window.addEventListener('resize', function ($event) {
      _this.$root.$emit('windowResize', $event);
    });
    window.addEventListener('scroll', function ($event) {
      _this.$root.$emit('windowScroll', $event);
    });
  }
});
// CONCATENATED MODULE: ./App.vue?vue&type=script&lang=js&
 /* harmony default export */ var Appvue_type_script_lang_js_ = (lib_vue_loader_options_Appvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./App.vue?vue&type=style&index=0&lang=scss&
var Appvue_type_style_index_0_lang_scss_ = __webpack_require__(211);

// EXTERNAL MODULE: ./App.vue?vue&type=style&index=1&style=scss&lang=css&
var Appvue_type_style_index_1_style_scss_lang_css_ = __webpack_require__(213);

// CONCATENATED MODULE: ./App.vue







/* normalize component */

var App_component = Object(componentNormalizer["a" /* default */])(
  Appvue_type_script_lang_js_,
  lib_vue_loader_options_Appvue_type_template_id_1af68e1e_render,
  lib_vue_loader_options_Appvue_type_template_id_1af68e1e_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var App = (App_component.exports);
// EXTERNAL MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-awesome-swiper/dist/vue-awesome-swiper.js
var vue_awesome_swiper = __webpack_require__(81);
var vue_awesome_swiper_default = /*#__PURE__*/__webpack_require__.n(vue_awesome_swiper);

// EXTERNAL MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/swiper/dist/css/swiper.css
var swiper = __webpack_require__(216);

// CONCATENATED MODULE: ./js/plugins/vue-awesome-swiper.js



external_Vue_default.a.use(vue_awesome_swiper_default.a);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Btn.vue?vue&type=template&id=aa8dfb36&
var Btnvue_type_template_id_aa8dfb36_render = function () {
var _obj;
var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c(_vm.component,_vm._b({tag:"component",staticClass:"c-btn truncate",class:[( _obj = {
            small: _vm.small,
            large: _vm.large,
            block: _vm.block,
            outline: _vm.outline,
            loading: _vm.loading
        }, _obj[_vm.kind] = true, _obj['c-btn-icon'] =  _vm.icon && !_vm.$slots.default, _obj['group'] =  true, _obj['tw-inline-block tw-px-4 tw-py-2 tw-rounded-md'] =  true, _obj['tw-text-sm tw-leading-5 tw-no-underline'] =  true, _obj['tw-border tw-border-solid'] =  true, _obj['disabled:tw-opacity-50 disabled:tw-cursor-default'] =  true, _obj['tw-text-interactive-inverse'] =  (_vm.kind === 'primary' || _vm.kind === 'danger') && !_vm.outline, _obj['hover:tw-text-interactive-inverse'] =  (_vm.kind === 'primary' || _vm.kind === 'danger') && !_vm.outline, _obj['active:tw-text-interactive-inverse'] =  (_vm.kind === 'primary' || _vm.kind === 'danger') && !_vm.outline, _obj['tw-text-interactive'] =  _vm.kind === 'default', _obj['tw-border-interactive-primary'] =  _vm.kind === 'primary', _obj['tw-bg-interactive-primary'] =  _vm.kind === 'primary' && !_vm.outline, _obj['hover:tw-bg-interactive-primary-hover hover:tw-border-interactive-primary-hover'] =  _vm.kind === 'primary' && !_vm.outline, _obj['active:tw-bg-interactive-primary-active active:tw-border-interactive-primary-active'] =  _vm.kind === 'primary' && !_vm.outline, _obj['disabled:tw-bg-interactive-primary disabled:tw-border-interactive-primary'] =  _vm.kind === 'primary' && !_vm.outline, _obj['tw-text-interactive-primary'] =  _vm.kind === 'primary' && _vm.outline, _obj['hover:tw-bg-interactive-primary'] =  _vm.kind === 'primary' && _vm.outline, _obj['active:tw-bg-interactive-primary-active'] =  _vm.kind === 'primary' && _vm.outline, _obj['tw-border-interactive-secondary tw-text-interactive'] =  _vm.kind === 'secondary', _obj['hover:tw-cursor-pointer hover:tw-bg-interactive-secondary-hover hover:tw-border-interactive-secondary-hover hover:tw-no-underline'] =  _vm.kind === 'secondary', _obj['active:tw-cursor-pointer active:tw-bg-interactive-secondary-active active:tw-border-interactive-secondary-active'] =  _vm.kind === 'secondary', _obj['tw-bg-interactive-secondary'] =  _vm.kind === 'secondary' && !_vm.outline, _obj['tw-text-interactive'] =  _vm.kind === 'secondary' && !_vm.outline, _obj['tw-border-interactive-danger'] =  _vm.kind === 'danger', _obj['tw-bg-interactive-danger'] =  _vm.kind === 'danger' && !_vm.outline, _obj['hover:tw-bg-interactive-danger-hover hover:tw-border-interactive-danger-hover'] =  _vm.kind === 'danger' && !_vm.outline, _obj['active:tw-bg-interactive-danger-active active:tw-border-interactive-danger-active'] =  _vm.kind === 'danger' && !_vm.outline, _obj['disabled:tw-bg-interactive-danger disabled:tw-border-interactive-danger'] =  _vm.kind === 'danger' && !_vm.outline, _obj['tw-text-interactive-danger'] =  _vm.kind === 'danger' && _vm.outline, _obj['hover:tw-bg-interactive-danger'] =  _vm.kind === 'danger' && _vm.outline, _obj['active:tw-bg-interactive-danger-active'] =  _vm.kind === 'danger' && _vm.outline, _obj )],attrs:{"to":_vm.to,"href":_vm.href,"target":_vm.target,"type":_vm.computedType},on:{"click":function($event){return _vm.$emit('click')}}},'component',_vm.additionalAttributes,false),[(_vm.loading)?[_c('spinner',{attrs:{"animationClass":("border-" + _vm.animationColor + " group-hover:border-" + _vm.animationColorHover)}})]:_vm._e(),_vm._v(" "),_c('div',{staticClass:"c-btn-content"},[(_vm.icon && _vm.icon.length > 0)?_c('icon',{attrs:{"icon":_vm.icon,"size":"sm"}}):_vm._e(),_vm._v(" "),_vm._t("default"),_vm._v(" "),(_vm.trailingIcon && _vm.trailingIcon.length > 0)?_c('icon',{staticClass:"ml-1",attrs:{"icon":_vm.trailingIcon,"size":"sm"}}):_vm._e()],2)],2)}
var Btnvue_type_template_id_aa8dfb36_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/Btn.vue?vue&type=template&id=aa8dfb36&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Btn.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ var Btnvue_type_script_lang_js_ = ({
  name: 'Btn',
  props: {
    /**
     * 'button', 'submit', 'reset', or 'menu'
     */
    type: {
      type: String,
      "default": 'button'
    },

    /**
     * 'default', 'primary', or 'danger'
     */
    kind: {
      type: String,
      "default": 'secondary'
    },

    /**
     * Smaller version of button if set to `true`.
     */
    small: {
      type: Boolean,
      "default": false
    },

    /**
     * Larger version of button if set to `true`.
     */
    large: {
      type: Boolean,
      "default": false
    },

    /**
     * Block version of button if set to `true`.
     */
    block: {
      type: Boolean,
      "default": false
    },

    /**
     * Disabled version of button if set to `true`.
     */
    disabled: {
      type: Boolean,
      "default": false
    },

    /**
     * Outline version of button if set to `true`.
     */
    outline: {
      type: Boolean,
      "default": false
    },
    icon: {
      type: [String, Array],
      "default": null
    },
    trailingIcon: {
      type: String,
      "default": null
    },
    loading: {
      type: Boolean,
      "default": false
    },
    to: {
      type: String,
      "default": null
    },
    href: {
      type: String,
      "default": null
    },
    target: {
      type: String,
      "default": null
    }
  },
  computed: {
    additionalAttributes: function additionalAttributes() {
      var attrs = {};

      if (this.disabled) {
        attrs.disabled = true;
      }

      return attrs;
    },
    component: function component() {
      if (this.to !== null && this.to !== '') {
        return 'router-link';
      }

      if (this.href !== null && this.href !== '') {
        return 'a';
      }

      return 'button';
    },
    computedType: function computedType() {
      if (this.to !== null || this.href !== null) {
        return null;
      }

      return this.type;
    },
    animationColor: function animationColor() {
      return this.kind === 'secondary' ? 'interactive' : !this.outline ? 'text-inverse' : 'interactive-' + this.kind;
    },
    animationColorHover: function animationColorHover() {
      return this.kind === 'secondary' ? 'interactive' : 'text-inverse';
    }
  }
});
// CONCATENATED MODULE: ./js/components/ui/Btn.vue?vue&type=script&lang=js&
 /* harmony default export */ var ui_Btnvue_type_script_lang_js_ = (Btnvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/ui/Btn.vue?vue&type=style&index=0&lang=scss&
var Btnvue_type_style_index_0_lang_scss_ = __webpack_require__(218);

// CONCATENATED MODULE: ./js/components/ui/Btn.vue






/* normalize component */

var Btn_component = Object(componentNormalizer["a" /* default */])(
  ui_Btnvue_type_script_lang_js_,
  Btnvue_type_template_id_aa8dfb36_render,
  Btnvue_type_template_id_aa8dfb36_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Btn = (Btn_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Dropdown.vue?vue&type=template&id=6e6d7736&
var Dropdownvue_type_template_id_6e6d7736_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"c-dropdown",class:{
    'is-invalid': _vm.invalid,
    'tw-w-full': _vm.fullwidth,
    disabled: _vm.disabled,
}},[_c('select',{class:{
            'form-select sm:tw-text-sm sm:tw-leading-5 tw-ps-3 tw-pe-10 tw-rounded-md': true,
            'tw-w-full': _vm.fullwidth,
            'tw-border-danger': _vm.invalid,
            'tw-border-field': !_vm.invalid,
        },attrs:{"disabled":_vm.disabled},domProps:{"value":_vm.value},on:{"input":function($event){return _vm.$emit('input', $event.target.value)}}},_vm._l((_vm.options),function(option,key){return _c('option',{key:key,domProps:{"value":option.value}},[_vm._v(_vm._s(option.label))])}),0)])}
var Dropdownvue_type_template_id_6e6d7736_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/Dropdown.vue?vue&type=template&id=6e6d7736&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Dropdown.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ var Dropdownvue_type_script_lang_js_ = ({
  props: {
    disabled: {
      type: Boolean,
      "default": false
    },
    invalid: {
      type: Boolean,
      "default": false
    },
    fullwidth: {
      type: Boolean,
      "default": false
    },
    id: {
      type: String,
      "default": function _default() {
        return 'c-dropdown-id-' + Math.random().toString(36).substr(2, 9);
      }
    },
    options: {
      type: Array,
      "default": null
    },
    value: {
      type: [String, Number],
      "default": null
    }
  }
});
// CONCATENATED MODULE: ./js/components/ui/Dropdown.vue?vue&type=script&lang=js&
 /* harmony default export */ var ui_Dropdownvue_type_script_lang_js_ = (Dropdownvue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/ui/Dropdown.vue?vue&type=style&index=0&lang=scss&
var Dropdownvue_type_style_index_0_lang_scss_ = __webpack_require__(220);

// CONCATENATED MODULE: ./js/components/ui/Dropdown.vue






/* normalize component */

var Dropdown_component = Object(componentNormalizer["a" /* default */])(
  ui_Dropdownvue_type_script_lang_js_,
  Dropdownvue_type_template_id_6e6d7736_render,
  Dropdownvue_type_template_id_6e6d7736_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Dropdown = (Dropdown_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Icon.vue?vue&type=template&id=3cf4fae2&
var Iconvue_type_template_id_3cf4fae2_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c(_vm.computedComponent,{tag:"component",staticClass:"tw-w-4 tw-h-4 align-middle"})}
var Iconvue_type_template_id_3cf4fae2_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/Icon.vue?vue&type=template&id=3cf4fae2&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/BookIcon.vue?vue&type=template&id=b8d41046&
var BookIconvue_type_template_id_b8d41046_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{staticStyle:{"enable-background":"new 0 0 417 448"},attrs:{"version":"1.1","id":"Layer_1","xmlns":"http://www.w3.org/2000/svg","x":"0px","y":"0px","viewBox":"0 0 417 448","xml:space":"preserve","fill":"currentColor"}},[_c('path',{attrs:{"d":"M409.8,119.5c6.2,9,8,20.8,4.5,32.3l-68.8,226.5c-6.2,21.2-28.2,37.8-49.8,37.8H65c-25.5,0-52.8-20.2-62-46.2\n\tc-4-11.2-4-22.2-0.5-31.8c0.5-5,1.5-10,1.8-16c0.2-4-2-7.2-1.5-10.2c1-6,6.2-10.2,10.2-17c7.5-12.5,16-32.8,18.8-45.8\n\tc1.2-4.8-1.2-10.2,0-14.5c1.2-4.8,6-8.2,8.5-12.8C47,210.2,55.8,188,57,176.2c0.5-5.2-2-11-0.5-15c1.8-5.8,7.2-8.2,11-13.2\n\tc6-8.2,16-32,17.5-45.3c0.5-4.2-2-8.5-1.2-13c1-4.8,7-9.8,11-15.5c10.5-15.5,12.5-49.8,44.2-40.8l-0.2,0.8c4.2-1,8.5-2.2,12.8-2.2\n\th190.2c11.8,0,22.2,5.2,28.5,14c6.5,9,8,20.8,4.5,32.5L306.2,305c-11.8,38.5-18.2,47-50,47H39c-3.2,0-7.2,0.8-9.5,3.8\n\tc-2,3-2.2,5.2-0.2,10.8c5,14.5,22.2,17.5,36,17.5H296c9.2,0,20-5.2,22.8-14.2l75-246.8c1.5-4.8,1.5-9.8,1.2-14.2\n\tC400.8,111,406,114.5,409.8,119.5z M143.8,120c-1.5,4.5,1,8,5.5,8h152c4.2,0,9-3.5,10.5-8l5.2-16c1.5-4.5-1-8-5.5-8h-152\n\tc-4.2,0-9,3.5-10.5,8L143.8,120z M123,184c-1.5,4.5,1,8,5.5,8h152c4.2,0,9-3.5,10.5-8l5.2-16c1.5-4.5-1-8-5.5-8h-152\n\tc-4.2,0-9,3.5-10.5,8L123,184z"}})])}
var BookIconvue_type_template_id_b8d41046_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/BookIcon.vue?vue&type=template&id=b8d41046&

// CONCATENATED MODULE: ./js/components/ui/icons/BookIcon.vue

var BookIcon_script = {}


/* normalize component */

var BookIcon_component = Object(componentNormalizer["a" /* default */])(
  BookIcon_script,
  BookIconvue_type_template_id_b8d41046_render,
  BookIconvue_type_template_id_b8d41046_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var BookIcon = (BookIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/CheckIcon.vue?vue&type=template&id=192985d0&
var CheckIconvue_type_template_id_192985d0_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"version":"1.1","xmlns":"http://www.w3.org/2000/svg","width":"28","height":"28","viewBox":"0 0 28 28","fill":"currentColor"}},[_c('title',[_vm._v("check")]),_vm._v(" "),_c('path',{attrs:{"d":"M26.109 8.844c0 0.391-0.156 0.781-0.438 1.062l-13.438 13.438c-0.281 0.281-0.672 0.438-1.062 0.438s-0.781-0.156-1.062-0.438l-7.781-7.781c-0.281-0.281-0.438-0.672-0.438-1.062s0.156-0.781 0.438-1.062l2.125-2.125c0.281-0.281 0.672-0.438 1.062-0.438s0.781 0.156 1.062 0.438l4.594 4.609 10.25-10.266c0.281-0.281 0.672-0.438 1.062-0.438s0.781 0.156 1.062 0.438l2.125 2.125c0.281 0.281 0.438 0.672 0.438 1.062z"}})])}
var CheckIconvue_type_template_id_192985d0_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/CheckIcon.vue?vue&type=template&id=192985d0&

// CONCATENATED MODULE: ./js/components/ui/icons/CheckIcon.vue

var CheckIcon_script = {}


/* normalize component */

var CheckIcon_component = Object(componentNormalizer["a" /* default */])(
  CheckIcon_script,
  CheckIconvue_type_template_id_192985d0_render,
  CheckIconvue_type_template_id_192985d0_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var CheckIcon = (CheckIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/ChevronLeftIcon.vue?vue&type=template&id=28c40a56&
var ChevronLeftIconvue_type_template_id_28c40a56_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"xmlns":"http://www.w3.org/2000/svg","viewBox":"0 0 20 20","fill":"currentColor"}},[_c('path',{attrs:{"fill-rule":"evenodd","d":"M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z","clip-rule":"evenodd"}})])}
var ChevronLeftIconvue_type_template_id_28c40a56_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/ChevronLeftIcon.vue?vue&type=template&id=28c40a56&

// CONCATENATED MODULE: ./js/components/ui/icons/ChevronLeftIcon.vue

var ChevronLeftIcon_script = {}


/* normalize component */

var ChevronLeftIcon_component = Object(componentNormalizer["a" /* default */])(
  ChevronLeftIcon_script,
  ChevronLeftIconvue_type_template_id_28c40a56_render,
  ChevronLeftIconvue_type_template_id_28c40a56_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ChevronLeftIcon = (ChevronLeftIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/ChevronRightIcon.vue?vue&type=template&id=10e194d1&
var ChevronRightIconvue_type_template_id_10e194d1_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"xmlns":"http://www.w3.org/2000/svg","viewBox":"0 0 20 20","fill":"currentColor"}},[_c('path',{attrs:{"fill-rule":"evenodd","d":"M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z","clip-rule":"evenodd"}})])}
var ChevronRightIconvue_type_template_id_10e194d1_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/ChevronRightIcon.vue?vue&type=template&id=10e194d1&

// CONCATENATED MODULE: ./js/components/ui/icons/ChevronRightIcon.vue

var ChevronRightIcon_script = {}


/* normalize component */

var ChevronRightIcon_component = Object(componentNormalizer["a" /* default */])(
  ChevronRightIcon_script,
  ChevronRightIconvue_type_template_id_10e194d1_render,
  ChevronRightIconvue_type_template_id_10e194d1_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ChevronRightIcon = (ChevronRightIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/CopyIcon.vue?vue&type=template&id=638ee73e&
var CopyIconvue_type_template_id_638ee73e_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"aria-hidden":"true","focusable":"false","data-prefix":"fas","data-icon":"copy","role":"img","xmlns":"http://www.w3.org/2000/svg","viewBox":"0 0 448 512"}},[_c('path',{attrs:{"fill":"currentColor","d":"M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z"}})])}
var CopyIconvue_type_template_id_638ee73e_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/CopyIcon.vue?vue&type=template&id=638ee73e&

// CONCATENATED MODULE: ./js/components/ui/icons/CopyIcon.vue

var CopyIcon_script = {}


/* normalize component */

var CopyIcon_component = Object(componentNormalizer["a" /* default */])(
  CopyIcon_script,
  CopyIconvue_type_template_id_638ee73e_render,
  CopyIconvue_type_template_id_638ee73e_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var CopyIcon = (CopyIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/ExclamationCircleIcon.vue?vue&type=template&id=61802dc8&
var ExclamationCircleIconvue_type_template_id_61802dc8_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"xmlns":"http://www.w3.org/2000/svg","viewBox":"0 0 20 20","fill":"currentColor"}},[_c('path',{attrs:{"fill-rule":"evenodd","d":"M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z","clip-rule":"evenodd"}})])}
var ExclamationCircleIconvue_type_template_id_61802dc8_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/ExclamationCircleIcon.vue?vue&type=template&id=61802dc8&

// CONCATENATED MODULE: ./js/components/ui/icons/ExclamationCircleIcon.vue

var ExclamationCircleIcon_script = {}


/* normalize component */

var ExclamationCircleIcon_component = Object(componentNormalizer["a" /* default */])(
  ExclamationCircleIcon_script,
  ExclamationCircleIconvue_type_template_id_61802dc8_render,
  ExclamationCircleIconvue_type_template_id_61802dc8_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ExclamationCircleIcon = (ExclamationCircleIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/ExclamationTriangleIcon.vue?vue&type=template&id=dfb83318&
var ExclamationTriangleIconvue_type_template_id_dfb83318_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"version":"1.1","xmlns":"http://www.w3.org/2000/svg","width":"28","height":"28","viewBox":"0 0 28 28","fill":"currentColor"}},[_c('title',[_vm._v("exclamation-triangle")]),_vm._v(" "),_c('path',{attrs:{"d":"M16 21.484v-2.969c0-0.281-0.219-0.516-0.5-0.516h-3c-0.281 0-0.5 0.234-0.5 0.516v2.969c0 0.281 0.219 0.516 0.5 0.516h3c0.281 0 0.5-0.234 0.5-0.516zM15.969 15.641l0.281-7.172c0-0.094-0.047-0.219-0.156-0.297-0.094-0.078-0.234-0.172-0.375-0.172h-3.437c-0.141 0-0.281 0.094-0.375 0.172-0.109 0.078-0.156 0.234-0.156 0.328l0.266 7.141c0 0.203 0.234 0.359 0.531 0.359h2.891c0.281 0 0.516-0.156 0.531-0.359zM15.75 1.047l12 22c0.344 0.609 0.328 1.359-0.031 1.969s-1.016 0.984-1.719 0.984h-24c-0.703 0-1.359-0.375-1.719-0.984s-0.375-1.359-0.031-1.969l12-22c0.344-0.641 1.016-1.047 1.75-1.047s1.406 0.406 1.75 1.047z"}})])}
var ExclamationTriangleIconvue_type_template_id_dfb83318_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/ExclamationTriangleIcon.vue?vue&type=template&id=dfb83318&

// CONCATENATED MODULE: ./js/components/ui/icons/ExclamationTriangleIcon.vue

var ExclamationTriangleIcon_script = {}


/* normalize component */

var ExclamationTriangleIcon_component = Object(componentNormalizer["a" /* default */])(
  ExclamationTriangleIcon_script,
  ExclamationTriangleIconvue_type_template_id_dfb83318_render,
  ExclamationTriangleIconvue_type_template_id_dfb83318_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var ExclamationTriangleIcon = (ExclamationTriangleIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/InfoCircleIcon.vue?vue&type=template&id=30a624b8&
var InfoCircleIconvue_type_template_id_30a624b8_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"viewBox":"0 0 100 100","version":"1.1","xmlns":"http://www.w3.org/2000/svg"}},[_c('title',[_vm._v("info-circle")]),_vm._v(" "),_c('desc',[_vm._v("Created with Sketch.")]),_vm._v(" "),_c('defs'),_vm._v(" "),_c('g',{attrs:{"id":"Page-1","stroke":"none","stroke-width":"1","fill":"none","fill-rule":"evenodd"}},[_c('g',{attrs:{"id":"info-circle","fill":"currentColor"}},[_c('path',{attrs:{"d":"M66.6666667,81.25 L66.6666667,70.8333333 C66.6666667,69.6614583 65.7552083,68.75 64.5833333,68.75 L58.3333333,68.75 L58.3333333,35.4166667 C58.3333333,34.2447917 57.421875,33.3333333 56.25,33.3333333 L35.4166667,33.3333333 C34.2447917,33.3333333 33.3333333,34.2447917 33.3333333,35.4166667 L33.3333333,45.8333333 C33.3333333,47.0052083 34.2447917,47.9166667 35.4166667,47.9166667 L41.6666667,47.9166667 L41.6666667,68.75 L35.4166667,68.75 C34.2447917,68.75 33.3333333,69.6614583 33.3333333,70.8333333 L33.3333333,81.25 C33.3333333,82.421875 34.2447917,83.3333333 35.4166667,83.3333333 L64.5833333,83.3333333 C65.7552083,83.3333333 66.6666667,82.421875 66.6666667,81.25 Z M58.3333333,22.9166667 L58.3333333,12.5 C58.3333333,11.328125 57.421875,10.4166667 56.25,10.4166667 L43.75,10.4166667 C42.578125,10.4166667 41.6666667,11.328125 41.6666667,12.5 L41.6666667,22.9166667 C41.6666667,24.0885417 42.578125,25 43.75,25 L56.25,25 C57.421875,25 58.3333333,24.0885417 58.3333333,22.9166667 Z M100,50 C100,77.6041667 77.6041667,100 50,100 C22.3958333,100 0,77.6041667 0,50 C0,22.3958333 22.3958333,0 50,0 C77.6041667,0 100,22.3958333 100,50 Z","id":"Shape"}})])])])}
var InfoCircleIconvue_type_template_id_30a624b8_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/InfoCircleIcon.vue?vue&type=template&id=30a624b8&

// CONCATENATED MODULE: ./js/components/ui/icons/InfoCircleIcon.vue

var InfoCircleIcon_script = {}


/* normalize component */

var InfoCircleIcon_component = Object(componentNormalizer["a" /* default */])(
  InfoCircleIcon_script,
  InfoCircleIconvue_type_template_id_30a624b8_render,
  InfoCircleIconvue_type_template_id_30a624b8_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var InfoCircleIcon = (InfoCircleIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/LinkIcon.vue?vue&type=template&id=da423b62&
var LinkIconvue_type_template_id_da423b62_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"xmlns":"http://www.w3.org/2000/svg","viewBox":"0 0 20 20","fill":"currentColor"}},[_c('path',{attrs:{"fill-rule":"evenodd","d":"M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z","clip-rule":"evenodd"}})])}
var LinkIconvue_type_template_id_da423b62_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/LinkIcon.vue?vue&type=template&id=da423b62&

// CONCATENATED MODULE: ./js/components/ui/icons/LinkIcon.vue

var LinkIcon_script = {}


/* normalize component */

var LinkIcon_component = Object(componentNormalizer["a" /* default */])(
  LinkIcon_script,
  LinkIconvue_type_template_id_da423b62_render,
  LinkIconvue_type_template_id_da423b62_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var LinkIcon = (LinkIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/icons/SearchIcon.vue?vue&type=template&id=9980d9f6&
var SearchIconvue_type_template_id_9980d9f6_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('svg',{attrs:{"version":"1.1","xmlns":"http://www.w3.org/2000/svg","width":"26","height":"28","viewBox":"0 0 26 28","fill":"currentColor"}},[_c('title',[_vm._v("search")]),_vm._v(" "),_c('path',{attrs:{"d":"M18 13c0-3.859-3.141-7-7-7s-7 3.141-7 7 3.141 7 7 7 7-3.141 7-7zM26 26c0 1.094-0.906 2-2 2-0.531 0-1.047-0.219-1.406-0.594l-5.359-5.344c-1.828 1.266-4.016 1.937-6.234 1.937-6.078 0-11-4.922-11-11s4.922-11 11-11 11 4.922 11 11c0 2.219-0.672 4.406-1.937 6.234l5.359 5.359c0.359 0.359 0.578 0.875 0.578 1.406z"}})])}
var SearchIconvue_type_template_id_9980d9f6_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/icons/SearchIcon.vue?vue&type=template&id=9980d9f6&

// CONCATENATED MODULE: ./js/components/ui/icons/SearchIcon.vue

var SearchIcon_script = {}


/* normalize component */

var SearchIcon_component = Object(componentNormalizer["a" /* default */])(
  SearchIcon_script,
  SearchIconvue_type_template_id_9980d9f6_render,
  SearchIconvue_type_template_id_9980d9f6_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var SearchIcon = (SearchIcon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Icon.vue?vue&type=script&lang=js&
//
//
//
//










/* harmony default export */ var Iconvue_type_script_lang_js_ = ({
  props: {
    icon: String
  },
  components: {
    BookIcon: BookIcon,
    CheckIcon: CheckIcon,
    ChevronLeftIcon: ChevronLeftIcon,
    ChevronRightIcon: ChevronRightIcon,
    CopyIcon: CopyIcon,
    ExclamationCircleIcon: ExclamationCircleIcon,
    ExclamationTriangleIcon: ExclamationTriangleIcon,
    InfoCircleIcon: InfoCircleIcon,
    LinkIcon: LinkIcon,
    SearchIcon: SearchIcon
  },
  computed: {
    computedComponent: function computedComponent() {
      return this.icon + '-icon';
    }
  }
});
// CONCATENATED MODULE: ./js/components/ui/Icon.vue?vue&type=script&lang=js&
 /* harmony default export */ var ui_Iconvue_type_script_lang_js_ = (Iconvue_type_script_lang_js_); 
// CONCATENATED MODULE: ./js/components/ui/Icon.vue





/* normalize component */

var Icon_component = Object(componentNormalizer["a" /* default */])(
  ui_Iconvue_type_script_lang_js_,
  Iconvue_type_template_id_3cf4fae2_render,
  Iconvue_type_template_id_3cf4fae2_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Icon = (Icon_component.exports);
// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Spinner.vue?vue&type=template&id=6befc426&
var Spinnervue_type_template_id_6befc426_render = function () {
var _obj, _obj$1;
var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"c-spinner",class:( _obj = {
    'tw-inline-block': true
}, _obj[_vm.size] = true, _obj )},[_c('div',{staticClass:"animation",class:[( _obj$1 = {
        'tw-border-gray-400': !_vm.animationClass
    }, _obj$1[_vm.animationClass] = _vm.animationClass, _obj$1 )]})])}
var Spinnervue_type_template_id_6befc426_staticRenderFns = []


// CONCATENATED MODULE: ./js/components/ui/Spinner.vue?vue&type=template&id=6befc426&

// CONCATENATED MODULE: /Users/ben/Sites/craft4/repos/cms/node_modules/babel-loader/lib??ref--1!/Users/ben/Sites/craft4/repos/cms/node_modules/vue-loader/lib??vue-loader-options!./js/components/ui/Spinner.vue?vue&type=script&lang=js&
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ var Spinnervue_type_script_lang_js_ = ({
  props: {
    animationClass: {
      type: String
    },

    /**
     * 'base' or 'lg'
     */
    size: {
      type: String,
      "default": 'base'
    }
  }
});
// CONCATENATED MODULE: ./js/components/ui/Spinner.vue?vue&type=script&lang=js&
 /* harmony default export */ var ui_Spinnervue_type_script_lang_js_ = (Spinnervue_type_script_lang_js_); 
// EXTERNAL MODULE: ./js/components/ui/Spinner.vue?vue&type=style&index=0&lang=scss&
var Spinnervue_type_style_index_0_lang_scss_ = __webpack_require__(222);

// CONCATENATED MODULE: ./js/components/ui/Spinner.vue






/* normalize component */

var Spinner_component = Object(componentNormalizer["a" /* default */])(
  ui_Spinnervue_type_script_lang_js_,
  Spinnervue_type_template_id_6befc426_render,
  Spinnervue_type_template_id_6befc426_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Spinner = (Spinner_component.exports);
// CONCATENATED MODULE: ./main.js
function main_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) { symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); } keys.push.apply(keys, symbols); } return keys; }

function main_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { main_ownKeys(Object(source), true).forEach(function (key) { main_defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { main_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function main_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/* global Craft */

/* global Garnish */

/* global $ */















external_Vue_default.a.filter('currency', currency);
external_Vue_default.a.filter('escapeHtml', escapeHtml);
external_Vue_default.a.filter('formatDate', formatDate);
external_Vue_default.a.filter('formatNumber', formatNumber);
external_Vue_default.a.filter('t', t);
external_Vue_default.a.component('btn', Btn);
external_Vue_default.a.component('dropdown', Dropdown);
external_Vue_default.a.component('icon', Icon);
external_Vue_default.a.component('spinner', Spinner);
Garnish.$doc.ready(function () {
  Craft.initUiElements();
  window.pluginStoreApp = new external_Vue_default.a({
    router: router,
    store: store,
    render: function render(h) {
      return h(App);
    },
    components: {
      Modal: Modal,
      StatusMessage: StatusMessage,
      App: App
    },
    data: function data() {
      return {
        allDataLoaded: false,
        cartDataLoaded: false,
        coreDataLoaded: false,
        craftDataLoaded: false,
        craftIdDataLoaded: false,
        modalStep: null,
        pageTitle: 'Plugin Store',
        plugin: null,
        pluginId: null,
        pluginLicenseInfoLoaded: false,
        pluginStoreDataError: false,
        showModal: false,
        statusMessage: null
      };
    },
    computed: main_objectSpread(main_objectSpread({}, Object(external_Vuex_["mapState"])({
      cart: function cart(state) {
        return state.cart.cart;
      },
      craftId: function craftId(state) {
        return state.craft.craftId;
      }
    })), {}, {
      /**
       * Returns `true``if the core data and the plugin license info have been loaded.
       *
       * @returns {boolean}
       */
      pluginStoreDataLoaded: function pluginStoreDataLoaded() {
        return this.coreDataLoaded && this.pluginLicenseInfoLoaded;
      }
    }),
    watch: {
      cart: function cart(_cart) {
        this.$emit('cartChange', _cart);
      },
      craftId: function craftId() {
        this.$emit('craftIdChange');
      }
    },
    methods: {
      /**
       * Displays a notice.
       *
       * @param message
       */
      displayNotice: function displayNotice(message) {
        Craft.cp.displayNotice(message);
      },

      /**
       * Displays an error.
       *
       * @param message
       */
      displayError: function displayError(message) {
        Craft.cp.displayError(message);
      },

      /**
       * Opens up the modal.
       *
       * @param modalStep
       */
      openModal: function openModal(modalStep) {
        this.modalStep = modalStep;
        this.showModal = true;
      },

      /**
       * Closes the modal.
       */
      closeModal: function closeModal() {
        this.showModal = false;
      },

      /**
       * Updates Craft ID.
       *
       * @param craftIdJson
       */
      updateCraftId: function updateCraftId(craftId, callback) {
        var _this = this;

        this.$store.commit('craft/updateCraftId', craftId);

        if (this.craftId && this.craftId.email !== this.cart.email) {
          // Update the cart’s email with the one from the Craft ID account
          var data = {
            email: this.craftId.email
          };
          this.$store.dispatch('cart/saveCart', data).then(function () {
            _this.$emit('craftIdUpdated');

            if (callback) {
              callback();
            }
          })["catch"](function (error) {
            _this.$root.displayError("Couldn’t update cart’s email.");

            if (callback) {
              callback();
            }

            throw error;
          });
        } else {
          this.$emit('craftIdUpdated');

          if (callback) {
            callback();
          }
        }
      },

      /**
       * Initializes components that live outside of the Vue app.
       */
      initializeOuterComponents: function initializeOuterComponents() {
        var _this2 = this;

        // Header Title
        var $headerTitle = $('#header h1');
        $headerTitle.on('click', function () {
          _this2.$router.push({
            path: '/'
          });
        }); // Cart button

        var $cartButton = $('#cart-button');
        $cartButton.on('click', function (e) {
          e.preventDefault();

          _this2.openModal('cart');
        });
        $cartButton.keydown(function (e) {
          switch (e.which) {
            case 13: // Enter

            case 32:
              // Space
              e.preventDefault();

              _this2.openModal('cart');

              break;
          }
        });
        this.$on('cartChange', function (cart) {
          var totalQty = 0;

          if (cart) {
            totalQty = cart.totalQty;
          }

          $('.badge', $cartButton).html(totalQty);
        }); // Plugin Store actions

        var $pluginStoreActions = $('#pluginstore-actions');
        var $pluginStoreActionsSpinner = $('#pluginstore-actions-spinner'); // Show actions spinner when Plugin Store data has finished loading but Craft data has not.

        this.$on('dataLoaded', function () {
          if (_this2.pluginStoreDataLoaded && !(_this2.craftDataLoaded && _this2.cartDataLoaded && _this2.craftIdDataLoaded)) {
            $pluginStoreActionsSpinner.removeClass('hidden');
          }
        }); // Hide actions spinner when Plugin Store data and Craft data have finished loading.

        this.$on('allDataLoaded', function () {
          $pluginStoreActions.removeClass('hidden');
          $pluginStoreActionsSpinner.addClass('hidden');
        }); // Craft ID

        var $craftId = $('#craftid-account');
        var $craftIdConnectForm = $('#craftid-connect-form');
        var $craftIdDisconnectForm = $('#craftid-disconnect-form');
        this.$on('craftIdChange', function () {
          if (this.craftId) {
            $('.label', $craftId).html(this.craftId.username);
            $craftId.removeClass('hidden');
            $craftIdConnectForm.addClass('hidden');
            $craftIdDisconnectForm.removeClass('hidden');
          } else {
            $craftId.addClass('hidden');
            $craftIdConnectForm.removeClass('hidden');
            $craftIdDisconnectForm.addClass('hidden');
          }
        }); // Cancel ajax requests when an outbound link gets clicked

        $('a[href]').on('click', function () {
          _this2.$store.dispatch('craft/cancelRequests');

          _this2.$store.dispatch('pluginStore/cancelRequests');
        });
      },

      /**
       * Loads the cart data.
       */
      loadCartData: function loadCartData() {
        var _this3 = this;

        this.$store.dispatch('cart/getCart').then(function () {
          _this3.cartDataLoaded = true;

          _this3.$emit('dataLoaded');
        });
      },

      /**
       * Loads Craft data.
       */
      loadCraftData: function loadCraftData(afterSuccess) {
        var _this4 = this;

        this.$store.dispatch('craft/getCraftData').then(function () {
          _this4.craftDataLoaded = true;

          _this4.$emit('dataLoaded');

          if (typeof afterSuccess === 'function') {
            afterSuccess();
          }
        })["catch"](function () {
          _this4.craftDataLoaded = true;
        });
      },
      loadCraftIdData: function loadCraftIdData() {
        var _this5 = this;

        if (window.craftIdAccessToken) {
          var accessToken = window.craftIdAccessToken;
          this.$store.dispatch('craft/getCraftIdData', {
            accessToken: accessToken
          }).then(function () {
            _this5.craftIdDataLoaded = true;

            _this5.$emit('dataLoaded');
          });
        } else {
          this.craftIdDataLoaded = true;
          this.$emit('dataLoaded');
        }
      },

      /**
       * Loads all the data required for the Plugin Store and cart to work.
       */
      loadData: function loadData() {
        var _this6 = this;

        this.loadPluginStoreData();
        this.loadCraftData(function () {
          _this6.loadCraftIdData();

          _this6.loadCartData();
        });
      },

      /**
       * Loads the Plugin Store’s plugin data.
       */
      loadPluginStoreData: function loadPluginStoreData() {
        var _this7 = this;

        // core data
        this.$store.dispatch('pluginStore/getCoreData').then(function () {
          _this7.coreDataLoaded = true;

          _this7.$emit('dataLoaded');
        })["catch"](function (error) {
          if (external_axios_default.a.isCancel(error)) {// Request canceled
          } else {
            _this7.pluginStoreDataError = true;
            _this7.statusMessage = _this7.$options.filters.t('The Plugin Store is not available, please try again later.', 'app');
            throw error;
          }
        }); // plugin license info

        this.$store.dispatch('craft/getPluginLicenseInfo').then(function () {
          _this7.pluginLicenseInfoLoaded = true;

          _this7.$emit('dataLoaded');
        })["catch"](function (error) {
          if (external_axios_default.a.isCancel(error)) {// Request canceled
          } else {
            throw error;
          }
        });
      },

      /**
       * Checks that all the data has been loaded.
       *
       * @returns {null}
       */
      onDataLoaded: function onDataLoaded() {
        if (!this.pluginStoreDataLoaded) {
          return null;
        }

        if (!this.craftDataLoaded) {
          return null;
        }

        if (!this.cartDataLoaded) {
          return null;
        }

        if (!this.craftIdDataLoaded) {
          return null;
        }

        this.allDataLoaded = true;
        this.$emit('allDataLoaded');
      }
    },
    created: function created() {
      // Page Title
      this.pageTitle = this.$options.filters.t("Plugin Store", 'app'); // Status message

      this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app'); // Initialize outer components

      this.initializeOuterComponents(); // On data loaded

      this.$on('dataLoaded', this.onDataLoaded); // Load data

      this.loadData();
    }
  }).$mount('#app');
});

/***/ })
/******/ ]);
//# sourceMappingURL=app.js.map