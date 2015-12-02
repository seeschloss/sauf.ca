"use strict";

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
var currentAnimated = true;
var currentPictures = true;
var currentLinks    = true;

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

		label.appendChild(text);

		this.parentElement.parentElement.parentElement.appendChild(label);

		showImageTags(img.dataset.pictureId);
	};

	img.onclick = function(e) {
		e.preventDefault();
		e.stopPropagation();

		if (img.parentElement.id == 'zoom-overlay') {
			sauf.viewer.toggleZoom();
		} else if (!this.parentElement.className.match(/zoomable/)) {
			closeViewer();
			updateHistory();
		} else {
			sauf.viewer.toggleZoom();
		}
	};
};

var previousImage = function(image) {
	if (!image) {
		return null;
	}

	var previous = image.previousSibling;
	while (previous && previous.target == '_blank') {
		previous = previous.previousSibling;
	}
	return previous;
};

var nextImage = function(image) {
	if (!image) {
		return null;
	}

	var next = image.nextSibling;
	while (next && next.target == '_blank') {
		next = next.nextSibling;
	}
	return next;
};

var attachProgressUpdateHandler = function(video, progress) {
	video.addEventListener('canplay', function() {
		progress.style.maxWidth = video.clientWidth + 'px';
	});

	window.onresize = function() {
		progress.style.maxWidth = video.clientWidth + 'px';
	};

	video.addEventListener('timeupdate', function() {
		var percent = (100 / video.duration) * video.currentTime;
		progress.value = percent;
	}, false);

	progress.addEventListener('click', function(e) {
		e.preventDefault();
		e.stopPropagation();

		var start = this.getClientRects()[0].left;
		var stop = this.getClientRects()[0].right;

		var position = (e.clientX - start) / (stop - start);

		video.currentTime = Math.round(video.duration * position * 100) / 100;
	});
};

var toggleComments = function(image) {
	if (image.dataset.tribuneName != 'dlfp') {
		return;
	}

	if (document.querySelector('#comments')) {
		var panel = document.querySelector('#comments');
		panel.className = '';
		setTimeout(function() {
			panel.remove();
		}, 500);

		if (document.querySelector('#show-comments')) {
			var arrow = document.querySelector('#show-comments');
			arrow.className = 'up';
		}

		return;
	}

	if (document.querySelector('#show-comments')) {
		var arrow = document.querySelector('#show-comments');
		arrow.className = 'down';
	}

	var panel = document.createElement('div');
	panel.id = 'comments';

	var viewer = document.querySelector('#viewer');
	viewer.insertBefore(panel, viewer.firstChild);

	var list = document.createElement('ul');

	getComments(image.dataset.postId, function(posts) {
		panel.appendChild(list);
		appendComments(panel, list, posts);

		var now = (new Date()).getTime() / 1000;
		if (+image.dataset.date > now - 36000) {
			checkCanPostComments(image.dataset.tribuneName, function(canPostComments) {
				if (canPostComments) {
					var form = document.createElement('form');
					form.id = 'post-form';
					var text = document.createElement('input');
					text.placeholder = 'coin ! coin !';
					text.id = 'message'; text.name = 'message';
					form.appendChild(text);
					panel.appendChild(form);

					form.onkeydown = function(e) {
						e.stopPropagation();
					};

					form.onsubmit = function(e) {
						e.preventDefault();
						e.stopPropagation();
						postComment(image.dataset.tribuneName, this.message.value, function(success, new_comments) {
							if (success) {
								this.message.value = "";
							}
							appendComments(panel, list, new_comments.filter(commentIsInDiscussion));
						});
					};
				} else {
					var form = document.createElement('form');
					form.id = 'post-form';
					form.action = 'oauth.php';
					var button = document.createElement('button');
					button.innerHTML = "S'authentifier sur DLFP";
					form.appendChild(button);
					panel.appendChild(form);
				}
			});
		} else {
			var span = document.createElement('span');
			span.className = 'too-old';
			span.innerHTML = '(trop vieux pour répondre)';
			panel.appendChild(span);
		}
	});

	addWheelListener(panel, function(e) {
		e.stopPropagation();
	});
	panel.onclick = function(e) {
		e.stopPropagation();
	}

	setTimeout(function() {
		panel.className = 'wide';
	}, 100);
};

var stringIsInDiscussion = function(string) {
	var clocks = document.querySelectorAll('.clock');
	for (var i = 0; i < clocks.length; i++) {
		var clock = clocks.item(i);

		if (string.match(clock.innerHTML)) {
			return true;
		}
	}

	return false;
};

