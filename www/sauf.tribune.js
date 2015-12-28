"use strict";

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
						var form = this;
						postComment(image.dataset.tribuneName, this.message.value, function(success, new_comments) {
							if (success) {
								form.message.value = "";
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
