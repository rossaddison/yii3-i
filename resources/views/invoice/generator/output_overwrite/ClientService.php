<?php

declare(strict_types=1); 

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;


final class ClientService
{

    private ClientRepository $repository;

    public function __construct(ClientRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveClient(Client $model, ClientForm $form): void
    {
        
   null!==$form->getClient_date_created() ? $model->setClient_date_created($form->getClient_date_created()) : '';
   null!==$form->getClient_date_modified() ? $model->setClient_date_modified($form->getClient_date_modified()) : '';
   null!==$form->getPostaladdress_id() ? $model->setPostaladdress_id($form->getPostaladdress_id()) : '';
   null!==$form->getClient_name() ? $model->setClient_name($form->getClient_name()) : '';
   null!==$form->getClient_surname() ? $model->setClient_surname($form->getClient_surname()) : '';
   null!==$form->getClient_address_1() ? $model->setClient_address_1($form->getClient_address_1()) : '';
   null!==$form->getClient_address_2() ? $model->setClient_address_2($form->getClient_address_2()) : '';
   null!==$form->getClient_building_number() ? $model->setClient_building_number($form->getClient_building_number()) : '';
   null!==$form->getClient_city() ? $model->setClient_city($form->getClient_city()) : '';
   null!==$form->getClient_state() ? $model->setClient_state($form->getClient_state()) : '';
   null!==$form->getClient_zip() ? $model->setClient_zip($form->getClient_zip()) : '';
   null!==$form->getClient_country() ? $model->setClient_country($form->getClient_country()) : '';
   null!==$form->getClient_phone() ? $model->setClient_phone($form->getClient_phone()) : '';
   null!==$form->getClient_fax() ? $model->setClient_fax($form->getClient_fax()) : '';
   null!==$form->getClient_mobile() ? $model->setClient_mobile($form->getClient_mobile()) : '';
   null!==$form->getClient_email() ? $model->setClient_email($form->getClient_email()) : '';
   null!==$form->getClient_web() ? $model->setClient_web($form->getClient_web()) : '';
   null!==$form->getClient_vat_id() ? $model->setClient_vat_id($form->getClient_vat_id()) : '';
   null!==$form->getClient_tax_code() ? $model->setClient_tax_code($form->getClient_tax_code()) : '';
   null!==$form->getClient_language() ? $model->setClient_language($form->getClient_language()) : '';
   null!==$form->getClient_active() ? $model->setClient_active($form->getClient_active()) : '';
   null!==$form->getClient_number() ? $model->setClient_number($form->getClient_number()) : '';
   null!==$form->getClient_avs() ? $model->setClient_avs($form->getClient_avs()) : '';
   null!==$form->getClient_insurednumber() ? $model->setClient_insurednumber($form->getClient_insurednumber()) : '';
   null!==$form->getClient_veka() ? $model->setClient_veka($form->getClient_veka()) : '';
   null!==$form->getClient_birthdate() ? $model->setClient_birthdate($form->getClient_birthdate()) : '';
   null!==$form->getClient_gender() ? $model->setClient_gender($form->getClient_gender()) : '';
 
        $this->repository->save($model);
    }
    
    public function deleteClient(Client $model): void
    {
        $this->repository->delete($model);
    }
}