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

use filter_biblelinks\api\parser_biblegateway;
use filter_biblelinks\api\parser_biblecom;

require_once(dirname(__DIR__, 4) . '/config.php');
require_login();

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
    const BIBLEGATEWAY = "biblegateway";

    const BIBLECOM = "biblecom";

    protected array $data;

    protected string $versions;

    protected string $passage;

    /**
     * Constructor for the Bible API
     *
     * Sets the data returned from each of the parsers
     *
     * @param string $versions Bible versions requested
     * @param string $passage Passage requested
     */
    public function __construct($versions, $passage) {

        $this->versions = $versions;
        $this->passage = $passage;

        // Biblegateway.com data.
        $biblegatewaydata = $this->parsebiblegateway();

        // Bible.com data.
        $biblecomdata = $this->parsebiblecom();

        // Merged the parsed data.
        $data = array_merge($biblegatewaydata, $biblecomdata);

        $this->data = $data;
    }

    public function getdata() {
        return $this->data;
    }

    /**
     * Get Version Details
     *
     * This returns the needed details to begin parsing for the selectd version
     *
     * @return array $data Bible Version Details
     */
    private function parsingdetails($version) {
        $data = [];

        $passages = explode(';', $this->passage);
        $passages = array_map('trim', $passages);

        // Turkish version.
        $data['TCL02'] = [];
        $data['TCL02'] = [];
        $data['TCL02']['version'] = 'TCL02';
        $data['TCL02']['passages'] = $passages;
        $data['TCL02']['parser'] = self::BIBLECOM;
        $data['TCL02']['bibleid'] = 170;

        // Romanian version.
        $data['VDC'] = [];
        $data['VDC'] = [];
        $data['VDC']['version'] = 'VDC';
        $data['VDC']['passages'] = $passages;
        $data['VDC']['parser'] = self::BIBLECOM;
        $data['VDC']['bibleid'] = 191;

        // Czech version.
        $data['CSP'] = [];
        $data['CSP'] = [];
        $data['CSP']['version'] = 'CSP';
        $data['CSP']['passages'] = $passages;
        $data['CSP']['parser'] = self::BIBLECOM;
        $data['CSP']['bibleid'] = 509;

        // Hungarian version.
        $data['HUNB'] = [];
        $data['HUNB'] = [];
        $data['HUNB']['version'] = 'HUNB';
        $data['HUNB']['passages'] = $passages;
        $data['HUNB']['parser'] = self::BIBLECOM;
        $data['HUNB']['bibleid'] = 1239;

        // If the version does not exist, assume biblegateway.com.
        if (!array_key_exists($version, $data)) {
            $data[$version] = [
                'version' => $version,
                'passages' => $passages,
                'parser' => self::BIBLEGATEWAY,
            ];
        }

        // Return the data.
        return $data[$version];
    }

    /**
     * Parse BibleGateway.com
     *
     * Lookup and cache results from biblegateway.com
     *
     * @return array $data Data fetched from biblegateway.com or the cache
     */
    private function parsebiblegateway() {
        $passage = $this->passage;

        $versionsarray = explode(',', $this->versions);

        // Get versions available on biblegateway.com.
        $versiondata = array_filter($versionsarray, function ($version) {
            $versiondetails = $this->parsingdetails($version);
            return $versiondetails['parser'] === self::BIBLEGATEWAY;
        });
        $details = array_map(function ($version) {
            return $this->parsingdetails($version);
        }, $versiondata);

        $data = [];
        foreach ($details as $detail) {
            // Get biblegateway.com data.
            $parser = new parser_biblegateway($detail);
            $data[] = $parser->getdata();
        }

        return $data;
    }

    /**
     * Parse Bible.com
     *
     * Lookup and cache results from bible.com
     *
     * @param string $versions Bible versions requested
     * @param string $passage Passage requested
     * @return array $data Data fetched from bible.com or the cache
     */
    private function parsebiblecom() {
        $versions = $this->versions;
        $passage = $this->passage;

        $versionsarray = explode(',', $versions);

        // Get versions available on biblegateway.com.
        $versiondata = array_filter($versionsarray, function ($version) {
            $versiondetails = $this->parsingdetails($version);
            return $versiondetails['parser'] === self::BIBLECOM;
        });
        $details = array_map(function ($version) {
            return $this->parsingdetails($version);
        }, $versiondata);

        $data = [];
        foreach ($details as $detail) {
            // Get biblegateway.com data.
            $parser = new parser_biblecom($detail);
            $data[] = $parser->getdata();
        }

        return $data;
    }
}
