<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title><?= $response['result']['sub'] === 'all' ? '' : $response['result']['sub'] . ' - ' ?>Knights of New</title>

<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">

<meta name="description" content="reddit live feed">

<?php include __DIR__ . '/ssi/styles-blocking.php'; ?>
<?php include __DIR__ . '/ssi/scripts-blocking.php'; ?>

</head>

<body data-sub="<?= $response['result']['sub'] ?>">

<header>
	<div class="kon-wrapper">
		<h1><a href="//<?= KON\Config::WWWHOST?>">Knights of New</a></h1>
	</div>
</header>

<div class="kon-messages"></div>

<main></main>

<?php include __DIR__ . '/ssi/scripts-non-blocking.php'; ?>
<script>
(function(KON) {

KON.load('UI.Sub', function() {
	KON.UI.Sub({'queue': <?= json_encode($response['result']['posts']) ?>}).render();
});

})(window.KON);
</script>

</body>

</html>
