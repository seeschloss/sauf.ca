var Sauf = function(doc) {
	this.document = doc;
	this.thumbnails = {};

	this.viewer = new Viewer(this);
	this.status = new Status(this);

	this.initThumbnails();
};

Sauf.prototype.appendThumbnail = function(data) {
	var thumbnail = Thumbnail.create(this, data);

	var thumbnails_element = document.querySelector('#thumbnails');
	thumbnails_element.appendChild(thumbnail.element);

	this.registerThumbnail(thumbnail);
};

Sauf.prototype.initThumbnails = function() {
	var elements = this.document.querySelectorAll('span.thumbnail');

	for (var i = 0; i < elements.length; i++) {
		var element = elements.item(i);

		this.registerThumbnail(element);
	}
};

Sauf.prototype.registerThumbnail = function(element) {
	if (element instanceof Thumbnail) {
		var thumbnail = element;
	} else {
		var thumbnail = new Thumbnail(this, element);
	}

	this.thumbnails[thumbnail.id] = thumbnail;
};

Sauf.prototype.firstThumbnail = function() {
	var element = this.document.querySelector('span.thumbnail');
	return this.findThumbnail(element);
};

Sauf.prototype.lastThumbnail = function() {
	var elements = this.document.querySelectorAll('span.thumbnail');
	return this.findThumbnail(elements.item(elements.length - 1));
};

Sauf.prototype.findThumbnail = function(element) {
	for (var id in this.thumbnails) {
		if (this.thumbnails[id].element == element) {
			return this.thumbnails[id];
		}
	}

	return null;
};

Sauf.prototype.setCurrentMedia = function(media) {
	var previous = document.querySelectorAll('.thumbnail.current');
	for (var i = 0; i < previous.length; i++) {
		previous.item(i).className = previous.item(i).className.replace(/current/, '');
	}

	this.currentMedia = media;
	if (this.currentMedia) {
		this.currentMedia.parent.element.className += ' current';
		this.status.show(media);
		media.parent.linkElement.scrollIntoViewIfNeeded();
	}

	// TODO remove later
	currentImage = media.parent.linkElement;
};

var Status = function(site) {
	this.site = site;

	this.element = document.querySelector('#status');
};

Status.prototype.clear = function() {
	this.element.innerHTML = '';
};

Status.prototype.reset = function() {
	this.element.innerHTML = '<a id="contact-link" href="http://github.com/seeschloss/sauf.ca">github</a> <a id="contact-link" href="mailto:see@sauf.ca">contact</a>';
};

