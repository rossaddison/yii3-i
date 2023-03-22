<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PartyLegalEntity implements XmlSerializable
{
    private string $registrationName;
    private ?string $companyId;
    private ?string $companyIdAttributes;
    
    public function __construct(string $registrationName, ?string $companyId, ?string $companyIdAttributes) {
        $this->registrationName = $registrationName;
        $this->companyId = $companyId;
        $this->companyIdAttributes = $companyIdAttributes;
    } 

    /**
     * 
     * @return string
     */
    public function getRegistrationName(): string
    {
        return $this->registrationName;
    }

    /**
     * 
     * @param string $registrationName
     * @return PartyLegalEntity
     */
    public function setRegistrationName(string $registrationName): PartyLegalEntity
    {
        $this->registrationName = $registrationName;
        return $this;
    }

    /**
     * 
     * @return null|string
     */
    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }

    /**
     * 
     * @param null|string $companyId
     * @param null|string $companyIdAttributes
     * @return PartyLegalEntity
     */
    public function setCompanyId(?string $companyId, ?string $companyIdAttributes = null): PartyLegalEntity
    {
        $this->companyId = $companyId;
        if (null!==($companyIdAttributes)) {
            $this->companyIdAttributes = $companyIdAttributes;
        }
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
            Schema::CBC . 'RegistrationName' => $this->registrationName,
        ]);
        if ($this->companyId !== null) {
            $writer->write([
                [
                    'name' => Schema::CBC . 'CompanyID',
                    'value' => $this->companyId,
                    /** @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=CompanyId */
                    'attributes' => $this->companyIdAttributes,
                ],
            ]);
        }
    }
}
