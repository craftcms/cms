/*

 http://github.com/valums/file-uploader

 Multiple file upload component with progress-bar, drag-and-drop.

 Copyright (C) 2011 by Andris Valums

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.

 */

var qqUploader;

(function(){

//
// Helper functions
//

    var QqUploader = QqUploader || {};

    /**
     * Adds all missing properties from second obj to first obj
     */

    QqUploader.extend = function(first, second){
        for (var prop in second){
            first[prop] = second[prop];
        }
    };

    /**
     * Searches for a given element in the array, returns -1 if it is not present.
     * @param {Number} [from] The index at which to begin the search
     */
    QqUploader.indexOf = function(arr, elt, from){
        if (arr.indexOf) return arr.indexOf(elt, from);

        from = from || 0;
        var len = arr.length;

        if (from < 0) from += len;

        for (; from < len; from++){

            if (from in arr && arr[from] === elt){

                return from;
            }
        }

        return -1;

    };

    QqUploader.getUniqueId = (function(){
        var id = 0;
        return function(){ return id++; };
    })();

//
// Events

    QqUploader.attach = function(element, type, fn){
        if (element.addEventListener){
            element.addEventListener(type, fn, false);
        } else if (element.attachEvent){
            element.attachEvent('on' + type, fn);
        }
    };
    QqUploader.detach = function(element, type, fn){
        if (element.removeEventListener){
            element.removeEventListener(type, fn, false);
        } else if (element.attachEvent){
            element.detachEvent('on' + type, fn);
        }
    };

    QqUploader.preventDefault = function(e){
        if (e.preventDefault){
            e.preventDefault();
        } else{
            e.returnValue = false;
        }
    };

//
// Node manipulations

    /**
     * Insert node a before node b.
     */
    QqUploader.insertBefore = function(a, b){
        b.parentNode.insertBefore(a, b);
    };
    QqUploader.remove = function(element){
        element.parentNode.removeChild(element);
    };

    QqUploader.contains = function(parent, descendant){

        // compareposition returns false in this case
        if (parent == descendant) return true;

        if (parent.contains){
            return parent.contains(descendant);
        } else {
            return !!(descendant.compareDocumentPosition(parent) & 8);
        }
    };

    /**
     * Creates and returns element from html string
     * Uses innerHTML to create an element
     */
    QqUploader.toElement = (function(){
        var div = document.createElement('div');
        return function(html){
            div.innerHTML = html;
            var element = div.firstChild;
            div.removeChild(element);
            return element;
        };
    })();

//
// Node properties and attributes

    /**
     * Sets styles for an element.
     * Fixes opacity in IE6-8.
     */
    QqUploader.css = function(element, styles){
        if (styles.opacity != null){
            if (typeof element.style.opacity != 'string' && typeof(element.filters) != 'undefined'){
                styles.filter = 'alpha(opacity=' + Math.round(100 * styles.opacity) + ')';
            }
        }
        QqUploader.extend(element.style, styles);
    };
    QqUploader.hasClass = function(element, name){
        var re = new RegExp('(^| )' + name + '( |$)');
        return re.test(element.className);
    };
    QqUploader.addClass = function(element, name){
        if (!QqUploader.hasClass(element, name)){
            element.className += ' ' + name;
        }
    };
    QqUploader.removeClass = function(element, name){
        var re = new RegExp('(^| )' + name + '( |$)');
        element.className = element.className.replace(re, ' ').replace(/^\s+|\s+$/g, "");
    };
    QqUploader.setText = function(element, text){
        element.innerText = text;
        element.textContent = text;
    };

//
// Selecting elements

    QqUploader.children = function(element){
        var children = [],
            child = element.firstChild;

        while (child){
            if (child.nodeType == 1){
                children.push(child);
            }
            child = child.nextSibling;
        }

        return children;
    };

    QqUploader.getByClass = function(element, className){
        if (element.querySelectorAll){
            return element.querySelectorAll('.' + className);
        }

        var result = [];
        var candidates = element.getElementsByTagName("*");
        var len = candidates.length;

        for (var i = 0; i < len; i++){
            if (QqUploader.hasClass(candidates[i], className)){
                result.push(candidates[i]);
            }
        }
        return result;
    };

    /**
     * obj2url() takes a json-object as argument and generates
     * a querystring. pretty much like jQuery.param()
     *

     * how to use:
     *
     *    `QqUploader.obj2url({a:'b',c:'d'},'http://any.url/upload?otherParam=value');`
     *
     * will result in:
     *
     *    `http://any.url/upload?otherParam=value&a=b&c=d`
     *
     * @param  Object JSON-Object
     * @param  String current querystring-part
     * @return String encoded querystring
     */
    QqUploader.obj2url = function(obj, temp, prefixDone){
        var uristrings = [],
            prefix = '&',
            add = function(nextObj, i){
                var nextTemp = temp

                    ? (/\[\]$/.test(temp)) // prevent double-encoding
                    ? temp
                    : temp+'['+i+']'
                    : i;
                if ((nextTemp != 'undefined') && (i != 'undefined')) {

                    uristrings.push(
                        (typeof nextObj === 'object')

                            ? QqUploader.obj2url(nextObj, nextTemp, true)
                            : (Object.prototype.toString.call(nextObj) === '[object Function]')
                            ? encodeURIComponent(nextTemp) + '=' + encodeURIComponent(nextObj())
                            : encodeURIComponent(nextTemp) + '=' + encodeURIComponent(nextObj)

                    );
                }
            };

        if (!prefixDone && temp) {
            prefix = (/\?/.test(temp)) ? (/\?$/.test(temp)) ? '' : '&' : '?';
            uristrings.push(temp);
            uristrings.push(QqUploader.obj2url(obj));
        } else if ((Object.prototype.toString.call(obj) === '[object Array]') && (typeof obj != 'undefined') ) {
            // we wont use a for-in-loop on an array (performance)
            for (var i = 0, len = obj.length; i < len; ++i){
                add(obj[i], i);
            }
        } else if ((typeof obj != 'undefined') && (obj !== null) && (typeof obj === "object")){
            // for anything else but a scalar, we will use for-in-loop
            for (var i in obj){
                add(obj[i], i);
            }
        } else {
            uristrings.push(encodeURIComponent(temp) + '=' + encodeURIComponent(obj));
        }

        return uristrings.join(prefix)
            .replace(/^&/, '')
            .replace(/%20/g, '+');

    };

//
//
// Uploader Classes
//
//

    var QqUploader = QqUploader || {};

    /**
     * Creates upload button, validates upload, but doesn't create file list or dd.

     */
    QqUploader.FileUploaderBasic = function(o){
        this._options = {
            // set to true to see the server response
            debug: false,
            action: '/server/upload',
            params: {},
            button: null,
            multiple: true,
            maxConnections: 3,
            // validation

            allowedExtensions: [],

            sizeLimit: 0,

            minSizeLimit: 0,

            // events
            // return false to cancel submit
            onSubmit: function(id, fileName){},
            onProgress: function(id, fileName, loaded, total){},
            onComplete: function(id, fileName, responseJSON){},
            onCancel: function(id, fileName){},
            // messages

            messages: {
                typeError: "{file} has invalid extension. Only {extensions} are allowed.",
                sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
                minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
                emptyError: "{file} is empty, please select files again without it.",
                onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."

            },
            showMessage: function(message){
                alert(message);
            }

        };
        QqUploader.extend(this._options, o);

        // number of files being uploaded
        this._filesInProgress = 0;
        this._handler = this._createUploadHandler();

        if (this._options.button){

            this._button = this._createUploadButton(this._options.button);
        }

        this._preventLeaveInProgress();

    };

    QqUploader.FileUploaderBasic.prototype = {
        setParams: function(params){
            this._options.params = params;
        },
        getInProgress: function(){
            return this._filesInProgress;

        },
        _createUploadButton: function(element){
            var self = this;

            return new QqUploader.UploadButton({
                element: element,
                multiple: this._options.multiple && QqUploader.UploadHandlerXhr.isSupported(),
                onChange: function(input){
                    self._onInputChange(input);
                }

            });

        },

        _createUploadHandler: function(){
            var self = this,
                handlerClass;

            if(QqUploader.UploadHandlerXhr.isSupported()){

                handlerClass = 'UploadHandlerXhr';

            } else {
                handlerClass = 'UploadHandlerForm';
            }

            var handler = new QqUploader[handlerClass]({
                debug: this._options.debug,
                action: this._options.action,

                maxConnections: this._options.maxConnections,

                onProgress: function(id, fileName, loaded, total){

                    self._onProgress(id, fileName, loaded, total);
                    self._options.onProgress(id, fileName, loaded, total);

                },

                onComplete: function(id, fileName, result){
                    self._onComplete(id, fileName, result);
                    self._options.onComplete(id, fileName, result);
                },
                onCancel: function(id, fileName){
                    self._onCancel(id, fileName);
                    self._options.onCancel(id, fileName);
                }
            });

            return handler;
        },

        _preventLeaveInProgress: function(){
            var self = this;

            QqUploader.attach(window, 'beforeunload', function(e){
                if (!self._filesInProgress){return;}

                var e = e || window.event;
                // for ie, ff
                e.returnValue = self._options.messages.onLeave;
                // for webkit
                return self._options.messages.onLeave;

            });

        },

        _onSubmit: function(id, fileName){
            this._filesInProgress++;

        },
        _onProgress: function(id, fileName, loaded, total){

        },
        _onComplete: function(id, fileName, result){
            this._filesInProgress--;

            if (result.error){
                this._options.showMessage(result.error);
            }

        },
        _onCancel: function(id, fileName){
            this._filesInProgress--;

        },
        _onInputChange: function(input){
            if (this._handler instanceof QqUploader.UploadHandlerXhr){

                this._uploadFileList(input.files);

            } else {

                if (this._validateFile(input)){

                    this._uploadFile(input);

                }

            }

            this._button.reset();

        },

        _uploadFileList: function(files){
            for (var i=0; i<files.length; i++){
                if ( !this._validateFile(files[i])){
                    return;
                }

            }

            for (var i=0; i<files.length; i++){
                this._uploadFile(files[i]);

            }

        },

        _uploadFile: function(fileContainer){

            var id = this._handler.add(fileContainer);
            var fileName = this._handler.getName(id);

            if (this._options.onSubmit(id, fileName) !== false){
                this._onSubmit(id, fileName);
                this._handler.upload(id, this._options.params);
            }
        },

        _validateFile: function(file){
            var name, size;

            if (file.value){
                // it is a file input

                // get input value and remove path to normalize
                name = file.value.replace(/.*(\/|\\)/, "");
            } else {
                // fix missing properties in Safari
                name = file.fileName != null ? file.fileName : file.name;
                size = file.fileSize != null ? file.fileSize : file.size;
            }

            if (! this._isAllowedExtension(name)){

                this._error('typeError', name);
                return false;

            } else if (size === 0){

                this._error('emptyError', name);
                return false;

            } else if (size && this._options.sizeLimit && size > this._options.sizeLimit){

                this._error('sizeError', name);
                return false;

            } else if (size && size < this._options.minSizeLimit){
                this._error('minSizeError', name);
                return false;

            }

            return true;

        },
        _error: function(code, fileName){
            var message = this._options.messages[code];

            function r(name, replacement){ message = message.replace(name, replacement); }

            r('{file}', this._formatFileName(fileName));

            r('{extensions}', this._options.allowedExtensions.join(', '));
            r('{sizeLimit}', this._formatSize(this._options.sizeLimit));
            r('{minSizeLimit}', this._formatSize(this._options.minSizeLimit));

            this._options.showMessage(message);

        },
        _formatFileName: function(name){
            if (name.length > 33){
                name = name.slice(0, 19) + '...' + name.slice(-13);

            }
            return name;
        },
        _isAllowedExtension: function(fileName){
            var ext = (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '').toLowerCase() : '';
            var allowed = this._options.allowedExtensions;

            if (!allowed.length){return true;}

            for (var i=0; i<allowed.length; i++){
                if (allowed[i].toLowerCase() == ext){ return true;}

            }

            return false;
        },

        _formatSize: function(bytes){
            var i = -1;

            do {
                bytes = bytes / 1024;
                i++;

            } while (bytes > 99);

            return Math.max(bytes, 0.1).toFixed(1) + ['kB', 'MB', 'GB', 'TB', 'PB', 'EB'][i];

        }
    };

    /**
     * Class that creates upload widget with drag-and-drop and file list
     * @inherits QqUploader.FileUploaderBasic
     */
    QqUploader.FileUploader = function(o){
        // call parent constructor
        QqUploader.FileUploaderBasic.apply(this, arguments);

        // additional options

        QqUploader.extend(this._options, {
            element: null,
            // if set, will be used instead of QqUploader-upload-list in template
            listElement: null,

            template: '<div class="QqUploader-uploader">' +

                '<div class="QqUploader-upload-drop-area"><span>Drop files here to upload</span></div>' +
                '<div class="QqUploader-upload-button">Upload a file</div>' +
                '<ul class="QqUploader-upload-list"></ul>' +

                '</div>',

            // template for one item in file list
            fileTemplate: '<li>' +
                '<span class="QqUploader-upload-file"></span>' +
                '<span class="QqUploader-upload-spinner"></span>' +
                '<span class="QqUploader-upload-size"></span>' +
                '<a class="QqUploader-upload-cancel" href="#">Cancel</a>' +
                '<span class="QqUploader-upload-failed-text">Failed</span>' +
                '</li>',

            classes: {
                // used to get elements from templates
                button: 'QqUploader-upload-button',
                drop: 'QqUploader-upload-drop-area',
                dropActive: 'QqUploader-upload-drop-area-active',
                list: 'QqUploader-upload-list',

                file: 'QqUploader-upload-file',
                spinner: 'QqUploader-upload-spinner',
                size: 'QqUploader-upload-size',
                cancel: 'QqUploader-upload-cancel',

                // added to list item when upload completes
                // used in css to hide progress spinner
                success: 'QqUploader-upload-success',
                fail: 'QqUploader-upload-fail'
            }
        });
        // overwrite options with user supplied

        QqUploader.extend(this._options, o);

        this._element = this._options.element;
        this._element.innerHTML = this._options.template;

        this._listElement = this._options.listElement || this._find(this._element, 'list');

        this._classes = this._options.classes;

        this._button = this._createUploadButton(this._find(this._element, 'button'));

        this._bindCancelEvent();
        this._setupDragDrop();
    };

// inherit from Basic Uploader
    QqUploader.extend(QqUploader.FileUploader.prototype, QqUploader.FileUploaderBasic.prototype);

    QqUploader.extend(QqUploader.FileUploader.prototype, {
        /**
         * Gets one of the elements listed in this._options.classes
         **/
        _find: function(parent, type){

            var element = QqUploader.getByClass(parent, this._options.classes[type])[0];

            if (!element){
                throw new Error('element not found: ' + type);
            }

            return element;
        },
        _setupDragDrop: function(){
            var self = this,
                dropArea = this._find(this._element, 'drop');

            var dz = new QqUploader.UploadDropZone({
                element: dropArea,
                onEnter: function(e){
                    QqUploader.addClass(dropArea, self._classes.dropActive);
                    e.stopPropagation();
                },
                onLeave: function(e){
                    e.stopPropagation();
                },
                onLeaveNotDescendants: function(e){
                    QqUploader.removeClass(dropArea, self._classes.dropActive);

                },
                onDrop: function(e){
                    dropArea.style.display = 'none';
                    QqUploader.removeClass(dropArea, self._classes.dropActive);
                    self._uploadFileList(e.dataTransfer.files);

                }
            });

            dropArea.style.display = 'none';

            QqUploader.attach(document, 'dragenter', function(e){

                if (!dz._isValidFileDrag(e)) return;

                dropArea.style.display = 'block';

            });

            QqUploader.attach(document, 'dragleave', function(e){
                if (!dz._isValidFileDrag(e)) return;

                var relatedTarget = document.elementFromPoint(e.clientX, e.clientY);
                // only fire when leaving document out
                if ( ! relatedTarget || relatedTarget.nodeName == "HTML"){

                    dropArea.style.display = 'none';

                }
            });

        },
        _onSubmit: function(id, fileName){
            QqUploader.FileUploaderBasic.prototype._onSubmit.apply(this, arguments);
            this._addToList(id, fileName);

        },
        _onProgress: function(id, fileName, loaded, total){
            QqUploader.FileUploaderBasic.prototype._onProgress.apply(this, arguments);

            var item = this._getItemByFileId(id);
            var size = this._find(item, 'size');
            size.style.display = 'inline';

            var text;

            if (loaded != total){
                text = Math.round(loaded / total * 100) + '% from ' + this._formatSize(total);
            } else {

                text = this._formatSize(total);
            }

            QqUploader.setText(size, text);

        },
        _onComplete: function(id, fileName, result){
            QqUploader.FileUploaderBasic.prototype._onComplete.apply(this, arguments);

            // mark completed
            var item = this._getItemByFileId(id);

            QqUploader.remove(this._find(item, 'cancel'));
            QqUploader.remove(this._find(item, 'spinner'));

            if (result.success){
                QqUploader.addClass(item, this._classes.success);

            } else {
                QqUploader.addClass(item, this._classes.fail);
            }

        },
        _addToList: function(id, fileName){
            var item = QqUploader.toElement(this._options.fileTemplate);

            item.qqfileId = id;

            var fileElement = this._find(item, 'file');

            QqUploader.setText(fileElement, this._formatFileName(fileName));
            this._find(item, 'size').style.display = 'none';

            this._listElement.appendChild(item);
        },
        _getItemByFileId: function(id){
            var item = this._listElement.firstChild;

            // there can't be txt nodes in dynamically created list
            // and we can  use nextSibling
            while (item){

                if (item.qqfileId == id) return item;

                item = item.nextSibling;
            }

        },
        /**
         * delegate click event for cancel link

         **/
        _bindCancelEvent: function(){
            var self = this,
                list = this._listElement;

            QqUploader.attach(list, 'click', function(e){

                e = e || window.event;
                var target = e.target || e.srcElement;

                if (QqUploader.hasClass(target, self._classes.cancel)){

                    QqUploader.preventDefault(e);

                    var item = target.parentNode;
                    self._handler.cancel(item.qqfileId);
                    QqUploader.remove(item);
                }
            });
        }

    });

    QqUploader.UploadDropZone = function(o){
        this._options = {
            element: null,

            onEnter: function(e){},
            onLeave: function(e){},

            // is not fired when leaving element by hovering descendants

            onLeaveNotDescendants: function(e){},

            onDrop: function(e){}

        };
        QqUploader.extend(this._options, o);

        this._element = this._options.element;

        this._disableDropOutside();
        this._attachEvents();

    };

    QqUploader.UploadDropZone.prototype = {
        _disableDropOutside: function(e){
            // run only once for all instances
            if (!QqUploader.UploadDropZone.dropOutsideDisabled ){

                QqUploader.attach(document, 'dragover', function(e){
                    if (e.dataTransfer){
                        e.dataTransfer.dropEffect = 'none';
                        e.preventDefault();

                    }

                });

                QqUploader.UploadDropZone.dropOutsideDisabled = true;

            }

        },
        _attachEvents: function(){
            var self = this;

            QqUploader.attach(self._element, 'dragover', function(e){
                if (!self._isValidFileDrag(e)) return;

                var effect = e.dataTransfer.effectAllowed;
                if (effect == 'move' || effect == 'linkMove'){
                    e.dataTransfer.dropEffect = 'move'; // for FF (only move allowed)

                } else {

                    e.dataTransfer.dropEffect = 'copy'; // for Chrome
                }

                e.stopPropagation();
                e.preventDefault();

            });

            QqUploader.attach(self._element, 'dragenter', function(e){
                if (!self._isValidFileDrag(e)) return;

                self._options.onEnter(e);
            });

            QqUploader.attach(self._element, 'dragleave', function(e){
                if (!self._isValidFileDrag(e)) return;

                self._options.onLeave(e);

                var relatedTarget = document.elementFromPoint(e.clientX, e.clientY);

                // do not fire when moving a mouse over a descendant
                if (QqUploader.contains(this, relatedTarget)) return;

                self._options.onLeaveNotDescendants(e);

            });

            QqUploader.attach(self._element, 'drop', function(e){
                if (!self._isValidFileDrag(e)) return;

                e.preventDefault();
                self._options.onDrop(e);
            });

        },
        _isValidFileDrag: function(e){
            var dt = e.dataTransfer,
            // do not check dt.types.contains in webkit, because it crashes safari 4

                isWebkit = navigator.userAgent.indexOf("AppleWebKit") > -1;

            // dt.effectAllowed is none in Safari 5
            // dt.types.contains check is for firefox

            return dt && dt.effectAllowed != 'none' &&

                (dt.files || (!isWebkit && dt.types.contains && dt.types.contains('Files')));

        }

    };

    QqUploader.UploadButton = function(o){
        this._options = {
            element: null,

            // if set to true adds multiple attribute to file input

            multiple: false,
            // name attribute of file input
            name: 'file',
            onChange: function(input){},
            hoverClass: 'QqUploader-upload-button-hover',
            focusClass: 'QqUploader-upload-button-focus'

        };

        QqUploader.extend(this._options, o);

        this._element = this._options.element;

        // make button suitable container for input
        QqUploader.css(this._element, {
            position: 'relative',
            overflow: 'hidden',
            // Make sure browse button is in the right side
            // in Internet Explorer
            direction: 'ltr'
        });

        this._input = this._createInput();
    };

    QqUploader.UploadButton.prototype = {
        /* returns file input element */

        getInput: function(){
            return this._input;
        },
        /* cleans/recreates the file input */
        reset: function(){
            if (this._input.parentNode){
                QqUploader.remove(this._input);

            }

            QqUploader.removeClass(this._element, this._options.focusClass);
            this._input = this._createInput();
        },

        _createInput: function(){

            var input = document.createElement("input");

            if (this._options.multiple){
                input.setAttribute("multiple", "multiple");
            }

            input.setAttribute("type", "file");
            input.setAttribute("name", this._options.name);

            QqUploader.css(input, {
                position: 'absolute',
                // in Opera only 'browse' button
                // is clickable and it is located at
                // the right side of the input
                right: 0,
                top: 0,
                fontFamily: 'Arial',
                // 4 persons reported this, the max values that worked for them were 243, 236, 236, 118
                fontSize: '118px',
                margin: 0,
                padding: 0,
                cursor: 'pointer',
                opacity: 0
            });

            this._element.appendChild(input);

            var self = this;
            QqUploader.attach(input, 'change', function(){
                self._options.onChange(input);
            });

            QqUploader.attach(input, 'mouseover', function(){
                QqUploader.addClass(self._element, self._options.hoverClass);
            });
            QqUploader.attach(input, 'mouseout', function(){
                QqUploader.removeClass(self._element, self._options.hoverClass);
            });
            QqUploader.attach(input, 'focus', function(){
                QqUploader.addClass(self._element, self._options.focusClass);
            });
            QqUploader.attach(input, 'blur', function(){
                QqUploader.removeClass(self._element, self._options.focusClass);
            });

            // IE and Opera, unfortunately have 2 tab stops on file input
            // which is unacceptable in our case, disable keyboard access
            if (window.attachEvent){
                // it is IE or Opera
                input.setAttribute('tabIndex', "-1");
            }

            return input;

        }

    };

    /**
     * Class for uploading files, uploading itself is handled by child classes
     */
    QqUploader.UploadHandlerAbstract = function(o){
        this._options = {
            debug: false,
            action: '/upload.php',
            // maximum number of concurrent uploads

            maxConnections: 999,
            onProgress: function(id, fileName, loaded, total){},
            onComplete: function(id, fileName, response){},
            onCancel: function(id, fileName){}
        };
        QqUploader.extend(this._options, o);

        this._queue = [];
        // params for files in queue
        this._params = [];
    };
    QqUploader.UploadHandlerAbstract.prototype = {
        log: function(str){
            if (this._options.debug && window.console) console.log('[uploader] ' + str);

        },
        /**
         * Adds file or file input to the queue
         * @returns id
         **/

        add: function(file){},
        /**
         * Sends the file identified by id and additional query params to the server
         */
        upload: function(id, params){
            var len = this._queue.push(id);

            var copy = {};

            QqUploader.extend(copy, params);
            this._params[id] = copy;

            // if too many active uploads, wait...
            if (len <= this._options.maxConnections){

                this._upload(id, this._params[id]);
            }
        },
        /**
         * Cancels file upload by id
         */
        cancel: function(id){
            this._cancel(id);
            this._dequeue(id);
        },
        /**
         * Cancells all uploads
         */
        cancelAll: function(){
            for (var i=0; i<this._queue.length; i++){
                this._cancel(this._queue[i]);
            }
            this._queue = [];
        },
        /**
         * Returns name of the file identified by id
         */
        getName: function(id){},
        /**
         * Returns size of the file identified by id
         */

        getSize: function(id){},
        /**
         * Returns id of files being uploaded or
         * waiting for their turn
         */
        getQueue: function(){
            return this._queue;
        },
        /**
         * Actual upload method
         */
        _upload: function(id){},
        /**
         * Actual cancel method
         */
        _cancel: function(id){},

        /**
         * Removes element from queue, starts upload of next
         */
        _dequeue: function(id){
            var i = QqUploader.indexOf(this._queue, id);
            this._queue.splice(i, 1);

            var max = this._options.maxConnections;

            if (this._queue.length >= max && i < max){
                var nextId = this._queue[max-1];
                this._upload(nextId, this._params[nextId]);
            }
        }

    };

    /**
     * Class for uploading files using form and iframe
     * @inherits QqUploader.UploadHandlerAbstract
     */
    QqUploader.UploadHandlerForm = function(o){
        QqUploader.UploadHandlerAbstract.apply(this, arguments);

        this._inputs = {};
    };
// @inherits QqUploader.UploadHandlerAbstract
    QqUploader.extend(QqUploader.UploadHandlerForm.prototype, QqUploader.UploadHandlerAbstract.prototype);

    QqUploader.extend(QqUploader.UploadHandlerForm.prototype, {
        add: function(fileInput){
            fileInput.setAttribute('name', 'qqfile');
            var id = 'QqUploader-upload-handler-iframe' + QqUploader.getUniqueId();

            this._inputs[id] = fileInput;

            // remove file input from DOM
            if (fileInput.parentNode){
                QqUploader.remove(fileInput);
            }

            return id;
        },
        getName: function(id){
            // get input value and remove path to normalize
            return this._inputs[id].value.replace(/.*(\/|\\)/, "");
        },

        _cancel: function(id){
            this._options.onCancel(id, this.getName(id));

            delete this._inputs[id];

            var iframe = document.getElementById(id);
            if (iframe){
                // to cancel request set src to something else
                // we use src="javascript:false;" because it doesn't
                // trigger ie6 prompt on https
                iframe.setAttribute('src', 'javascript:false;');

                QqUploader.remove(iframe);
            }
        },

        _upload: function(id, params){

            var input = this._inputs[id];

            if (!input){
                throw new Error('file with passed id was not added, or already uploaded or cancelled');
            }

            var fileName = this.getName(id);

            var iframe = this._createIframe(id);
            var form = this._createForm(iframe, params);
            form.appendChild(input);

            var self = this;
            this._attachLoadEvent(iframe, function(){

                self.log('iframe loaded');

                var response = self._getIframeContentJSON(iframe);

                self._options.onComplete(id, fileName, response);
                self._dequeue(id);

                delete self._inputs[id];
                // timeout added to fix busy state in FF3.6
                setTimeout(function(){
                    QqUploader.remove(iframe);
                }, 1);
            });

            form.submit();

            QqUploader.remove(form);

            return id;
        },

        _attachLoadEvent: function(iframe, callback){
            QqUploader.attach(iframe, 'load', function(){
                // when we remove iframe from dom
                // the request stops, but in IE load
                // event fires
                if (!iframe.parentNode){
                    return;
                }

                // fixing Opera 10.53
                if (iframe.contentDocument &&
                    iframe.contentDocument.body &&
                    iframe.contentDocument.body.innerHTML == "false"){
                    // In Opera event is fired second time
                    // when body.innerHTML changed from false
                    // to server response approx. after 1 sec
                    // when we upload file with iframe
                    return;
                }

                callback();
            });
        },
        /**
         * Returns json object received by iframe from server.
         */
        _getIframeContentJSON: function(iframe){
            // iframe.contentWindow.document - for IE<7
            var doc = iframe.contentDocument ? iframe.contentDocument: iframe.contentWindow.document,
                response;

            this.log("converting iframe's innerHTML to JSON");
            this.log("innerHTML = " + doc.body.innerHTML);

            try {
                response = eval("(" + doc.body.innerHTML + ")");
            } catch(err){
                response = {};
            }

            return response;
        },
        /**
         * Creates iframe with unique name
         */
        _createIframe: function(id){
            // We can't use following code as the name attribute
            // won't be properly registered in IE6, and new window
            // on form submit will open
            // var iframe = document.createElement('iframe');
            // iframe.setAttribute('name', id);

            var iframe = QqUploader.toElement('<iframe src="javascript:false;" name="' + id + '" />');
            // src="javascript:false;" removes ie6 prompt on https

            iframe.setAttribute('id', id);

            iframe.style.display = 'none';
            document.body.appendChild(iframe);

            return iframe;
        },
        /**
         * Creates form, that will be submitted to iframe
         */
        _createForm: function(iframe, params){
            // We can't use the following code in IE6
            // var form = document.createElement('form');
            // form.setAttribute('method', 'post');
            // form.setAttribute('enctype', 'multipart/form-data');
            // Because in this case file won't be attached to request
            var form = QqUploader.toElement('<form method="post" enctype="multipart/form-data"></form>');

            var queryString = QqUploader.obj2url(params, this._options.action);

            form.setAttribute('action', queryString);
            form.setAttribute('target', iframe.name);
            form.style.display = 'none';
            document.body.appendChild(form);

            return form;
        }
    });

    /**
     * Class for uploading files using xhr
     * @inherits QqUploader.UploadHandlerAbstract
     */
    QqUploader.UploadHandlerXhr = function(o){
        QqUploader.UploadHandlerAbstract.apply(this, arguments);

        this._files = [];
        this._xhrs = [];

        // current loaded size in bytes for each file

        this._loaded = [];
    };

// static method
    QqUploader.UploadHandlerXhr.isSupported = function(){
        var input = document.createElement('input');
        input.type = 'file';

        return (
            'multiple' in input &&
                typeof File != "undefined" &&
                typeof (new XMLHttpRequest()).upload != "undefined" );

    };

// @inherits QqUploader.UploadHandlerAbstract
    QqUploader.extend(QqUploader.UploadHandlerXhr.prototype, QqUploader.UploadHandlerAbstract.prototype)

    QqUploader.extend(QqUploader.UploadHandlerXhr.prototype, {
        /**
         * Adds file to the queue
         * Returns id to use with upload, cancel
         **/

        add: function(file){
            if (!(file instanceof File)){
                throw new Error('Passed obj in not a File (in QqUploader.UploadHandlerXhr)');
            }

            return this._files.push(file) - 1;

        },
        getName: function(id){

            var file = this._files[id];
            // fix missing name in Safari 4
            return file.fileName != null ? file.fileName : file.name;

        },
        getSize: function(id){
            var file = this._files[id];
            return file.fileSize != null ? file.fileSize : file.size;
        },

        /**
         * Returns uploaded bytes for file identified by id

         */

        getLoaded: function(id){
            return this._loaded[id] || 0;

        },

        /**
         * Sends the file identified by id and additional query params to the server
         *
         * @param id int
         * @param params object of name-value string pairs
         * @private
         */
        _upload: function(id, params){
            var file = this._files[id],
                name = this.getName(id),
                size = this.getSize(id);

            this._loaded[id] = 0;

            var xhr = this._xhrs[id] = new XMLHttpRequest();
            var self = this;

            xhr.upload.onprogress = function(e){
                if (e.lengthComputable){
                    self._loaded[id] = e.loaded;
                    self._options.onProgress(id, name, e.loaded, e.total);
                }
            };

            xhr.onreadystatechange = function(){

                if (xhr.readyState == 4){
                    self._onComplete(id, xhr);

                }
            };

            // build query string
            params = params || {};
            params['qqfile'] = name;
            var queryString = QqUploader.obj2url(params, this._options.action);

            xhr.open("POST", queryString, true);
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            xhr.setRequestHeader("X-File-Name", encodeURIComponent(name));
            xhr.setRequestHeader("Content-Type", "application/octet-stream");
            xhr.send(file);
        },
        _onComplete: function(id, xhr){
            // the request was aborted/cancelled
            if (!this._files[id]) return;

            var name = this.getName(id);
            var size = this.getSize(id);

            this._options.onProgress(id, name, size, size);

            if (xhr.status == 200){
                this.log("xhr - server response received");
                this.log("responseText = " + xhr.responseText);

                var response;

                try {
                    response = eval("(" + xhr.responseText + ")");
                } catch(err){
                    response = {};
                }

                this._options.onComplete(id, name, response);

            } else {

                this._options.onComplete(id, name, {});
            }

            this._files[id] = null;
            this._xhrs[id] = null;

            this._dequeue(id);

        },
        _cancel: function(id){
            this._options.onCancel(id, this.getName(id));

            this._files[id] = null;

            if (this._xhrs[id]){
                this._xhrs[id].abort();
                this._xhrs[id] = null;

            }
        }
    });

    qqUploader = QqUploader;
})();