Status.prototype.show = function(media) {
	this.clear();

	var thumbnail = media.parent;

	var category = thumbnail.dataset.type.split(/\//)[0];

	var date = document.createElement('span');
	date.className = 'date';
	date.innerHTML = formatDate(thumbnail.dataset.date);
	this.element.appendChild(date);

	if (thumbnail.dataset.url) {
		var link = document.createElement('a');
		link.className = 'link';
		link.href = thumbnail.dataset.url;
		link.innerHTML = link.href;
		link.innerHTML = link.innerHTML.replace(/https?:\/\//, '');
		if (link.innerHTML.length > 40) {
			link.innerHTML = link.innerHTML.substr(0, 40) + '...';
		}
		this.element.appendChild(link);
	}

	var bloubs = document.createElement('a');
	bloubs.className = 'doublons hidden';
	bloubs.href = '=' + thumbnail.dataset.md5;
	bloubs.innerHTML = 'Doublons';
	this.element.appendChild(bloubs);

	if (thumbnail.dataset.bloubs > 0) {
		var nbloubs = thumbnail.dataset.bloubs;
		if (nbloubs > 1) {
			nbloubs -= 1;
			bloubs.className = 'doublons';
			if (nbloubs > 1) {
				bloubs.innerHTML = nbloubs + ' doublons';
			} else {
				bloubs.innerHTML = nbloubs + ' doublon';
			}
		}
	} else {
		var req = new XMLHttpRequest();
		switch (category) {
			case 'image':
			case 'video':
				var url = 'bloubs.json?picture=' + thumbnail.dataset.id;
				break;
			default:
				var url = 'bloubs.json?link=' + thumbnail.dataset.id;
				break;
		}
		req.open('GET', url, true);
		req.onreadystatechange = function(e) {
			if (req.readyState == 4) {
				if (req.status == 200 || req.status == 0) {
					var data = null;
					if (data = JSON.parse(req.responseText)) {
						var nbloubs = data[thumbnail.dataset.id];
						thumbnail.dataset.bloubs = nbloubs;
						if (nbloubs > 1) {
							nbloubs -= 1;
							bloubs.className = 'doublons';
							if (nbloubs > 1) {
								bloubs.innerHTML = nbloubs + ' doublons';
							} else {
								bloubs.innerHTML = nbloubs + ' doublon';
							}
						}
					}
				}
			}
		};
		req.send(null);
	}

	if (category == 'image' || category == 'video') {
		var google = document.createElement('a');
		google.className = 'google';
		google.href = 'https://www.google.com/searchbyimage?hl=en&safe=off&site=search&image_url=' + thumbnail.dataset.src;
		google.innerHTML = 'Google';
		this.element.appendChild(google);
	}

	if (thumbnail.dataset.tribuneUrl && thumbnail.dataset.tribuneName) {
		var tribune = document.createElement('a');
		tribune.className = 'tribune';
		tribune.href = thumbnail.dataset.tribuneUrl;
		tribune.innerHTML = thumbnail.dataset.tribuneName;
		this.element.appendChild(tribune);
	}

	if (thumbnail.dataset.userName) {
		var user = document.createElement('a');
		user.className = 'user';
		user.href = thumbnail.dataset.tribuneUrl;
		if (thumbnail.dataset.tribuneName == "dlfp") {
			// bombefourchette.com history here
			var date = new Date(+thumbnail.dataset.date * 1000);

			var day = date.getDate();
			var month = date.getMonth() + 1;
			var year = date.getFullYear();
			if (day   < 10) { day   = "0" + day;    }
			if (month < 10) { month = "0" + month;  }
			user.href = "http://bombefourchette.com/t/dlfp/" + year + "-" + month + "-" + day;
			if (+thumbnail.dataset.postId > 0) {
				user.href += "#" + thumbnail.dataset.postId;
			}
		} else if (thumbnail.dataset.tribuneName == "euromussels") {
			// bombefourchette.com history here
			var date = new Date(+thumbnail.dataset.date * 1000);

			var day = date.getDate();
			var month = date.getMonth() + 1;
			var year = date.getFullYear();
			if (day   < 10) { day   = "0" + day;    }
			if (month < 10) { month = "0" + month;  }
			user.href = "http://bombefourchette.com/t/euromussels/" + year + "-" + month + "-" + day;
			if (+thumbnail.dataset.postId > 0) {
				user.href += "#" + thumbnail.dataset.postId;
			}
		}
		user.innerHTML = thumbnail.dataset.userName;
		this.element.appendChild(user);
	}

	var title = document.createElement('span');
	title.className = 'title';
	if (category == 'image' || category == 'video') {
		title.innerHTML = thumbnail.dataset.title;
	} else {
		title.innerHTML = thumbnail.dataset.context;
	}
	this.element.appendChild(title);

	if (false && thumbnail.dataset.tribuneName == "dlfp") {
		var upArrow = document.createElement('span');
		upArrow.id = 'show-comments';
		upArrow.className = 'up';
		upArrow.innerHTML = '▲';
		this.element.appendChild(upArrow);

		upArrow.onclick = function(e) {
			toggleComments(currentImage);
		};
	}
};

var Viewer = function(site) {
	this.site = site;
	this.opened = false;

//	var image = document.querySelector('#thumbnails a[data-id="' + picture_id + '"]');
};

Viewer.prototype.createElement = function() {
	var element = this.site.document.createElement('div');
	element.id = 'viewer';
	this.site.document.querySelector('body').appendChild(element);
	this.element = element;

	var extra = document.createElement('div');
	extra.className = 'extra';
	this.extra = extra;

	var picture = document.createElement('div');
	picture.className = 'picture';
	this.picture = picture;
	this.picture.addEventListener('click', this);

	var row = document.createElement('div');
	row.className = 'row';

	picture.appendChild(row);

	var left = document.createElement('a');
	left.innerHTML = '<';
	left.id = 'arrow-left';
	left.href = "";
	left.className = 'hidden';
	this.left = left;

	var right = document.createElement('a');
	right.id = 'arrow-right';
	right.innerHTML = '>';
	right.href = "";
	right.className = 'hidden';
	this.right = right;

	this.container = document.createElement('div');
	row.appendChild(this.container);

	this.container.appendChild(this.left);
	this.container.appendChild(this.right);

	this.element.appendChild(this.extra);
	this.element.appendChild(this.picture);

	var info = document.createElement('div');
	info.className = 'info';
	this.element.appendChild(info);

	this.left.addEventListener('click', this);
	this.right.addEventListener('click', this);
};

Viewer.prototype.enableZoom = function() {
	if (!this.media) {
		return false;
	}

	this.zoomOverlay = document.createElement('div');
	this.zoomOverlay.id = 'zoom-overlay';

	this.zoomOverlay.onclick = function() {
		console.log("overlay");
		document.querySelector('body').className = '';
		zoomOverlay.parentElement.removeChild(zoomOverlay);
		this.close();
		updateHistory();
	};

	var img = document.querySelector('img.media');
	this.zoomOverlay.appendChild(img);

	document.querySelector('body').appendChild(this.zoomOverlay);
	document.querySelector('body').className = 'zoomed';
};

Viewer.prototype.disableZoom = function() {
	if (this.zoomOverlay) {
		var img = document.querySelector('img.media');
		var picture = document.querySelector('#viewer .picture .row .image-container');
		picture.insertBefore(img, picture.firstChild);
		this.zoomOverlay.remove();
		this.zoomOverlay = null;
		document.querySelector('body').className = '';
		img.scrollIntoViewIfNeeded();
	}
};

Viewer.prototype.toggleZoom = function() {
	if (this.media.isZoomable()) {
		if (this.zoomOverlay) {
			this.disableZoom();
		} else {
			this.enableZoom();
		}
	}
};

Viewer.prototype.handleEvent = function(e) {
	switch (e.target) {
		case this.left:
		case this.right:
			return this.handleArrowEvent(e);
		case this.previewElement:
			return this.handlePreviewElementEvent(e);
		case this.picture:
			this.close();
			break;
	}
};

Viewer.prototype.handlePreviewElementEvent = function(e) {
	switch (e.type) {
		case 'click':
			console.log("click");
			console.log(this);
			console.log(this.previewElement);
			e.preventDefault();
			e.stopPropagation();

			if (this.media.isZoomable()) {
				this.toggleZoom();
			} else {
				this.close();
				updateHistory();
			}
			break;
		case 'load':
			var percent = this.previewElement.height/this.previewElement.naturalHeight * 100;

			if (percent > 99) {
				this.media.zoomable = false;
				this.previewElement.parentElement.className = this.previewElement.parentElement.className.replace(/zoomable/, '');
			} else {
				this.media.zoomable = true;
			}
			console.log(this.media);
			console.log(percent);
			console.log(this.media.isZoomable());

			var label = document.createElement('span');
			label.className = 'image-label';

			var text = document.createElement('span');
			text.innerHTML = this.previewElement.naturalWidth + 'x' + this.previewElement.naturalHeight + ' (' + Math.round(percent) + '%)';

			label.appendChild(text);

			this.element.appendChild(label);

			showImageTags(this.previewElement.dataset.pictureId);
			break;
	}
};

Viewer.prototype.handleArrowEvent = function(e) {
	if (e.type == 'click') {
		switch (e.target) {
			case this.left:
				e.preventDefault(); e.stopPropagation();
				this.showPrevious();
				break;
			case this.right:
				e.preventDefault(); e.stopPropagation();
				this.showNext();
				break;
		}
	}
};

Viewer.prototype.canPreview = function(thumbnail) {
	return thumbnail && thumbnail.media && (thumbnail.image || thumbnail.animated);
};

Viewer.prototype.showPrevious = function() {
	if (this.media) {
		var previous = this.media.parent.previous();
		while (previous && !this.canPreview(previous)) {
			previous = previous.previous();
		}

		if (this.canPreview(previous)) {
			this.show(previous.media);
		}
	}
};

Viewer.prototype.showNext = function() {
	if (this.media) {
		var next = this.media.parent.next();
		while (next && !this.canPreview(next)) {
			next = next.next();
		}

		if (this.canPreview(next)) {
			this.show(next.media);
		}
	}
};

Viewer.prototype.show = function(media) {
	this.media = media;

	this.close();

	if (!this.canPreview(media.parent)) {
		return false;
	}

	this.createElement();

	this.site.setCurrentMedia(media);
	updateHistory();

	if (media.parent.previous()) {
		this.left.className = '';
	}

	if (media.parent.next()) {
		this.right.className = '';
	}
	
	this.previewElement = media.previewElement();
	if (media.isVideo()) {
		this.container.className = 'video-container displayed-picture';
	} else {
		this.container.className = 'image-container displayed-picture zoomable';
	}

	this.previewElement.addEventListener('load', this);
	this.previewElement.addEventListener('click', this);

	this.container.insertBefore(this.previewElement, this.right);

	if (media.isVideo()) {
		var progress = document.createElement('progress');
		progress.value = 0;
		progress.max = 100;
		this.container.appendChild(progress);

		attachProgressUpdateHandler(this.previewElement, progress);
	}

	this.previewElement.focus();

	this.site.status.show(media);

	this.opened = true;
};

Viewer.prototype.close = function() {
	if (this.element && this.element.parentElement) {
		this.element.parentElement.removeChild(this.element);
		updateHistory();
	}

	if (this.zoomOverlay) {
		document.querySelector('body').className = '';
		this.zoomOverlay.parentElement.removeChild(this.zoomOverlay);
		this.close();
		updateHistory();
	}

	this.opened = false;
};

var Thumbnail = function(site, element) {
	if (site === undefined) {
		return;
	}

	this.site = site;

	this.element = element;
	this.linkElement = this.element.childNodes[0];
	this.dataset = this.linkElement.dataset;

	this.id = this.dataset.uniqueId;

	this.media = null;

	switch (this.dataset.media) {
		case "link":
			this.link = new Link(this);
			this.media = this.link;
			break;
		case "animated":
			this.animated = new Animated(this);
			this.media = this.animated;
			break;
		case "image":
			this.image = new Image(this);
			this.media = this.image;
			break;
	}

	this.setupEventHandlers();
};

// TODO
Thumbnail.create = function(site, data) {
	var category = data['type'].split(/\//)[0];
	switch (category) {
		case 'image':
		case 'video':
			var element = Thumbnail.createPictureElement(data);
			break;
		default:
			var element = Thumbnail.createLinkElement(data);
			break;
	}

	return new Thumbnail(site, element);
};

Thumbnail.createLinkElement = function(data) {
	var outer_span = document.createElement('span');
	outer_span.className = 'thumbnail';

	var link = document.createElement('a');
	link.id = 'thumbnail-' + data.id;
	link.href = data.url;
	link.target = '_blank';
	link.className = 'thumbnail-link link';

	// what a mess
	link.dataset.id = data['id'];
	link.dataset.url = data['url'];
	link.dataset.title = data['title'];
	link.dataset.description = data['description'];
	link.dataset.target = data['target'];
	link.dataset.userName = data['user'];
	link.dataset.tags = data['tags'].join(', ');
	link.dataset.date = data['date'];
	link.dataset.tribuneName = data['tribune-name'];
	link.dataset.tribuneUrl = data['tribune-url'];
	link.dataset.postId = data['post-id'];
	link.dataset.thumbnailSrc = data['thumbnail-src'];
	link.dataset.screenshotPng = data['screenshot-png'];
	link.dataset.screenshotPdf = data['screenshot-pdf'];
	link.dataset.context = data['context'];
	link.dataset.bloubs = data['bloubs'];
	link.dataset.type = data['type'];

	if (link.dataset.thumbnailSrc) {
		var img = document.createElement('img');
		img.src = link.dataset.thumbnailSrc;
		img.alt = '';
		img.className = 'link-preview';
		link.appendChild(img);
	}

	var text_span = document.createElement('span');
	text_span.className = 'link-text';
	text_span.title = '';
	if (link.dataset.title) {
		var span = document.createElement('span');
		span.className = 'link-title';
		span.innerHTML = link.dataset.title;
		text_span.title += link.dataset.title;
		text_span.appendChild(span);
	}

	if (link.dataset.description) {
		var span = document.createElement('span');
		span.className = 'link-description';
		span.innerHTML = link.dataset.description;
		text_span.title += link.dataset.description;
		text_span.appendChild(span);
	}

	link.appendChild(text_span);
	outer_span.appendChild(link);

	if (link.dataset.screenshotPng || link.dataset.screenshotPdf) {
		var extra_span = document.createElement('span');
		extra_span.className = 'link-extra';

		if (link.dataset.screenshotPng) {
			var a = document.createElement('a');
			a.target = '_blank';
			a.className = 'link-png';
			a.href = link.dataset.screenshotPng;
			a.innerHTML = 'PNG';

			extra_span.appendChild(a);
		}

		if (link.dataset.screenshotPdf) {
			var a = document.createElement('a');
			a.target = '_blank';
			a.className = 'link-pdf';
			a.href = link.dataset.screenshotPdf;
			a.innerHTML = 'PDF';

			extra_span.appendChild(a);
		}

		outer_span.appendChild(extra_span);
	}

	return outer_span;
};

Thumbnail.createPictureElement = function(data) {
	var span = document.createElement('span');
	span.className = 'thumbnail';

	var link = document.createElement('a');
	link.id = 'thumbnail-' + data.id;
	link.href = '+' + data.id;
	link.className = 'thumbnail-link';

	// what a mess
	link.dataset.url = data['url'];
	link.dataset.src = data['src'];
	link.dataset.thumbnailSrc = data['thumbnail-src'];
	link.dataset.animated = data['animated'];
	link.dataset.userName = data['user'];
	link.dataset.title = data['title'];
	link.dataset.tribuneName = data['tribune-name'];
	link.dataset.tribuneUrl = data['tribune-url'];
	link.dataset.postId = data['post-id'];
	link.dataset.date = data['date'];
	link.dataset.id = data['id'];
	link.dataset.tags = data['tags'].join(', ');
	link.dataset.md5 = data['md5'];
	link.dataset.type = data['type'];

	var img = document.createElement('img');
	img.height = '100';
	img.width = '100';
	img.src = link.dataset.thumbnailSrc;
	img.alt = '';

	link.appendChild(img);
	span.appendChild(link);

	return span;
};

Thumbnail.prototype.setupEventHandlers = function() {
	// Using addEventListener to setup this as the listener for these
	// events, which calls this.handleEvent for evert such registered
	// event, means we can easily preserve a reference to "this", and
	// still nicely break up the handlers into properties of this.

	this.element.addEventListener('mouseover', this);
	this.element.addEventListener('mouseout', this);

	if (!this.link) {
		this.element.addEventListener('click', this);
	}
};

Thumbnail.prototype.handleEvent = function(e) {
	switch (e.type) {
		case "mouseover":
			return "onMouseover" in this.media ? this.media.onMouseover(e) : null;
		case "mouseout":
			return "onMouseout" in this.media ? this.media.onMouseout(e) : null;
		case "click":
			return "onClick" in this.media ? this.media.onClick(e) : null;
	}
};

Thumbnail.prototype.previous = function() {
	return this.site.findThumbnail(this.element.previousSibling);
};

Thumbnail.prototype.next = function() {
	return this.site.findThumbnail(this.element.nextSibling);
};

Thumbnail.prototype.markNSFW = function() {
	if (this.dataset.media == "link") {
		return false;
	}

	var that = this;

	var req = new XMLHttpRequest();
	req.open('GET', 'nsfw.json?picture=' + this.dataset.id, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					that.linkElement.dataset.tags = data['tags'].join(', ');
					that.dataset.tags = data['tags'].join(', ');
				}
			}
		}
	};
	req.send(null);
};

