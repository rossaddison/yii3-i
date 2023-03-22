<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use InvalidArgumentException;

class TaxSubTotal implements XmlSerializable
{
    private null|float $taxableAmount = null;
    private null|float $taxAmount = null;
    private null|TaxCategory $taxCategory = null;
    private null|float $percent = null;

    /**
     * 
     * @return float|null
     */
    public function getTaxableAmount(): ?float
    {
        return $this->taxableAmount;
    }

    /**
     * 
     * @param float|null $taxableAmount
     * @return TaxSubTotal
     */
    public function setTaxableAmount(?float $taxableAmount): TaxSubTotal
    {
        $this->taxableAmount = $taxableAmount;
        return $this;
    }

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
     * @return TaxSubTotal
     */
    public function setTaxAmount(?float $taxAmount): TaxSubTotal
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    /**
     * 
     * @return TaxCategory|null
     */
    public function getTaxCategory(): ?TaxCategory
    {
        return $this->taxCategory;
    }

    /**
     * 
     * @param TaxCategory $taxCategory
     * @return TaxSubTotal
     */
    public function setTaxCategory(TaxCategory $taxCategory): TaxSubTotal
    {
        $this->taxCategory = $taxCategory;
        return $this;
    }

    /**
     * 
     * @return float|null
     */
    public function getPercent(): ?float
    {
        return $this->percent;
    }

    /**
     * 
     * @param float|null $percent
     * @return TaxSubTotal
     */
    public function setPercent(?float $percent): TaxSubTotal
    {
        $this->percent = $percent;
        return $this;
    }

    /**
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate() : void
    {
        if ($this->taxableAmount === null) {
            throw new InvalidArgumentException('Missing taxsubtotal taxableAmount');
        }

        if ($this->taxAmount === null) {
            throw new InvalidArgumentException('Missing taxsubtotal taxamount');
        }

        if ($this->taxCategory === null) {
            throw new InvalidArgumentException('Missing taxsubtotal taxcategory');
        }
    }

    /**
     * The xmlSerialize method is called during xml writing.
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $writer->write([
            [
                'name' => Schema::CBC . 'TaxableAmount',
                'value' => number_format($this->taxableAmount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ],
            [
                'name' => Schema::CBC . 'TaxAmount',
                'value' => number_format($this->taxAmount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ]
        ]);

        if ($this->percent !== null) {
            $writer->write([
                Schema::CBC . 'Percent' => $this->percent
            ]);
        }

        $writer->write([
            Schema::CAC . 'TaxCategory' => $this->taxCategory
        ]);
    }
}
