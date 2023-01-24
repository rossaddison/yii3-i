<?php

declare(strict_types=1); 

namespace App\Invoice\Sumex;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Sumex\SumexRepository;
use App\Invoice\Sumex\SumexForm;

final class SumexService
{

    private SumexRepository $repository;

    public function __construct(SumexRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveSumex(object $model, SumexForm $form, SettingRepository $s): void
    {
       $form->getInvoice() ? $model->setInvoice($form->getInvoice()) : '';
       $form->getReason() ? $model->setReason($form->getReason()) : '';
       $form->getDiagnosis() ? $model->setDiagnosis($form->getDiagnosis()) : '';
       $form->getObservations() ? $model->setObservations($form->getObservations()) : '';
       $model->setTreatmentstart($form->getTreatmentstart($s));
       $model->setTreatmentend($form->getTreatmentend($s));
       $model->setCasedate($form->getCasedate($s));
       $form->getCasenumber() ? $model->setCasenumber($form->getCasenumber()) : '';
 
       $this->repository->save($model);
    }
    
    public function deleteSumex(object $model): void
    {
        $this->repository->delete($model);
    }
}