var commentIsInDiscussion = function(post) {
	return stringIsInDiscussion(post.message);
};

var appendComments = function(panel, list, posts) {
	posts.forEach(function(post) {
		var clock = post.time.substr(8, 2) + ':' + post.time.substr(10, 2) + ':' + post.time.substr(12, 2);

		var li = document.createElement('li');
		li.innerHTML += '<span class="clock">' + clock + '</span> <span class="login">' + post.login + '</span> <span class="message">' + detectClocks(detectTotoz(post.message)) + '</span>';
		list.appendChild(li);
	});

	var clocks = document.querySelectorAll('.clock');
	for (var i = 0; i < clocks.length; i++) {
		var clock = clocks.item(i);
		clock.onmouseover = function(e) {
			panel.className = 'wide fade';
			highlightClocks(this.innerHTML);
		};
		clock.onmouseout = function(e) {
			unhighlightClocks();
			panel.className = 'wide';
		};
		clock.onclick = function(e) {
			var form = document.querySelector('#post-form');
			if (form) {
				form.message.value = this.innerHTML + ' ';
				form.message.focus();
			}
		};
	}

	var totozes = document.querySelectorAll('.totoz');
	for (var i = 0; i < totozes.length; i++) {
		var totoz = totozes.item(i);
		totoz.onmouseover = function(e) {
			var name = this.innerHTML.substr(2, this.innerHTML.length - 2 - 1);
			var url = 'https://totoz.eu/img/' + name;

			var img = document.createElement('img');
			img.src = url;
			img.className = 'totoz-img';
			img.style.left = (e.clientX + 2) + 'px';
			img.style.top = (e.clientY + 2) + 'px';

			document.querySelector('body').appendChild(img);
		};
		totoz.onmouseout = function(e) {
			var img = document.querySelector('img.totoz-img');
			if (img) {
				img.remove();
			}
		};
	}
};

var postComment = function(tribune, message, callback) {
	if (tribune != 'dlfp') {
		callback(false, []);
		return;
	}

	if (!stringIsInDiscussion(message)) {
		callback(false, []);
		return;
	}

	var url = 'oauth/dlfp/post.json';
	var req = new XMLHttpRequest();
	var params = 'message=' + message;
	req.open('POST', url, true);
	req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					callback(data.length > 0, data);
				}
			}
		}
	};
	req.send(params);
};

var checkCanPostComments = function(tribune, callback) {
	if (tribune != 'dlfp') {
		callback(false);
		return;
	}

	var url = 'oauth/dlfp/can_post.json';
	var req = new XMLHttpRequest();
	req.open('GET', url, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if ((data = JSON.parse(req.responseText)) !== undefined) {
					callback(data);
				}
			}
		}
	};
	req.send(null);
};

var detectClocks = function(message) {
	return message.replace(
		 /((([0-9]{4})-((0[1-9])|(1[0-2]))-((0[1-9])|([12][0-9])|(3[01])))#)?((([01]?[0-9])|(2[0-3])):([0-5][0-9])(:([0-5][0-9]))?([:\^][0-9]|[¹²³⁴⁵⁶⁷⁸⁹])?(@[0-9A-Za-z]+)?)/g,
		 "<span class='clock' data-timestamp='$3$4$7$12$15$17$18'>\$1\$11</span>"
	);
};

var detectTotoz = function(message) {
	return message.replace(
		 /(\[:[^\]]*\])/g,
		 "<span class='totoz'>\$1</span>"
	);
};

var highlightClocks = function(time) {
	var clocks = document.querySelectorAll('.clock');
	for (var i = 0; i < clocks.length; i++) {
		var clock = clocks.item(i);
		if (clock.innerHTML == time) {
			clock.className = 'clock highlighted';
		} else {
			clock.className = 'clock';
		}
	}
};

var unhighlightClocks = function() {
	var clocks = document.querySelectorAll('.clock');
	for (var i = 0; i < clocks.length; i++) {
		var clock = clocks.item(i);
		clock.className = 'clock';
	}
};

var getComments = function(post_id, callback) {
	var url = 'oauth/dlfp/conversation/' + post_id + '.json';
	var req = new XMLHttpRequest();
	req.open('GET', url, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					callback(data);
				}
			}
		}
	};
	req.send(null);
};

var showImageTags = function(image_id) {
	var image = document.querySelector('#thumbnails a[data-id="' + image_id + '"]');
	if (image && document.querySelector('#viewer .image-label span')) {
		document.querySelector('#viewer .image-label span').innerHTML += " " + image.dataset.tags;
	}
};

