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

namespace filter_biblelinks\api;

class parser {
    protected array $passages;

    protected string $version;

    /**
     * Setup the parsers
     */
    public function __construct($version, $passages) {
        $this->version = $version;
        $this->passages = $passages;
    }

    /**
     * Format the passage array
     *
     * @param string $passage Passage reference.
     * @return array $passages Individual passage references.
     */
    public function formatpassages($passage) {
        $parts = explode(' ', $passage);

        // Get the bookname.
        $book = $parts[0];
        $chapterkey = 1;
        if (strlen($book) === 1) {
            $book = $parts[0] . " " . $parts[1];
            $chapterkey = 2;
        }

        // Get the chapter.
        $chapter = explode(':', $parts[$chapterkey])[0];

        // Format the passages.
        $passage = preg_replace('/,\s+/', ',', $passage);
        $passages = [];
        foreach (explode(',', $passage) as $p) {
            if (strpos($p, $book) === false) {
                $passages[] = $book . " " . $chapter . ":" . $p;
            } else {
                $passages[] = $p;
            }
        }

        return $passages;
    }

    /**
     * Check the cache for a saved translation
     */
    public function checkcache() {
        global $DB;

        $data = [];
        $data[$this->version] = [];

        foreach ($this->passages as $passage) {
            $passages = $this->formatpassages($passage);

            foreach ($passages as $p2) {
                $results = $DB->get_record(
                    'filter_biblelinks_cache',
                    [
                    'version' => trim($this->version),
                    'pkey' => trim($p2),
                    ],
                    '*'
                );

                $item = [];
                $item['version'] = $this->version;
                $item['pkey'] = $p2;
                $item['passage'] = $results ? $results->passage : null;
                $item['text'] = $results ? $results->text : null;

                array_push($data[$this->version], $item);
            }
        }
        return $data;
    }

    /**
     * Function to convert nodes list into HTML string.
     */
    public function nodestohtml($nodes) {
        $html = '';
        foreach ($nodes as $node) {
            $html .= $node->ownerDocument->saveHTML($node);
        }
        return $html;
    }

    /**
     * Function to clean out attributes and remove links from HTML
     */
    public function cleanhtml($html) {
        // Define the list of allowed tags.
        $allowedtags = '<p><br><h1><h2><h3><h4><h5><h6><strong><em><u><blockquote><ul><ol><li>';

        // Strip tags and keep only the allowed tags.
        $cleanedhtml = strip_tags($html, $allowedtags);

        // Remove class attributes.
        $cleanedhtml = preg_replace('/(<[^>]+) class="[^"]*"/i', '$1', $cleanedhtml);

        // Remove items like [abc123].
        $cleanedhtml = preg_replace('/\[[a-z0-9]+\]/i', '', $cleanedhtml);

        // Remove items like (abc123).
        $cleanedhtml = preg_replace('/\([A-Za-z0-9]+\)/i', '', $cleanedhtml);

        // Replace h3 with strong.
        $cleanedhtml = preg_replace('/<h3>(.*?)<\/h3>/', '<strong>$1</strong>', $cleanedhtml);

        return $cleanedhtml;
    }
}
