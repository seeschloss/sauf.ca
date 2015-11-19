<?php
ini_set('session.use_cookies', '0');

if (strpos($_SERVER['REQUEST_URI'], '/latest.json') !== 0 and !empty($_GET['search'])) {
	header('Location: /?' . urlencode($_GET['search']));
}

require '../inc/common.inc.php';

$site = new Site($GLOBALS['config']['title']);

if (strpos($_SERVER['REQUEST_URI'], '/oauth/dlfp/can_post.json') === 0) {
	header('Content-Type: application/json');

	$oauth = new OAuth();
	print json_encode($oauth->can_post());
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/oauth/dlfp/post.json') === 0) {
	header('Content-Type: application/json');

	$oauth = new OAuth();
	print json_encode($oauth->tribune_post($_REQUEST['message']));
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/oauth/dlfp/upload.json') === 0) {
	header('Content-Type: application/json');

	$oauth = new OAuth();
	if (isset($_REQUEST['file'])) {
		print json_encode($oauth->tribune_post_url($_REQUEST['file'], $_REQUEST['comment']));
	} else {
		print json_encode($oauth->tribune_upload_file($_FILES['filedata'], $_REQUEST['comment']));
	}
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/oauth/dlfp/callback') === 0) {
	$oauth = new OAuth();
	$oauth->process();
	header('Location: /');
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/oauth/dlfp/conversation/') === 0) {
	$id = substr($_SERVER['REQUEST_URI'], strlen('/oauth/dlfp/conversation/'));
	$id = substr($id, 0, strpos($id, '.json'));
	$id = (int)$id;

	header('Content-Type: application/json');
	$oauth = new OAuth();
	print $oauth->conversation($id);
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/feeds/all.tsv') === 0) {
	header('Content-Type: text/tab-separated-values; charset=utf8');

	print $site->latest_tsv($_GET);
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/feeds/all.xml') === 0) {
	header('Content-Type: application/xml; charset=utf8');

	print $site->latest_xml($_GET);
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/latest.json') === 0) {
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
} else if (strpos($_SERVER['REQUEST_URI'], '/bloubs.json') === 0) {
	header('Content-Type: application/json');

	if (isset($_GET['picture'])) {
		$picture = new Picture();
		if ($picture->load($_GET['picture'])) {
			print json_encode(array(
				$picture->id => $picture->bloubs(),
			));
		}
	} else if (isset($_GET['link'])) {
		$link = new Link();
		if ($link->load($_GET['link'])) {
			print json_encode(array(
				$link->id => $link->bloubs(),
			));
		}
	}
	exit();
} else if (strpos($_SERVER['REQUEST_URI'], '/nsfw.json') === 0) {
	header('Content-Type: application/json');

	$picture = new Picture();
	if ($picture->load($_GET['picture']) and strpos($picture->tags, "nsfw") === FALSE) {
		if ($picture->tags) {
			$picture->tags .= ", nsfw";
		} else {
			$picture->tags = "nsfw";
		}
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
} else if (strpos($_SERVER['REQUEST_URI'], '/=') === 0) {
} else if (strpos($_SERVER['REQUEST_URI'], '/!') === 0) {
} else if (strpos($_SERVER['REQUEST_URI'], '/*') === 0) {
	$_SERVER['REQUEST_URI'] = '/';
} else if (strpos($_SERVER['REQUEST_URI'], '/upload') === 0) {
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
				<div class="search">
					<form id="search">
						<input type="checkbox" checked name="links" title="Afficher les liens" id="checkbox-links" /><label for="checkbox-links">liens</label><br />
						<input type="checkbox" checked name="pictures" title="Afficher les images" id="checkbox-pictures" /><label for="checkbox-pictures">images fixes</label><br />
						<input type="checkbox" checked name="animated" title="GIF animés uniquement" id="checkbox-animated" /><label for="checkbox-animated">animations</label><br />
						<input type="search" placeholder="Search..." name="search" />
					</form>
				</div>
				<div class="header">'.$site->header().'</div>
				<div class="links">
					<ul>
						<li class="external">
							<a href="http://tfeserver.fr/tribune_urls.html">URL postées</a>
						</li>
						<li class="external spacer">
							<a href="http://bombefourchette.com/">Historique</a>
						</li>
						<li>
							<a href="!">gifs animés</a>
						</li>
						<li>
							<a href="!tut_tut">*tut tut*</a>
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
						<li>
							<code>t</code> afficher les commentaires d\'une image
						</li>
					</ul>
				</div>
			</div>
			<div id="content">
				<div id="thumbnails-wrapper">
					<div id="thumbnails">'.
						$site->show_thumbnails()
					.'</div>
				</div>
			</div>
			<div id="information">
				<span id="status">
					<a id="contact-link" href="http://github.com/seeschloss/sauf.ca">github</a>
					<a id="contact-link" href="mailto:see@sauf.ca">contact</a>
				</span>
			</div>
		</div>'
;
$content .= $site->foot();

print $content;
?>
