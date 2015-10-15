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
    'prelim_primary'            => new Part(6806, 'english-tripos-prelim', 'English Tripos - Prelim', null, false, null),
    'part1_primary'             => new Part(6816, 'english-tripos-partI', 'English Tripos - Part I', null, false, null),
    'part2_primary'             => new Part(6788, 'english-tripos-partII', 'English Tripos - Part II', null, false, null),

    // Graduate parts
    'american-mphil'            => new Part(7825, 'american-mphil', 'American Literature MPhil', null, false, null),
    'c-and-c-mphil'             => new Part(6520, 'c-and-c-mphil', 'Criticism and Culture MPhil', null, false, null),
    'eighteenth-mphil'          => new Part(6518, 'eighteenth-mphil', '18th Century and Romantic Studies MPhil', null, false, null),
    'modern-mphil'              => new Part(6351, 'modern-mphil', 'Modern and Contemporary MPhil', null, false, null),
    'med-ren-mphil'             => new Part(6516, 'med-ren-mphil', 'Medieval and Renaissance Literature MPhil', null, false, null),
    'research-seminar'          => new Part(6245, 'research-seminar', 'Research Seminar', null, false, null),
    'phd'                       => new Part(6223, 'phd', 'English Phd', null, false, null),
);
?>
