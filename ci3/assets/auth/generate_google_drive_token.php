<?php
// require __DIR__ . '/../vendor/autoload.php';
require 'C:/xampp/htdocs/gdrive/vendor/autoload.php';

$clientSecret = "client_secret_823425529917-la64cdoa1fcj8vtq6glu1lbtur5ng33i.apps.googleusercontent.com.json";
$token = "client_token_823425529917-la64cdoa1fcj8vtq6glu1lbtur5ng33i.apps.googleusercontent.com.json";

//$clientSecret = "client.secret.aaquinones.fo12@dswd.gov.ph.json";
//$token = "token.aaquinones.fo12@dswd.gov.ph.json";

$client = new Google_Client();
$client->setApplicationName('Drive API PHP');
$client->setAuthConfig($clientSecret);

$client->setAccessType('offline');
$client->setPrompt('select_account consent');
$client->setScopes([
    'https://www.googleapis.com/auth/drive.file',
    'https://www.googleapis.com/auth/drive.readonly',
    'https://www.googleapis.com/auth/drive'
]);

if (file_exists($token)) {
    $accessToken = json_decode(file_get_contents($token), true);
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($accessToken['refresh_token']);
        $accessToken = $client->getAccessToken();
        file_put_contents($token, json_encode($accessToken));
    }
} else {
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    file_put_contents($token, json_encode($accessToken));
}
$client->setAccessToken($accessToken);
$service = new Google_Service_Gmail($client);