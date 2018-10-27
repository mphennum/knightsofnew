<link href="//<?= KON\Config::IMGHOST ?>/favicon.png" rel="shortcut icon">
<link href="//fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
<?php
if (KON\Config::DEVMODE) {
	foreach (KON\Config::$manifest['css'] as $package => $files) {
		foreach ($files as $file) {
			echo '<link href="//', KON\Config::CSSHOST, '/src/' . $file . '.css" rel="stylesheet">', "\n";
		}
	}
} else {
	foreach (KON\Config::$manifest['css'] as $package => $files) {
		echo '<link href="//', KON\Config::CSSHOST, '/min/' . $package . '.css" rel="stylesheet">', "\n";
	}
}
?>
