(function() {

'use strict';

var KON = window.KON = window.KON || {};
var UI = KON.UI = KON.UI || {};

KON.modules = KON.modules || {};
KON.modules['UI'] = true;

UI.__init__ = function(callback) {
	delete UI.__init__;

	callback();
};

})();
