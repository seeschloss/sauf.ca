<?php
require 'inc/common.inc.php';

$site = new Site("Sauf.ça");

if (strpos($_SERVER['REQUEST_URI'], '/latest.json') === 0) {
	header('Content-Type: application/json');

	print $site->latest_json($_GET);
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/history.json') === 0) {
	header('Content-Type: application/json');

	print $site->history_json($_GET['until'], $_GET['count']);
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/tags.json') === 0) {
	header('Content-Type: application/json');

	print $site->tags_json($_GET['picture']);
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/nsfw.json') === 0) {
	header('Content-Type: application/json');

	$picture = new Picture();
	if ($picture->load($_GET['picture']) and strpos($picture->tags, "nsfw") === FALSE) {
		$picture->tags .= "nsfw";
		$picture->update();
	}

	print json_encode($picture->json_array());
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/sfw.json') === 0) {
	header('Content-Type: application/json');

	$picture = new Picture();
	if ($picture->load($_GET['picture']) and strpos($picture->tags, "nsfw") !== FALSE) {
		$picture->tags = trim(str_replace("nsfw", "", $picture->tags));
		$picture->update();
	}

	print json_encode($picture->json_array());
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/+') === 0 and strpos($_SERVER['REQUEST_URI'], '/img') !== FALSE and preg_match('@/\+([0-9]*)/img@', $_SERVER['REQUEST_URI'], $matches)) {
	$picture = new Picture();
	if ($picture->load($matches[1])) {
		$src = url(PICTURES_PREFIX.'/'.$picture->src, true);
		header('HTTP/1.0 301 Permanent Redirect');
		header('Location: '.$src);
		die();
	} else {
		header('HTTP/1.0 404 Not Found');
		header('Content-Type: text/plain');
		echo "Not Found\n";
		die();
	}
} else if (strpos($_SERVER['REQUEST_URI'], '/+') === 0) {
	$picture = new Picture();
	if (!$picture->load(substr($_SERVER['REQUEST_URI'], 2))) {
		header('HTTP/1.0 404 Not Found');
		header('Content-Type: text/plain');
		echo "Not Found\n";
		die();
	}
} else if (strpos($_SERVER['REQUEST_URI'], '/?') === 0) {
} else if (strpos($_SERVER['REQUEST_URI'], '/!') === 0) {
} else if (strlen($_SERVER['REQUEST_URI']) > 1) {
	header('HTTP/1.0 404 Not Found');
	header('Content-Type: text/plain');
	echo "Not Found\n";
	die();
}

$content = '';

$content .= $site->head();
$content .= $site->viewer().'
		<div id="wrapper">
			<div id="sitebar">
				<h1><a href="/">'.$site->name().'</a></h1><h2>Les images postées sur les tribunes de la moulosphère francophone.</h2>
				<div class="search"><form id="search"><input type="checkbox" name="animated" title="GIF animés uniquement" /><input placeholder="Search..." type="search" name="search" /></form></div>
				<div class="header">'.$site->header().'</div>
				<div class="links">
					<ul>
						<li>
							<a href="/!">Gifs animés</a>
						</li>
						<li>
							<a href="/!tut_tut">*tut tut*</a>
						</li>
						<li>
							<a href="/?houplaboom&lt;">houplababes</a>
						</li>
					</ul>
				</div>
				<div class="commands">
					<ul>
						<li>
							<code>r</code> recharger les nouvelles images
						</li>
						<li>
							<code>q</code> diminuer le contraste, surtout pour le NSFW
						</li>
						<li>
							<code>←/→</code> naviguer parmi les images
						</li>
						<li>
							<code>↲</code> afficher/fermer une image
						</li>
						<li>
							<code>[espace]</code> afficher l\'image en grand
						</li>
					</ul>
				</div>
			</div>
			<div id="content">
				<div id="thumbnails-wrapper">
					<div id="thumbnails">'.
						$site->thumbnails()
					.'</div>
				</div>
			</div>
			<div id="information">
				<span id="status">
				</span>
			</div>
		</div>'
;
$content .= $site->foot();

print $content;
?>
