<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Party implements XmlSerializable
{
    private ?string $name;
    private ?string $partyIdentificationId;
    private ?string $partyIdentificationSchemeId;
    private ?Address $postalAddress;
    private ?Address $physicalLocation;
    private ?Contact $contact;
    private ?PartyTaxScheme $partyTaxScheme;
    private ?PartyLegalEntity $partyLegalEntity;
    private ?string $endpointID;
    /** @var null|string|int $endpointId_schemeID */
    private mixed $endpointID_schemeID;
    
    public function __construct(?string $name, ?string $partyIdentificationId, ?string $partyIdentificationSchemeId, ?Address $postalAddress, ?Address $physicalLocation, ?Contact $contact, ?PartyTaxScheme $partyTaxScheme, ?PartyLegalEntity $partyLegalEntity, ?string $endpointID, mixed $endpointID_schemeID) {
        $this->name = $name;
        $this->partyIdentificationId = $partyIdentificationId;
        $this->partyIdentificationSchemeId = $partyIdentificationSchemeId;
        $this->postalAddress = $postalAddress;
        $this->physicalLocation = $physicalLocation;
        $this->contact = $contact;
        $this->partyTaxScheme = $partyTaxScheme;
        $this->partyLegalEntity = $partyLegalEntity;
        $this->endpointID = $endpointID;
        /** @var string $this->endpointID_schemeID */
        $this->endpointID_schemeID = $endpointID_schemeID;
    }

    /**
     * 
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 
     * @param null|string $name
     * @return Party
     */
    public function setName(?string $name): Party
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 
     * @return null|string
     */
    public function getPartyIdentificationId(): ?string
    {
        return $this->partyIdentificationId;
    }

    /**
     * 
     * @param null|string $partyIdentificationId
     * @return Party
     */
    public function setPartyIdentificationId(?string $partyIdentificationId): Party
    {
        $this->partyIdentificationId = $partyIdentificationId;
        return $this;
    }

    /**
     * 
     * @return null|string
     */
    public function getPartyIdentificationSchemeId(): ?string
    {
        return $this->partyIdentificationSchemeId;
    }

    /**
     * 
     * @param null|string $partyIdentificationSchemeId
     * @return Party
     */
    public function setPartyIdentificationSchemeId(?string $partyIdentificationSchemeId): Party
    {
        $this->partyIdentificationSchemeId = $partyIdentificationSchemeId;
        return $this;
    }

    /**
     * 
     * @return null|Address
     */
    public function getPostalAddress(): ?Address
    {
        return $this->postalAddress;
    }

    /**
     * 
     * @param null|Address $postalAddress
     * @return Party
     */
    public function setPostalAddress(?Address $postalAddress): Party
    {
        $this->postalAddress = $postalAddress;
        return $this;
    }

    /**
     * 
     * @return null|PartyLegalEntity
     */
    public function getPartyLegalEntity(): ?PartyLegalEntity
    {
        return $this->partyLegalEntity;
    }

    /**
     * 
     * @param null|PartyLegalEntity $partyLegalEntity
     * @return Party
     */
    public function setPartyLegalEntity(?PartyLegalEntity $partyLegalEntity): Party
    {
        $this->partyLegalEntity = $partyLegalEntity;
        return $this;
    }

    /**
     * 
     * @return null|Address
     */
    public function getPhysicalLocation(): ?Address
    {
        return $this->physicalLocation;
    }

    /**
     * 
     * @param null|Address $physicalLocation
     * @return Party
     */
    public function setPhysicalLocation(?Address $physicalLocation): Party
    {
        $this->physicalLocation = $physicalLocation;
        return $this;
    }

    /**
     * 
     * @return null|PartyTaxScheme
     */
    public function getPartyTaxScheme(): ?PartyTaxScheme
    {
        return $this->partyTaxScheme;
    }

    /**
     * 
     * @param null|PartyTaxScheme $partyTaxScheme
     * @return Party
     */
    public function setPartyTaxScheme(?PartyTaxScheme $partyTaxScheme) : Party
    {
        $this->partyTaxScheme = $partyTaxScheme;
        return $this;
    }

    /**
     * 
     * @return null|Contact
     */
    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    /**
     * 
     * @param null|Contact $contact
     * @return Party
     */
    public function setContact(?Contact $contact): Party
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=EndpointId
     * @param null|string $endpointID
     * @param mixed $schemeID
     * @return Party
     */
    public function setEndpointID(?string $endpointID, mixed $schemeID): Party
    {
        $this->endpointID = $endpointID;
        /** @var string $this->endpointID_schemeID */
        $this->endpointID_schemeID = is_int($schemeID) ? (string)$schemeID : ((is_string($schemeID)) ? $schemeID : '');
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->endpointID !== null && $this->endpointID_schemeID !== null) {
            $writer->write([
                [
                    'name' => Schema::CBC . 'EndpointID',
                    'value' => $this->endpointID,
                    'attributes' => [
                        'schemeID' => is_numeric($this->endpointID_schemeID)
                            ? sprintf('%04d', +$this->endpointID_schemeID)
                            : $this->endpointID_schemeID
                    ]
                ]
            ]);
        }

        if ($this->partyIdentificationId !== null) {
            $partyIdentificationAttributes = [];

            /**
             * For Danish Suppliers it is mandatory to use schemeID when PartyIdentification/ID is used for AccountingCustomerParty or AccountingSupplierParty
             * @see https://github.com/search?q=org%3AOpenPEPPOL+PartyIdentification&type=code
             */
            if (!empty($this->getPartyIdentificationSchemeId())) {
                $partyIdentificationAttributes['schemeID'] = $this->getPartyIdentificationSchemeId();
            }

            $writer->write([
                Schema::CAC . 'PartyIdentification' => [
                    [
                        'name' => Schema::CBC . 'ID',
                        'value' => $this->partyIdentificationId,
                        'attributes' => $partyIdentificationAttributes
                    ]
                ],
            ]);
        }

        if ($this->name !== null) {
            $writer->write([
                Schema::CAC . 'PartyName' => [
                    Schema::CBC . 'Name' => $this->name
                ]
            ]);
        }

        $writer->write([
            Schema::CAC . 'PostalAddress' => $this->postalAddress
        ]);

        if ($this->physicalLocation !== null) {
            $writer->write([
               Schema::CAC . 'PhysicalLocation' => [Schema::CAC . 'Address' => $this->physicalLocation]
            ]);
        }

        if ($this->partyTaxScheme !== null) {
            $writer->write([
                Schema::CAC . 'PartyTaxScheme' => $this->partyTaxScheme
            ]);
        }

        if ($this->partyLegalEntity !== null) {
            $writer->write([
                Schema::CAC . 'PartyLegalEntity' => $this->partyLegalEntity
            ]);
        }

        if ($this->contact !== null) {
            $writer->write([
                Schema::CAC . 'Contact' => $this->contact
            ]);
        }
    }
}
