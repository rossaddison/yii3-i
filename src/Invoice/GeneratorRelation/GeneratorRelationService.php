<?php
declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

final class GeneratorRelationService
{
    private GeneratorRelationRepository $repository;

    public function __construct(GeneratorRelationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveGeneratorRelation(object $model, GeneratorRelationForm $form): void
    {
        null!==$form->getLowercase_name() ? $model->setLowercase_name($form->getLowercase_name()) : '';
        null!==$form->getCamelcase_name() ? $model->setCamelcase_name($form->getCamelcase_name()) : '';
        null!==$form->getView_field_name() ? $model->setView_field_name($form->getView_field_name()) : '';
        null!==$form->getgentor_id() ? $model->setGentor_id($form->getGentor_id()) : '';
        $this->repository->save($model);
    }
    
    public function deleteGeneratorRelation(object $model): void
    {
        $this->repository->delete($model);
    }
}
