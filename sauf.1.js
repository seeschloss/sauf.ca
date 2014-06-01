// creates a global "addWheelListener" method
// example: addWheelListener( elem, function( e ) { console.log( e.deltaY ); e.preventDefault(); } );
(function(window,document) {

    var prefix = "", _addEventListener, onwheel, support;

    // detect event model
    if ( window.addEventListener ) {
        _addEventListener = "addEventListener";
    } else {
        _addEventListener = "attachEvent";
        prefix = "on";
    }

    // detect available wheel event
    support = "onwheel" in document.createElement("div") ? "wheel" : // Modern browsers support "wheel"
              document.onmousewheel !== undefined ? "mousewheel" : // Webkit and IE support at least "mousewheel"
              "DOMMouseScroll"; // let's assume that remaining browsers are older Firefox

    window.addWheelListener = function( elem, callback, useCapture ) {
        _addWheelListener( elem, support, callback, useCapture );

        // handle MozMousePixelScroll in older Firefox
        if( support == "DOMMouseScroll" ) {
            _addWheelListener( elem, "MozMousePixelScroll", callback, useCapture );
        }
    };

    function _addWheelListener( elem, eventName, callback, useCapture ) {
        elem[ _addEventListener ]( prefix + eventName, support == "wheel" ? callback : function( originalEvent ) {
            !originalEvent && ( originalEvent = window.event );

            // create a normalized event object
            var event = {
                // keep a ref to the original event object
                originalEvent: originalEvent,
                target: originalEvent.target || originalEvent.srcElement,
                type: "wheel",
                deltaMode: originalEvent.type == "MozMousePixelScroll" ? 0 : 1,
                deltaX: 0,
                delatZ: 0,
                preventDefault: function() {
                    originalEvent.preventDefault ?
                        originalEvent.preventDefault() :
                        originalEvent.returnValue = false;
                }
            };
            
            // calculate deltaY (and deltaX) according to the event
            if ( support == "mousewheel" ) {
                event.deltaY = - 1/40 * originalEvent.wheelDelta;
                // Webkit also support wheelDeltaX
                originalEvent.wheelDeltaX && ( event.deltaX = - 1/40 * originalEvent.wheelDeltaX );
            } else {
                event.deltaY = originalEvent.detail;
            }

            // it's time to fire the callback
            return callback( event );

        }, useCapture || false );
    }

})(window,document);

if (!Element.prototype.scrollIntoViewIfNeeded) {
  Element.prototype.scrollIntoViewIfNeeded = function (centerIfNeeded) {
    centerIfNeeded = arguments.length === 0 ? true : !!centerIfNeeded;

    var parent = this.parentNode,
        parentComputedStyle = window.getComputedStyle(parent, null),
        parentBorderTopWidth = parseInt(parentComputedStyle.getPropertyValue('border-top-width')),
        parentBorderLeftWidth = parseInt(parentComputedStyle.getPropertyValue('border-left-width')),
        overTop = this.offsetTop - parent.offsetTop < parent.scrollTop,
        overBottom = (this.offsetTop - parent.offsetTop + this.clientHeight - parentBorderTopWidth) > (parent.scrollTop + parent.clientHeight),
        overLeft = this.offsetLeft - parent.offsetLeft < parent.scrollLeft,
        overRight = (this.offsetLeft - parent.offsetLeft + this.clientWidth - parentBorderLeftWidth) > (parent.scrollLeft + parent.clientWidth),
        alignWithTop = overTop && !overBottom;

    if ((overTop || overBottom) && centerIfNeeded) {
      parent.scrollTop = this.offsetTop - parent.offsetTop - parent.clientHeight / 2 - parentBorderTopWidth + this.clientHeight / 2;
    }

    if ((overLeft || overRight) && centerIfNeeded) {
      parent.scrollLeft = this.offsetLeft - parent.offsetLeft - parent.clientWidth / 2 - parentBorderLeftWidth + this.clientWidth / 2;
    }

    if ((overTop || overBottom || overLeft || overRight) && !centerIfNeeded) {
      this.scrollIntoView(alignWithTop);
    }
  };
}


var currentImage = null;
var currentTerm = '';
var currentAnimated = false;

