<?php
require __DIR__ . "/vendor/autoload.php";

use chillerlan\QRCode\QRCode;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$uuid    = 'b30e03d2-1159-4939-90c7-e250d01d0012';
$rawJson = file_get_contents('./data/people.json');
$peoples = array_filter(
    json_decode($rawJson, false),
    function($people) use ($uuid) {
        return $people->id === $uuid;
    }
);

if (!$peoples) {
    error_log('uuid not found');
    exit;
}

// We take the first occurrence as a basis
$people  = array_shift($peoples);
$dirs    = array_filter(glob('./faxes/*'), 'is_dir');
$folders = getUuidFromQrCodes($dirs, $uuid);
asort($folders);

// $files = array_unshift($files, './blankPage.html');

generatePdf($folders, $people);

function generatePdf($folders, $people)
{

    $mpdf = new Mpdf();
    $mpdf->WriteHTML(
        sprintf(
            file_get_contents('./titlePage.html'),
            $people->id,
            $people->name,
            $people->date_of_birth,
            count($folders)
        )
    );

    foreach ($folders as $folder) {
        $mpdf->AddPage();
        $mpdf->WriteHTML(
            sprintf(
                file_get_contents('./faxPage.html'),
                date('m/d/Y H:i', strtotime(str_replace('./faxes/', '', $folder))),
            )
        );

        foreach(getFolderFiles($folder) as $file) {
            $mpdf->imageVars['faxPage'] = file_get_contents($folders[0] . '/' . $file);
            $html = '<img src="var:faxPage" />';
            $mpdf->WriteHTML($html);
        }
    }
    $mpdf->Output('uuid.pdf', 'I');
}

function getFolderFiles($folder)
{
    $directories = ['.', '..'];
    return array_diff(scandir($folder), $directories);
}

function getUuidFromQrCodes($dirs, $uuid)
{
    return array_filter(
        $dirs,
        function($dir) use ($uuid) {
            if (!file_exists($dir . '/1.png')) {
                return;
            }

            $result  = (new QRCode)->readFromFile($dir . '/1.png'); // -> DecoderResult
            $content = $result->data;
            $content = (string) $result;

            if ($content !== $uuid) {
                return;
            }

            return $dir;
        }
    );
}

// $defaultConfig     = (new ConfigVariables())->getDefaults();
// $defaultFontConfig = (new FontVariables())->getDefaults();
// $mpdf->setHeader('<div>1 page of 6</div>');

