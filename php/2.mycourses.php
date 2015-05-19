<?php

require('config.php');

/////////////////////////////////////////////////
// 2. Get the organisational units we can edit //
/////////////////////////////////////////////////

// The organisational units can be retrieved from /api/orgunit
$curl = curl_init( "${api_url}/orgunit?includePermissions=true&type=course" );
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($status_code != 200) {
    print "Something went wrong!\n${response}";
    exit(1);
}
curl_close($curl);

// The server will always response with JSON data
$data = json_decode($response, true);

// Grab just those organisational units we can edit. When returning
// lists, the server will return a JSON object that contains a `results`
// array with the actual elements.
// Each organisational unit will have a `canManage` property that indicates
// whether you can manage it
print "You can edit:\n";
foreach ($data['results'] as $orgUnit) {
    if ($orgUnit['canManage']) {
        print "- " . $orgUnit['displayName'] . " (id=" . $orgUnit['id'] . ")\n";
    }
}

?>