var fullImageHandlers = function(img) {
	img.onload = function() {
		var percent = this.height/this.naturalHeight * 100;

		if (percent > 99) {
			this.className = this.className.replace(/zoomable/, '');
		}

		var label = document.createElement('span');
		label.className = 'image-label';

		var text = document.createElement('span');
		text.innerHTML = this.naturalWidth + 'x' + this.naturalHeight + ' (' + Math.round(percent) + '%)';

		label.style.width = (this.width + 1) + 'px';
		label.appendChild(text);

		this.parentElement.appendChild(label);

		showImageTags(img.dataset.pictureId);
	};

	img.onclick = function(e) {
		e.preventDefault();
		e.stopPropagation();

		if (!this.className.match(/zoomable/)) {
			closeViewer();
		} else {
			toggleZoom();
		}
	};
};

var previousImage = function(image) {
	return image.previousSibling;
};

var nextImage = function(image) {
	return image.nextSibling;
};

var showImage = function(image) {
	if (document.querySelector('#viewer')) {
		closeViewer();
	}

	setCurrentImage(image);

	if (history && history.pushState) {
		history.pushState({image: image.dataset.id}, "Sauf.ça - " + image.dataset.url, "/+" + image.dataset.id);
	}

	var viewer = document.createElement('div');
	viewer.id = 'viewer';

	document.querySelector('body').appendChild(viewer);

	var extra = document.createElement('div');
	extra.className = 'extra';

	var picture = document.createElement('div');
	picture.className = 'picture';

	var left = document.createElement('a');
	left.innerHTML = '<';
	left.id = 'arrow-left';
	left.href = "";
	picture.appendChild(left);
	if (!previousImage(image)) {
		left.className = 'hidden';
	} else {
		left.onclick = function(e) {
			e.preventDefault(); e.stopPropagation(); rewindCurrentImage();
			if (currentImage && document.querySelector('#viewer')) {
				showImage(currentImage);
			}
		};
	}
	
	if (image.dataset.src.match(/.webm$/)) {
		var video = document.createElement('video');
		fullImageHandlers(video);
		video.className = 'displayed-picture';
		video.src = image.dataset.src;
		video.autoplay = true;
		video.muted = true;
		video.controls = false;
		video.loop = true;
		video.dataset.pictureId = image.dataset.id;
		picture.appendChild(video);
		video.focus();
	} else {
		var img = document.createElement('img');
		fullImageHandlers(img);
		img.className = 'displayed-picture zoomable';
		img.src = image.dataset.src;
		img.dataset.pictureId = image.dataset.id;
		picture.appendChild(img);
		img.focus();
	}

	var right = document.createElement('a');
	right.id = 'arrow-right';
	right.innerHTML = '>';
	right.href = "";
	picture.appendChild(right);
	if (!nextImage(image)) {
		right.className = 'hidden';
	} else {
		right.onclick = function(e) {
			e.preventDefault(); e.stopPropagation(); advanceCurrentImage();
			if (currentImage && document.querySelector('#viewer')) {
				showImage(currentImage);
			}
		};
	}

	showImageStatus(image);

	var info = document.createElement('div');
	info.className = 'info';

	viewer.appendChild(extra);
	viewer.appendChild(picture);
	viewer.appendChild(info);

	viewer.onclick = function() {
		closeViewer();
	};

	setTimeout(function() {
		addWheelListener(viewer, viewerScrollHandler);
	}, 250);
};

var showImageTags = function(image_id) {
	var image = document.querySelector('#thumbnails a[data-id="' + image_id + '"]');
	if (image) {
		document.querySelector('#viewer .image-label span').innerHTML += " " + image.dataset.tags;
	}
};

var lastScroll = 0;
var viewerScrollHandler = function(e) {
	var now = (new Date()).getTime();
	if (now - lastScroll < 250) {
		return;
	}

	var delta = 0;

	if (e.wheelDelta) delta = e.wheelDelta / 120;
	if (e.detail) delta = -e.detail / 3;

	if (!delta && e.deltaY) delta = e.deltaY;

	if (delta > 0) {
		advanceCurrentImage();
		if (currentImage && document.querySelector('#viewer')) {
			showImage(currentImage);
		}
		lastScroll = now;
	} else if (delta < 0) {
		rewindCurrentImage();
		if (currentImage && document.querySelector('#viewer')) {
			showImage(currentImage);
		}
		lastScroll = now;
	}
};

var attachClickHandler = function(image) {
	image.onclick = function(e) {
		e.preventDefault();

		if (e.shiftKey && e.ctrlKey) {
			markNsfw(this);
		} else {
			showImage(this);
		}
	};

	image.onmouseover = function(e) {
		e.preventDefault(); e.stopPropagation();
		startAnimation(this);
		showImageStatus(this);
	};

	image.onmouseout = function(e) {
		e.preventDefault(); e.stopPropagation();
		stopAnimation(this);
		resetStatus();
	};
};

