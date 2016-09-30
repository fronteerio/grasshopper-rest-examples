<?php

// Grab the model classes
require('../php/model.php');
// Grab the config data
require('./config.php');

// This function kickstarts the script
function init() {
    global $parts;
    date_default_timezone_set('Europe/London');

    // Loop through the parts that are configured in ./config.php and
    // import them into the timetable system
    foreach ($parts as $partName => $part) {
        handle_part($part, $partName);
    }
}

// This function takes in a part object (from config.php) and it's name (e.g., partia or partiii)
//
// It will read in the data for that part from the CSV file, create the appropriate tree structure for
// the part and then import it using the Timetable REST API.
function handle_part(&$part, $name) {
    // Get the data for this part from the CSV file
    $rows = read_csv($name);

    // Build up a tree of:
    // Modules
    //   - Series
    //       - Events
    //
    // The `rows` array will contain a row for each event, so will have to intelligently group data
    // that belongs to the same series/modules together. We do this by running through the rows twice:
    //   - A first time to group the rows by module
    //   - A second time to run group the rows for a module into series
    $tree = array();
    $modules = array();
    foreach ($rows as $row) {
        $moduleName = $row[11];
        if (isset($modules[$moduleName])) {
            $modules[$moduleName][] = $row;
        } else {
            $modules[$moduleName] = array($row);

            // Each module needs a unique external id. Assuming the name won't change, this should do
            // just fine
            $moduleExternalId = "{$name}.{$moduleName}";
            $moduleDescription = "";
            $published = true;
            $metadata = null;

            // Create a new module object. The class is defined in ../php/model.php and is a utility
            // class that allows you to add series to it. At the end of this function we will serialize
            // the entire structure into JSON and send it to the REST API.
            $module = new Module($moduleExternalId, $moduleName, $moduleDescription, $published, $metadata);
            $tree[$moduleName] = $module;
        }
    }

    // The `modules` associative array now contains a set of rows(~events) for each module. Run through
    // those rows and create series for them
    foreach ($modules as $moduleName => $moduleRows) {
        foreach ($moduleRows as $row) {
            // Create a series and add it to the module. If we already added the series to the module
            // in a previous iteration, the original series will be returned. This ensures that we add
            // the event to the same series object.
            $seriesName = $row[1];
            $seriesExternalId = "{$name}.{$moduleName}.{$seriesName}";
            $seriesDescription = "";
            $series = $tree[$moduleName]->get_or_add_series(new Series($seriesExternalId, $seriesName, $seriesDescription));

            // Get the event object from the row data and add it to the series
            $event = get_event_from_row($row);
            $series->add_event($event);
        }
    }

    // At this point we have a set of module objects that each holds series object who they then own the event objects
    // Add them to the part
    foreach ($tree as $moduleName => $module) {
        $part->add_child($module);
    }

    // The data munging is done, send it to the REST API
    import_part($part);
}

// Read the data from the CSV file
function read_csv($name) {
    // Read the CSV data into an array. Each row will be an element in the array.
    $csv = array_map('str_getcsv', file('data/' . $name . '.csv'));

    // Remove the very first row as we're not interested in the row headers
    array_shift($csv);
    
    // Return the CSV data
    return $csv;
}

// Get an event object from a single CSV ROW
function get_event_from_row($row) {
    $externalId = $row[2];
    $name = $row[1];
    $description = $row[1];
    $location = $row[9];
    $start = format_timestamp($row[3], $row[5]);
    $end = format_timestamp($row[6], $row[7]);
    $type = map_event_type($row[10]);
    $event = new Event($externalId, $name, $description, null, $location, $start, $end, $type);

    // Add the organisers if there are any. The CRSid is provided in the CSV data, so if we append
    // @cam.ac.uk we get a fully qualified shibboleth ID. This allows the Timetable system to display
    // the full name of the lecturer rather than just display the CRS id.
    if ($row[8] != "") {
        $organisers = explode(';', $row[8]);
        foreach ($organisers as $organiser) {
            $event->add_organiser(new Organiser(trim($organiser), trim($organiser) . "@cam.ac.uk"));
        }
    }
    return $event;
}

// Map a Materials Science event type to a Timetable event type
function map_event_type($type) {
    if ($type == "Practical") {
        return "Practical";
    } else if ($type == "Lecture") {
        return "Lecture";
    } else if ($type == "Examples Class" || $type == "computing class") {
        return "Class";
    } else {
        return "other";
    }
}

// Format a date (27/09/2016) and a time (12:00:00) into an ISO8601 timestamp (2016-09-27T12:00:00+00:00)
function format_timestamp($date, $time) {
    $dateParts = explode('/', $date);
    $yearMonthDay = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
    return date(DATE_ISO8601, strtotime($yearMonthDay . ' ' . $time));
}

// Import a part using the timetable API
function import_part($part) {
    // Get the URL of the API and the access token from the config
    global $api_url;
    global $access_token;
    // We should post the data to /api/orgunit/<id>/import
    $partId = $part->id;
    $url = "${api_url}/orgunit/${partId}/import";
    // We need to provide the part data formatted as JSON
    $importData = json_encode($part, JSON_PRETTY_PRINT);
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
    print "Imported data for " . $part->displayName . "\n\r";
}

init();

?>
