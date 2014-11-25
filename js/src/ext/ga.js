(function() {

'use strict';

var KON = window.KON = window.KON || {};
var Ext = KON.Ext = KON.Ext || {};
var GA = Ext.GA = Ext.GA || {};

KON.modules = KON.modules || {};
KON.modules['Ext.GA'] = true;

var key = 'UA-54556246-1';

GA.__init__ = function(callback) {
	delete GA.__init__;

	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	ga('create', key, 'auto');
	ga('send', 'pageview');

	callback();
};

})();