var startAnimation = function(image) {
	if (image.dataset.animated) {
		if (image.dataset.src.match(/.webm$/)) {
			var video = document.createElement('video');
			image.dataset.thumbnailSrc = image.firstChild.src;
			video.src = image.dataset.animated;
			video.autoplay = true;
			video.muted = true;
			video.controls = false;
			video.loop = true;
			video.poster = image.dataset.thumbnailSrc;
			image.removeChild(image.firstChild);
			image.appendChild(video);
		} else {
			image.dataset.thumbnailSrc = image.firstChild.src;
			image.firstChild.src = image.dataset.animated;
		}
	}
};

var stopAnimation = function(image) {
	if (image.dataset.animated) {
		if (image.dataset.src.match(/.webm$/)) {
			var img = document.createElement('img');
			img.src = image.dataset.thumbnailSrc;
			img.height = 100;
			img.width = 100;
			image.removeChild(image.firstChild);
			image.appendChild(img);
		} else {
			image.firstChild.src = image.dataset.thumbnailSrc;
		}
	}
};

var markNsfw = function(image) {
	var req = new XMLHttpRequest();
	req.open('GET', 'http://sauf.ca/nsfw.json?picture=' + image.dataset.id, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					image.dataset.tags = data['tags'].join(', ');
				}
			}
		}
	};
	req.send(null);
};

var closeViewer = function() {
	var viewer = document.querySelector('#viewer');
	viewer.parentElement.removeChild(viewer);

	if (history && history.pushState) {
		if (currentTerm || currentAnimated) {
			history.pushState({search: currentTerm, animated: currentAnimated}, "Sauf.ça", "/" + (currentAnimated ? '!' : '?') + currentTerm);
		} else {
			history.pushState({}, "Sauf.ça", "/");
		}
	}
};

var resetStatus = function() {
	if (!document.querySelector('#viewer')) {
		document.querySelector('#status').innerHTML = '';
	}
}

