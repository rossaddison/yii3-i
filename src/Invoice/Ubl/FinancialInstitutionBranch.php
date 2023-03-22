<?php

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class FinancialInstitutionBranch implements XmlSerializable
{
    private ?string $id;
    
    public function __construct(?string $id) {
        $this->id = $id;
    }

    /**
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param null|string $id
     * @return FinancialInstitutionBranch
     */
    public function setId(?string $id): FinancialInstitutionBranch
    {
        $this->id = $id;
        return $this;
    }

    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'ID' => $this->id
        ]);
    }
}
