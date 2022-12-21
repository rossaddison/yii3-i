<?php

declare(strict_types=1); 

namespace App\Invoice\ClientCustom;

use App\Invoice\Entity\ClientCustom;

final class ClientCustomService
{
    private ClientCustomRepository $repository;

    public function __construct(ClientCustomRepository $repository)
    {
       $this->repository = $repository;
    }

    public function saveClientCustom(ClientCustom $model, ClientCustomForm $form): void
    {
       /** @psalm-suppress PossiblyNullArgument */
       $model->setClient_id($form->getClient_id());
       /** @psalm-suppress PossiblyNullArgument */
       $model->setCustom_field_id($form->getCustom_field_id());
       /** @psalm-suppress PossiblyNullArgument */
       $model->setValue($form->getValue());       
       $this->repository->save($model);
    }
    
    public function deleteClientCustom(ClientCustom $model): void
    {
        $this->repository->delete($model);
    }
}