<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class ContractDocumentReference implements XmlSerializable
{
    private ?string $id;
    
    public function __construct(?string $id) {
        $this->id = $id;
    }

    /**
     * 
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * 
     * @param null|string $id
     * @return ContractDocumentReference
     */
    public function setId(?string $id): ContractDocumentReference
    {
        $this->id = $id;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->id !== null) {
            $writer->write([ Schema::CBC . 'ID' => $this->id ]);
        }
    }
}
