<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use InvalidArgumentException;

class TaxTotal implements XmlSerializable
{
    private null|float $taxAmount = null;
    private array $taxSubTotals = [];

    /**
     * 
     * @return float|null
     */
    public function getTaxAmount(): ?float
    {
        return $this->taxAmount;
    }

    /**
     * 
     * @param float|null $taxAmount
     * @return TaxTotal
     */
    public function setTaxAmount(?float $taxAmount): TaxTotal
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    /**
     * 
     * @return array
     */
    public function getTaxSubTotals(): array
    {
        return $this->taxSubTotals;
    }

    /**
     * 
     * @param TaxSubTotal $taxSubTotal
     * @return TaxTotal
     */
    public function addTaxSubTotal(TaxSubTotal $taxSubTotal): TaxTotal
    {
        $this->taxSubTotals[] = $taxSubTotal;
        return $this;
    }

    /**
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate() : void
    {
        if ($this->taxAmount === null) {
            throw new InvalidArgumentException('Missing taxtotal taxamount');
        }
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $writer->write([
            [
                'name' => Schema::CBC . 'TaxAmount',
                'value' => number_format($this->taxAmount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ],
        ]);
        
        /** @var TaxSubTotal $taxSubTotal */
        foreach ($this->taxSubTotals as $taxSubTotal) {
            $writer->write([Schema::CAC . 'TaxSubtotal' => $taxSubTotal]);
        }
    }
}
