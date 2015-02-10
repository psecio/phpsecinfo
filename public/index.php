<?php

require_once '../vendor/autoload.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

$checksUrl = 'https://raw.githubusercontent.com/psecio/versionscan/master/src/Psecio/Versionscan/checks.json';
$checksFile = __DIR__.'/checks.json';

if (realpath($checksFile) === false || filemtime($checksFile) < strtotime('-5 minutes')) {
    $content = file_get_contents($checksUrl);
    file_put_contents($checksFile, $content);
}
$checksFile = realpath($checksFile);

$app->get('/version/:version', function($version) use ($app, $checksFile) {
    $matches = array();

    // get the contents of the "checks" file
    $checks = json_decode(file_get_contents($checksFile));
    // error_log(print_r($checks, true));
    $parts = explode('.', $version);
    $major = (isset($parts[0])) ? $parts[0] : 1;
    $minor = (isset($parts[1])) ? $parts[1] : 1;

    // find anything that has a version "more" than the one given
    foreach ($checks->checks as $check) {
        // first look for one with the same major/minor
        foreach ($check->fixVersions->base as $ver) {
            if (strstr($ver, $major.'.'.$minor) !== false) {
                if (version_compare($ver, $version) > 0) {
                    $matches[] = $check;
                }
            }
        }
    }

    $app->render(
        200,
        array('version' => $version, 'matches' => $matches)
    );
});

$app->run();