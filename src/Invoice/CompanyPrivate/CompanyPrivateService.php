<?php

declare(strict_types=1); 

namespace App\Invoice\CompanyPrivate;

use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Setting\SettingRepository;


final class CompanyPrivateService
{

    private CompanyPrivateRepository $repository;

    public function __construct(CompanyPrivateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveCompanyPrivate(CompanyPrivate $model, CompanyPrivateForm $form, SettingRepository $s): void
    {
       //null!==$form->getCompany_id() ? $model->setCompany($model->getCompany()->getId() == $form->getCompany_id() ? $model->getCompany() : null): ''; 
       $model->setCompany_id($form->getCompany_id());
       $model->setVat_id($form->getVat_id());
       $model->setTax_code($form->getTax_code());
       $model->setIban($form->getIban());
       $model->setGln($form->getGln());
       $model->setRcc($form->getRcc());
       $model->setLogo_filename($form->getLogo_filename());
       $model->setStart_date($form->getStart_date($s));
       $model->setEnd_date($form->getEnd_date($s));
       $this->repository->save($model);
    }
    
    public function addCompanyPrivate(CompanyPrivate $model, CompanyPrivateForm $form, SettingRepository $s): void
    {
       $model->setCompany_id($form->getCompany_id());
       $model->setVat_id($form->getVat_id());
       $model->setTax_code($form->getTax_code());
       $model->setIban($form->getIban());
       $model->setGln($form->getGln());
       $model->setRcc($form->getRcc());
       $model->setLogo_filename($form->getLogo_filename());
       $model->setStart_date($form->getStart_date($s));
       $model->setEnd_date($form->getEnd_date($s));
       $this->repository->save($model);
    }
    
    public function deleteCompanyPrivate(CompanyPrivate $model): void
    {
        $this->repository->delete($model);
    }
}