var lastScroll = 0;
var viewerScrollHandler = function(e) {
	e.stopPropagation();

	var now = (new Date()).getTime();
	if (now - lastScroll < 250) {
		return;
	}

	var delta = 0;

	if (e.wheelDelta) delta = e.wheelDelta / 120;
	if (e.detail) delta = -e.detail / 3;

	if (!delta && e.deltaY) delta = e.deltaY;

	if (delta > 0) {
		//advanceCurrentImage();
		if (currentImage && document.querySelector('#viewer')) {
			//showImage(currentImage);
		}
		lastScroll = now;
	} else if (delta < 0) {
		//rewindCurrentImage();
		if (currentImage && document.querySelector('#viewer')) {
			//showImage(currentImage);
		}
		lastScroll = now;
	}
};

var closeViewer = function() {
	currentImage = undefined;

	var viewer = document.querySelector('#viewer');
	if (viewer) {
		viewer.parentElement.removeChild(viewer);
	}

	var zoom = document.querySelector('#zoom-overlay');
	if (zoom) {
		zoom.parentElement.removeChild(zoom);
		document.querySelector('body').className = '';
	}
};

var showThumbnailStatus = function(thumbnail) {
	var category = thumbnail.dataset.type.split(/\//)[0];

	var status = document.querySelector('#status');
	status.innerHTML = '';

	var date = document.createElement('span');
	date.className = 'date';
	date.innerHTML = formatDate(thumbnail.dataset.date);
	status.appendChild(date);

	if (thumbnail.dataset.url) {
		var link = document.createElement('a');
		link.className = 'link';
		link.href = thumbnail.dataset.url;
		link.innerHTML = link.href;
		link.innerHTML = link.innerHTML.replace(/https?:\/\//, '');
		if (link.innerHTML.length > 40) {
			link.innerHTML = link.innerHTML.substr(0, 40) + '...';
		}
		status.appendChild(link);
	}

	var bloubs = document.createElement('a');
	bloubs.className = 'doublons hidden';
	bloubs.href = '=' + thumbnail.dataset.md5;
	bloubs.innerHTML = 'Doublons';
	status.appendChild(bloubs);

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
		status.appendChild(google);
	}

	if (thumbnail.dataset.tribuneUrl && thumbnail.dataset.tribuneName) {
		var tribune = document.createElement('a');
		tribune.className = 'tribune';
		tribune.href = thumbnail.dataset.tribuneUrl;
		tribune.innerHTML = thumbnail.dataset.tribuneName;
		status.appendChild(tribune);
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
		status.appendChild(user);
	}

	var title = document.createElement('span');
	title.className = 'title';
	if (category == 'image' || category == 'video') {
		title.innerHTML = thumbnail.dataset.title;
	} else {
		title.innerHTML = thumbnail.dataset.context;
	}
	status.appendChild(title);

	if (currentImage && thumbnail.dataset.tribuneName == "dlfp") {
		var upArrow = document.createElement('span');
		upArrow.id = 'show-comments';
		upArrow.className = 'up';
		upArrow.innerHTML = '▲';
		status.appendChild(upArrow);

		upArrow.onclick = function(e) {
			toggleComments(currentImage);
		};
	}
};

var updateHistory = function() {
	if (history && history.pushState) {
		var title = "Sauf.ça";
		var data = {
			upload: false
		};
		var url = "/";

		if (currentTerm || currentAnimated) {
			data.search = currentTerm;
			data.animated = currentAnimated;
			url = "/" + (currentAnimated ? '!' : '?') + currentTerm;
		}

		if (currentImage) {
			data.image = currentImage.dataset.id;
			url = "/+" + currentImage.dataset.id;
		}

		if (document.querySelector('#upload-form')) {
			data.upload = true;
			url = '/upload';
		}

		if (JSON.stringify(history.state) != JSON.stringify(data)) {
			history.pushState(data, title, url);
		}
	}
};

var performSearch = function(term) {
	currentTerm = term;

	var animatedParam = currentAnimated ? '1' : '0';
	var picturesParam = currentPictures ? '1' : '0';
	var linksParam = currentLinks ? '1' : '0';

	var uri = 'latest.json?count=250&animated=' + animatedParam + '&pictures=' + picturesParam + '&links=' + linksParam;
	if (currentTerm != "") {
		uri += "&search=" + encodeURIComponent(currentTerm);
	}
	var req = new XMLHttpRequest();
	req.open('GET', uri, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					document.querySelector('#thumbnails').innerHTML = '';
					data.sort(function(a, b) { return +a.date > +b.date ? -1 : 1 });
					for (var i in data) {
						sauf.appendThumbnail(data[i]);
					}
				}
			}
		}
	};
	req.send(null);
};

