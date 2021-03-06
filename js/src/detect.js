(function(KON, XMLHttpRequest) {

'use strict';

var Detect = KON.Detect = KON.Detect || {};

KON.modules['Detect'] = true;

Detect.__init__ = function(callback) {
	delete Detect.__init__;
	Detect.XHR = (XMLHttpRequest && 'withCredentials' in new XMLHttpRequest());
	Detect.XDR = (typeof window.XDomainRequest !== 'undefined');

	callback();
};

})(window.KON, window.XMLHttpRequest);
