<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PaymentTerms implements XmlSerializable
{
    private ?string $note;
    private string $settlementDiscountPercent;
    private string $amount;
    private ?SettlementPeriod $settlementPeriod;
    
    public function __construct(?SettlementPeriod $settlementPeriod,
                                string $settlementDiscountPercent,
                                ?string $note,
                                string $amount            
                                ) {
            $this->settlementPeriod = $settlementPeriod;
            $this->settlementDiscountPercent = $settlementDiscountPercent;            
            $this->note = $note;
            $this->amount = $amount;
    }

    /**
     * @return null|string
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param null|string $note
     * @return PaymentTerms
     */
    public function setNote(?string $note): PaymentTerms
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return string
     */
    public function getSettlementDiscountPercent(): string
    {
        return $this->settlementDiscountPercent;
    }

    /**
     * @param string $settlementDiscountPercent
     * @return PaymentTerms
     */
    public function setSettlementDiscountPercent(string $settlementDiscountPercent): PaymentTerms
    {
        $this->settlementDiscountPercent = $settlementDiscountPercent;
        return $this;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @return PaymentTerms
     */
    public function setAmount(string $amount): PaymentTerms
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return null|SettlementPeriod
     */
    public function getSettlementPeriod(): ?SettlementPeriod
    {
        return $this->settlementPeriod;
    }

    /**
     * @param null|SettlementPeriod $settlementPeriod
     * @return PaymentTerms
     */
    public function setSettlementPeriod(?SettlementPeriod $settlementPeriod): PaymentTerms
    {
        $this->settlementPeriod = $settlementPeriod;
        return $this;
    }
    
    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PaymentTerms
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->note !== null) {
            $writer->write([ Schema::CBC . 'Note' => $this->note ]);
        }

        $writer->write([ Schema::CBC . 'SettlementDiscountPercent' => $this->settlementDiscountPercent ]);
        
        $writer->write([
            [
                'name' => Schema::CBC . 'Amount',
                'value' => number_format((float)$this->amount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => 'EUR'
                ]
            ]
        ]);

        if ($this->settlementPeriod !== null) {
            $writer->write([ Schema::CAC . 'SettlementPeriod' => $this->settlementPeriod ]);
        }
    }
}
