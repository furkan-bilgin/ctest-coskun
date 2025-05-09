<?php
function checkTurnstile()
{
    $captcha = $_POST['cf-turnstile-response'];

    if (!$captcha) {
        // What happens when the CAPTCHA was entered incorrectly
        echo '<h2>Please check the the captcha form.</h2>';
        exit;
    }

    // Load the secret key from the json
    $secretKey = file_get_contents('env.json');
    $secretKey = json_decode($secretKey, true);
    $secretKey = $secretKey['turnstile-secret-key'];

    // Get the IP address of the user
    $ip = $_SERVER['REMOTE_ADDR'];

    $url_path = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = array('secret' => $secretKey, 'response' => $captcha, 'remoteip' => $ip);

    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );

    $stream = stream_context_create($options);

    $result = file_get_contents(
        $url_path,
        false,
        $stream
    );

    $response =  $result;
    $responseKeys = json_decode($response, true);

    if (intval($responseKeys["success"]) !== 1) {
        return false;
    }
    return true;
}
