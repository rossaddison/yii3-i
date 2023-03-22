<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class OrderReference implements XmlSerializable
{
    private ?string $id;
    private ?string $salesOrderId;
    
    public function __construct(?string $id, ?string $salesOrderId) {
        $this->id = $id;
        $this->salesOrderId = $salesOrderId;
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
     * @param null|string $id
     * @return OrderReference
     */
    public function setId(?string $id): OrderReference
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSalesOrderId(): ?string
    {
        return $this->salesOrderId;
    }

    /**
     * @param null|string $salesOrderId
     * @return OrderReference
     */
    public function setSalesOrderId(?string $salesOrderId): OrderReference
    {
        $this->salesOrderId = $salesOrderId;
        return $this;
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=SalesOrderId
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->id !== null) {
            $writer->write([ Schema::CBC . 'ID' => $this->id ]);
        }
        if ($this->salesOrderId !== null) {
            $writer->write([ Schema::CBC . 'SalesOrderID' => $this->salesOrderId ]);
        }
    }
}
