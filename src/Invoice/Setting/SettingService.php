<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

final class SettingService
{
    private SettingRepository $repository;

    public function __construct(SettingRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveSetting(object $model, SettingForm $form): void
    {
        $form->getSetting_key() ? $model->setSetting_key($form->getSetting_key()) : '';
        $form->getSetting_value() ? $model->setSetting_value($form->getSetting_value()) : '';
        $this->repository->save($model);
    }
    
    public function deleteSetting(object $model): void
    {
        $this->repository->delete($model);
    }
}
