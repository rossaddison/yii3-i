<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use InvalidArgumentException;

class TaxCategory implements XmlSerializable
{
    private null|string $id = '';
    private array $idAttributes = [
        'schemeID' => TaxCategory::UNCL5305,
        'schemeName' => 'Duty or tax or fee category'
    ];
    private string $name = '';
    private string $percent = '';
    private TaxScheme $taxScheme;
    private string $taxExemptionReason = '';
    private string $taxExemptionReasonCode = '';
    public const UNCL5305 = 'UNCL5305';
    
    public function __construct(TaxScheme $taxScheme) {
        $this->taxScheme = $taxScheme;
    }

    /**
     * @return string
     */
    public function getId(): null|string
    {
        if (!empty($this->id)) {
            return $this->id;
        }
        
        if ($this->getPercent() >= 21) {
            return 'S';
        } elseif ($this->getPercent() <= 21 && $this->getPercent() >= 6) {
            return 'AA';
        } else {
            return 'Z';
        }        
        return null;
    }

    /**
     * @param string|null $id
     * @param  array $attributes
     * @return TaxCategory
     */
    public function setId(?string $id, array $attributes = null): TaxCategory
    {
        $this->id = $id;
        if (null!==($attributes)) {
            $this->idAttributes = $attributes;
        }
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 
     * @param string $name
     * @return TaxCategory
     */
    public function setName(string $name): TaxCategory
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getPercent(): ?string
    {
        return $this->percent;
    }

    /**
     * 
     * @param string $percent
     * @return TaxCategory
     */
    public function setPercent(string $percent): TaxCategory
    {
        $this->percent = $percent;
        return $this;
    }

    /**
     * 
     * @return TaxScheme
     */
    public function getTaxScheme(): TaxScheme
    {
        return $this->taxScheme;
    }

    /**
     * 
     * @param TaxScheme $taxScheme
     * @return TaxCategory
     */
    public function setTaxScheme(TaxScheme $taxScheme): TaxCategory
    {
        $this->taxScheme = $taxScheme;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getTaxExemptionReason(): ?string
    {
        return $this->taxExemptionReason;
    }

    /**
     * 
     * @param string $taxExemptionReason
     * @return TaxCategory
     */
    public function setTaxExemptionReason(string $taxExemptionReason): TaxCategory
    {
        $this->taxExemptionReason = $taxExemptionReason;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getTaxExemptionReasonCode(): ?string
    {
        return $this->taxExemptionReasonCode;
    }

    /**
     * 
     * @param string $taxExemptionReasonCode
     * @return TaxCategory
     */
    public function setTaxExemptionReasonCode(string $taxExemptionReasonCode): TaxCategory
    {
        $this->taxExemptionReasonCode = $taxExemptionReasonCode;
        return $this;
    }

    /**
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate() : void
    {
        if ($this->getId() === null) {
            throw new InvalidArgumentException('Missing taxcategory id');
        }

        if ($this->getPercent() === null) {
            throw new InvalidArgumentException('Missing taxcategory percent');
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
                'name' => Schema::CBC . 'ID',
                'value' => $this->getId(),
                'attributes' => $this->idAttributes,
            ],
        ]);
        $writer->write([Schema::CBC . 'Name' => $this->name]);
        $writer->write([Schema::CBC . 'Percent' => number_format((float)$this->percent , 2, '.', ''),]);
        $writer->write([Schema::CBC . 'TaxExemptionReasonCode' => $this->taxExemptionReasonCode]);
        $writer->write([Schema::CBC . 'TaxExemptionReason' => $this->taxExemptionReason]);
        $writer->write([Schema::CAC . 'TaxScheme' => $this->taxScheme]);        
    }
}
