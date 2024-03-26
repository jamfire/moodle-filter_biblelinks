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

use filter_biblelinks\api\parser;

class parser_biblegateway {
    protected string $baseurl;

    protected array $passages;

    protected string $version;

    protected object $parser;

    /**
     * Setup the API.
     */
    public function __construct($details) {
        $this->baseurl = "https://www.biblegateway.com/passage/";
        $this->passages = $details['passages'];
        $this->version = $details['version'];

        // Get the parser.
        $this->parser = new parser($this->version, $this->passages);
    }

    /**
     * Get the passage
     *
     * @return json
     */
    public function getdata() {
        global $DB;

        // Check the cache first.
        $cached = $this->parser->checkcache();

        // Loop through the cache.
        foreach ($cached as $version => $items) {
            foreach ($items as $idx => $item) {
                if (!$item['text']) {
                    // Build the url.
                    $url = $this->baseurl . "?search=" . $item['pkey'];
                    $url .= "&version=" . $item['version'];

                    // Get the html.
                    $html = file_get_contents($url);

                    // Could not fetch the html.
                    if ($html === false) {
                        // Handle error when fetching HTML.
                        echo json_encode([
                            'error' => 'Could not fetch passage: ' . $url,
                        ]);
                        exit();
                    }

                    // Build a dom for parsing.
                    $dom = new \DOMDocument();
                    // Suppress errors for invalid HTML.
                    @$dom->loadHTML($html);

                    $parseddata = [];
                    $divelements = $dom->getElementsByTagName('div');

                    foreach ($divelements as $element) {
                        if ($element->getAttribute('class') == 'passage-table') {
                            $this->parseelement($parseddata, $element);
                        }
                    }

                    if ($parseddata[0]['text']) {
                        // Save the item to the cache.
                        $record = [
                            'version' => trim($item['version']),
                            'pkey' => trim($item['pkey']),
                            'passage' => trim($parseddata[0]['passage']),
                            'text' => trim($parseddata[0]['text']),
                            'fetched' => time(),
                        ];
                        $DB->insert_record(
                            'filter_biblelinks_cache',
                            $record
                        );

                        // Set the cached text.
                        $cached[$version][$idx]['text'] = $parseddata[0]['text'];
                        $cached[$version][$idx]['passage'] = $parseddata[0]['passage'];
                    }
                }
            }
        }

        return $cached;
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
                        'version' => $translation,
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
                    if (!in_array($child->getAttribute('class'), $ignores)) {
                        $nodes[] = $child;
                    }
                }
            }

            // Convert nodes list into HTML string.
            $html .= $this->parser->nodesToHTML($nodes);

            // Clean out attributes and remove links.
            $html = $this->parser->cleanHTML($html);

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
}
