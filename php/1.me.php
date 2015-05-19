<?php

require('config.php');

///////////////////////////////////////////
// 1. Getting information about yourself //
///////////////////////////////////////////

// The endpoint we can use for this is available on /me
$curl = curl_init( "${api_url}/me" );

// We always have to pass along the access token, so the server knows who we are
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));

// We'd like to capture the response in a variable, not print it
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($curl);

// If no errors occur, the server will always respond with a 200 status code
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($status_code != 200) {
    print "Something went wrong!\n${response}";
    exit(1);
}

// Don't forget to clean up after ourselves
curl_close($curl);

// The server will always response with JSON data
$data = json_decode($response, true);

$name = $data['displayName'];
print "Hello " . $name . "!\n";

?>