var showImageStatus = function(image) {
	var status = document.querySelector('#status');
	status.innerHTML = '';

	var date = document.createElement('span');
	date.className = 'date';
	date.innerHTML = formatDate(image.dataset.date);
	status.appendChild(date);

	if (image.dataset.url) {
		var link = document.createElement('a');
		link.className = 'link';
		link.href = image.dataset.url;
		link.innerHTML = link.href;
		link.innerHTML = link.innerHTML.replace(/https?:\/\//, '');
		if (link.innerHTML.length > 20) {
			link.innerHTML = link.innerHTML.substr(0, 20) + '...';
		}
		status.appendChild(link);
	}

	var google = document.createElement('a');
	google.className = 'google';
	google.href = 'https://www.google.com/searchbyimage?hl=en&safe=off&site=search&image_url=' + image.dataset.url;
	google.innerHTML = 'Google';
	status.appendChild(google);

	if (image.dataset.tribuneUrl && image.dataset.tribuneName) {
		var tribune = document.createElement('a');
		tribune.className = 'tribune';
		tribune.href = image.dataset.tribuneUrl;
		tribune.innerHTML = image.dataset.tribuneName;
		status.appendChild(tribune);
	}

	if (image.dataset.userName) {
		var user = document.createElement('a');
		user.className = 'user';
		user.href = image.dataset.tribuneUrl;
		if (image.dataset.tribuneName == "dlfp") {
			// bombefourchette.com history here
			var date = new Date(+image.dataset.date * 1000);

			var day = date.getDate();
			var month = date.getMonth() + 1;
			var year = date.getFullYear();
			if (day   < 10) { day   = "0" + day;    }
			if (month < 10) { month = "0" + month;  }
			user.href = "http://bombefourchette.com/t/DLFP/" + year + "-" + month + "-" + day;
			if (+image.dataset.postId > 0) {
				user.href += "#" + image.dataset.postId;
			}
		}
		user.innerHTML = image.dataset.userName;
		status.appendChild(user);
	}

	var title = document.createElement('span');
	title.className = 'title';
	title.innerHTML = image.dataset.title;
	status.appendChild(title);
};

var toggleZoom = function() {
	var zoomOverlay = document.querySelector('#zoom-overlay');
	var img = document.querySelector('img.displayed-picture');

	if (!img || !img.className.match(/zoomable/)) {
		return false;
	}

	if (zoomOverlay) {
		var picture = document.querySelector('#viewer .picture');
		picture.insertBefore(img, picture.firstChild);
		zoomOverlay.parentElement.removeChild(zoomOverlay);
		document.querySelector('body').className = '';
		img.scrollIntoViewIfNeeded();
	} else {
		zoomOverlay = document.createElement('div');
		zoomOverlay.id = 'zoom-overlay';

		zoomOverlay.onclick = function() {
			document.querySelector('body').className = '';
			zoomOverlay.parentElement.removeChild(zoomOverlay);
			closeViewer();
		};

		zoomOverlay.appendChild(img);

		document.querySelector('body').appendChild(zoomOverlay);
		document.querySelector('body').className = 'zoomed';
	}
};

var advanceCurrentImage = function() {
	var zoomOverlay = document.querySelector('#zoom-overlay');
	if (zoomOverlay) {
		zoomOverlay.parentElement.removeChild(zoomOverlay);
		document.querySelector('body').className = '';
	}

	if (!currentImage) {
		currentImage = document.querySelector('#thumbnails a:first-child');
	} else if (currentImage.nextSibling) {
		currentImage.className = currentImage.className.replace(/current/, '');
		currentImage = currentImage.nextSibling;
	}

	if (currentImage && !currentImage.className.match(/current/)) {
		currentImage.className += ' current';
	}
};

var rewindCurrentImage = function() {
	var zoomOverlay = document.querySelector('#zoom-overlay');
	if (zoomOverlay) {
		zoomOverlay.parentElement.removeChild(zoomOverlay);
		document.querySelector('body').className = '';
	}

	if (!currentImage) {
		currentImage = document.querySelector('#thumbnails a:first-child');
	} else if (currentImage.previousSibling) {
		currentImage.className = currentImage.className.replace(/current/, '');
		currentImage = currentImage.previousSibling;
	}

	if (currentImage && !currentImage.className.match(/current/)) {
		currentImage.className += ' current';
	}
};

var setCurrentImage = function(image) {
	var previous = document.querySelector('#thumbnails a.current');
	if (previous) {
		previous.className = previous.className.replace(/current/, '');
	}

	currentImage = image;
	if (currentImage) {
		currentImage.className += ' current';
		showImageStatus(image);
	}
};

var performSearch = function(term, animated) {
	currentTerm = term;
	currentAnimated = animated;
	if (term == "" && !animated) {
		if (history && history.pushState) {
			history.pushState({}, "Sauf.ça", "/");
		}
		return resetToLatest();
	}

	if (history && history.pushState) {
		if (animated) {
			history.pushState({search: term, animated: true}, "Sauf.ça", "/!" + term);
		} else {
			history.pushState({search: term, animated: false}, "Sauf.ça", "/?" + term);
		}
	}
	var uri = 'http://sauf.ca/latest.json?count=250&search=' + encodeURIComponent(term);
	if (animated) {
		uri += '&animated=1';
	}
	var req = new XMLHttpRequest();
	req.open('GET', uri, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					document.querySelector('#thumbnails').innerHTML = '';
					data.sort(function(a, b) { return +a.id > +b.id ? -1 : 1 });
					for (var i in data) {
						appendNewPicture(data[i]);
					}
				}
			}
		}
	};
	req.send(null);
};

var resetToLatest = function() {
	var req = new XMLHttpRequest();
	req.open('GET', 'http://sauf.ca/latest.json?since=0', true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					data.sort(function(a, b) { return +a.id > +b.id ? 1 : -1 });
					document.querySelector('#thumbnails').innerHTML = '';
					for (var i in data) {
						prependNewPicture(data[i]);
					}
				}
			}
		}
	};
	req.send(null);
};

var appendOldPictures = function() {
	var oldest = 0;
	if (document.querySelector('#thumbnails a:last-child')) {
		oldest = +document.querySelector('#thumbnails a:last-child').dataset.id;
	}

	var url = 'http://sauf.ca/history.json?count=250&until=' + oldest;
	if (currentTerm != "") {
		url += "&search=" + encodeURIComponent(currentTerm);
	}
	if (currentAnimated) {
		url += '&animated=1';
	}

	var req = new XMLHttpRequest();
	req.open('GET', url, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					for (var i in data) {
						appendNewPicture(data[i]);
					}
				}
			}
		}
	};
	req.send(null);
};