var appendOldThumbnails = function() {
	var oldest = 0;
	var last = sauf.lastThumbnail();
	if (last) {
		oldest = last.dataset.date;
	}

	sauf.retrieveThumbnails({
		until: oldest,
		count: 250,
		animated: currentAnimated ? '1' : '0',
		pictures: currentPictures ? '1' : '0',
		links: currentLinks ? '1' : '0',
		search: currentTerm != "" ? currentTerm : undefined,
		animated: currentAnimated ? 1 : undefined
	}, function(responseText) {
		var data;
		if (data = JSON.parse(responseText)) {
			data.sort(function(a, b) { return +a.id > +b.id ? 1 : -1 });
			for (var i in data) {
				sauf.appendThumbnail(data[i]);
			}
		}
	});
};

var prependNewThumbnails = function() {
	var newest = 0;
	if (document.querySelectorAll('#thumbnails a.picture').length) {
		newest = +document.querySelectorAll('#thumbnails a.picture').item(0).dataset.date;
	}

	sauf.retrieveThumbnails({
		since: newest,
		animated: currentAnimated ? '1' : '0',
		pictures: currentPictures ? '1' : '0',
		links: currentLinks ? '1' : '0',
		search: currentTerm != "" ? currentTerm : undefined,
		animated: currentAnimated ? 1 : undefined
	}, function(responseText) {
		var data;
		if (data = JSON.parse(responseText)) {
			data.sort(function(a, b) { return +a.id > +b.id ? 1 : -1 });
			for (var i in data) {
				sauf.prependThumbnail(data[i]);
			}
		}
	});
};

