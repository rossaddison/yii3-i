<?php

declare(strict_types=1);

namespace App\Invoice\Sumex;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;
use \DateTime;

final class SumexForm extends FormModel
{    
    private ?int $invoice=null;
    private ?int $reason=null;
    private ?string $diagnosis='';
    private ?string $observations='';
    private ?string $treatmentstart='';
    private ?string $treatmentend='';
    private ?string $casedate='';
    private ?string $casenumber='';

    public function getInvoice() : int|null
    {
      return $this->invoice;
    }

    public function getReason() : int|null
    {
      return $this->reason;
    }

    public function getDiagnosis() : string|null
    {
      return $this->diagnosis;
    }

    public function getObservations() : string|null
    {
      return $this->observations;
    }

    public function getTreatmentstart() : DateTime|null
    {
        return new \DateTime($this->treatmentstart);       
    }

    public function getTreatmentend() : DateTime|null
    {
        return new \DateTime($this->treatmentend);
    }

    public function getCasedate() : DateTime|null
    {
        return new \DateTime($this->casedate);
    }

    public function getCasenumber() : string|null
    {
      return $this->casenumber;
    }

    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
        'invoice' => [new Required()],
        'reason' => [new Required()],
        'diagnosis' => [new Required()],
        'observations' => [new Required()],
        'treatmentstart' => [new Required()],
        'treatmentend' => [new Required()],
        'casedate' => [new Required()],
        'casenumber' => [new Required()],
    ];
}
}
