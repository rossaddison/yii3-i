<?php

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use DateTime;

class InvoicePeriod implements XmlSerializable
{
    private DateTime $startDate;
    private DateTime $endDate;
    
    public function __construct(DateTime $startDate, DateTime $endDate) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     * @return InvoicePeriod
     */
    public function setStartDate(DateTime $startDate): InvoicePeriod
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * @param DateTime $endDate
     * @return InvoicePeriod
     */
    public function setEndDate(DateTime $endDate): InvoicePeriod
    {
        $this->endDate = $endDate;
        return $this;
    }
    
    /**
     * The xmlSerialize method is called during xml writing.
     *
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'StartDate' => $this->startDate->format('Y-m-d') ?: '',
        ]);
        
        $writer->write([
            Schema::CBC . 'EndDate' => $this->endDate->format('Y-m-d') ?: '',
        ]);
    }
}
