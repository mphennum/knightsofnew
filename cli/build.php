#!/usr/bin/php
<?php

namespace KON;

require __DIR__ . '/../sys/bootstrap.php';

$opts = getopt('c', ['help']);

if (isset($opts['help'])) {
	CLI::message('KON build / compilation');
	CLI::message('usage: ', 'build.php');
	CLI::message('-c     ', 'clean the minified dir');
	exit(0);
}

CLI::title('KON build / compilation');
$dir = realpath(__DIR__ . '/../');

// options

if (isset($opts['c'])) {
	CLI::notice('Cleaning out minified dirs');
	exec('rm -rf ' . $dir . '/js/min/*');
	exec('rm -rf ' . $dir . '/css/min/*');
}

// js

CLI::subtitle('Compiling JS');

$manifest = Config::$manifest['js'];

// live script

$script = <<<'EOT'
	window.KON = {
		"devmode": false,
		"urls": {
			"js": "//%s/min/",
			"css": "//%s/min/"
		},
		"map": {%s},
		"packages": {%s}
	};
EOT;

$map = [];
$packages = [];
foreach ($manifest as $package => $files) {
	$pkg = [];
	foreach ($files as $file) {
		$pkg[] = '"' . $file . '"';
		$map[] = '"' . $file . '": "' . $package . '"';
	}

	$packages[] = '"' . $package . '": [' . implode(',', $pkg) . ']';
}

$script = sprintf($script, Config::JSHOST, Config::CSSHOST, implode(',', $map), implode(',', $packages));
$tmp = $dir . '/tmp/live.js';
file_put_contents($tmp, '(function() {' . $script . '})();');

$compiler = $dir . '/bin/compiler.jar';
foreach ($manifest as $package => $files) {
	CLI::message('Compiling package: ', $package);
	$cmd = 'java -jar ' . $compiler;

	if ($package === 'KON') {
		$cmd .= ' --js ' . $tmp;
	}

	foreach ($files as $file) {
		$cmd .= ' --js ' . $dir . '/js/src/' . str_replace('.', '/', strtolower($file)) . '.js';
	}

	$cmd .= ' --js_output_file ' . $dir . '/js/min/' . str_replace('.', '/', strtolower($package)) . '.js';

	$mkdir = $dir . '/js/min/' . str_replace('.', '/', strtolower($package));
	$mkdir = preg_replace('/\/[^\/]+$/', '', $mkdir);
	if (!file_exists($mkdir)) {
		if (!mkdir($mkdir, 0775, true)) {
			CLI::error('mkdir failed for: ' . $mkdir);
		}
	}

	exec($cmd);
}

unlink($tmp);

// css

CLI::subtitle('Compiling CSS');

$manifest = Config::$manifest['css'];

$compiler = $dir . '/bin/yuicompressor-2.4.8.jar';
foreach ($manifest as $package => $files) {
	CLI::message('Compiling package: ', $package);


	$tmp = $dir . '/tmp/' . $package . '.css';
	$src = '';
	foreach ($files as $file) {
		$src .= file_get_contents($dir . '/css/src/' . $file . '.css');
	}

	file_put_contents($tmp, $src);

	exec('java -jar ' . $compiler . ' ' . $tmp . ' -o ' . $dir . '/css/min/' . $package . '.css --type css --charset utf-8');

	unlink($tmp);
}
