<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/** @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-LegalMonetaryTotal/ */
class LegalMonetaryTotal implements XmlSerializable
{
    // Sum of all Invoice line net amounts in the Invoice. Must be rounded to maximum 2 decimals.    
    private float $lineExtensionAmount = 0.00;
    // The total amount of the Invoice without VAT. Must be rounded to maximum 2 decimals.
    private float $taxExclusiveAmount = 0.00;
    // The total amount of the Invoice with VAT. Must be rounded to maximum 2 decimals.
    private float $taxInclusiveAmount = 0.00;
    // Sum of all allowances on document level in the Invoice. Must be rounded to maximum 2 decimals.
    private float $allowanceTotalAmount = 0.00;
    // The outstanding amount that is requested to be paid. Must be rounded to maximum 2 decimals.
    private float $payableAmount = 0.00;
    
    public function __construct(float $lineExtensionAmount, float $taxExclusiveAmount, float $taxInclusiveAmount, float $allowanceTotalAmount, float $payableAmount) {
        $this->lineExtensionAmount = $lineExtensionAmount;
        $this->taxExclusiveAmount = $taxExclusiveAmount;
        $this->taxInclusiveAmount = $taxInclusiveAmount;
        $this->allowanceTotalAmount = $allowanceTotalAmount;
        $this->payableAmount = $payableAmount;
    }

    /**
     * 
     * @return float
     */
    public function getLineExtensionAmount(): float
    {
        return $this->lineExtensionAmount;
    }

    /**
     * 
     * @param float $lineExtensionAmount
     * @return LegalMonetaryTotal
     */
    public function setLineExtensionAmount(float $lineExtensionAmount): LegalMonetaryTotal
    {
        $this->lineExtensionAmount = $lineExtensionAmount;
        return $this;
    }

    /**
     * 
     * @return float
     */
    public function getTaxExclusiveAmount(): float
    {
        return $this->taxExclusiveAmount;
    }

    /**
     * 
     * @param float $taxExclusiveAmount
     * @return LegalMonetaryTotal
     */
    public function setTaxExclusiveAmount(float $taxExclusiveAmount): LegalMonetaryTotal
    {
        $this->taxExclusiveAmount = $taxExclusiveAmount;
        return $this;
    }

    /**
     * 
     * @return float
     */
    public function getTaxInclusiveAmount(): float
    {
        return $this->taxInclusiveAmount;
    }

    /**
     * 
     * @param float $taxInclusiveAmount
     * @return LegalMonetaryTotal
     */
    public function setTaxInclusiveAmount(float $taxInclusiveAmount): LegalMonetaryTotal
    {
        $this->taxInclusiveAmount = $taxInclusiveAmount;
        return $this;
    }

    /**
     * 
     * @return float
     */
    public function getAllowanceTotalAmount(): float
    {
        return $this->allowanceTotalAmount;
    }

    /**
     * 
     * @param float $allowanceTotalAmount
     * @return LegalMonetaryTotal
     */
    public function setAllowanceTotalAmount(float $allowanceTotalAmount): LegalMonetaryTotal
    {
        $this->allowanceTotalAmount = $allowanceTotalAmount;
        return $this;
    }

    /**
     * 
     * @return float
     */
    public function getPayableAmount(): float
    {
        return $this->payableAmount;
    }

    /**
     * 
     * @param float $payableAmount
     * @return LegalMonetaryTotal
     */
    public function setPayableAmount(float $payableAmount): LegalMonetaryTotal
    {
        $this->payableAmount = $payableAmount;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        
        $writer->write([
            /**
             * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cbc-LineExtensionAmount/
             */
            [
                'name' => Schema::CBC . 'LineExtensionAmount',
                'value' => number_format($this->lineExtensionAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]

            ],
            [
                'name' => Schema::CBC . 'TaxExclusiveAmount',
                'value' => number_format($this->taxExclusiveAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]

            ],
            [
                'name' => Schema::CBC . 'TaxInclusiveAmount',
                'value' => number_format($this->taxInclusiveAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]

            ],
            [
                'name' => Schema::CBC . 'AllowanceTotalAmount',
                'value' => number_format($this->allowanceTotalAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]

            ],
            [
                'name' => Schema::CBC . 'PayableAmount',
                'value' => number_format($this->payableAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ],
        ]);
    }
}
