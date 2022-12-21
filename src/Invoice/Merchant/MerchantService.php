<?php

declare(strict_types=1); 

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository as sR;


final class MerchantService
{

    private MerchantRepository $repository;

    public function __construct(MerchantRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param Merchant $model
     * @param MerchantForm $form
     * @return void
     */
    public function saveMerchant(Merchant $model, MerchantForm $form): void
    {
       $model->setInv_id($form->getInv_id());
       $model->setSuccessful($form->getSuccessful());
       $model->setDate($form->getDate());
       $model->setDriver($form->getDriver());
       $model->setResponse($form->getResponse());
       $model->setReference($form->getReference());
 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Merchant $model
     * @param array $array
     * @return void
     */
    public function saveMerchant_via_payment_handler(Merchant $model, array $array): void
    {
       $model->setInv_id((int)$array['inv_id']);
       $model->setSuccessful($array['merchant_response_successful']);
       $model->setDate($array['merchant_response_date']);
       $model->setDriver($array['merchant_response_driver']);
       // Payment success message
       $model->setResponse($array['merchant_response']);
       $model->setReference($array['merchant_response_reference']);
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Merchant $model
     * @return void
     */
    public function deleteMerchant(Merchant $model): void
    {
        $this->repository->delete($model);
    }
    
    /**
     * 
     * @param sR $sR
     * @param string $date
     * @return \DateTime
     */
    public function getDateTime(sR $sR, string $date) : \DateTime
    {
        $datehelper = new DateHelper($sR);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($sR->get_setting('time_zone') ? $sR->get_setting('time_zone') : 'Europe/London')); 
        $datetime->format($datehelper->style());
        $str_replace = str_replace($datehelper->separator(), '-', $date);
        $datetime->modify($str_replace);
        return $datetime;
    }
}