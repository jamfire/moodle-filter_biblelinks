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

require_once(dirname(__DIR__, 2) . '/config.php');
require_login();

header('Content-Type: application/json; charset=utf-8');

/**
 * Passage Scraper
 *
 * Processess course data for moodleform. This class is logic heavy.
 *
 * @package    filter_biblelinks
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bibleapi {
    protected string $baseurl;

    protected string $passage;

    protected string $version;

    /**
     * Setup the API.
     */
    public function __construct() {
        $this->baseurl = "https://www.biblegateway.com/passage/";

        if (!array_key_exists('passage', $_GET)) {
            echo json_encode([
                "error" => "You did not specify a passage.",
            ]);
            exit();
        }

        if (!array_key_exists('version', $_GET)) {
            echo json_encode([
                "error" => "You did not specify a version.",
            ]);
            exit();
        }

        $this->passage = @$_GET['passage'];
        $this->version = @$_GET['version'];
    }

    /**
     * Get the passage
     *
     * @return json
     */
    public function getdata() {
        // Build the url.
        $url = $this->baseurl . "?search=" . $this->passage;
        $url .= "&version=" . $this->version;

        // Get the html.
        $html = file_get_contents($url);

        // Could not fetch the html.
        if ($html === false) {
            // Handle error when fetching HTML.
            echo json_encode([
                'error' => 'Could not fetch passage.',
            ]);
            exit();
        }

        // Build a dom for parsing.
        $dom = new DOMDocument();
        // Suppress errors for invalid HTML.
        @$dom->loadHTML($html);

        $parseddata = [];
        $divelements = $dom->getElementsByTagName('div');

        foreach ($divelements as $element) {
            if ($element->getAttribute('class') == 'passage-table') {
                $this->parseelement($parseddata, $element);
            }
        }

        // Get the versions.
        $versions = explode(',', $this->version);
        $data = [];
        foreach ($versions as $version) {
            $data[$version] = [];
            foreach ($parseddata as $item) {
                if ($item['translation'] === $version) {
                    $data[$version][] = $item;
                }
            }
        }

        echo json_encode([
            'url' => $url,
            'data' => $data,
        ]);

        exit();
    }

    /**
     * Parse Element
     */
    private function parseelement(&$organizeddata, $node) {
        foreach ($node->childNodes as $childnode) {
            if ($childnode->nodeType == XML_ELEMENT_NODE) {
                $translation = $childnode->hasAttribute('data-translation') ? $childnode->getAttribute('data-translation') : '';

                if ($translation) {
                    $data = [
                        'translation' => $translation,
                        'passage' => '',
                        'text' => '',
                    ];

                    foreach ($childnode->childNodes as $childnode2) {
                        if ($childnode2->nodeType == XML_ELEMENT_NODE) {
                            switch ($childnode2->getAttribute('class')) {
                                case 'passage-display':
                                    // Get the inner HTML of the passage-display element.
                                    $data['passage'] = $this->getpassage($childnode2);
                                    break;
                                case 'passage-text':
                                    // Get the inner HTML of the passage-text element.
                                    $data['text'] = $this->getpassagetext($childnode2);
                                    break;
                            }
                        }
                    }

                    $organizeddata[] = $data;
                }

                $this->parseElement($organizeddata, $childnode);
            }
        }
    }

    /**
     * Get Passage
     *
     * @return string Passage
     */
    private function getpassage($element) {
        $text = '';

        foreach ($element->childNodes as $child) {
            if ($child->nodeType == XML_ELEMENT_NODE) {
                if ($child->getAttribute('class') === 'dropdown-display-text') {
                    $text .= $child->nodeValue;
                } else {
                    return $this->getpassage($child);
                }
            }
        }

        return $text;
    }

    /**
     * Get Passage Text
     */
    private function getpassagetext($element) {
        // Check if the current element has the class 'text-html'.
        $classattribute = $element->getAttribute('class');
        if ($element->nodeType == XML_ELEMENT_NODE && strpos($classattribute, 'text-html') !== false) {
            $html = '';

            $ignores = ['footnotes', 'full-chap-link', 'crossrefs hidden'];
            $nodes = [];

            foreach ($element->childNodes as $child) {
                if ($child->nodeType == XML_ELEMENT_NODE) {
                    // echo "<pre>";
                    // var_dump($child->getAttribute('class'));
                    // echo "</pre>";
                    if (!in_array($child->getAttribute('class'), $ignores)) {
                        $nodes[] = $child;
                    }
                }
            }

            // Convert nodes list into HTML string.
            $html .= $this->nodesToHTML($nodes);

            // Clean out attributes and remove links.
            $html = $this->cleanHTML($html);

            // echo "<pre>";
            // var_dump($html);
            // echo "</pre>";

            // Return the inner HTML of the current element.
            return $html;
        }

        // Initialize variable to store the inner HTML.
        $innerhtml = '';

        // Iterate over child nodes of the element.
        foreach ($element->childNodes as $child) {
            // Check if the child node is an element node.
            if ($child->nodeType == XML_ELEMENT_NODE) {
                // Recursively call getpassagetext for the child element.
                $childhtml = $this->getpassagetext($child);
                // If inner HTML found in child, return it.
                if ($childhtml) {
                    return $childhtml;
                }
            }
        }

        // If no inner HTML found, return empty string.
        return $innerhtml;
    }

    /**
     * Function to convert nodes list into HTML string.
     */
    private function nodestohtml($nodes) {
        $html = '';
        foreach ($nodes as $node) {
            $html .= $node->ownerDocument->saveHTML($node);
        }
        return $html;
    }

    /**
     * Function to clean out attributes and remove links from HTML
     */
    private function cleanhtml($html) {
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

$bibleapi = new bibleapi();
$bibleapi->getdata();
