<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Price implements XmlSerializable
{
    private string $priceAmount;
    private string $baseQuantity;
    private string $unitCode = UnitCode::UNIT;
    private string $unitCodeListId;
    private ?AllowanceCharge $allowanceCharge;

    public function __construct(?AllowanceCharge $allowanceCharge, string $priceAmount, string $baseQuantity, string $unitCodeListId ) {
        $this->allowanceCharge = $allowanceCharge;
        $this->baseQuantity = $baseQuantity;
        $this->unitCodeListId = $unitCodeListId;
        $this->priceAmount = $priceAmount;
    }
    
    /**
     * @return string
     */
    public function getPriceAmount(): string
    {
        return $this->priceAmount;
    }

    /**
     * @param string $priceAmount
     * @return Price
     */
    public function setPriceAmount(string $priceAmount): Price
    {
        $this->priceAmount = $priceAmount;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseQuantity(): string
    {
        return $this->baseQuantity;
    }

    /**
     * @param string $baseQuantity
     * @return Price
     */
    public function setBaseQuantity(string $baseQuantity): Price
    {
        $this->baseQuantity = $baseQuantity;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnitCode(): string
    {
        return $this->unitCode;
    }

    /**
     * @param string $unitCode
     * See also: src/UnitCode.php
     * @return Price
     */
    public function setUnitCode(string $unitCode): Price
    {
        $this->unitCode = $unitCode;
        return $this;
    }


    /**
     * @return string
     */
    public function getUnitCodeListId(): string
    {
        return $this->unitCodeListId;
    }

    /**
     * @param string $unitCodeListId
     * @return Price
     */
    public function setUnitCodeListId(string $unitCodeListId): Price
    {
        $this->unitCodeListId = $unitCodeListId;
        return $this;
    }

    /**
     * @return null|AllowanceCharge
     */
    public function getAllowanceCharge(): ?AllowanceCharge
    {
        return $this->allowanceCharge;
    }

    /**
     * @param null|AllowanceCharge $allowanceCharge
     * @return Price
     */
    public function setAllowanceCharge(?AllowanceCharge $allowanceCharge): Price
    {
        $this->allowanceCharge = $allowanceCharge;
        return $this;
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=Price
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $baseQuantityAttributes = [
            'unitCode' => $this->unitCode,
        ];

        if (!empty($this->getUnitCodeListId())) {
            $baseQuantityAttributes['unitCodeListID'] = $this->getUnitCodeListId();
        }

        $writer->write([
            [
                'name' => Schema::CBC . 'PriceAmount',
                'value' => number_format((float)$this->priceAmount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ],
            [
                'name' => Schema::CBC . 'BaseQuantity',
                'value' => number_format((float)$this->baseQuantity ?: 0, 2, '.', ''),
                'attributes' => $baseQuantityAttributes
            ]
        ]);

        if ($this->allowanceCharge !== null) {
            $writer->write([
                Schema::CAC . 'AllowanceCharge' => $this->allowanceCharge,
            ]);
        }
    }
}
