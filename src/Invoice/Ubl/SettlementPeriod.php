<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use DateTime;

class SettlementPeriod implements XmlSerializable
{
    private DateTime $startDate;
    private DateTime $endDate;
    
    private function __construct(DateTime $startDate, DateTime $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }        

    /**
     * 
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * 
     * @param DateTime $startDate
     * @return SettlementPeriod
     */
    public function setStartDate(DateTime $startDate): SettlementPeriod
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * 
     * @return DateTime
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * 
     * @param DateTime $endDate
     * @return SettlementPeriod
     */
    public function setEndDate(DateTime $endDate): SettlementPeriod
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'StartDate' => $this->startDate->format('Y-m-d') ?: '',
            Schema::CBC . 'EndDate' => $this->endDate->format('Y-m-d') ?: '',
        ]);

        $writer->write([
            [
                'name' => Schema::CBC . 'DurationMeasure',
                'value' => $this->endDate->diff($this->startDate)->format('%d'),
                'attributes' => [
                    'unitCode' => 'DAY'
                ]
            ]
        ]);
    }
}
