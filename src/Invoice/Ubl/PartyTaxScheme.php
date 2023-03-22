<?php
declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use InvalidArgumentException;

class PartyTaxScheme implements XmlSerializable
{
    private ?string $registrationName;
    private string $companyId;
    // A null tax scheme will be validated to be mandatory
    private ?TaxScheme $taxScheme;
    
    public function __construct(?TaxScheme $taxScheme, ?string $registrationName, string $companyId, ) {
        $this->taxScheme = $taxScheme;
        $this->registrationName = $registrationName;
        $this->companyId = $companyId;
    }

    /**
     * @return null|string
     */
    public function getRegistrationName(): ?string
    {
        return $this->registrationName;
    }

    /**
     * @param null|string $registrationName
     * @return PartyTaxScheme
     */
    public function setRegistrationName(?string $registrationName): PartyTaxScheme
    {
        $this->registrationName = $registrationName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    /**
     * @param string $companyId
     * @return PartyTaxScheme
     */
    public function setCompanyId(string $companyId): PartyTaxScheme
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     * @return null|TaxScheme
     */
    public function getTaxScheme(): ?TaxScheme
    {
        return $this->taxScheme;
    }

    /**
     * @param null|TaxScheme $taxScheme
     * @return PartyTaxScheme
     */
    public function setTaxScheme(?TaxScheme $taxScheme): PartyTaxScheme
    {
        $this->taxScheme = $taxScheme;
        return $this;
    }

    /**
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate() : void
    {
        if ($this->taxScheme === null) {
            throw new InvalidArgumentException('Missing TaxScheme');
        }
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PartyTaxScheme
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->registrationName !== null) {
            $writer->write([
                Schema::CBC . 'RegistrationName' => $this->registrationName
            ]);
        }
        
        $writer->write([
            Schema::CBC . 'CompanyID' => $this->companyId
        ]);

        $writer->write([
            Schema::CAC . 'TaxScheme' => $this->taxScheme
        ]);
    }
}
