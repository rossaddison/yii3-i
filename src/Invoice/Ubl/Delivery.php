<?php
declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use DateTime;

class Delivery implements XmlSerializable
{
    private ?DateTime $actualDeliveryDate;
    private ?Address $deliveryLocation;
    private ?Party $deliveryParty;

    public function __construct(?DateTime $actualDeliveryDate, ?Address $deliveryLocation, ?Party $deliveryParty) {
        $this->actualDeliveryDate = $actualDeliveryDate;
        $this->deliveryLocation = $deliveryLocation;
        $this->deliveryParty = $deliveryParty;       
    }
    
    /**
     * @return null|DateTime
     */
    public function getActualDeliveryDate() : ?DateTime
    {
        return $this->actualDeliveryDate;
    }

    /**
     * @param null|DateTime $actualDeliveryDate
     * @return Delivery
     */
    public function setActualDeliveryDate(?DateTime $actualDeliveryDate): Delivery
    {
        $this->actualDeliveryDate = $actualDeliveryDate;
        return $this;
    }

    /**
     * @return null|Address
     */
    public function getDeliveryLocation() : ?Address
    {
        return $this->deliveryLocation;
    }

    /**
     * @param Address $deliveryLocation
     * @return null|Delivery
     */
    public function setDeliveryLocation(?Address $deliveryLocation): ?Delivery
    {
        $this->deliveryLocation = $deliveryLocation;
        return $this;
    }

    /**
     * @return null|Party
     */
    public function getDeliveryParty() : ?Party
    {
        return $this->deliveryParty;
    }

    /**
     * @param Party $deliveryParty
     * @return null|Delivery
     */
    public function setDeliveryParty(?Party $deliveryParty): ?Delivery
    {
        $this->deliveryParty = $deliveryParty;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->actualDeliveryDate !== null) {
            $writer->write([
               Schema::CBC . 'ActualDeliveryDate' => $this->actualDeliveryDate->format('Y-m-d')
            ]);
        }
        if ($this->deliveryLocation !== null) {
            $writer->write([
               Schema::CAC . 'DeliveryLocation' => [ Schema::CAC . 'Address' => $this->deliveryLocation ]
            ]);
        }
        if ($this->deliveryParty !== null) {
            $writer->write([
               Schema::CAC . 'DeliveryParty' => $this->deliveryParty
            ]);
        }
    }
}
