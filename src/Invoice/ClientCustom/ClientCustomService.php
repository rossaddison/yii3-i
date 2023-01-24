<?php

declare(strict_types=1); 

namespace App\Invoice\ClientCustom;

final class ClientCustomService
{
    private ClientCustomRepository $repository;

    public function __construct(ClientCustomRepository $repository)
    {
       $this->repository = $repository;
    }

    public function saveClientCustom(object $model, ClientCustomForm $form): void
    {
       null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       null!==$form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       null!==$form->getValue() ? $model->setValue($form->getValue()) : '';       
       $this->repository->save($model);
    }
    
    public function deleteClientCustom(object $model): void
    {
        $this->repository->delete($model);
    }
}