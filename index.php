<?php

if (!isset($_GET['id'])){
	http_response_code(404);
	echo "No ID sent.";
	die();
}

use GuzzleHttp\Client;
use Converter\BBCodeConverter;
use Symfony\Component\Dotenv\Dotenv;
use Steam\Steam;
use Steam\Configuration;
use Steam\Runner\GuzzleRunner;
use Steam\Utility\GuzzleUrlBuilder;
use Steam\Runner\DecodeJsonStringRunner;
use Steam\Command\User\GetPlayerSummaries;
use Steam\Command\RemoteStorage\GetPublishedFileDetails;

require __DIR__ . '/vendor/autoload.php';

(new Dotenv())->load(__DIR__ . '/.env');

$steam = new Steam(new Configuration([
	Configuration::STEAM_KEY => $_ENV['API_KEY']
]));
$steam->addRunner(new GuzzleRunner(new Client(), new GuzzleUrlBuilder()));
$steam->addRunner(new DecodeJsonStringRunner());

$res = $steam->run(new GetPublishedFileDetails($_GET['id']));
$res = $res['response'];

if ($res['result'] !== 1){
	http_response_code(404);
	echo "No details found.";
	die();
}


$info = $res['publishedfiledetails'][0];

$author = $info['creator'];
$author = $steam->run(new GetPlayerSummaries([$author]));
$author = $author['response']['players'][0];

$info['description'] = (new BBCodeConverter($info['description']))->toMarkdown();
$info['description'] = preg_replace_callback('/\[h(\d)\](.*?)\[\/h\1\]/m', function ($match){
	return str_repeat('#', intval($match[1])) . ' ' . trim($match[2]);
}, $info['description']);

$card = [
	'card' => 'summary_large_image',
	'site' => $author['personaname'],
	'title' => "{$info['title']} by {$author['personaname']}",
	'description' => $info['description'],
	'image' => $info['preview_url'],
	'image:alt' => "{$info['title']} preview image."
];

if (strlen($card['description']) < 50){
	unset($card['description']);
}

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
