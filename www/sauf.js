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

var updateHistory = function() {
	if (history && history.pushState) {
		var title = "Sauf.ça";
		var data = {
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
			sauf.prependNewThumbnails();
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

setInterval(sauf.prependNewThumbnails, 5 * 60 * 1000);

document.querySelector('#thumbnails-wrapper').onscroll = function() {
	if (this.firstElementChild.offsetHeight - this.scrollTop < this.offsetHeight*2) {
		sauf.appendOldThumbnails();
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

