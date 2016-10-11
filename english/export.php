<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Grab the model classes
require('../php/model.php');
// Grab the config data
require('./config.php');
// Given a year in which the Michaelmas term takes place, get a time range that
// includes all three terms. For example, if 2015 is passed in, this function
// will return a range going from the 1st of October 2015 till the 1st of July 2016
function get_start_end_for_year($year) {
    return array(
        'start' => strtotime($year . '-10-01'),
        'end' => strtotime($year + 1 . '-07-01')
    );
}
// Get the events from the mrbs database
function get_events_for_year($year) {
    $db = mysqli_connect('localhost', 'root', '', 'english');
    mysqli_set_charset($db, "utf8");
    if ($db->connect_errno > 0) {
        die('Unable to connect to database [' . $db->connect_error . ']');
    }
    $startEnd = get_start_end_for_year($year);
    $statement = $db->prepare(
        'SELECT mrbs_entry.*, mrbs_room.room_name FROM mrbs_entry
         LEFT JOIN (mrbs_room) ON (mrbs_room.id = mrbs_entry.room_id)
         WHERE start_time >= ?
         AND end_time <= ?
         AND no_plasma is NULL
         AND faculty_teaching is not NULL
         ORDER BY start_time ASC'
    );
    if ($statement == false) {
        die('prepare() failed: ' . htmlspecialchars($db->error));
    }
    $statement->bind_param('ii', $startEnd['start'], $startEnd['end']);
    $statement->execute();
    $parameters = array();
    $meta = $statement->result_metadata();
    // Build: a) An array $row containing column names from $statement
    //        b) An array $parameters containing references to each value in $row
    while ($field = $meta->fetch_field()) {
        $parameters[] = &$row[$field->name];
    }
    // Bind each each column in $statement to each value in the $row array
    // (references to $row values are stored in $parameters).
    call_user_func_array(array($statement, 'bind_result'), $parameters);
    $events = array();
    while ($statement->fetch()) {
        // Copy the $row array into a new array $x and store that in $events.
        // The $row array's values populated on each fetch() call as they're
        // bound above.
        foreach($row as $key => $val) {
            $x[$key] = $val;
        }
        $events[] = $x;
    }
    $db->close();
    return $events;
}
// Given a flat list of events, return a hash of events that indexed by whether they take
// place in an undergraduate or graduate part
function group_events_by_part($events) {
    $parts = array(
        'prelim_primary' => array(),
        'part1_primary' => array(),
        'part2_primary' => array(),
        'grad_primary' => array()
    );
    foreach ($events as $event) {
        // Filter out events that take place on Saturday or Sunday. Although unlikely,
        // this happens when someone creates a repeatable event that re-occurs every day
        // from say Thursday till Tuesday but forgot to remove the sat/sun entries
        if (date("w", $event['start_time']) == 0 || date("w", $event['start_time'])==6) {
            continue;
        }
        // An event can be in multiple parts at the same time. Duplicate these events
        // in each part
        foreach ($parts as $partName => $partEvents) {
            if (!empty($event[$partName])) {
                $parts[$partName][] = $event;
            }
        }
    }
    return $parts;
}
// Build the undergraduate timetables
// partName is one of prelim_primary, part1_primary or part2_primary
function build_ug_part_timetable($events, $partName, &$timetables) {
    // Map the column name to a pretty name. This will also be the displayName
    // of the part in the Timetable UI
    $mappedParts = array(
        'prelim_primary' => 'Prelim',
        'part1_primary' => 'Part I',
        'part2_primary' => 'Part II'
    );
    foreach ($events as $event) {
        $module_title = $event[$partName];
        $series_title = $event['ical_uid'];
        if (strcasecmp($module_title, "general") == 0) {
            $timetables[$partName][$mappedParts[$partName]]['General'][$series_title][] = $event;
        // 7ab is a special key that means it should go under both paper 7a and paper 7b. There should
        // be no paper 7ab in the timetable system however.
        } else if ($module_title == '7ab') {
            $timetables[$partName][$mappedParts[$partName]]['Paper 7a'][$series_title][] = $event;
            $timetables[$partName][$mappedParts[$partName]]['Paper 7b'][$series_title][] = $event;
        } else if ($module_title == 'd12') {
            $timetables[$partName][$mappedParts[$partName]]['Paper d1'][$series_title][] = $event;
            $timetables[$partName][$mappedParts[$partName]]['Paper d2'][$series_title][] = $event;
        } else {
            $timetables[$partName][$mappedParts[$partName]]['Paper ' . $module_title][$series_title][] = $event;
        }
    }
}
// Build the graduate timetables
// partName = 'grad_primary'
function build_grad_part_timetable($events, $partName, &$timetables) {
    $mphils = array(
        'american-mphil',
        'c-and-c-mphil',
        'eighteenth-mphil',
        'modern-mphil',
        'med-ren-mphil',
        'research-seminar',
        'phd'
    );
    foreach ($events as $event) {
        // General events need to be added to a "General module" under all MPhils
        if (strcasecmp($event[$partName], "general") == 0) {
            foreach ($mphils as $mphil) {
                $timetables[$mphil]['MPhil']['General'][$event['ical_uid']][] = $event;
            }
        // All the events for the Mphils go under 1 module which holds the same name as the MPhil.
        // For example, The "18th Century and Romantic English Studies MPhil" will only have 1 module
        // and it's aptly named: "18th Century and Romantic MPhil"
        } else {
            $eventPartName = substitute_module($event[$partName]);
            $timetables[$event[$partName]]['MPhil'][$eventPartName][$event['ical_uid']][] = $event;
        }
    }
}
// Build out the undergrad and graduate timetables
function build_timetables_hierarchy($events) {
    // Index the events by whether they are a prelim, I, II or graduate part
    $parts = group_events_by_part($events);
    $timetables = array();
    foreach ($parts as $partName => $partEvents) {
        switch ($partName) {
            case 'prelim_primary':
            case 'part1_primary':
            case 'part2_primary':
                build_ug_part_timetable($partEvents, $partName, $timetables);
                break;
            case 'grad_primary':
                build_grad_part_timetable($partEvents, $partName, $timetables);
                break;
        }
    }
    return $timetables;
}
// Given a simple key, get the pretty name for a module. If no pretty name could be found,
// the simple key will be returned
function substitute_module($moduleName) {
    $substitutions = array(
        'american-mphil' => 'American Literature MPhil',
        'c-and-c-mphil' => 'Criticism and Culture MPhil',
        'eighteenth-mphil' => '18th Century and Romantic Studies MPhil',
        'modern-mphil' => 'Modern and Contemporary MPhil',
        'med-ren-mphil' => 'Medieval and Renaissance Literature MPhil',
        'research-seminar' => 'Research Seminar',
        'phd' => 'PhD',
        'general' => 'General'
    );
    $substitution = $substitutions[$moduleName];
    return $substitution ? $substitution : $moduleName;
}
// Construct the timetable model
function build_timetables_json($timetables, &$parts) {
    foreach ($timetables as $timetableKey => $timetableVal) {
        $part = $parts[$timetableKey];
        foreach ($timetableVal as $partKey => $partVal) {
            foreach ($partVal as $moduleKey => $moduleVal) {
                $moduleId = "${timetableKey}-{$partKey}-${moduleKey}";
                $module = new Module($moduleId, $moduleKey, null, null, null);
                $part->add_child($module);
                foreach ($moduleVal as $seriesKey => $seriesVal) {
		    // original code for seriesID which uses the id field from mrbs_entry
                    //$seriesId = "${timetableKey}-{$partKey}-${moduleKey}-${seriesKey}";
		    // code from Simon which uses the ical_uid instead. this id doesn't change on edit, which id does. -jlp
		    $seriesId = "${timetableKey}-{$partKey}-${moduleKey}-${seriesKey}";
                    $series = new Series($seriesId, $seriesVal[0]['name'], null);
                    $module->add_series($series);
		    $i=0;
                    foreach ($seriesVal as $eventVal) {
			// again this uses 'id' and that changes on edit. I wrote a replacement which uses series_id + ical_uid + an
			// incremented number to make a unique, REPEATABLE, number for each individual event. This means the API can
			// UPDATE existing extries instead of creating new ones. Not sure of the implications if the no of items changes, though... -JLP
                        //$eventExternalId = "${seriesId}-" . $eventVal['id'];
			$eventExternalId = $i . "-" . str_replace(' ', '_', $seriesId);
			//echo $eventExternalId . "\r\n";
                        $location = get_event_location($eventVal);
                        $start = date(DATE_ISO8601, $eventVal['start_time']);
                        $end = date(DATE_ISO8601, $eventVal['end_time']);
                        $type = get_event_type_from_name($eventVal['name']);
                        $event = new Event($eventExternalId, $eventVal['name'], $eventVal['description'], null, $location, $start, $end, $type);
                        $event->add_organiser(new Organiser($eventVal['requestor'], null));
                        $series->add_event($event);
			$i++;
                    }
                }
            }
        }
    }
    return $parts;
}
// Get the location for an event
function get_event_location($event) {
    $location = !empty($event['other_room_name']) ? $event['other_room_name'] : $event['room_name'];
    if (preg_match('/^slot/i', $location)) {
        $location = 'TBA';
    }
    return htmlspecialchars($location);
}
// Get the type of the event
function get_event_type_from_name($name) {
    $types = array (
        'L' => 'Lecture',
        'C' => 'Class',
        'S' => 'Seminar'
    );
    if (preg_match("/\(\d+([a-z]|[A-Z])/", $name, $matches) && isset($types[$matches[1]])) {
        return $types[$matches[1]];
    }
    return 'Other';
}
// Import a part using the timetable API
function importPart($part) {
    // Get the URL of the API and the access token from the config
    global $api_url;
    global $access_token;

    print "Importing data for " . $part->externalId . "\n\r";

    // We should post the data to /api/orgunit/<id>/import
    $partId = $part->id;
    $url = "${api_url}/orgunit/${partId}/import";
    // We need to provide the part data formatted as JSON
    $importData = json_encode($part, JSON_UNESCAPED_UNICODE);
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - No errors';
        break;
        case JSON_ERROR_DEPTH:
            echo ' - Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            echo ' - Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        case JSON_ERROR_RECURSION:
            echo ' - One or more recursive references in the value to be encoded ';
        case JSON_ERROR_INF_OR_NAN:
            echo ' - One or more NAN or INF values in the value to be encoded';
        case JSON_ERROR_UNSUPPORTED_TYPE:
            echo ' - A value of a type that cannot be encoded was given';
        default:
            echo ' - Unknown error';
        break;
    }
    print "\r\nJSON Encoded data:\r\n";
    print($importData);
    print("\r\nExecuting API request now:\r\n");

    // We will use cURL to talk to the timetable REST API
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    // We need to pass along our access token in an "Authorization" header. This
    // token will identify who the request is coming from and allows the timetable
    // system to ensure nobody else can change the data
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
    // Pass along the stringified JSON data
    curl_setopt($curl, CURLOPT_POSTFIELDS, array('data' => $importData, 'deleteMissing'=>'true'));
