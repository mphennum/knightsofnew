(function(KON) {

'use strict';

var UI = KON.UI = KON.UI || {};
var Sub = UI.Sub = UI.Sub || {};

KON.modules['UI.Sub'] = true;

Sub.__init__ = function(callback) {
	delete Sub.__init__;

	KON.load('UI.Tmpl', function() {
		var $ = KON.$;
		var $window = KON.$window;
		var $main = KON.$main;

		var Tmpl = UI.Tmpl;

		var MAXCOLS = 6;
		var ARTICLEWIDTH = 295;

		Sub = UI.Sub = function(opts) {
			if (!(this instanceof Sub)) {
				return new Sub(opts);
			}

			opts = opts || {};

			Tmpl.prototype.constructor.call(this, opts);

			this.playing = true;
			this.sub = null;
			this.since = null;
			this.seen = {};
			this.$sections = [];
			this.totalArticles = 0;
			this.$articles = [];
			this.$paused = [];

			this.queue = opts.queue || [];

			return this;
		};

		Sub.prototype = Object.create(Tmpl.prototype);

		// posts

		var renderPost = function(force) {
			if (this.queue.length) {
				this.$waiting.hide();
			} else {
				this.$waiting.fadeIn(300);
			}

			if (!this.playing) {
				return;
			}

			var post = this.queue.pop();
			if (!post) {
				return;
			}

			var $article = $(
				'<article style="display: none" data-id="' + post.id + '" data-ts="' + post.created + '">' +
					'<span class="kon-ts">' + KON.ago(post.created) + '</span>' +
					'<h2><a href="' + post.url + '">' + post.title + '</a></h2>' +
					'<p>posted by <a href="' + KON.urls.reddit + 'u/' + post.author + '">' + post.author + '</a> in <a href="' + KON.urls.www + 'r/' + post.sub + '">/r/' + post.sub + '</a></p>' +
					'<p class="kon-image"><a href="' + post.url + '"><img src="' + post.url + '"></a></p>' +
					'<p><a href="' + KON.urls.reddit + 'r/' + post.sub + '/comments/' + post.id + '">comments</a></p>' +
				'</article>'
			);

			// votes

			var $votes = $('<span class="kon-votes" data-vote="none">');
			var $upvote = $('<button class="kon-upvote">');
			var $downvote = $('<button class="kon-downvote">');

			$votes.append($upvote);
			$votes.append($downvote);

			$article.prepend($votes);

			$upvote.click(function(event) {
				if (!KON.loggedIn) {
					KON.login();
					return;
				}

				if ($votes.data('vote') === 'up') {
					$votes.data('vote', 'none');
					$upvote.removeClass('kon-voted');
					KON.request('post/vote', {'sid': KON.sid, 'id': post.id, 'dir': 0});
				} else {
					$votes.data('vote', 'up');
					$upvote.addClass('kon-voted');
					KON.request('post/vote', {'sid': KON.sid, 'id': post.id, 'dir': 1});
				}

				$downvote.removeClass('kon-voted');

				if (event.preventDefault) {
					event.preventDefault();
				}

				return false;
			});

			$downvote.click(function(event) {
				if (!KON.loggedIn) {
					KON.login();
					return;
				}

				if ($votes.data('vote') === 'down') {
					$votes.data('vote', 'none');
					$downvote.removeClass('kon-voted');
					KON.request('post/vote', {'sid': KON.sid, 'id': post.id, 'dir': 0});
				} else {
					$votes.data('vote', 'down');
					$downvote.addClass('kon-voted');
					KON.request('post/vote', {'sid': KON.sid, 'id': post.id, 'dir': -1});
				}

				$upvote.removeClass('kon-voted');

				if (event.preventDefault) {
					event.preventDefault();
				}

				return false;
			});

			this.$sections[this.totalArticles++ % this.$sections.length].prepend($article);

			setTimeout((function() {
				if (!this.playing) {
					this.$paused.push($article);
					return;
				}

				$article.fadeIn(300);
			}).bind(this), force ? 1 : 5 * 1000);

			this.$articles.unshift($article);

			if (this.$articles.length > 60) {
				var $remove = this.$articles.pop();
				$remove.fadeOut(300, function() {
					$remove.remove();
				});
			}
		};

		// play / pause

		var play = function() {
			this.playing = true;
			this.$play.removeClass('kon-play');
			this.$play.addClass('kon-pause');

			for (var i = 0; i < this.$paused.length; ++i) {
				this.$paused[i].fadeIn(300);
			}

			this.$paused = [];
		};

		var pause = function() {
			this.playing = false;
			this.$play.removeClass('kon-pause');
			this.$play.addClass('kon-play');
		};

		// update

		var updateTS = function() {
			for (var i = 0; i < this.$articles.length; ++i) {
				var $article = this.$articles[i];
				$article.children('.kon-ts').text(KON.ago($article.data('ts')));
			}
		};

		// fetch

		var fetchPosts = function() {
			var opts = {
				'sub': this.sub
			};

			if (this.since) {
				opts.since = this.since;
			}

			KON.request('post/list', opts, (function(resp) {
				if (resp.status.code !== 200) {
					return;
				}

				var posts = resp.result.posts;
				for (var i = posts.length - 1; i > -1; --i) {
					var post = posts[i];

					if (this.seen[post.id]) {
						continue;
					}

					this.seen[post.id] = true;
					this.since = post.id;
					this.queue.unshift(post);

					if (this.queue.length > 100) {
						this.queue = this.queue.slice(0, 99);
					}
				}
			}).bind(this));
		};

		// resize

		var resize = function(width) {
			var cols = Math.floor((width - 5) / ARTICLEWIDTH);

			if (cols < 1) {
				cols = 1;
			} else if (cols > MAXCOLS) {
				cols = MAXCOLS;
			}

			if (cols === this.$sections.length) {
				return;
			}

			var w = (cols * ARTICLEWIDTH + 5) + 'px';
			KON.$header.children('.kon-wrapper').css('max-width', w);
			$main.css('max-width', w);
			$main.empty();
			this.$sections = [];
			for (var i = 0; i < cols; ++i) {
				var $section = $('<section>');
				this.$sections.push($section);
				$main.append($section);
			}

			for (var i = 0; i < this.$articles.length; ++i) {
				this.$sections[i % this.$sections.length].append(this.$articles[i]);
			}
		};

		// render

		Sub.prototype.render = function() {
			Tmpl.prototype.render.call(this);

			KON.$body.addClass('kon-sub');
			this.sub = KON.$body.data('sub') || 'all';

			// buttons
			this.$play = $('<button class="kon-pause"><hr><hr></button>');
			this.$play.click((function() {
				if (this.playing) {
					pause.call(this);
				} else {
					play.call(this);
				}
			}).bind(this));

			KON.$header.children('.kon-wrapper').prepend(this.$play);

			// queue
			var n = this.queue.length;
			if (n) {
				this.since = this.queue[0].id;
				for (var i = 0; i < n; ++i) {
					this.seen[this.queue[i].id] = true;
				}
			}

			// resize

			var timer;
			var width = $window.width();
			resize.call(this, width);
			$window.resize((function() {
				var w = $window.width();
				if (width === w) {
					return;
				}

				width = w;
				clearTimeout(timer);
				timer = setTimeout(resize.bind(this, w), 300);
			}).bind(this));

			// render posts
			this.$waiting = $('<p style="display: none">Waiting for more posts...</p>');
			KON.$messages.append(this.$waiting);
			renderPost.call(this, true);
			renderPost.call(this);
			setInterval(renderPost.bind(this), 5 * 1000);

			// fetch posts
			fetchPosts.call(this);
			setInterval(fetchPosts.bind(this), 60 * 1000);

			// update
			setInterval(updateTS.bind(this), 15 * 1000);

			return this;
		};

		callback();
	});
};

})(window.KON);
