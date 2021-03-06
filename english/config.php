<?php

// The base URL of the REST API
$api_url = 'https://staging.timetable.cam.ac.uk/api';

// The access token that has the necessary permissions to make modifications
$access_token = '5f7hp3X6WQaIteb4BcrR1xJge7SMufZ6U3ZbCrTccBdKT2KQ3OuuaK56u2MFu5lEDXrkhWGR5r6NTZkdg9pl4gnFZbEdQSP5Fp1CmAQHf80krVVHPLWZc0bFaD77Xy3PjA1yOQAhUkjNcTTnzjEhPprb2tuOJi6EqnA25lSasTLvcYZADR9StAXxMaJCcWtu7lvXyOIQpuekz5HzPFzsVJety0E6w4peXTpMswUTtOQbCzc22XZ5pnH0KqGBQDQW';

// These are the parts we will be importing into the Timetable system
// The first argument of the Parts instances are the IDs of the respective
// parts in the Timetable system
$parts = array(
    // Undergraduate parts
    'prelim_primary'            => new Part(14189, 'english-tripos-prelim', 'Prelim', null, false, null),
    'part1_primary'             => new Part(14162, 'english-tripos-partI', 'Part I', null, false, null),
    'part2_primary'             => new Part(14172, 'english-tripos-partII', 'Part II', null, false, null),

    // Graduate parts
    'american-mphil'            => new Part(14878, 'american-mphil', 'MPhil', null, false, null),
    'c-and-c-mphil'             => new Part(13915, 'c-and-c-mphil', 'MPhil', null, false, null),
    'eighteenth-mphil'          => new Part(13913, 'eighteenth-mphil', 'MPhil', null, false, null),
    'modern-mphil'              => new Part(13762, 'modern-mphil', 'MPhil', null, false, null),
    'med-ren-mphil'             => new Part(14966, 'med-ren-mphil', 'MPhil', null, false, null),
    'research-seminar'          => new Part(13620, 'research-seminar', 'Research Seminar', null, false, null),
    'phd'                       => new Part(13618, 'phd', 'PhD', null, false, null),
);
?>
