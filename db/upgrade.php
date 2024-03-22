<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Bible Links Filter Cache
 *
 * Manage database migrations for filter_biblelinks
 *
 * @package    filter_biblelinks
 * @copyright  2024 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Upgrade_API
 */

/**
 * Bible Links Upgrade
 *
 * @param integer $oldversion
 * @return boolean
 */
function xmldb_filter_biblelinks_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Create initial table.
    if ($oldversion < 2024032201) {
        // Define table filter_biblelinks to be created.
        $table = new xmldb_table('filter_biblelinks_cache');

        // Define fields to be added to filter_biblelinks.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('version', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pkey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('passage', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('text', XMLDB_TYPE_TEXT, 'longtext', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fetched', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        // Add keys to filter_biblelinks.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Add indexes to filter_biblelinks.
        $table->add_index('version_index', XMLDB_INDEX_NOTUNIQUE, ['version']);
        $table->add_index('pkey_index', XMLDB_INDEX_NOTUNIQUE, ['pkey']);

        // Conditionally launch create table for filter_biblelinks.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coursetranslator savepoint reached.
        upgrade_plugin_savepoint(true, 2024032201, 'filter', 'biblelinks');
    }
}
