<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class TaxScheme implements XmlSerializable
{
    private string $id = '';
    private string $taxTypeCode = '';
    private string $name = '';

    /**
     * 
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * 
     * @param string $id
     * @return TaxScheme
     */
    public function setId(string $id): TaxScheme
    {
        $this->id = $id;
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getTaxTypeCode(): string
    {
        return $this->taxTypeCode;
    }

    /**
     * @param string $taxTypeCode
     * @return $this
     */
    public function setTaxTypeCode(string $taxTypeCode)
    {
        $this->taxTypeCode = $taxTypeCode;
        return $this;
    }

    /**
     *  @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'ID' => $this->id
        ]);
        
        $writer->write([
            Schema::CBC . 'TaxTypeCode' => $this->taxTypeCode
        ]);
        
        $writer->write([
            Schema::CBC . 'Name' => $this->name
        ]);
    }
}
