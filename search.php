<?php
require __DIR__ . "/vendor/autoload.php";

use ycit\GeneratePdfFromFax;

$rawJson = file_get_contents('./data/people.json');

if (isset($_GET['people']) && $_GET['people']) {

    /**
     * Search 'uuid' in JSON data.
     *
     * @param string $uuid
     *
     * return stdClass
     */
    function getPeopleFromUuid($rawJson, $uuid)
    {
        if (!$uuid) {
            error_log('No ID available!');
            return false;
        }
        $srcJson = json_decode($rawJson, false);

        if (!$srcJson) {
            error_log('Empty or incorrect json database!');
            return false;
        }

        $peoples = array_filter(
            $srcJson,
            function($people) use ($uuid) {
                return $people->id === $uuid;
            }
        );

        if (!$peoples) {
            error_log('Uuid not found.');
            return false;
        }

        // We take the first occurrence as a basis
        return array_shift($peoples);
    }

    /**
     * It is worth using at least the symfony-http-foundation package.
     *
     * TODO:
     * - Valiate incoming data.
     * - Routing/URL parameters.
     */
    $generator = new GeneratePdfFromFax(getPeopleFromUuid($rawJson, $_GET['people']));
    $generator->generatePdf();
    exit;
} else {
    $srcJson = json_decode($rawJson, false);

    if (!$srcJson) {
        error_log('Empty or incorrect json database!');
        exit;
    }
    foreach ($srcJson as $people) {
        $generator = new GeneratePdfFromFax($people, 'F');
        $generator->generatePdf();
    }
}
