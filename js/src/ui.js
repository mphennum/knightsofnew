(function(KON) {

'use strict';

var UI = KON.UI = KON.UI || {};

KON.modules['UI'] = true;

UI.__init__ = function(callback) {
	delete UI.__init__;

	callback();
};

})(window.KON);
