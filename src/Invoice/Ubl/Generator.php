<?php
declare(strict_types=1);

namespace App\Invoice\Ubl;

use App\Invoice\Ubl\Invoice;
use App\Invoice\Ubl\CreditNote;
use Sabre\Xml\Service;

class Generator
{
    public static string $currencyID;
    
    /** 
     * @psalm-suppress MissingReturnType 
     */
    public static function invoice(Invoice $invoice, string $currencyId = 'EUR') 
    {
        self::$currencyID = $currencyId;
                
        $xmlService = new Service();

        $xmlService->namespaceMap = [
            'urn:oasis:names:specification:ubl:schema:xsd:' . $invoice->xmlTagName . '-2' => '',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2' => 'cbc',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2' => 'cac'
        ];
        
        $input_array = [$invoice];
        // TODO testing on this value 
        /** @psalm-suppress InvalidArgument */
        return $xmlService->write($invoice->xmlTagName, $input_array);
    }

    /** @psalm-suppress MissingReturnType */
    public static function creditNote(CreditNote $creditNote, string $currencyId = 'EUR') 
    {
        return self::invoice($creditNote, $currencyId);
    }
}