//print_r($importData);
//die();
    // Quick note, if you wish to delete all the data in the part, you could comment out the above
    // line and replace it with this one:
    //    curl_setopt($curl, CURLOPT_POSTFIELDS, array('data' => '{}', 'deleteMissing' => 'true'));
    // That would import "empty" data and delete everything else
    // Execute the request
    $response = curl_exec($curl);
    // If the request is successful, a "200" status code will be returned. If for whatever
    // reason the request failed, another code will be returned. Typical codes would be:
    //  - 400: The JSON part data that was submitted is incorrect
    //  - 401: You tried to update a part you do not have access to
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($status_code != 200) {
        print "Something went wrong!\n\r<br />${response}\n\r";
        // As something went wrong, we stop the script from doing anything further
        exit(1);
    }
    curl_close($curl);
    // The part data has been imported and should now be in the timetable system
    print "Imported data for " . $part->externalId . "\n\r";
}
function init() {
    global $parts;
    date_default_timezone_set('Europe/London');
    // Get the events from the database
    $events = get_events_for_year(2016);
    // Build the timetable hierarchy based on the events
    $timetables = build_timetables_hierarchy($events);
    // Generate the importable JSON from the timetable hierarchy
    $timetables = build_timetables_json($timetables, $parts);
    // Import each part
    foreach ($parts as $partId => $part) {
        importPart($part);
    }
}
init();
?>
