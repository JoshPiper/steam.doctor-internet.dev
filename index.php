<?php

if (!isset($_GET['id'])){http_response_code(404); echo "No ID sent."; die();}

use GuzzleHttp\Client;
use Steam\Runner\GuzzleRunner;
use Steam\Utility\GuzzleUrlBuilder;
use Steam\Runner\DecodeJsonStringRunner;

require __DIR__ . '/vendor/autoload.php';

(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

$steam = new \Steam\Steam(new \Steam\Configuration([
	\Steam\Configuration::STEAM_KEY => $_ENV['API_KEY']
]));
$steam->addRunner(new GuzzleRunner(new Client(), new GuzzleUrlBuilder()));
$steam->addRunner(new DecodeJsonStringRunner());

$res = $steam->run(new \Steam\Command\RemoteStorage\GetPublishedFileDetails($_GET['id']));
$res = $res['response'];

if ($res['result'] !== 1){
	http_response_code(404);
	echo "No details found.";
	die();
}



$info = $res['publishedfiledetails'][0];
//echo "<pre>";
//var_dump($info);
//echo "</pre>";

$author = $info['creator'];
$author = $steam->run(new \Steam\Command\User\GetPlayerSummaries([$author]));
$author = $author['response']['players'][0];
//echo "<pre>";
//var_dump($author);
//echo "</pre>";

$info['description'] = (new \Converter\BBCodeConverter($info['description']))->toMarkdown();
$info['description'] = preg_replace_callback('/\[h(\d)\](.*?)\[\/h\1\]/m', function($match){
	return str_repeat('#', intval($match[1])) . ' ' . trim($match[2]);
}, $info['description']);

//echo "<pre>";
//var_dump($info['description']);
//echo "</pre>";

$card = [
	'card' => 'summary_large_image',
	'site' => $author['personaname'],
	'title' => "{$info['title']} by {$author['personaname']}",
	'description' => $info['description'],
	'image' => $info['preview_url'],
	'image:alt' => "{$info['title']} preview image."
];

if (strlen($card['description']) < 50){unset($card['description']);}

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= htmlspecialchars($info['title']) ?></title>
		<?php foreach ($card as $key => $value){ ?>
		<meta name="twitter:<?= $key ?>" content="<?= htmlspecialchars($value) ?>">
		<?php } ?>
	</head>
	<body>
		<script>
			window.location.replace("https://steamcommunity.com/workshop/filedetails/?id=<?= $info['publishedfileid'] ?>")
		</script>
	</body>
</html>
