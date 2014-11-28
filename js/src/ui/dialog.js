(function(KON) {

'use strict';

var UI = KON.UI = KON.UI || {};
var Dialog = UI.Dialog = UI.Dialog || {};

KON.modules['UI.Dialog'] = true;

Dialog.__init__ = function(callback) {
	delete Dialog.__init__;

	KON.load('UI', function() {
		var $ = KON.$;

		Dialog = UI.Dialog = function(opts) {
			if (!(this instanceof Dialog)) {
				return new Dialog(opts);
			}

			opts = opts || {};

			this.rendered = false;
			this.showing = false;

			this.title = opts.title || null;
			this.content = opts.content || null;
			this.width = opts.width || 300;
			this.height = opts.height || 500;

			if (!opts.buttons) {
				this.buttons = [{
					'text': 'close',
					'onclick': this.hide.bind(this)
				}];
			} else {
				this.buttons = opts.buttons || [];
			}

			this.escapable = opts.escapable || false;

			return this;
		};

		Dialog.prototype.render = function() {
			this.$ = $('<div class="kon-dialog" style="display: none">');

			this.$overlay = $('<div class="kon-overlay">');
			this.$.append(this.$overlay);

			if (this.escapable) {
				this.$overlay.click(this.hide.bind(this));
			}

			this.$box = $('<div class="kon-box">');
			this.$box.css({
				'margin-top': (this.height * -0.5) + 'px',
				'margin-left': (this.width * -0.5) + 'px',
				'width': this.width + 'px',
				'height': this.height + 'px',
				'top': '50%',
				'left': '50%'
			});

			this.$.append(this.$box);

			if (this.title) {
				this.$title = $('<h3>' + this.title + '</h3>');
				this.$box.append(this.$title);
			}

			if (this.content) {
				this.$content = $('<div class="kon-content">' + this.content + '</div>');
				this.$box.append(this.$content);
			}

			if (this.buttons.length) {
				this.$buttons = $('<div class="kon-buttons">');
				this.$box.append(this.$buttons);
				for (var i = 0; i < this.buttons.length; ++i) {
					var button = this.buttons[i];
					var $button = $('<button>' + button.text + '</button>');
					$button.click((button.onclick || SB.noop).bind(this));
					this.$buttons.append($button);
				}
			}

			KON.$body.append(this.$);

			this.rendered = true;
			return this;
		};

		Dialog.prototype.show = function() {
			if (!this.rendered) {
				this.render();
			}

			if (this.showing) {
				return this;
			}

			this.$.show();
			this.showing = true;
			return this;
		};

		Dialog.prototype.hide = function() {
			if (!this.showing) {
				return this;
			}

			this.$.hide();
			this.showing = false;
			return this;
		};

		callback();
	});
};

})(window.KON);
