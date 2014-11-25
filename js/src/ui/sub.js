(function() {

'use strict';

var KON = window.KON = window.KON || {};
var UI = KON.UI = KON.UI || {};
var Sub = UI.Sub = UI.Sub || {};

KON.modules = KON.modules || {};
KON.modules['UI.Sub'] = true;

Sub.__init__ = function(callback) {
	delete Sub.__init__;

	KON.load('UI.Tmpl', function() {
		var $ = KON.$;
		var Tmpl = UI.Tmpl;

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
			this.$articles = [];
			this.$paused = [];

			this.queue = opts.queue || [];

			return this;
		};

		Sub.prototype = Object.create(Tmpl.prototype);

		// render

		Sub.prototype.render = function() {
			Tmpl.prototype.render.call(this);

			KON.$body.addClass('kon-sub');
			this.sub = KON.$body.data('sub') || 'all';

			// buttons
			this.$play = $('<button class="kon-pause"><hr><hr></button>');
			this.$play.click((function() {
				this.playing = !this.playing;
				if (this.playing) {
					this.play();
				} else {
					this.pause();
				}
			}).bind(this));

			KON.$header.children('.kon-wrapper').prepend(this.$play);

			// queue
			var n = this.queue.length;
			if (n) {
				this.since = this.queue[0].id;
				for (var i = 0; i < n; i++) {
					this.seen[this.queue[i].id] = true;
				}
			}

			// render posts
			this.$waiting = $('<p style="display: none">Waiting for more posts...</p>');
			KON.$messages.append(this.$waiting);
			this.renderPost(true);
			this.renderPost();
			setInterval(this.renderPost.bind(this), 5 * 1000);

			// fetch posts
			this.fetchPosts();
			setInterval(this.fetchPosts.bind(this), 60 * 1000);

			// update
			setInterval(this.updateTS.bind(this), 15 * 1000);

			return this;
		};

		// posts

		Sub.prototype.renderPost = function(force) {
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

			KON.$main.prepend($article);

			setTimeout((function() {
				if (!this.playing) {
					this.$paused.push($article);
					return;
				}

				$article.fadeIn(300);
			}).bind(this), force ? 1 : 5 * 1000);

			this.$articles.unshift($article);

			if (this.$articles.length > 10) {
				var $remove = this.$articles.pop();
				$remove.fadeOut(300, function() {
					$remove.remove();
				});
			}
		};

		// play / pause

		Sub.prototype.play = function() {
			this.$play.removeClass('kon-play');
			this.$play.addClass('kon-pause');

			for (var i = 0, n = this.$paused.length; i < n; i++) {
				this.$paused[i].fadeIn(300);
			}

			this.$paused = [];
		};

		Sub.prototype.pause = function() {
			this.$play.removeClass('kon-pause');
			this.$play.addClass('kon-play');
		};

		// update

		Sub.prototype.updateTS = function() {
			for (var i = 0, n = this.$articles.length; i < n; i++) {
				var $article = this.$articles[i];
				$article.children('.kon-ts').text(KON.ago($article.data('ts')));
			}
		};

		// fetch

		Sub.prototype.fetchPosts = function() {
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
				for (var i = posts.length - 1; i > -1; i--) {
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

		callback();
	});
};

})();
