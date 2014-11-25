(function() {

'use strict';

var KON = window.KON = window.KON || {};
var UI = KON.UI = KON.UI || {};
var Tmpl = UI.Tmpl = UI.Tmpl || {};

KON.modules = KON.modules || {};
KON.modules['UI.Tmpl'] = true;

Tmpl.__init__ = function(callback) {
	delete Tmpl.__init__;

	KON.load('UI', function() {
		var $ = KON.$;

		Tmpl = UI.Tmpl = function(opts) {
			if (!(this instanceof Tmpl)) {
				return new Tmpl(opts);
			}

			opts = opts || {};

			return this;
		};

		Tmpl.prototype.render = function() {
			KON.request('user/get', {'sid': KON.sid}, (function(resp) {
				if (resp.result && resp.result.session && resp.result.session['logged-in']) {
					KON.loggedIn = true;
					KON.$body.addClass('kon-logged-in');
				} else {
					KON.loggedIn = false;
					KON.$body.addClass('kon-logged-out');
				}

				var $login = $('<button class="kon-login">login</button>');
				var $logout = $('<button class="kon-logout">logout</button>');
				$login.click(KON.oauth.bind(KON));
				$logout.click(KON.logout.bind(KON));

				var $wrapper = KON.$header.children('.kon-wrapper');
				$wrapper.prepend($login);
				$wrapper.prepend($logout);
			}).bind(this));

			return this;
		};

		callback();
	});
};

})();
