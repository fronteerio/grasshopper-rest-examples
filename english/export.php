<?php

// Grab the model classes
require('../php/model.php');

// Grab the config data
require('./config.php');

function get_start_end_for_year($year) {
    return array(
        'start' => strtotime($year . '-10-01'),
        'end' => strtotime($year + 1 . '-07-01')
    );
}

function get_events_for_year($year) {
    $db = mysqli_connect('127.0.0.1', 'root', '', 'english');
    if ($db->connect_errno > 0) {
        die('Unable to connect to database [' . $db->connect_error . ']');
    }

    /*
      Query for RMBS

    $sql = 'SELECT mrbs_entry.*, mrbs_room.room_name FROM mrbs_entry
         LEFT JOIN (mrbs_room) ON (mrbs_room.id = mrbs_entry.room_id)
         WHERE start_time >= ?
         AND end_time <= ?
         ORDER BY mrbs_entry.start_time ASC'
    */

    $startEnd = get_start_end_for_year($year);
    $statement = $db->prepare(
        'SELECT * FROM events
         WHERE start_time >= ?
         AND end_time <= ?
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

function group_events_by_part($events) {
    $parts = array(
        'prelim_primary' => array(),
        'part1_primary' => array(),
        'part2_primary' => array(),
        'grad_primary' => array()
    );

    foreach ($events as $event) {
        foreach ($parts as $partName => $partEvents) {
            if (!empty($event[$partName])) {
                $parts[$partName][] = $event;
            }
        }
    }

    return $parts;
}

function build_ug_part_timetable($events, $partName, &$timetables) {
    $mappedParts = array(
        'prelim_primary' => 'prelim',
        'part1_primary' => 'I',
        'part2_primary' => 'II'
    );

    foreach ($events as $event) {
        $module_title = $event[$partName];
        $series_title = $event['name'];
        if (strcasecmp($module_title, "general") == 0) {
            $timetables['english-tripos'][$mappedParts[$partName]]['General'][$series_title][] = $event;
        } else if ($module_title == '7ab') {
            $timetables['english-tripos'][$mappedParts[$partName]]['Paper 7a'][$series_title][] = $event;
            $timetables['english-tripos'][$mappedParts[$partName]]['Paper 7b'][$series_title][] = $event;
        } else {
            $timetables['english-tripos'][$mappedParts[$partName]]['Paper ' . $module_title][$series_title][] = $event;
        }
    }
}

function build_grad_part_timetable($events, $partName, &$timetables) {
    foreach ($events as $event) {
        $eventPartName = substitute_module($event[$partName]);
        if ($event[$partName] == 'research-seminar') {
            $timetables['english-research-seminars'][$eventPartName][$eventPartName][$event['name']][] = $event;
        } else if ($event[$partName] == 'phd') {
            $timetables['english-phd'][$eventPartName][$eventPartName][$event['name']][] = $event;
        } else {
            $timetables[$event[$partName]]['MPhil'][$eventPartName][$event['name']][] = $event;
        }
    }
}

function build_timetables_hierarchy($events) {
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

function build_timetables_json($timetables, &$courses) {
    foreach ($timetables as $timetableKey => $timetableVal) {
        $course = $courses[$timetableKey];
        foreach ($timetableVal as $partKey => $partVal) {
            $partId = "${timetableKey}-{$partKey}";
            $part = new Part($partId, $partKey, null, null, null);
            $course->add_child($part);

            foreach ($partVal as $moduleKey => $moduleVal) {
                $moduleId = "${timetableKey}-{$partKey}-${moduleKey}";
                $module = new Module($moduleId, $moduleKey, null, null, null);
                $part->add_child($module);

                foreach ($moduleVal as $seriesKey => $seriesVal) {
                    $seriesId = md5("${timetableKey}-{$partKey}-${moduleKey}-${seriesKey}");
                    $series = new Series($seriesId, $seriesKey, null);
                    $module->add_series($series);

                    foreach ($seriesVal as $eventVal) {
                        $eventExternalId = "${seriesId}-" . $eventVal['id'];
                        $location = get_event_location($eventVal);
                        $start = date(DATE_ISO8601, $eventVal['start_time']);
                        $end = date(DATE_ISO8601, $eventVal['end_time']);
                        $type = get_event_type_from_name($eventVal['name']);
                        $event = new Event($eventExternalId, $eventVal['name'], $eventVal['description'], null, $location, $start, $end, $type);
                        $event->add_organiser(new Organiser($eventVal['requestor'], null));
                        $series->add_event($event);
                    }
                }
            }
        }
    }

    return $courses;
}

function get_event_location($event) {
    $location = !empty($event['other_room_name']) ? $event['other_room_name'] : $event['room_name'];
    if (preg_match('/^slot/i', $location)) {
        $location = 'TBA';
    }
    return htmlspecialchars($location);
}

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

function importCourse($course) {
    // Get the URL of the API and the access token from the config
    global $api_url;
    global $access_token;

    // We should post the data to /api/orgunit/<id>/import
    $courseId = $course->id;
    $url = "${api_url}/orgunit/${courseId}/import";

    // We need to provide the course data formatted as JSON
    $importData = json_encode($course, JSON_PRETTY_PRINT);

    // We will use cURL to talk to the timetable REST API
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);

    // We need to pass along our access token in an "Authorization" header. This
    // token will identify who the request is coming from and allows the timetable
    // system to ensure nobody else can change the data
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));

    // Pass along the stringified JSON data
    curl_setopt($curl, CURLOPT_POSTFIELDS, array('data' => $importData));

    // Execute the request
    $response = curl_exec($curl);

    // If the request is successful, a "200" status code will be returned. If for whatever
    // reason the request failed, another code will be returned. Typical codes would be:
    //  - 400: The JSON course data that was submitted is incorrect
    //  - 401: You tried to update a course you do not have access to
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($status_code != 200) {
        print "Something went wrong!\n${response}";

        // As something went wrong, we stop the script from doing anything further
        exit(1);
    }
    curl_close($curl);

    // The course data has been imported and should now be in the timetable system
    print "Imported data for " . $course->displayName . "\n";
}

function init() {
    global $courses;

    date_default_timezone_set('Europe/London');

    // Get the events from the database
    $events = get_events_for_year(2014);

    // Build the timetable hierarchy based on the events
    $timetables = build_timetables_hierarchy($events);

    // Generate the importable XML from the timetable hierarchy
    $timetables = build_timetables_json($timetables, $courses);

    // Import each course
    foreach ($courses as $courseId => $course) {
        importCourse($course);
    }
}

init();

?>
