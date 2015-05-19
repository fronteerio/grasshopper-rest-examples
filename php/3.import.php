<?php

require('config.php');

//////////////////////////////////////////////////
// 3. Importing data for an organisational unit //
//////////////////////////////////////////////////

// We'll create a data structure for our course. We have some helper classes
// sitting in the model file which help with some of the basic stuff
require('model.php');

// We will be importing the tree for an organisational unit with the following id
$orgUnitId = 3187;

// Create a very simple tree:
// course
//   - part
//      - module
//        - part
//          - series
//            - event
//              - organiser
$course = new Course('course-1', 'English PHD', 'course', null, null, null);
$part = new Part('part-1', 'Part I', 'part', null, null, null);
$module = new Module('module-1', 'module I', 'module', null, null, null);

$course->addChild($part);
$part->addChild($module);

$series = new Series('series-a', 'Series A', null);
$module->addSeries($series);

$event = new Event('event-1', 'Event a', 'Event descrip', null, 'Room 1', '2015-04-21T13:45:00', '2015-04-21T14:45:00', 'lab');
$series->addEvent($event);

$organiser = new Organiser('Simon Gaeremynck', 'sg555');
$event->addOrganiser($organiser);

// Encore our tree into JSON
$importData = json_encode($course);

// Submit it to the import endpoint, because of all the things it needs to do, this could take a while
$curl = curl_init("${api_url}/orgunit/${orgUnitId}/import");
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, "deleteMissing=false&data=${importData}");
$response = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($status_code != 200) {
    print "Something went wrong!\n${response}";
    exit(1);
}
curl_close($curl);

// Simply print out the response
print_r($response);

?>
