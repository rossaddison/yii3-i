<?php

declare(strict_types=1); 

namespace App\Invoice\UserClient;

final class UserClientService
{
    private UserClientRepository $repository;

    public function __construct(UserClientRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveUserClient(object $model, UserClientForm $form): void
    {        
       $form->getUser_id() ? $model->setUser_id($form->getUser_id()) : '';
       $form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       $this->repository->save($model);
    }
    
    public function deleteUserClient(object $model): void
    {       
       $this->repository->delete($model);
    }
}