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

use filter_biblelinks\api\get_biblegateway;

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
    /**
     * Setup the API.
     */
    public function __construct($versions, $passage) {

        $versionsarray = explode(',', $versions);

        $biblegatewayversions = array_filter($versionsarray, function ($version) {
            $versiondetails = $this->getversiondetails($version);
            return $versiondetails['parser'] === self::BIBLEGATEWAY;
        });

        $biblegateway = new get_biblegateway(implode(',', $biblegatewayversions), $passage);
        $data = $biblegateway->getdata();

        $this->data = $data;

        return $data;
    }

    public function getdata() {
        return $this->data;
    }

    private function getversiondetails($version) {
        $data = [];

        $data['TCL02'] = [];
        $data['TCL02'] = [];
        $data['TCL02']['version'] = 'TCL02';
        $data['TCL02']['parser'] = self::BIBLECOM;

        if (!array_key_exists($version, $data)) {
            $data[$version] = [
                'version' => $version,
                'parser' => self::BIBLEGATEWAY,
            ];
        }

        return $data[$version];
    }
}