var Link = function(par) {
	this.parent = par;
	this.site = this.parent.site;
};

Link.prototype.isZoomable = function() {
	return false;
};

Link.prototype.onMouseover = function(e) {
	e.preventDefault(); e.stopPropagation();
	this.site.status.show(this);
};

Link.prototype.onMouseout = function(e) {
	e.preventDefault(); e.stopPropagation();
	this.site.status.reset();
};

Link.prototype.isVideo = function() {
	return false;
};

Link.prototype.previewElement = function() {
	// Links shouldn't be previewed, at least for now.
	var element = document.createElement('span');
	return element;
};

var Image = function(par) {
	this.parent = par;
	this.site = this.parent.site;

	// This is updated on load
	this.zoomable = false;
};

Image.prototype.isZoomable = function() {
	return this.zoomable;
};

Image.prototype.onMouseover = function(e) {
	e.preventDefault(); e.stopPropagation();
	this.site.status.show(this);
};

Image.prototype.onMouseout = function(e) {
	e.preventDefault(); e.stopPropagation();
	this.site.status.reset();
};

Image.prototype.onClick = function(e) {
	e.preventDefault();

	if (e.shiftKey && e.ctrlKey) {
		this.parent.markNSFW(this.parent.linkElement);
	} else {
		this.site.viewer.show(this);
	}
};

