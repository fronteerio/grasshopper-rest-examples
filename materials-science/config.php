<?php

// The base URL of the REST API
// Change me to https://2016-17.timetable.cam.ac.uk to push data to production
$api_url = 'https://staging.timetable.cam.ac.uk/api';

// The access token that has the necessary permissions to make modifications
$access_token = '--CHANGE-ME-TO-THE-LONG-ACCESS-TOKEN--';

// These are the parts we will be importing into the Timetable system
// The first argument of the Parts instances are the IDs of the respective
// parts in the Timetable system
$parts = array(
    'partia'    => new Part(15274, 'materials-science-part-ia', 'Part IA', null, false, null),
    'partib'    => new Part(15271, 'materials-science-part-ib', 'Part IB', null, false, null),
    'partii'    => new Part(15270, 'materials-science-part-ii', 'Part II', null, false, null),
    'partiii'   => new Part(15277, 'materials-science-part-iii', 'Part III', null, false, null)
);
?>