var prependNewPictures = function() {
	var newest = 0;
	if (document.querySelector('#thumbnails a:first-child')) {
		newest = +document.querySelector('#thumbnails a:first-child').dataset.id;
	}

	var url = 'http://sauf.ca/latest.json?since=' + newest;
	if (currentTerm != "") {
		url += "&search=" + encodeURIComponent(currentTerm);
	}
	if (currentAnimated) {
		url += '&animated=1';
	}

	var req = new XMLHttpRequest();
	req.open('GET', url, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					data.sort(function(a, b) { return +a.id > +b.id ? 1 : -1 });
					for (var i in data) {
						prependNewPicture(data[i]);
					}
				}
			}
		}
	};
	req.send(null);
};

var appendNewPicture = function(data) {
	if (document.querySelector('#thumbnails a[data-id="' + data.id + '"]')) {
		return false;
	}

	var link = document.createElement('a');
	link.id = 'thumbnail-' + data.id;
	link.href = 'http://sauf.ca/+' + data.id;
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

	var img = document.createElement('img');
	img.height = '100';
	img.width = '100';
	img.src = link.dataset.thumbnailSrc;
	img.alt = '';

	link.appendChild(img);

	var thumbnails = document.querySelector('#thumbnails');
	thumbnails.appendChild(link);

	attachClickHandler(link);
};

var prependNewPicture = function(data) {
	if (document.querySelector('#thumbnails a[data-id="' + data.id + '"]')) {
		return false;
	}

	var link = document.createElement('a');
	link.id = 'thumbnail-' + data.id;
	link.href = 'http://sauf.ca/+' + data.id;
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

	var img = document.createElement('img');
	img.src = link.dataset.thumbnailSrc;
	img.alt = '';
	//img.style.width = '0';

	link.appendChild(img);

	//setTimeout(function() { img.style.width = '100px'; }, 100);


	var thumbnails = document.querySelector('#thumbnails');
	thumbnails.insertBefore(link, thumbnails.firstChild);

	attachClickHandler(link);
};

document.querySelector('body').onkeydown = function(e) {
	switch (e.which) {
		case 13: // enter
			if (currentImage) {
				var viewer = document.querySelector('#viewer');
				if (viewer) {
					closeViewer();

					var zoom = document.querySelector('#zoom-overlay');
					if (zoom) {
						zoom.parentElement.removeChild(zoom);
						document.querySelector('body').className = '';
					}
				} else {
					showImage(currentImage);
				}
			}
			break;
		case 27: // esc
			var viewer = document.querySelector('#viewer');
			if (viewer) {
				closeViewer();
			}

			var zoom = document.querySelector('#zoom-overlay');
			if (zoom) {
				zoom.parentElement.removeChild(zoom);
				document.querySelector('body').className = '';
			}
			break;
		case 32: // space
			toggleZoom();
			if (!document.activeElement.type == "search") {
				e.preventDefault();
				e.stopPropagation();
			}
			break;
		case 35: // end
			if (currentImage) {
				currentImage.className = currentImage.className.replace(/current/, '');
			}
			currentImage = document.querySelector('#thumbnails a:last-child');
			if (currentImage && !currentImage.className.match(/current/)) {
				currentImage.className += ' current';
			}
			showImageStatus(currentImage);
			if (currentImage && document.querySelector('#viewer')) {
				showImage(currentImage);
			}
			currentImage.scrollIntoViewIfNeeded();
			break;
		case 36: // home
			if (currentImage) {
				currentImage.className = currentImage.className.replace(/current/, '');
			}
			currentImage = document.querySelector('#thumbnails a:first-child');
			if (currentImage && !currentImage.className.match(/current/)) {
				currentImage.className += ' current';
			}
			showImageStatus(currentImage);
			if (currentImage && document.querySelector('#viewer')) {
				showImage(currentImage);
			}
			currentImage.scrollIntoViewIfNeeded();
			break;
		case 37: // left arrow
			rewindCurrentImage();
			showImageStatus(currentImage);
			if (currentImage && document.querySelector('#viewer')) {
				showImage(currentImage);
			}
			currentImage.scrollIntoViewIfNeeded();
			break;
		case 39: // right arrow
			advanceCurrentImage();
			showImageStatus(currentImage);
			if (currentImage && document.querySelector('#viewer')) {
				showImage(currentImage);
			}
			currentImage.scrollIntoViewIfNeeded();
			break;
		case 81: // q
			if (document.querySelector('#content').className.match(/quiet/)) {
				document.querySelector('#content').className = document.querySelector('#content').className.replace(/quiet/, '');
			} else {
				document.querySelector('#content').className += ' quiet';
			}
			break;
		case 82: // r
			prependNewPictures();
			break;
		default:
			console.log(e.which);
	}
};

