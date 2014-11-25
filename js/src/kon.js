(function(document) {

'use strict';

var KON = window.KON = window.KON || {};

// vars

KON.devmode = (KON.devmode !== false);

KON.noop = function() {};

KON.map = KON.map || {};
KON.packages = KON.packages || {};

KON.modules = KON.modules || {};
KON.modules['KON'] = true;

KON.scripts = KON.scripts || {};

KON.urls = KON.urls || {};
KON.urls.www = '//www.knightsofnew.com/';
KON.urls.api = '//api.knightsofnew.com/';
KON.urls.js = KON.urls.js || '//js.knightsofnew.com/src/';
KON.urls.css = KON.urls.css || '//css.knightsofnew.com/src/';
KON.urls.reddit = '//www.reddit.com/';
KON.urls.oauth = 'https://ssl.reddit.com/api/v1/authorize';
KON.urls.redirect = '//www.knightsofnew.com/login';

KON.keys = KON.keys || {};
KON.keys.reddit = '8MCAJKSaMNGXjQ';

KON.loggedIn = false;

// init

KON.__init__ = function(opts) {
	delete KON.__init__;

	opts = opts || {};

	var $ = KON.$ = window.jQuery;
	KON.$head = $('head');
	KON.$body = $('body');
	KON.$header = KON.$body.children('header');
	KON.$messages = KON.$body.children('.kon-messages');
	KON.$main = KON.$body.children('main');

	// cookies

	var cookies = document.cookie.split('; ');
	for (var i = 0; i < cookies.length; ++i) {
		var parts = cookies[i].split('=');
		if (parts[0] === 'sid') {
			KON.sid = parts[1];
		}
	}
};

// load

var head = document.getElementsByTagName('head')[0];

var delayedLoad = [];
KON.load = function(module, callback) {
	delayedLoad.push({
		'module': module,
		'callback': callback
	});
};

var init = function(modules, callback) {
	callback = callback || KON.noop;

	var complete = {};
	var ready = function(module) {
		complete[module] = true;
		for (var k in complete) {
			if (!complete[k]) {
				return;
			}
		}

		callback();
	};

	var mods = [];
	for (var i = 0; i < modules.length; ++i) {
		var module = modules[i];
		complete[module] = false;

		var mod = KON;
		var parts = module.split('.');
		for (var j = 0; j < parts.length; ++j) {
			mod = mod[parts[j]];
		}

		mods.push({
			'name': module,
			'module': mod
		});
	}

	for (var i = 0; i < mods.length; ++i) {
		var mod = mods[i];
		mod.module.__init__(ready.bind(this, mod.name));
	}
};

var load = function(module, callback) {
	callback = callback || KON.noop;

	var modules = [module];
	if (KON.map[module]) {
		module = KON.map[module];
		modules = KON.packages[module];
	}

	if (KON.modules[module]) {
		callback();
		return;
	}

	KON.loadScript(KON.urls.js + module.toLowerCase().replace('.', '/') + '.js', init.bind(this, modules, callback));
};

KON.loadScript = function(src, callback) {
	callback = callback || KON.noop;

	if (KON.scripts[src]) {
		callback();
		return;
	}

	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.async = 'true';

	var loaded = false;
	var ready = function() {
		if (loaded) {
			return;
		}

		loaded = true;
		setTimeout(function() {
			head.removeChild(script);
		}, 10);

		KON.scripts[src] = true;
		callback();
	};

	script.onload = ready;
	script.onreadystatechange = function() {
		if (this.readyState === 'loaded' || this.readyState === 'complete') {
			ready();
		}
	};

	script.src = src;
	head.appendChild(script);
};

// request

var requestXHR = function(method, url, params, callback) {
	callback = callback || KON.noop;

	var xhr = new XMLHttpRequest();
	xhr.open(method, url, true);

	var done = false;
	xhr.onreadystatechange = function() {
		if (xhr.readyState === 4 && !done) {
			done = true;
			callback(JSON.parse(xhr.responseText));
		}
	};

	xhr.send(null);
};

var requestXDR = function(method, url, params, callback) {
	callback = callback || KON.noop;

	var xdr = new XDomainRequest();
	xdr.onprogress = KON.noop; // prevents random "aborted" when the request was successful bug (IE)
	xdr.ontimeout = KON.noop;
	xdr.onerror = KON.noop;
	xdr.onload = function() {
		callback(JSON.parse(xdr.responseText));
	};

	xdr.open(method, url);
	xdr.send();
};

KON.trueRequest = KON.noop;
var request = function(uri, params, callback) {
	uri = uri || '';
	params = params || {};
	callback = callback || KON.noop;

	var query = [];
	for (var k in params) {
		query.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
	}

	if (query.length) {
		uri += '?' + query.join('&');
	}

	KON.trueRequest('GET', KON.urls.api + uri, null, callback);
};

var delayedRequest = [];
KON.request = function(uri, params, callback) {
	delayedRequest.push({
		'uri': uri,
		'params': params,
		'callback': callback
	});
};

// ago

KON.ago = function(ts) {
	ts = parseInt(ts);

	var now = parseInt(Date.now() / 1000);
	var ago = now - ts;

	if (ago < 90) { // < 1 minute
		return '1m';
	}

	if (ago < 60 * 60) { // < 1 hour
		return Math.floor(ago / 60) + 'm';
	}

	if (ago < 60 * 60 * 24) { // < 1 day
		return Math.floor(ago / (60 * 60)) + 'h';
	}

	if (ago < 60 * 60 * 24 * 365) { // < 1 year
		return Math.floor(ago / (60 * 60 * 24)) + 'd';
	}

	return Math.floor(ago / (60 * 60 * 24 * 365)) + 'y';
};

// login

KON.oauth = function() {
	window.location.href =
		KON.urls.oauth +
		'?client_id=' + encodeURIComponent(KON.keys.reddit) +
		'&response_type=' + encodeURIComponent('code') +
		'&state=' + encodeURIComponent(KON.sid) +
		'&redirect_uri=' + encodeURIComponent('http:' + KON.urls.redirect) +
		'&duration=' + encodeURIComponent('permanent') +
		'&scope=' + encodeURIComponent('report,vote')
	;
};

KON.login = function() {
	KON.load('UI.Dialog', function() {
		KON.UI.Dialog({
			'title': 'Login required',
			'content': '<p>You must login to reddit to vote.</p>',
			'width': 200,
			'height': 85,
			'buttons': [
				{
					'text': 'cancel',
					'onclick': KON.UI.Dialog.prototype.hide
				},
				{
					'text': 'login',
					'onclick': KON.oauth
				}
			]
		}).show();
	});
};

KON.logout = function() {
	if (!KON.loggedIn) {
		return;
	}

	KON.loggedIn = false;

	KON.$body.removeClass('kon-logged-in');
	KON.$body.addClass('kon-logged-out');
	KON.request('user/logout', {'sid': KON.sid});
};

// required

KON.required = KON.required || {};

KON.required.detect = function(callback) {
	load('Detect', callback || KON.noop);
};

KON.required.poly = function(callback) {
	load('Poly', callback || KON.noop);
};

KON.required.jq = function(callback) {
	callback = callback || KON.noop;

	if (window.jQuery) {
		callback();
		return;
	}

	KON.loadScript('//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js', callback);
};

// preload

KON.preload = KON.preload || {};

KON.preload.ga = function(callback) {
	callback = callback || KON.noop;
	KON.load('Ext.GA');
	callback();
};

KON.__preload__ = function(callback) {
	delete KON.__preload__;

	callback = callback || KON.noop;

	var preloads = [];
	var scripts = document.getElementsByTagName('script');
	for (var i = 0; i < scripts.length; ++i) {
		var matches = scripts[i].src.match(/\/kon\.js#?(.+)?$/);
		if (matches) {
			preloads = matches[1] ? matches[1].split(',') : [];
			break;
		}
	}

	var complete = {};
	var ready = function(name) {
		complete[name] = true;
		for (var k in complete) {
			if (!complete[k]) {
				return;
			}
		}

		callback();
	};

	var includes = [];

	for (var k in KON.required) {
		complete['required-' + k] = false;
		includes.push(KON.required[k].bind(this, ready.bind(this, 'required-' + k)));
	}

	for (var i = 0; i < preloads.length; ++i) {
		var preload = preloads[i];
		if (KON.preload[preload]) {
			complete['preload-' + preload] = false;
			includes.push(KON.preload[preload].bind(this, ready.bind(this, 'preload-' + preload)));
		}
	}

	if (includes.length) {
		for (var i = 0; i < includes.length; ++i) {
			includes[i]();
		}

		return;
	}

	callback();
};

// init

KON.__preload__(function() {
	KON.__init__();

	if (KON.packages.KON) {
		KON.packages.KON.shift();
		init(KON.packages.KON);
	}

	KON.load = load;
	while (delayedLoad.length) {
		var delayed = delayedLoad.shift();
		KON.load(delayed.module, delayed.callback);
	}

	KON.request = request;
	KON.trueRequest = KON.Detect.XHR ? requestXHR : requestXDR;
	while (delayedRequest.length) {
		var delayed = delayedRequest.shift();
		KON.request();
	}
});

})(document);
