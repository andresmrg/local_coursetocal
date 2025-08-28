<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_coursetocal.
 */
function xmldb_local_coursetocal_upgrade($oldversion) {
    global $DB;

    // Bump this version number to match version.php (see below).
    $target = 2025082800;

    if ($oldversion < $target) {
        // 1) Legacy rows may have type=-99 and no component set.
        //    Stamp the component so future lookups by component+uuid always work.
        $DB->set_field_select(
            'event',
            'component',
            'local_coursetocal',
            "(component IS NULL OR component = '') AND type = -99"
        );

        // 2) Convert legacy marker type=-99 to standard type=1 for this plugin's events.
        $DB->set_field_select(
            'event',
            'type',
            1,
            "component = 'local_coursetocal' AND type = -99"
        );

        // (Optional) If you also changed how you attach events (e.g., courseid from 0→1),
        // you could normalize here too. Example:
        // $DB->set_field_select('event', 'courseid', 1,
        //     "component = 'local_coursetocal' AND courseid <> 1");

        upgrade_plugin_savepoint(true, $target, 'local', 'coursetocal');
    }

    return true;
}
