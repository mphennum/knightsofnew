<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Error - Knights of New</title>

<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">

<meta name="description" content="reddit live feed">

<?php include __DIR__ . '/ssi/styles-blocking.php'; ?>
<?php include __DIR__ . '/ssi/scripts-blocking.php'; ?>

</head>

<body class="kon-error">

<header>
	<div class="kon-wrapper">
		<h1><a href="//<?= KON\Config::WWWHOST?>">Knights of New</a></h1>
	</div>
</header>

<main>
	<article>
		<h2>Error <?= $response['status']['code'] ?></h2>
		<p><?= $response['status']['message'] ?><?= isset($response['status']['reason']) ? ': ' . $response['status']['reason'] : '' ?></p>
	</article>
</main>

<?php include __DIR__ . '/ssi/scripts-non-blocking.php'; ?>
<script>
(function(KON) {

KON.load('UI.Tmpl', function() {
	KON.UI.Tmpl().render();
});

})(window.KON);
</script>

</body>

</html>
