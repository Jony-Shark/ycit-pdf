<?php
/**
 * Company name to namespace.
 */
namespace ycit;

use chillerlan\QRCode\QRCode;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * PDFGenerate from faxes by uuid.
 */
class GeneratePdfFromFax
{
    /**
     * Linux folder markers.
     *
     * @var array
     */

    private const DIR_NAMES = ['.', '..'];

    /**
     * Default QRCode image.
     *
     * @var string
     */
    private const CODE_IMG = '/1.png';

    /**
     * Type for glob list.
     *
     * @var string
     */
    private const TYPE_DIR = 'is_dir';

    /**
     * Default first page template with %s chars.
     *
     * @var string
     */
    private const COVER_TEMPLATE = '/template/titlePage.html';

    /**
     * Default fax page template with %s char.
     *
     * @var string
     */
    private const FAX_TEMPLATE = '/template/faxPage.html';

    /**
     * Requested date format.
     *
     * @var string
     */
    private const FAX_DATE = 'm/d/Y H:i';

    /**
     * Faxes base directory.
     *
     * @var string
     */
    private const FAX_DIR = '/faxes/';

    /**
     * People object.
     *
     * @var stdClass
     */
    private $people;

    /**
     * Initialize
     *
     * @param stdClass $people
     */
    public function __construct($people, $type = 'I')
    {
        $this->people     = $people;
        $this->createType = $type;
    }

    /**
     * Read files from working directory.
     *
     * @param string $folder
     *
     * @return array<string>
     */
    private function getFolderFiles($folder)
    {
        return array_diff(scandir($folder), self::DIR_NAMES);
    }

    /**
     * Path setting based on the current build.
     *
     * @param string $folder
     *
     * @return string
     */
    private function getDirName($folder)
    {
        return dirname(dirname(__FILE__)) . $folder;
    }

    /**
     * Search 'uuid' from first images of faxes.
     *
     * @param array $dirs
     *
     * @return array<string>
     */
    private function getUuidFromQrCodes($dirs)
    {
        $uuid = $this->people->id;
        return array_filter(
            $dirs,
            function($dir) use ($uuid) {
                if (!file_exists($dir . self::CODE_IMG)) {
                    return;
                }

                $result  = (new QRCode)->readFromFile($dir . self::CODE_IMG); // -> DecoderResult
                $content = $result->data;
                $content = (string) $result;

                if ($content !== $uuid) {
                    return;
                }

                return $dir;
            }
        );
    }

    /**
     * Get all sudfolders of faxes with 'uuid'.
     *
     * @return array<string>
     */
    private function getFoldersWithUuid()
    {
        $dirs    = array_filter(glob($this->getDirName(self::FAX_DIR) . '*'), self::TYPE_DIR);
        $folders = $this->getUuidFromQrCodes($dirs);
        asort($folders);

        return $folders;
    }

    /**
     * Create a PDF file in the specified way.
     */
    public function generatePdf()
    {
        if (!$this->people) {
            error_log('Missing or incorrect people');
            return;
        }

        $folders = $this->getFoldersWithUuid();

        if (!$folders) {
            error_log('No faxes from ' . $this->people->name);
            return;
        }

        $mpdf = new Mpdf();
        $mpdf->WriteHTML(
            sprintf(
                file_get_contents($this->getDirName(self::COVER_TEMPLATE)),
                    $this->people->id,
                    $this->people->name,
                    $this->people->date_of_birth,
                    count($folders)
            )
        );

        foreach ($folders as $folder) {
            $mpdf->AddPage();
            $mpdf->WriteHTML(
                sprintf(
                    file_get_contents($this->getDirName(self::FAX_TEMPLATE)),
                        date(self::FAX_DATE, strtotime(str_replace($this->getDirName(self::FAX_DIR), '', $folder))),
                )
            );

            foreach($this->getFolderFiles($folder) as $file) {
                $mpdf->imageVars['faxPage'] = file_get_contents($folder . '/' . $file);
                $html = '<img src="var:faxPage" />';
                $mpdf->WriteHTML($html);
            }
        }
        $mpdf->Output('pdfs/' . $this->people->id . '.pdf', $this->createType);
    }
}
