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

class parser_biblecom {
    protected string $baseurl;

    protected array $passages;

    protected string $version;

    protected int $bibleid;

    protected object $parser;

    /**
     * Setup the API.
     */
    public function __construct($details) {
        // BASE_URL/BIBLE_ID/BIBLE_CODE_ID.
        $this->baseurl = "https://www.bible.com/bible/";
        $this->passages = $details['passages'];
        $this->version = $details['version'];
        $this->bibleid = $details['bibleid'];

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
                    $url = $this->baseurl . $this->bibleid;
                    $url .= "/" . $this->formatpassage($item['pkey']);

                    // Get the html.
                    $html = file_get_contents($url);

                    // Could not fetch the html.
                    if ($html === false) {
                        // Handle error when fetching HTML .
                        echo json_encode([
                            'error' => 'Could not fetch passage: ' . $url,
                        ]);
                        exit();
                    }

                    // Build a dom for parsing.
                    $dom = new \DOMDocument();
                    // Suppress errors for invalid HTML.
                    @$dom->loadHTML($html);

                    // Create a DOMXPath object to query the DOMDocument.
                    $xpath = new \DOMXPath($dom);

                    // Define the XPath query to select all h2 elements.
                    $query = "//h2[contains(normalize-space(), '{$version}')]";

                    // Query for all h2 elements in the document.
                    $h2elements = $xpath->query($query);

                    $parseddata = [];

                    // Loop through the h2 elements.
                    foreach ($h2elements as $h2idx => $h2element) {
                        // Get the passage.
                        $passage = trim(str_replace($version, '', $h2element->textContent));

                        // Select the immediate following sibling anchor (a) element.
                        $followinganchor = $xpath->query('./following-sibling::a[1]', $h2element)->item(0);

                        if ($followinganchor !== null) {
                            // Initialize an empty string to store the content within the anchor tag.
                            $text = '';

                            // Iterate through the child nodes of the anchor element.
                            foreach ($followinganchor->childNodes as $childnode) {
                                // Append the HTML representation of the child node to the content string.
                                $text .= $followinganchor->ownerDocument->saveHTML($childnode);
                            }

                            // Process the content within the anchor tag as needed.
                            $text = $this->parser->cleanhtml($text);
                        }

                        if ($text) {
                            // Save the item to the cache.
                            $record = [
                                'version' => trim($item['version']),
                                'pkey' => trim($item['pkey']),
                                'passage' => trim($passage),
                                'text' => trim($text),
                                'fetched' => time(),
                            ];
                            $DB->insert_record(
                                'filter_biblelinks_cache',
                                $record
                            );

                            // Set the cached text.
                            $cached[$version][$idx]['text'] = $text;
                            $cached[$version][$idx]['passage'] = $passage;
                        }
                    }
                }
            }
        }

        return $cached;
    }

    /**
     * Format Passage
     *
     * @param string Passage to format
     * @return string Formatted passage
     */
    private function formatpassage($passage) {

        $parts = explode(' ', $passage);

        // Get the Bookname.
        $bookname = $parts[0];
        if (strlen($bookname) === 1) {
            $bookname = $parts[0] . " " . $parts[1];
        }

        // List of book keys.
        $books = [
            "Genesis" => "GEN",
            "Exodus" => "EXO",
            "Leviticus" => "LEV",
            "Numbers" => "NUM",
            "Deuteronomy" => "DEU",
            "Joshua" => "JOS",
            "Judges" => "JDG",
            "Ruth" => "RUT",
            "1 Samuel" => "1SA",
            "2 Samuel" => "2SA",
            "1 Kings" => "1KI",
            "2 Kings" => "2KI",
            "1 Chronicles" => "1CH",
            "2 Chronicles" => "2CH",
            "Ezra" => "EZR",
            "Nehemiah" => "NEH",
            "Esther" => "EST",
            "Job" => "JOB",
            "Psalms" => "PSA",
            "Proverbs" => "PRO",
            "Ecclesiastes" => "ECC",
            "Song of Songs" => "SNG",
            "Isaiah" => "ISA",
            "Jeremiah" => "JER",
            "Lamentations" => "LAM",
            "Ezekiel" => "EZK",
            "Daniel" => "DAN",
            "Hosea" => "HOS",
            "Joel" => "JOL",
            "Amos" => "AMO",
            "Obadiah" => "OBA",
            "Jonah" => "JON",
            "Micah" => "MIC",
            "Nahum" => "NAM",
            "Habakkuk" => "HAB",
            "Zephaniah" => "ZEP",
            "Haggai" => "HAG",
            "Zechariah" => "ZEC",
            "Malachi" => "MAL",
            "Matthew" => "MAT",
            "Mark" => "MRK",
            "Luke" => "LUK",
            "John" => "JHN",
            "Acts" => "ACT",
            "Romans" => "ROM",
            "1 Corinthians" => "1CO",
            "2 Corinthians" => "2CO",
            "Galatians" => "GAL",
            "Ephesians" => "EPH",
            "Philippians" => "PHP",
            "Colossians" => "COL",
            "1 Thessalonians" => "1TH",
            "2 Thessalonians" => "2TH",
            "1 Timothy" => "1TI",
            "2 Timothy" => "2TI",
            "Titus" => "TIT",
            "Philemon" => "PHM",
            "Hebrews" => "HEB",
            "James" => "JAS",
            "1 Peter" => "1PE",
            "2 Peter" => "2PE",
            "1 John" => "1JN",
            "2 John" => "2JN",
            "3 John" => "3JN",
            "Jude" => "JUD",
            "Revelation" => "REV",
        ];

        $reference = end($parts);
        $chapterverses = str_replace(':', '.', $reference);

        $passageref = $books[$bookname] . ".";
        $passageref .= $chapterverses . "." . $this->version;

        return $passageref;
    }
}
