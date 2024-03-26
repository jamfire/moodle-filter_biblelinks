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

/*
 * @module     filter_biblelinks/loader
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Bible Links Passage Loader
 */

(function () {
    /**
     * Get text from processing areas and add them to contenteditables
     */
    document.addEventListener("DOMContentLoaded", function () {
        let passages = document.querySelectorAll(
            '[data-status="fetch"]'
        );

        passages.forEach(item => {
            let version = item.getAttribute('data-version');

            // Create a new XMLHttpRequest object
            let xhr = new XMLHttpRequest();

            let url = "/filter/biblelinks/api.php?passage="
                + item.dataset.passage + "&version="
                + item.dataset.version;

            // Configure the request as a GET request
            xhr.open("GET", url, true);

            // Set up a function to handle the response
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // Request was successful, handle the response
                    let response = JSON.parse(xhr.responseText);
                    console.log(response);
                    item.innerHTML = "";
                    response.data[version].forEach(passage => {
                        item.innerHTML += '<h5>' + passage.passage + '</h5>';
                        item.innerHTML += passage.text;
                    });
                } else {
                    // Request failed, handle the error
                    // console.error("Request failed with status:", xhr.status);
                }
            };

            // Set up a function to handle errors
            xhr.onerror = function () {
                // Request failed, handle the error
                // console.error("Request failed");
            };

            // Send the request
            xhr.send();
        });

    });
})();