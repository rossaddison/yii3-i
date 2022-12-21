<?php

declare(strict_types=1); 

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use App\Invoice\Setting\SettingRepository;


final class ClientNoteService
{

    private ClientNoteRepository $repository;

    public function __construct(ClientNoteRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function addClientNote(ClientNote $model, ClientNoteForm $form, SettingRepository $s): void
    {
       $model->setClient_id($form->getClient_id());
       $model->setDate($form->getDate($s));
       $model->setNote($form->getNote());
       $this->repository->save($model);
    }

    public function saveClientNote(ClientNote $model, ClientNoteForm $form, SettingRepository $s): void
    {
       $model->setClient_id($form->getClient_id());
       null!==$form->getClient_id() ? $model->setClient($model->getClient()->getClient_id() == $form->getClient_id() ? $model->getClient() : null): '';
       $model->setDate($form->getDate($s));
       $model->setNote($form->getNote());
       $this->repository->save($model);
    }
    
    public function deleteClientNote(ClientNote $model): void
    {
        $this->repository->delete($model);
    }
}