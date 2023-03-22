<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Address implements XmlSerializable
{
    private ?string $streetName;
    private ?string $additionalStreetName;
    private ?string $buildingNumber;
    private ?string $cityName;
    private ?string $postalZone;
    private ?string $countrySubentity;
    private ?Country $country;
    
    public function __construct(?string $streetName, ?string $additionalStreetName, ?string $buildingNumber, ?string $cityName, ?string $postalZone, ?string $countrySubEntity, ?Country $country) {
        $this->streetName = $streetName;
        $this->additionalStreetName  = $additionalStreetName;
        $this->buildingNumber = $buildingNumber;
        $this->cityName = $cityName;
        $this->postalZone = $postalZone;
        $this->countrySubentity = $countrySubEntity;
        $this->country = $country;
    } 

    /**
     * 
     * @return string|null
     */
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    /**
     * 
     * @param string|null $streetName
     * @return Address
     */
    public function setStreetName(?string $streetName): Address
    {
        $this->streetName = $streetName;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getAdditionalStreetName(): ?string
    {
        return $this->additionalStreetName;
    }

    /**
     * 
     * @param string|null $additionalStreetName
     * @return Address
     */
    public function setAdditionalStreetName(?string $additionalStreetName): Address
    {
        $this->additionalStreetName = $additionalStreetName;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    /**
     * 
     * @param string|null $buildingNumber
     * @return Address
     */
    public function setBuildingNumber(?string $buildingNumber): Address
    {
        $this->buildingNumber = $buildingNumber;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    /**
     * 
     * @param string|null $cityName
     * @return Address
     */
    public function setCityName(?string $cityName): Address
    {
        $this->cityName = $cityName;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getPostalZone(): ?string
    {
        return $this->postalZone;
    }

    /**
     * 
     * @param string|null $postalZone
     * @return Address
     */
    public function setPostalZone(?string $postalZone): Address
    {
        $this->postalZone = $postalZone;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getCountrySubentity(): ?string
    {
        return $this->countrySubentity;
    }

    /**
     * 
     * @param string $countrySubentity
     * @return Address
     */
    public function setCountrySubentity(string $countrySubentity): Address
    {
        $this->countrySubentity = $countrySubentity;
        return $this;
    }
    
    /**
     * 
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * 
     * @param Country|null $country
     * @return Address
     */
    public function setCountry(?Country $country): Address
    {
        $this->country = $country;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->streetName !== null) {
            $writer->write([
                Schema::CBC . 'StreetName' => $this->streetName
            ]);
        }
        if ($this->additionalStreetName !== null) {
            $writer->write([
                Schema::CBC . 'AdditionalStreetName' => $this->additionalStreetName
            ]);
        }
        if ($this->buildingNumber !== null) {
            $writer->write([
                Schema::CBC . 'BuildingNumber' => $this->buildingNumber
            ]);
        }
        if ($this->cityName !== null) {
            $writer->write([
                Schema::CBC . 'CityName' => $this->cityName,
            ]);
        }
        if ($this->postalZone !== null) {
            $writer->write([
                Schema::CBC . 'PostalZone' => $this->postalZone,
            ]);
        }
        if ($this->countrySubentity !== null) {
            $writer->write([
                Schema::CBC . 'CountrySubentity' => $this->countrySubentity,
            ]);
        }
        if ($this->country !== null) {
            $writer->write([
                Schema::CAC . 'Country' => $this->country,
            ]);
        }
    }
}
