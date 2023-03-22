<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class AllowanceCharge implements XmlSerializable
{
    private bool $chargeIndicator;
    private ?int $allowanceChargeReasonCode;
    private ?string $allowanceChargeReason;
    private ?int $multiplierFactorNumeric;
    private ?float $baseAmount;
    private float $amount;
    private ?TaxTotal $taxTotal;
    private ?TaxCategory $taxCategory;
    
    public function __construct(bool $chargeIndicator, ?int $allowanceChargeReasonCode, ?string $allowanceChargeReason, ?int $multiplierFactorNumeric, ?float $baseAmount, float $amount, ?TaxTotal $taxTotal, ?TaxCategory $taxCategory) {
        $this->chargeIndicator = $chargeIndicator;
        $this->allowanceChargeReasonCode = $allowanceChargeReasonCode;
        $this->allowanceChargeReason = $allowanceChargeReason;
        $this->multiplierFactorNumeric = $multiplierFactorNumeric;
        $this->baseAmount = $baseAmount;
        $this->amount = $amount;
        $this->taxTotal = $taxTotal;
        $this->taxCategory = $taxCategory;
    }

    /**
     * 
     * @return bool
     */
    public function isChargeIndicator(): bool
    {
        return $this->chargeIndicator;
    }

    /**
     * 
     * @param bool $chargeIndicator
     * @return AllowanceCharge
     */
    public function setChargeIndicator(bool $chargeIndicator): AllowanceCharge
    {
        $this->chargeIndicator = $chargeIndicator;
        return $this;
    }

    /**
     * 
     * @return int|null
     */
    public function getAllowanceChargeReasonCode(): ?int
    {
        return $this->allowanceChargeReasonCode;
    }

    /**
     * 
     * @param int|null $allowanceChargeReasonCode
     * @return AllowanceCharge
     */
    public function setAllowanceChargeReasonCode(?int $allowanceChargeReasonCode): AllowanceCharge
    {
        $this->allowanceChargeReasonCode = $allowanceChargeReasonCode;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getAllowanceChargeReason(): ?string
    {
        return $this->allowanceChargeReason;
    }

    /**
     * 
     * @param string|null $allowanceChargeReason
     * @return AllowanceCharge
     */
    public function setAllowanceChargeReason(?string $allowanceChargeReason): AllowanceCharge
    {
        $this->allowanceChargeReason = $allowanceChargeReason;
        return $this;
    }

    /**
     * 
     * @return int|null
     */
    public function getMultiplierFactorNumeric(): ?int
    {
        return $this->multiplierFactorNumeric;
    }

    /**
     * 
     * @param int|null $multiplierFactorNumeric
     * @return AllowanceCharge
     */
    public function setMultiplierFactorNumeric(?int $multiplierFactorNumeric): AllowanceCharge
    {
        $this->multiplierFactorNumeric = $multiplierFactorNumeric;
        return $this;
    }

    /**
     * 
     * @return float|null
     */
    public function getBaseAmount(): ?float
    {
        return $this->baseAmount;
    }

    /**
     * 
     * @param float|null $baseAmount
     * @return AllowanceCharge
     */
    public function setBaseAmount(?float $baseAmount): AllowanceCharge
    {
        $this->baseAmount = $baseAmount;
        return $this;
    }

    /**
     * 
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * 
     * @param float $amount
     * @return AllowanceCharge
     */
    public function setAmount(float $amount): AllowanceCharge
    {
        $this->amount = $amount;
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
     * @param TaxCategory|null $taxCategory
     * @return AllowanceCharge
     */
    public function setTaxCategory(?TaxCategory $taxCategory): AllowanceCharge
    {
        $this->taxCategory = $taxCategory;
        return $this;
    }

    /**
     * 
     * @return TaxTotal|null
     */
    public function getTaxtotal(): ?TaxTotal
    {
        return $this->taxTotal;
    }

    /**
     * 
     * @param TaxTotal|null $taxTotal
     * @return AllowanceCharge
     */
    public function setTaxtotal(?TaxTotal $taxTotal): AllowanceCharge
    {
        $this->taxTotal = $taxTotal;
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
            Schema::CBC . 'ChargeIndicator' => $this->chargeIndicator ? 'true' : 'false',
        ]);

        if ($this->allowanceChargeReasonCode !== null) {
            $writer->write([
                Schema::CBC . 'AllowanceChargeReasonCode' => $this->allowanceChargeReasonCode
            ]);
        }

        if ($this->allowanceChargeReason !== null) {
            $writer->write([
                Schema::CBC . 'AllowanceChargeReason' => $this->allowanceChargeReason
            ]);
        }

        if ($this->multiplierFactorNumeric !== null) {
            $writer->write([
                Schema::CBC . 'MultiplierFactorNumeric' => $this->multiplierFactorNumeric
            ]);
        }

        $writer->write([
            [
                'name' => Schema::CBC . 'Amount',
                'value' => $this->amount,
                'attributes' => [
                    'currencyID' => Generator::$currencyID
                ]
            ],
        ]);

        if ($this->taxCategory !== null) {
            $writer->write([
                Schema::CAC . 'TaxCategory' => $this->taxCategory
            ]);
        }

        if ($this->taxTotal !== null) {
            $writer->write([
                Schema::CAC . 'TaxTotal' => $this->taxTotal
            ]);
        }

        if ($this->baseAmount !== null) {
            $writer->write([
                [
                    'name' => Schema::CBC . 'BaseAmount',
                    'value' => $this->baseAmount,
                    'attributes' => [
                        'currencyID' => Generator::$currencyID
                    ]
                ]
            ]);
        }
    }
}