document.querySelector('body').onkeydown = function(e) {
	switch (e.which) {
		case 13: // enter
			if (sauf.currentMedia) {
				sauf.viewer.show(sauf.currentMedia);
			}
			break;
		case 27: // esc
			sauf.viewer.close();
			break;
		case 32: // space
			sauf.viewer.toggleZoom();

			if (document.activeElement.type != "search") {
				e.preventDefault();
				e.stopPropagation();
			}
			break;
		case 35: // end
			var last = sauf.lastThumbnail();
			sauf.setCurrentMedia(last.media);

			if (sauf.viewer.opened) {
				if (last.media && last.media.isZoomable()) {
					sauf.viewer.show(last.media);
				} else {
					sauf.viewer.close();
				}
			}
			break;
		case 36: // home
			var first = sauf.firstThumbnail();
			sauf.setCurrentMedia(first.media);

			if (sauf.viewer.opened) {
				if (first.media && first.media.isZoomable()) {
					sauf.viewer.show(first.media);
				} else {
					sauf.viewer.close();
				}
			}
			break;
		case 37: // left arrow
			if (sauf.viewer.opened) {
				sauf.viewer.showPrevious();
			} else {
				if (!sauf.currentMedia) {
					sauf.setCurrentMedia(sauf.firstThumbnail().media);
				} else {
					var previous = sauf.currentMedia.parent.previous();
					sauf.setCurrentMedia(previous.media);
				}
			}
			break;
		case 39: // right arrow
			if (sauf.viewer.opened) {
				sauf.viewer.showNext();
			} else {
				if (!sauf.currentMedia) {
					sauf.setCurrentMedia(sauf.firstThumbnail().media);
				} else {
					var next = sauf.currentMedia.parent.next();
					sauf.setCurrentMedia(next.media);
				}
			}
			break;
		case 81: // q
			if (document.querySelector('#content').className.match(/quiet/)) {
				document.querySelector('#content').className = document.querySelector('#content').className.replace(/quiet/, '');
				if (window.localStorage) {
					delete window.localStorage.quiet;
				}

			} else {
				document.querySelector('#content').className += ' quiet';
				if (window.localStorage) {
					window.localStorage.quiet = true;
				}
			}
			break;
		case 82: // r
			prependNewThumbnails();
			break;
		case 84: // t
			if (currentImage) {
				toggleComments(currentImage);
			}
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

var handleUpload = function(form) {
	var file = form.filedata.files[0];
	if (file) {
		postFile(file, form.comment.value);
	} else {
		postUrl(form.url.value, form.comment.value);
	}
};

var setupUpload = function() {
	var bloubs = document.querySelector('#sitebar .header');
	
	if (document.querySelector('#upload-form')) {
		var viewer = document.querySelector('#viewer');
		var form = document.querySelector('#upload-form');

		viewer.onclick = function() {
			closeViewer();
			updateHistory();
		};

		form.onclick = function(e) {
			e.stopPropagation();
		};

		form.onsubmit = function(e) {
			e.preventDefault();
			handleUpload(this);
		};
	}

	checkCanPostComments('dlfp', function(canPostComments) {
		if (!canPostComments) {
			return;
		}

		var upload = document.createElement('a');
		upload.id = 'upload-button';
		upload.href = 'upload';
		upload.innerHTML = '⤒';
		upload.title = 'Poster une nouvelle image sur DLFP';

		bloubs.appendChild(upload);

		upload.onclick = function(e) {
			e.preventDefault();
			showUpload();
			updateHistory();
		};
	});
};

var showUpload = function() {
	var viewer = document.createElement('div');
	viewer.id = 'viewer';

	var picture = document.createElement('div');
	picture.className = 'picture';

	var form = document.createElement('form');
	form.id = 'upload-form';
	form.action = 'upload';
	form.method = 'post';
	form.enctype = 'multipart/form-data';

	var input_file = document.createElement('input');
	input_file.type = 'file';
	input_file.name = 'filedata';
	input_file.id = 'upload-file';

	var input_url = document.createElement('input');
	input_url.type = 'text';
	input_url.name = 'url';
	input_url.id = 'upload-url';
	input_url.placeholder = 'URL';

	var upload_span = document.createElement('span');
	upload_span.id = 'upload-span';

	var input_comment = document.createElement('input');
	input_comment.type = 'text';
	input_comment.name = 'comment';
	input_comment.id = 'upload-comment';
	input_comment.placeholder = 'commentaire';

	var input_post = document.createElement('input');
	input_post.type = 'submit';
	input_post.name = 'post';
	input_post.id = 'upload-post';
	input_post.value = '⏎';

	var upload_explanation = document.createElement('p');
	upload_explanation.id = 'upload-explanation';
	upload_explanation.innerHTML = "Vous pouvez poster un lien vers une image ou une vidéo, ou bien uploader un fichier qui sera hébergé sur <a href='http://pomf.se'>pomf.se</a>.";

	upload_span.appendChild(input_file);
	upload_span.appendChild(input_url);
	form.appendChild(upload_explanation);
	form.appendChild(upload_span);
	form.appendChild(input_comment);
	form.appendChild(input_post);

	form.onclick = function(e) {
		e.stopPropagation();
	};

	form.onsubmit = function(e) {
		e.preventDefault();
		handleUpload(this);
	};

	viewer.appendChild(picture);
	picture.appendChild(form);

	viewer.onclick = function() {
		closeViewer();
		updateHistory();
	};

	document.querySelector('body').appendChild(viewer);
};

var postFile = function(file, comment) {
	var url = 'oauth/dlfp/upload.json';
	var req = new XMLHttpRequest();

	var fd = new FormData();
	fd.append('comment', comment);
	fd.append('filedata', file);

	req.open('POST', url, true);
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					if (!data.error) {
						closeViewer();
					}
				}
			}
		}
	};
	req.send(fd);
};

var postUrl = function(image, comment) {
	var url = 'oauth/dlfp/upload.json';
	var req = new XMLHttpRequest();
	var params = 'file=' + encodeURI(image) + '&comment=' + encodeURI(comment);
	req.open('POST', url, true);
	req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	req.onreadystatechange = function(e) {
		if (req.readyState == 4) {
			if (req.status == 200 || req.status == 0) {
				var data = null;
				if (data = JSON.parse(req.responseText)) {
					if (!data.error) {
						closeViewer();
					}
				}
			}
		}
	};
	req.send(params);
};

var images = document.querySelectorAll('span.thumbnail');

var picture = document.querySelector('#viewer .displayed-picture .media');
if (picture) {
	var picture_id = picture.dataset.pictureId;
	var image = document.querySelector('#thumbnails a[data-id="' + picture_id + '"]');
	fullImageHandlers(picture);
	document.querySelector('#viewer').onclick = function() {
		closeViewer();
		updateHistory();
	};

	var thumbnail = sauf.findThumbnail(image);
	if (thumbnail && thumbnail.media) {
		sauf.setCurrentMedia(thumbnail.media);
	}

	setTimeout(function() {
		addWheelListener(document.querySelector('#viewer'), viewerScrollHandler);
	}, 250);

	if (picture.tagName == 'VIDEO') {
		var progress = document.createElement('progress');

		progress.value = 0;
		progress.max = 100;
		picture.parentElement.appendChild(progress);

		attachProgressUpdateHandler(picture, progress);
	}
}

