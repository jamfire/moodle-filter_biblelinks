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

use filter_biblelinks\api\bibleapi;

require_once(dirname(__DIR__, 2) . '/config.php');
require_login();

header('Content-Type: application/json; charset=utf-8');

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

$version = @$_GET['version'];
$passage = @$_GET['passage'];

$bibleapi = new bibleapi($version, $passage);
$data = $bibleapi->getdata();

echo json_encode([
    'data' => $data,
]);

exit();