Image.prototype.isVideo = function() {
	return false;
};

Image.prototype.previewElement = function() {
	var element = document.createElement('img');
	element.src = this.parent.dataset.src;
	element.className = 'media';
	element.dataset.pictureId = this.parent.dataset.id;

	return element;
};

var Animated = function(par) {
	this.parent = par;
	this.site = this.parent.site;
};

Animated.prototype.startAnimation = function() {
	if (this.isVideo()) {
		var video = document.createElement('video');
		this.parent.dataset.thumbnailSrc = this.parent.linkElement.firstChild.src;
		video.src = this.parent.dataset.animated;
		video.autoplay = true;
		video.muted = true;
		video.controls = false;
		video.loop = true;
		video.playbackRate = 2;
		video.poster = this.parent.dataset.thumbnailSrc;
		this.parent.linkElement.removeChild(this.parent.linkElement.firstChild);
		this.parent.linkElement.appendChild(video);
	} else {
		this.parent.dataset.thumbnailSrc = this.parent.linkElement.firstChild.src;
		this.parent.linkElement.firstChild.src = this.parent.dataset.animated;
	}
};

Animated.prototype.stopAnimation = function() {
	if (this.isVideo()) {
		var img = document.createElement('img');
		img.src = this.parent.dataset.thumbnailSrc;
		img.height = 100;
		img.width = 100;
		this.parent.linkElement.removeChild(this.parent.linkElement.firstChild);
		this.parent.linkElement.appendChild(img);
	} else {
		this.parent.linkElement.firstChild.src = this.parent.dataset.thumbnailSrc;
	}
};

