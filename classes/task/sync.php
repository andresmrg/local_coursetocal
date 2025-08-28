<?php
namespace local_coursetocal\task;

defined('MOODLE_INTERNAL') || die();

class sync extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('tasksyncname', 'local_coursetocal'); // add a lang string
    }
    public function execute() {
        \local_coursetocal\helper::sync_events();
    }
}
