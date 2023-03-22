<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Contact implements XmlSerializable
{
    private ?string $name;
    private ?string $telephone;
    private ?string $telefax;
    private ?string $electronicMail;
    
    public function __construct(?string $name, ?string $telephone, ?string $telefax, ?string $electronicMail) {
        $this->name = $name;
        $this->telephone = $telephone;
        $this->telefax = $telefax;
        $this->electronicMail = $electronicMail;
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
     * @return Contact
     */
    public function setName(?string $name): Contact
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 
     * @return null|string
     */
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    /**
     * 
     * @param null|string $telephone
     * @return Contact
     */
    public function setTelephone(?string $telephone): Contact
    {
        $this->telephone = $telephone;
        return $this;
    }

    /**
     * 
     * @return null|string
     */
    public function getTelefax(): ?string
    {
        return $this->telefax;
    }

    /**
     * 
     * @param null|string $telefax
     * @return Contact
     */
    public function setTelefax(?string $telefax): Contact
    {
        $this->telefax = $telefax;
        return $this;
    }

    /**
     * 
     * @return null|string
     */
    public function getElectronicMail(): ?string
    {
        return $this->electronicMail;
    }

    /**
     * 
     * @param null|string $electronicMail
     * @return Contact
     */
    public function setElectronicMail(?string $electronicMail): Contact
    {
        $this->electronicMail = $electronicMail;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->name !== null) {
            $writer->write([
                Schema::CBC . 'Name' => $this->name
            ]);
        }

        if ($this->telephone !== null) {
            $writer->write([
                Schema::CBC . 'Telephone' => $this->telephone
            ]);
        }

        if ($this->telefax !== null) {
            $writer->write([
                Schema::CBC . 'Telefax' => $this->telefax
            ]);
        }

        if ($this->electronicMail !== null) {
            $writer->write([
                Schema::CBC . 'ElectronicMail' => $this->electronicMail
            ]);
        }
    }
}
