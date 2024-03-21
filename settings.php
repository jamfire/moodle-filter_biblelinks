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
 * Autotranslate Settings
 *
 * @package    filter_biblelinks
 * @copyright  2024 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // if ($ADMIN->fulltree) {
    // $translator = new translator();
    // $usage = $translator->getusage();
    // $a = new stdClass();
    // $a->count = $usage->character->count;
    // $a->limit = number_format($usage->character->limit);

    // Usage.
    // $settings->add(
    // new admin_setting_description(
    // 'filter_biblelinks/usage',
    // get_string('usage', 'filter_biblelinks'),
    // get_string('usagedesc', 'filter_biblelinks', $a)
    // )
    // );

    // Usage.
    // $settings->add(
    // new admin_setting_description(
    // 'filter_biblelinks/usagebreak',
    // null,
    // "<br />"
    // )
    // );

    // DeepL apikey.
    // $settings->add(
    // new admin_setting_configtext(
    // 'filter_biblelinks/deeplapikey',
    // get_string('apikey', 'filter_biblelinks'),
    // get_string('apikey_desc', 'filter_biblelinks'),
    // null,
    // PARAM_RAW_TRIMMED,
    // 40
    // )
    // );

    // Schedule jobs limit.
    // $settings->add(
    // new admin_setting_configtext(
    // 'filter_biblelinks/managelimit',
    // get_string('managelimit', 'filter_biblelinks'),
    // get_string('managelimit_desc', 'filter_biblelinks'),
    // 20,
    // PARAM_INT
    // )
    // );

    // Schedule jobs limit.
    // $settings->add(
    // new admin_setting_configtext(
    // 'filter_biblelinks/fetchlimit',
    // get_string('fetchlimit', 'filter_biblelinks'),
    // get_string('fetchlimit_desc', 'filter_biblelinks'),
    // 200,
    // PARAM_INT
    // )
    // );

    // Context level.
    // $settings->add(
    // new admin_setting_configmulticheckbox(
    // 'filter_biblelinks/selectctx',
    // get_string('selectctx', 'filter_biblelinks'),
    // get_string('selectctx_desc', 'filter_biblelinks'),
    // ['40', '50', '70', '80'], // Corrected to use string values.
    // [
    // '10' => get_string('ctx_system', 'filter_biblelinks'),
    // '30' => get_string('ctx_user', 'filter_biblelinks'),
    // '40' => get_string('ctx_coursecat', 'filter_biblelinks'),
    // '50' => get_string('ctx_course', 'filter_biblelinks'),
    // '70' => get_string('ctx_module', 'filter_biblelinks'),
    // '80' => get_string('ctx_block', 'filter_biblelinks'),
    // ]
    // )
    // );
    // }
}