setupUpload();

setInterval(prependNewThumbnails, 5 * 60 * 1000);

document.querySelector('#thumbnails-wrapper').onscroll = function() {
	if (this.firstElementChild.offsetHeight - this.scrollTop < this.offsetHeight*2) {
		appendOldThumbnails();
	}
};

document.querySelector('#search input').onkeydown = function(e) {
	e.stopPropagation();
}

var checkboxes = document.querySelectorAll('#search input[type="checkbox"]');
for (var i = 0; i < checkboxes.length; i++) {
	var checkbox = checkboxes.item(i);

	checkbox.onchange = function(e) {
		e.preventDefault();
		e.stopPropagation();

		currentLinks = this.parentElement.links.checked;
		currentPictures = this.parentElement.pictures.checked;
		currentAnimated = this.parentElement.animated.checked;

		if (window.localStorage) {
			window.localStorage.showLinks = currentLinks;
			window.localStorage.showPictures = currentPictures;
			window.localStorage.showAnimated = currentAnimated;
		}

		performSearch(this.parentElement.search.value);
		updateHistory();
	};
}

document.querySelector('#search').onkeydown = function(e) {
	e.stopPropagation();
};

document.querySelector('#search').onsubmit = function(e) {
	e.preventDefault();
	e.stopPropagation();
	performSearch(this.search.value);
	updateHistory();
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

var left = document.querySelector('#arrow-left');
if (left) {
	left.onclick = function(e) {
		sauf.viewer.showPrevious();
	};
}

var right = document.querySelector('#arrow-right');
if (right) {
	right.onclick = function(e) {
		sauf.viewer.showNext();
	};
}

window.onpopstate = function(e) {
	var needsSearch = false;

	if (!history.state) {
		sauf.viewer.close();
		return;
	}

	if (history.state.search != undefined || history.state.search != currentTerm) {
		needsSearch = true;
		currentTerm = history.state.search ? history.state.search : "";
		document.querySelector('#search input[type="search"]').value = currentTerm ? currentTerm : "";
	} else {
		document.querySelector('#search input[type="search"]').value = "";
	}
	
	if (history.state.animated != undefined || history.state.animated != currentAnimated) {
		needsSearch = true;
		currentAnimated = history.state.animated ? history.state.animated : false;
		document.querySelector('#search input[type="checkbox"]').checked = currentAnimated ? currentAnimated : false;
	} else {
		document.querySelector('#search input[type="checkbox"]').checked = false;
	}
	
	if (history.state.image != undefined) {
		currentImage = document.querySelector('#thumbnail-' + history.state.image);
		var thumbnail = sauf.findThumbnail(currentImage.parentElement);

		if (thumbnail && thumbnail.media) {
			sauf.setCurrentMedia(thumbnail.media);

			sauf.viewer.show(thumbnail.media);
		}
	} else if (needsSearch || currentTerm || currentAnimated) {
		sauf.viewer.close();
		performSearch(currentTerm, currentAnimated);
	} else {
		sauf.viewer.close();
	}

	if (history.state.upload == true) {
		showUpload();
	}
};

if (window.localStorage && window.localStorage.quiet == "true") {
	document.querySelector('#content').className += ' quiet quiet-fast';
	document.querySelector('#content').className = document.querySelector('#content').className.replace(/quiet-fast/, '');
}

if (window.localStorage) {
	var needsSearch = false;

	if (window.localStorage.showLinks != undefined) {
		currentLinks = window.localStorage.showLinks == "true";
		document.querySelector('#search #checkbox-links').checked = currentLinks;
		needsSearch = true;
	}
	if (window.localStorage.showPictures != undefined) {
		currentPictures = window.localStorage.showPictures == "true";
		document.querySelector('#search #checkbox-pictures').checked = currentPictures;
		needsSearch = true;
	}
	if (window.localStorage.showAnimated != undefined) {
		currentAnimated = window.localStorage.showAnimated == "true";
		document.querySelector('#search #checkbox-animated').checked = currentAnimated;
		needsSearch = true;
	}

	if (needsSearch) {
		//performSearch(currentTerm, currentAnimated);
	}
}

