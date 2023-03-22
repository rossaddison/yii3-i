<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class AdditionalDocumentReference implements XmlSerializable
{
    private string $id;
    private ?string $documentType;
    private ?string $documentDescription;
    private Attachment $attachment;
    
    public function __construct(string $id, ?string $documentType, ?string $documentDescription, Attachment $attachment) {
        $this->id = $id;
        $this->documentType = $documentType;
        $this->documentDescription = $documentDescription;
        $this->attachment = $attachment;
    }

    /**
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 
     * @param string $id
     * @return AdditionalDocumentReference
     */
    public function setId(string $id): AdditionalDocumentReference
    {
        $this->id = $id;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    /**
     * 
     * @param ?string $documentType
     * @return AdditionalDocumentReference
     */
    public function setDocumentType(?string $documentType): AdditionalDocumentReference
    {
        $this->documentType = $documentType;
        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getDocumentDescription(): ?string
    {
        return $this->documentDescription;
    }

    /**
     * 
     * @param string|null $documentDescription
     * @return AdditionalDocumentReference
     */
    public function setDocumentDescription(?string $documentDescription): AdditionalDocumentReference
    {
        $this->documentDescription = $documentDescription;
        return $this;
    }

    /**
     * 
     * @return Attachment|null
     */
    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    /**
     * 
     * @param Attachment $attachment
     * @return AdditionalDocumentReference
     */
    public function setAttachment(Attachment $attachment): AdditionalDocumentReference
    {
        $this->attachment = $attachment;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([ Schema::CBC . 'ID' => $this->id ]);
        if ($this->documentType !== null) {
            $writer->write([
                Schema::CAC . 'DocumentType' => $this->documentType
            ]);
        }
        if ($this->documentDescription !== null) {
            $writer->write([
                Schema::CBC . 'DocumentDescription' => $this->documentDescription
            ]);
        }
        $writer->write([ Schema::CAC . 'Attachment' => $this->attachment ]);
    }
}
