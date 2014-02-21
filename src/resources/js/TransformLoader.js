/**
 * Asset image transform loader class.
 */
TransformLoader = function(placeholderUrl, entityPlaceholderUrl, spinnerUrl, generateTransformUrl)
{
	this.placeholderUrl = placeholderUrl;
	this.entityPlaceholderUrl = entityPlaceholderUrl;
	this.spinnerUrl = spinnerUrl;
	this.generateTransformUrl = generateTransformUrl;

	// Preload the spinner
	var spinnerImage = new Image();
	spinnerImage.onload = this.bind('init');
	spinnerImage.src = spinnerUrl;
};


TransformLoader.prototype =
{
	bind: function(func, args)
	{
		var obj = this;

		return function() {
			obj[func].apply(obj, args);
		};
	},

	insertAfter: function(newElem, elem)
	{
		if (elem.nextSibling)
		{
			elem.parentNode.insertBefore(newElem, elem.nextSibling);
		}
		else
		{
			elem.parentNode.appendChild(newElem);
		}
	},

	escapeForRegex: function(str)
	{
		return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
	},

	init: function()
	{
		this.escapedPlaceholderUrl = this.escapeForRegex(this.placeholderUrl);
		this.escapedEntityPlaceholderUrl = this.escapeForRegex(this.entityPlaceholderUrl);
		this.placeholderUrlPattern = '(?:'+this.escapedPlaceholderUrl+'|'+this.escapedEntityPlaceholderUrl+')#(\\d+)\\b';

		this.placeholderUrlRegex = new RegExp(this.placeholderUrlPattern);
		this.multiPlaceholderUrlRegex = new RegExp(this.placeholderUrlPattern, 'g');

		// Find all of the transform URLs on the page, regardless of what type of element they're in

		this.transforms = {};
		this.totalTransforms = 0;
		this.pendingTransforms = 0;

		var allHtml = document.head.innerHTML + document.body.innerHTML,
			matches = allHtml.match(this.multiPlaceholderUrlRegex);

		if (matches)
		{
			for (var i = 0; i < matches.length; i++)
			{
				var match = matches[i].match(this.placeholderUrlRegex),
					transformId = match[1];

				if (typeof this.transforms[transformId] == 'undefined')
				{
					this.transforms[transformId] = {
						imageTags: [],
						styleTags: []
					};

					this.totalTransforms++;
					this.pendingTransforms++;
				}
			}
		}

		if (!this.totalTransforms)
		{
			return;
		}

		// Find all of the transform images and styles

		var imageTags = document.getElementsByTagName('img'),
			styleTags = document.getElementsByTagName('style'),
			totalImageTags = imageTags.length,
			totalStyleTags = styleTags.length;

		for (var i = 0; i < totalImageTags; i++)
		{
			var match = imageTags[i].src.match(this.placeholderUrlRegex);

			if (match)
			{
				this.transforms[match[1]].imageTags.push({
					tag:                imageTags[i],
					originalBgColor:    imageTags[i].style.backgroundColor,
					originalBgImage:    imageTags[i].style.backgroundImage,
					originalBgRepeat:   imageTags[i].style.backgroundRepeat,
					originalBgPosition: imageTags[i].style.backgroundPosition,
					originalBgSize:     imageTags[i].style.backgroundSize
				});

				imageTags[i].style.backgroundColor    = '#f5f5f5';
				imageTags[i].style.backgroundImage    = 'url('+this.spinnerUrl+')';
				imageTags[i].style.backgroundRepeat   = 'no-repeat';
				imageTags[i].style.backgroundPosition = '50% 50%';
				imageTags[i].style.backgroundSize     = '48px';
			}
		}

		// Pattern for not only finding the placeholder URLs within a <style> tag, everything up to the next '}'
		var styleTagPattern = new RegExp(this.placeholderUrlPattern+'[^\}]*', 'g');

		for (var i = 0; i < totalStyleTags; i++)
		{
			var matches = styleTags[i].innerHTML.match(styleTagPattern);

			if (matches && matches.length)
			{
				var tempTag = document.createElement('style');
				tempTag.setAttribute('type', 'text/css');
				var tempTagCss = styleTags[i].innerHTML;

				for (var j = 0; j < matches.length; j++)
				{
					var tempCss = matches[j] + ' ' +
						'background: #f5f5f5 url('+this.spinnerUrl+') no-repeat 50% 50% !important; ' +
						'background-size: 48px !important';

					tempTagCss = tempTagCss.replace(matches[j], tempCss);

					var match = matches[j].match(this.placeholderUrlRegex),
						transformId = match[1];

					this.transforms[transformId].styleTags.push({
						tag:         styleTags[i],
						tempTag:     tempTag,
						originalCss: matches[j],
						tempCss:     tempCss
					});
				}

				// Insert the temp <style> tag right after this one
				tempTag.innerHTML = tempTagCss;

				this.insertAfter(tempTag, styleTags[i]);
			}
		}

		// Catch a breather
		setTimeout(this.bind('makeGenerateTransformRequests'), 500);
	},

	makeGenerateTransformRequests: function()
	{
		// Generate the transforms
		for (var transformId in this.transforms)
		{
			this.makeGenerateTransformRequest(transformId);
		}
	},

	makeGenerateTransformRequest: function(transformId)
	{
		// Can't rely on jQuery being around :(
		var xhr = new XMLHttpRequest();

		xhr.onreadystatechange = this.getGenerateTransformResponseFunction(transformId, xhr);

		xhr.open('POST', this.generateTransformUrl, true);
		xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.send('transformId='+transformId);
	},

	getGenerateTransformResponseFunction: function(transformId, xhr)
	{
		var obj = this;

		return function()
		{
			obj.onTransformResponse.call(obj, transformId, xhr);
		};
	},

	onTransformResponse: function(transformId, xhr)
	{
		if (xhr.readyState == 4 && xhr.status == 200)
		{
			if (xhr.responseText == 'working')
			{
				// Try again in one second
				var obj = this;

				setTimeout(function() {
					obj.makeGenerateTransformRequest(transformId);
				}, 1000);

				return;
			}

			if (xhr.responseText.substr(0, 8) == 'success:')
			{
				// Boom.
				var transformUrl = xhr.responseText.substr(8);

				// Now load the actual image before swapping the spinner out
				var transformImage = new Image();
				transformImage.onload = this.bind('replaceTransformImages', [transformId, transformUrl]);
				transformImage.src = transformUrl;
			}
		}

		this.pendingTransforms--;
	},

	replaceTransformImages: function(transformId, transformUrl)
	{
		var placeholderRegex = new RegExp(this.escapedPlaceholderUrl+'#'+transformId+'\\b', 'g');

		// Return any <img> tags back to their original state
		for (var i = 0; i < this.transforms[transformId].imageTags.length; i++)
		{
			var imageTag = this.transforms[transformId].imageTags[i];
			imageTag.tag.src = imageTag.tag.src.replace(placeholderRegex, transformUrl);

			imageTag.tag.style.backgroundColor    = imageTag.originalBgColor;
			imageTag.tag.style.backgroundImage    = imageTag.originalBgImage;
			imageTag.tag.style.backgroundRepeat   = imageTag.originalBgRepeat;
			imageTag.tag.style.backgroundPosition = imageTag.originalBgPosition;
			imageTag.tag.style.backgroundSize     = imageTag.originalBgSize;
		}

		// Return any <style> tags back to their original state
		for (var i = 0; i < this.transforms[transformId].styleTags.length; i++)
		{
			var styleTag = this.transforms[transformId].styleTags[i];

			var tempTagHtml = styleTag.tempTag.innerHTML.replace(styleTag.tempCss, styleTag.originalCss);
			tempTagHtml = tempTagHtml.replace(placeholderRegex, transformUrl);
			styleTag.tempTag.innerHTML = tempTagHtml;
		}
	}
};