Animated.prototype.onMouseover = function(e) {
	e.preventDefault(); e.stopPropagation();
	this.startAnimation();
	this.site.status.show(this);
};

Animated.prototype.onMouseout = function(e) {
	e.preventDefault(); e.stopPropagation();
	this.stopAnimation();
	this.site.status.reset();
};

Animated.prototype.onClick = function(e) {
	e.preventDefault();

	if (e.shiftKey && e.ctrlKey) {
		this.parent.markNSFW(this.parent.linkElement);
	} else {
		this.site.viewer.show(this);
	}
};

Animated.prototype.isZoomable = function() {
	return false;
};

Animated.prototype.isVideo = function() {
	return this.parent.dataset.src && this.parent.dataset.src.match(/.webm$/);
};

Animated.prototype.previewElement = function() {
	if (this.isVideo()) {
		var element = document.createElement('video');
		element.src = this.parent.dataset.src;
		element.className = 'media';
		element.autoplay = true;
		element.muted = true;
		element.controls = false;
		element.loop = true;
		element.dataset.pictureId = this.parent.dataset.id;
	} else {
		var element = document.createElement('img');
		element.src = this.parent.dataset.src;
		element.className = 'media';
		element.dataset.pictureId = this.parent.dataset.id;
	}

	return element;
};

var sauf = new Sauf(document);
