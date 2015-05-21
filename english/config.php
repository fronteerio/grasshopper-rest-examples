<?php

// The base URL of the REST API
$api_url = 'http://2014.timetable.grasshopper.local/api';

// The access token that has the necessary permissions to make modifications
$access_token = '5f7hp3X6WQaIteb4BcrR1xJge7SMufZ6U3ZbCrTccBdKT2KQ3OuuaK56u2MFu5lEDXrkhWGR5r6NTZkdg9pl4gnFZbEdQSP5Fp1CmAQHf80krVVHPLWZc0bFaD77Xy3PjA1yOQAhUkjNcTTnzjEhPprb2tuOJi6EqnA25lSasTLvcYZADR9StAXxMaJCcWtu7lvXyOIQpuekz5HzPFzsVJety0E6w4peXTpMswUTtOQbCzc22XZ5pnH0KqGBQDQW';

// These are the courses we will be importing into the Timetable system
// The first argument of the Course instances are the IDs of the respective
// courses in the Timetable system
$courses = array(
    // Undergraduate course
    'english-tripos'            => new Course(6563, 'english-tripos', 'English Tripos', null, false, null),

    // Graduate courses
    'american-mphil'            => new Course(3166, 'american-mphil', 'American Literature MPhil', null, false, null),
    'c-and-c-mphil'             => new Course(3165, 'c-and-c-mphil', 'Criticism and Culture MPhil', null, false, null),
    'eighteenth-mphil'          => new Course(3164, 'eighteenth-mphil', '18th Century and Romantic Studies MPhil', null, false, null),
    'modern-mphil'              => new Course(3130, 'modern-mphil', 'Modern and Contemporary MPhil', null, false, null),
    'med-ren-mphil'             => new Course(3163, 'med-ren-mphil', 'Medieval and Renaissance Literature MPhil', null, false, null),
    'english-research-seminars' => new Course(3126, 'research-seminar', 'Research Seminar', null, false, null),
    'english-phd'               => new Course(3124, 'english-phd', 'English Phd', null, false, null),
);

?>
