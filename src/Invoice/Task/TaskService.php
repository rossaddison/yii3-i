<?php

declare(strict_types=1); 

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;

use App\Invoice\Setting\SettingRepository as sR;


final class TaskService
{

    private TaskRepository $repository;

    public function __construct(TaskRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveTask(Task $model, TaskForm $form, sR $sR): void
    {
       $form->getProject_id() ? $model->setProject_id($form->getProject_id()) : '';
       $form->getName() ? $model->setName($form->getName()) : '';
       $form->getDescription() ? $model->setDescription($form->getDescription()) : '';
       $form->getPrice() ? $model->setPrice($form->getPrice()) : $model->setPrice(0.00);
       $form->getStatus() ? $model->setStatus($form->getStatus()) : '';
       $form->getTax_rate_id() ? $model->setTax_rate_id($form->getTax_rate_id()) : '';
       $form->getFinish_date($sR) ? $model->setFinish_date($form->getFinish_date($sR)) : '';
       $this->repository->save($model);
    }
    
    public function deleteTask(Task $model): void
    {
        $this->repository->delete($model);
    }
}