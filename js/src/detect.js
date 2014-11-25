(function() {

'use strict';

var KON = window.KON = window.KON || {};
var Detect = KON.Detect = KON.Detect || {};

KON.modules = KON.modules || {};
KON.modules['Detect'] = true;

Detect.__init__ = function(callback) {
	delete Detect.__init__;
	Detect.XHR = (XMLHttpRequest && 'withCredentials' in new XMLHttpRequest());
	Detect.XDR = (typeof XDomainRequest !== 'undefined');

	callback();
};

})();
