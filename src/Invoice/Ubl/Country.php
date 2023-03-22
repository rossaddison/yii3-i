<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Country implements XmlSerializable
{
    private string $identificationCode;
    private ?string $listId;
    
    public function __construct(string $identificationCode, ?string $listId) {
        $this->identificationCode = $identificationCode;
        $this->listId = $listId;
    }

    /**
     * @return string
     */
    public function getIdentificationCode(): string
    {
        return $this->identificationCode;
    }

    /**
     * @param string $identificationCode
     * @return Country
     */
    public function setIdentificationCode(string $identificationCode): Country
    {
        $this->identificationCode = $identificationCode;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getListId(): ?string
    {
        return $this->listId;
    }

    /**
     * @param null|string $listId
     * @return Country
     */
    public function setListId(?string $listId): Country
    {
        $this->listId = $listId;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $attributes = [];

        if (!empty($this->listId)) {
            $attributes['listID'] = 'ISO3166-1:Alpha2';
        }

        $writer->write([
            'name' => Schema::CBC . 'IdentificationCode',
            'value' => $this->identificationCode,
            'attributes' => $attributes
        ]);
    }
}
