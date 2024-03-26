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
 * Bible Links content filter, with simplified syntax.
 *
 * @package    filter_biblelinks
 * @copyright  2024 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

// Get libs.
require_once($CFG->libdir . '/filterlib.php');
require_once(__DIR__ . "/vendor/autoload.php");

use core_customfield\output\field_data;
use filter_biblelinks\api\parser;

/**
 * Autotranslate current language if not system default
 *
 * The way the filter works is as follows:
 *
 * @package    filter_biblelinks
 * @copyright  2024 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_biblelinks extends moodle_text_filter {
    /*
     * Add the javascript to enable passage processing on this page.
     *
     * @param moodle_page $page The current page.
     * @param context $context The current context.
     */
    public function setup($page, $context) {
        global $CFG;

        if ($page->requires->should_create_one_time_item_now('filter_biblelinks-scripts')) {
            $page->requires->js(new moodle_url($CFG->wwwroot . '/filter/biblelinks/scripts/loader.js'));
        }
    }

    /**
     * This function filters the received text based on the language
     * tags embedded in the text, and the current user language or
     * 'other', if present.
     *
     * @param string $text The text to filter.
     * @param array $options The filter options.
     * @return string The filtered text for this multilang block.
     */
    public function filter($text, array $options = []): string {
        global $COURSE;
        global $DB;

        $contextlevel = $this->context->contextlevel;
        $skip = [];
        $skip[] = $contextlevel === CONTEXT_COURSE;
        $skip[] = $contextlevel === CONTEXT_MODULE;

        // Don't filter text, this is not a course.
        if (!$COURSE) {
            return $text;
        }

        // Get the custom fields for the course.
        $course = new core_course_list_element($COURSE);
        $customfields = [];

        if ($course->has_custom_fields()) {
            $customfields = $course->get_custom_fields();
        }

        $langs = [];
        foreach ($customfields as $field) {
            if ($field->get_field()->get_category()->get('name') === 'Course Language' && $field->get_value()) {
                $lang = $field->get_field()->get('shortname');
                $langs[] = str_replace('course_', '', $lang);
            }
        }

        // Base url for bible passages.
        $baseurl = "https://www.biblegateway.com/passage/?search=";

        // Bible passages pattern.
        $passagepattern = '\b(?:Genesis|Exodus|Leviticus|Numbers|Deuteronomy|Joshua|Judges|Ruth|' .
        '1\s*Samuel|2\s*Samuel|1\s*Kings|2\s*Kings|1\s*Chronicles|2\s*Chronicles|' .
        'Ezra|Nehemiah|Esther|Job|Psalms|Proverbs|Ecclesiastes|Song\s*of\s*Solomon|' .
        'Isaiah|Jeremiah|Lamentations|Ezekiel|Daniel|Hosea|Joel|Amos|Obadiah|Jonah|' .
        'Micah|Nahum|Habakkuk|Zephaniah|Haggai|Zechariah|Malachi|Matthew|Mark|Luke|' .
        'John|Acts|Romans|1\s*Corinthians|2\s*Corinthians|Galatians|Ephesians|Philippians|' .
        'Colossians|1\s*Thessalonians|2\s*Thessalonians|1\s*Timothy|2\s*Timothy|Titus|' .
        'Philemon|Hebrews|James|1\s*Peter|2\s*Peter|1\s*John|2\s*John|3\s*John|Jude|' .
        'Revelation)\s+\d+:\d+(?:-\d+)?(?:,\d+(?:-\d+)?)?(?:\|\w+(?:,\w+)*)?\b';

        $pattern = "/(?:$passagepattern(?:;\s*$passagepattern)*)/";

        // Split the lines so we can process them individually.
        $lines = explode("\n", $text);
        $newlines = [];

        $versions = $this->gettranslations($langs);

        // Iterate through each line and append links.
        foreach ($lines as $line) {
            preg_match($pattern, $line, $matches);
            if (!empty($matches[0])) {
                $localversions = $versions;
                // Build the url.
                $parts = explode('|', $matches[0]);
                $url = $baseurl . $parts[0];

                if (!empty($parts[1])) {
                    $localversions = $parts[1];
                }

                // Append localversions.
                $url .= "&version=" . $localversions;

                // Build the display text.
                $display = $parts[0];
                $display .= ' (' . str_replace(',', ', ', $localversions) . ')';

                // Format the match as a link.
                $link = '<a href="' . $url . '" target="_blank">' . $display . '</a>';
                $newline = str_replace($matches[0], $link, $line);
                $newlines[] = $newline;

                // Open the parallel.
                $versionarray = explode(',', $localversions);
                $html = '<div class="container-fluid w-100 mw-100 px-0">';
                $html .= '<div class="mt-3 mb-3 p-0 border row no-gutters rounded">';

                foreach ($versionarray as $version) {
                    // Open the passages column.
                    $passages = explode(';', $parts[0]);
                    $html .= '<div class="col">';

                    // Header row.
                    $html .= '<div class="bg-primary text-white px-3 py-2 mb-3"><strong>';
                    $html .= $parts[0] . ' ' . $version . '</strong></div>';

                    // Get the parser.
                    $parser = new parser($version, $passages);

                    // Loop through the passages.
                    foreach ($passages as $passage) {
                        // Parse out split passages.
                        $formattedpassages = $parser->formatpassages($passage);

                        foreach ($formattedpassages as $item) {
                            // Check for cached record.
                            $record = $DB->get_record(
                                'filter_biblelinks_cache',
                                [
                                    'version' => trim($version),
                                    'pkey' => trim($item),
                                ],
                                '*'
                            );

                            $status = "fetch";
                            if ($record) {
                                $status = "cached";
                            }
                            $html .= '<div class="px-3 pb-5 filter-biblelinks__bible-passage" data-version="';
                            $html .= trim($version) . '" data-status="' . $status . '"';
                            $html .= ' data-passage="' . trim($passage) . '">';

                            if ($record) {
                                $html .= '<h5>' . $record->passage . '</h5>';
                                $html .= '<div>' . $record->text . '</div>';
                            } else {
                                $html .= '<div class="spinner-border text-primary" role="status">';
                                $html .= '<span class="sr-only">Loading...</span>';
                                $html .= '</div>';
                            }

                            $html .= '</div>';
                        }
                    }

                    // Close the passages column.
                    $html .= "</div>";
                }

                // Close the parallel.
                $html .= '</div>';
                $html .= "</div>";

                $newlines[] = $html;
            } else {
                $newlines[] = $line;
            }
        }

        // Join the parsed text.
        $text = implode("\n", $newlines);

        return $text;
    }

    /**
     * Get translations based on default lang
     */
    private function gettranslations($langs) {

        // Default translations.
        $translations = [
            'ar' => 'NAV',
            'bg' => 'BPB',
            'cs' => 'B21',
            'en' => 'ESV',
            'hu' => 'KAR',
            'pl' => 'UBG',
            'ro' => 'RMNN',
            'ru' => 'NRT',
            'tr' => 'TCL02',
            'uk' => 'UKR',
        ];

        // Get the version associated with language.
        $versions = [];
        foreach ($langs as $key => $lang) {
            $versions[] = $translations[$lang];
        }

        return implode(',', $versions);
    }
}
