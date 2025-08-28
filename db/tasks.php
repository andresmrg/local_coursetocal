<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\local_coursetocal\task\sync',
        'blocking'  => 0,
        'minute'    => '20',
        'hour'      => '0',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*'
    ]
];