var formatDate = function(timestamp) {
	var date = new Date(timestamp * 1000);

	var day = date.getDate();
	var month = date.getMonth() + 1;
	var year = date.getFullYear();

	var hour = date.getHours();
	var minute = date.getMinutes();

	if (day    < 10) { day    = "0" + day;    }
	if (month  < 10) { month  = "0" + month;  }
	if (hour   < 10) { hour   = "0" + hour;   }
	if (minute < 10) { minute = "0" + minute; }

	return day + "/" + month + "/" + year + " " + hour + ":" + minute;
};

var images = document.querySelectorAll('a.thumbnail-link');

for (var i = 0; i < images.length; i++) {
	var image = images.item(i);

	attachClickHandler(image);
}

var picture = document.querySelector('#viewer .displayed-picture');
if (picture) {
	var picture_id = picture.dataset.pictureId;
	var image = document.querySelector('#thumbnails a[data-id="' + picture_id + '"]');
	fullImageHandlers(picture);
	document.querySelector('#viewer').onclick = function() {
		closeViewer();
	};
	setCurrentImage(image);
	setTimeout(function() {
		addWheelListener(document.querySelector('#viewer'), viewerScrollHandler);
	}, 250);
}

setInterval(prependNewPictures, 5 * 60 * 1000);

document.querySelector('#thumbnails-wrapper').onscroll = function() {
	if (this.firstElementChild.offsetHeight - this.scrollTop < this.offsetHeight*2) {
		appendOldPictures();
	}
};

document.querySelector('#search input').onkeydown = function(e) {
	e.stopPropagation();
}

document.querySelector('#search input[type="checkbox"]').onchange = function(e) {
	e.preventDefault();
	e.stopPropagation();
	performSearch(this.parentElement.search.value, this.parentElement.animated.checked);
};

document.querySelector('#search').onkeydown = function(e) {
	e.stopPropagation();
};

document.querySelector('#search').onsubmit = function(e) {
	e.preventDefault();
	e.stopPropagation();
	performSearch(this.search.value, this.animated.checked);
};

if (document.location.pathname.substr(0, 2) == "/!") {
	document.querySelector('#search input[type="checkbox"]').checked = true;
	document.querySelector('#search input[type="search"]').value = decodeURIComponent(document.location.pathname.substr(2));
	currentTerm = document.querySelector('#search input[type="search"]').value;
	currentAnimated = true;
} else if (document.location.search.substr(0, 1) == "?") {
	document.querySelector('#search input[type="checkbox"]').checked = false;
	document.querySelector('#search input[type="search"]').value = decodeURIComponent(document.location.search.substr(1));
	currentTerm = document.querySelector('#search input[type="search"]').value;
	currentAnimated = false;
}

// hmm
window.onpopstate = function() {
	if (document.location.pathname.substr(0, 2) == "/!") {
		document.querySelector('#search input[type="checkbox"]').checked = true;
		document.querySelector('#search input[type="search"]').value = decodeURIComponent(document.location.pathname.substr(2));
		performSearch(document.querySelector('#search input[type="search"]').value, true);
	} else if (document.location.search.substr(0, 1) == "?") {
		document.querySelector('#search input[type="checkbox"]').checked = false;
		document.querySelector('#search input[type="search"]').value = decodeURIComponent(document.location.search.substr(1));
		performSearch(document.querySelector('#search input[type="search"]').value, false);
	}
};

var left = document.querySelector('#arrow-left');
if (left) {
	left.onclick = function(e) {
		e.preventDefault(); e.stopPropagation(); rewindCurrentImage();
		if (currentImage && document.querySelector('#viewer')) {
			showImage(currentImage);
		}
	};
}

var right = document.querySelector('#arrow-right');
if (right) {
	right.onclick = function(e) {
		e.preventDefault(); e.stopPropagation(); advanceCurrentImage();
		if (currentImage && document.querySelector('#viewer')) {
			showImage(currentImage);
		}
	};
}

window.onpopstate = function(e) {
	if (document.location.pathname.match(/^\/+/)) {
		var image_id = document.location.pathname.substr(2);
		var image = document.querySelector('#thumbnail-' + image_id);
		if (image) {
			showImage(image);
		}
	}
};

