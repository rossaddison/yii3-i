<?php
declare(strict_types=1); 

namespace App\Invoice\Helpers;

use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\Quote;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;

// ********************************************************
// \Mpdf\Output\Destination::INLINE, or "I"
// send the file inline to the browser. The plug-in is used if available. 
// The name given by $filename is used when one selects the �Save as� option on the link generating the PDF.
// 
// \Mpdf\Output\Destination::DOWNLOAD, or "D"
// send to the browser and force a file download with the name given by $filename.
// 
// \Mpdf\Output\Destination::FILE, or "F"
// save to a local file with the name given by $filename (may include a path).
// 
// \Mpdf\Output\Destination::STRING_RETURN, or "S"
// return the document as a string. $filename is ignored.
// 
// Yiisoft\Files\FileHelper::ensuredirectory static function ensures that folders exist and are writeable using the 0775 permission 
// 
// ********************************************************
Class MpdfHelper 
{       
        /**
         * Blank default mode
         */
        const MODE_BLANK = '';
        /**
         * Core fonts mode
         */
        const MODE_CORE = 'c';
        /**
         * Unicode UTF-8 encoded mode
         */
        const MODE_UTF8 = 'UTF-8';
        /**
         * Asian fonts mode
         */
        const MODE_ASIAN = '+aCJK';
        /**
         * A3 page size format
         */
        const FORMAT_A3 = 'A3';
        /**
         * A4 page size format
         */
        const FORMAT_A4 = 'A4';
        /**
         * Letter page size format
         */
        const FORMAT_LETTER = 'Letter';
        /**
         * Legal page size format
         */
        const FORMAT_LEGAL = 'Legal';
        /**
         * Folio page size format
         */
        const FORMAT_FOLIO = 'Folio';
        /**
         * Ledger page size format
         */
        const FORMAT_LEDGER = 'Ledger-L';
        /**
         * Tabloid page size format
         */
        const FORMAT_TABLOID = 'Tabloid';
        /**
         * Portrait orientation
         */
        const ORIENT_PORTRAIT = 'P';
        /**
         * Landscape orientation
         */        
        const ORIENT_LANDSCAPE = 'L';
        /**
         * File output sent to browser inline
         */
        const DEST_BROWSER = 'I';
        /**
         * File output sent for direct download
         */
        const DEST_DOWNLOAD = 'D';
        /**
         * File output sent to a file
         */
        const DEST_FILE = 'F';
        /**
         * File output sent as a string
         */
        const DEST_STRING = 'S';
        /**
         * @var string
         */
        public string $mode = self::MODE_BLANK;
        
        /**
         * 
         * @var string
         */
        public string $format = self::FORMAT_A4;
        /**
         * @var integer
         */
        public int $defaultFontSize = 0;        
        /**
         * @var string
         */
        public string $defaultFont = '';
        /**
         * @var float 
         */
        public float $marginLeft = 15;
        /**
         * @var float 
         */
        public float $marginRight = 15;
        /**
         * @var float
         */
        public float $marginTop = 16;
        /**
         * @var float 
         */
        public float $marginBottom = 16;
        /**
         * @var float
         */
        public float $marginHeader = 9;
        /**
         * @var float
         */
        public float $marginFooter = 9;
        /**
         * @var string
         */
        public string $orientation = self::ORIENT_PORTRAIT;    
        
        /**
         * @var array
         */
        public array $options = [
            'autoScriptToLang' => true,
            'ignore_invalid_utf8' => true,
            'tabSpaces' => 4,
        ];
        
        // The mpdf->Output command currently has no return type therefore
        // psalm-suppress MissingReturnType
        
        /**
         * @param string $html
         * @param string $filename
         * @param bool $stream
         * @param null|string $password
         * @param sR $sR
         * @param bool $isInvoice
         * @param object|null $quote_or_invoice
         * @psalm-suppress MissingReturnType
         */
        public function pdf_create(string $html,
                                   string $filename, 
                                   bool $stream, 
                                   null|string $password , 
                                   sR $sR, 
                                   bool $isInvoice = false, 
                                   object|null $quote_or_invoice = null) 
        
        {
            $sR->load_settings();
            $invoice_array = [];            
            $aliases = $this->ensure_temp_mpdf_folder_and_uploads_folder_exist($sR);  
            $title = ($stream ? $sR::getTempMpdffolderRelativeUrl() . $filename . '.pdf':$filename . '.pdf');
            $start_mpdf = $this->initialize_pdf($password, $sR, $title, $quote_or_invoice, $aliases);
            $css = $this->get_css_file($aliases);
            $mpdf = $this->write_html_to_pdf($css,$html,$start_mpdf);            
            if ($isInvoice) {
                $this->isInvoice($filename, $invoice_array, $stream, $mpdf, $aliases, $sR); 
            }            
            if ($stream == true) {
                // send the file inline to the browser. The plug-in is used if available.
                return $mpdf->Output($filename . '.pdf', self::DEST_BROWSER);
            } else {
                // save to a local file with the name given by $filename (may include a path).
                $mpdf->Output($aliases->get('@uploads').$sR::getTempMpdffolderRelativeUrl() . $filename . '.pdf', self::DEST_FILE);
                return $aliases->get('@uploads').$sR::getTempMpdffolderRelativeUrl() . $filename . '.pdf';
            }
        }
        
        // The mpdf->Output command currently has no return type therefore
        // psalm-suppress MissingReturnType
        
        /**
         * 
         * @param string $filename
         * @param array $invoice_array
         * @param bool $stream
         * @param \Mpdf\Mpdf $mpdf
         * @param Aliases $aliases
         * @param SR $sR
         * @psalm-suppress MissingReturnType
         */
        private function isInvoice(string $filename, array $invoice_array, bool $stream, \Mpdf\Mpdf $mpdf, Aliases $aliases, SR $sR)
        {
            foreach (glob($aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl() . '*' . $filename . '.pdf') as $file) {
                array_push($invoice_array, $file);
            }

            if (!empty($invoice_array)) {
                rsort($invoice_array);

                if ($stream) {
                    return $mpdf->Output($filename . '.pdf', self::DEST_BROWSER);
                } else {
                    /** @var string $invoice_array[0] */
                    return $invoice_array[0];
                }
            }
            // Archive the file if it is an invoice
            $archive_folder = $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl() .'/Invoice';
            $archived_file = $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl() .'/Invoice/'. date('Y-m-d') . '_' . $filename . '.pdf';
            if (!is_dir($archive_folder)){
                FileHelper::ensureDirectory($archive_folder, 0775);
            }
            $mpdf->Output($archived_file, self::DEST_FILE);
            if ($stream) {
                return $mpdf->Output($filename . '.pdf', self::DEST_BROWSER);
            } else {
                return $archived_file;
            }
        }
        
        private function ensure_temp_mpdf_folder_and_uploads_folder_exist(SR $sR): Aliases {
            $aliases = new Aliases(['@invoice' => dirname(__DIR__), '@uploads' => '@invoice/Uploads']);
            // Invoice/Uploads/Temp/Mpdf
            $temp_mpdf_folder = $aliases->get('@uploads').$sR::getTempMpdffolderRelativeUrl();            
            if (!is_dir($temp_mpdf_folder)){
                FileHelper::ensureDirectory($temp_mpdf_folder, 0775);
            } 
            
            // Invoice/Uploads/Archive
            $folder = $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl();
            // Check if the archive folder is available
            if (!(is_dir($folder) || is_link($folder))) {
                FileHelper::ensureDirectory($folder, 0775);
            }
            return $aliases;
        }
        
       /**
        * 
        * @param string|null $password
        * @param SR $sR
        * @param string $title
        * @param object|null $quote_or_invoice
        * @param Aliases $aliases
        * @return \Mpdf\Mpdf
        */
        private function initialize_pdf(string|null $password, SR $sR, string $title, object|null $quote_or_invoice, Aliases $aliases): \Mpdf\Mpdf{
            $temp_mpdf_folder = $aliases->get('@uploads').$sR::getTempMpdffolderRelativeUrl();
            $mpdf = new \Mpdf\Mpdf(array_merge($this->options,['tempDir'=>$temp_mpdf_folder]));
            // mPDF configuration
            $mpdf->SetDirectionality('ltr');
            $mpdf->useAdobeCJK = true;
            $mpdf->autoScriptToLang = true;
            $mpdf->autoVietnamese = true;
            $mpdf->allow_charset_conversion = false;
            $mpdf->autoArabic = true;
            $mpdf->autoLangToFont = true;
            $mpdf->SetTitle($title);
            $mpdf->showImageErrors = true; 
            
            $content = $title. ': '. date($sR->trans('date_format'));
            $mpdf->SetHTMLHeader('<div style="text-align: right; font-size: 8px; font-weight: lighter;">'.$content.'</div>');

            // Set the footer if is invoice and if set in settings
            if (!empty($sR->get_setting('pdf_invoice_footer'))) {
                $mpdf->setAutoBottomMargin = 'stretch';
                $mpdf->SetHTMLFooter('<div id="footer">' . $sR->get_setting('pdf_invoice_footer') . '</div>');
            }

            // Watermark
            if (!empty($sR->get_setting('pdf_watermark'))) {
                $mpdf->showWatermarkText = true;
            }
            
            if (($quote_or_invoice instanceof Quote) || ($quote_or_invoice instanceof Inv)) { 
                if ((null!==$quote_or_invoice->getClient()?->getClient_language())) {
                    if (($sR->get_folder_language() === "Arabic") || $quote_or_invoice->getClient()?->getClient_language() === "Arabic") {
                        $mpdf->SetDirectionality('rtl');         
                    }        
                }
            }
            // Set a password if set for the voucher
            if (!empty($password)) {
                $mpdf->SetProtection(['copy', 'print'], $password, $password);
            }            
            return $mpdf;
        }
        
        /**
         * @return false|string
         */
        private function get_css_file(Aliases $aliases): string|false{
            $cssFile = $aliases->get('@invoice/Asset/kartik-v/kv-mpdf-bootstrap.min.css');
            return file_get_contents($cssFile);
        }
        
        /**
         * 
         * @param string|false $css
         * @param string $html
         * @param \Mpdf\Mpdf $mpdf
         * @return \Mpdf\Mpdf
         */
        private function write_html_to_pdf(string|false $css,string $html,\Mpdf\Mpdf $mpdf): \Mpdf\Mpdf{
            if (is_string($css)) {
                $mpdf->writeHtml($css,1);
            }
            $mpdf->WriteHTML($html,2);
            return $mpdf;
        }
        
        /**
         * Acknowledgement to yii2-mpdf
         *
         * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2020
         *
         * @package yii2-mpdf
         *
         * @version 1.0.6
         */
        private function options(): void{
            $this->options['mode'] = $this->mode;
            $this->options['format'] = $this->format;
            $this->options['default_font_size'] = $this->defaultFontSize;
            $this->options['default_font'] = $this->defaultFont;
            $this->options['margin_left'] = $this->marginLeft;
            $this->options['margin_right'] = $this->marginRight;
            $this->options['margin_top'] = $this->marginTop;
            $this->options['margin_bottom'] = $this->marginBottom;
            $this->options['margin_header'] = $this->marginHeader;
            $this->options['margin_footer'] = $this->marginFooter;
            $this->options['orientation'] = $this->orientation;
        }
}