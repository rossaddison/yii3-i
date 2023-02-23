<?php
declare(strict_types=1); 

namespace App\Invoice\Inv;
// Entities
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\InvCustom;
use App\User\User;
// Repositories
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
// Helpers
use App\Invoice\Helpers\DateHelper;
// Services
use App\Invoice\InvAmount\InvAmountService as IAS;
use App\Invoice\InvCustom\InvCustomService as ICS;
use App\Invoice\InvItem\InvItemService as IIS;
use App\Invoice\InvTaxRate\InvTaxRateService as ITRS;
// Ancillary
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Security\Random;

final class InvService
{
    private InvRepository $repository;
    private SessionInterface $session;

    public function __construct(IR $repository, SessionInterface $session)
    {
        $this->repository = $repository;
        $this->session = $session;    
    }
    
    /**
     * 
     * @param User $user
     * @param Inv $model
     * @param InvForm $form
     * @param SR $s
     * @return Inv $model
     */
    public function addInv(User $user, Inv $model, InvForm $form, SR $s): Inv
    {        
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style(null!==$form->getDate_created()? $form->getDate_created() : new \DateTime());
       $datetimeimmutable = new \DateTimeImmutable($datetime instanceof \DateTime ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate_created($datetimeimmutable);
       $model->setDate_due($s);
       null!==$form->getClient_id() ? $model->setClient_id((int)$form->getClient_id()) : '';       
       null!==$form->getGroup_id() ? $model->setGroup_id((int)$form->getGroup_id()) : '';
       null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : ''; 
       null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
       null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';
       null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : '';
       null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
       null!==$form->getPayment_method() ? $model->setPayment_method($form->getPayment_method()) : '';
       null!==$form->getTerms() ? $model->setTerms($form->getTerms()) : ''; 
       null!==$form->getCreditinvoice_parent_id() ? $model->setCreditinvoice_parent_id($form->getCreditinvoice_parent_id() ?: 0) : '';
       if ($model->isNewRecord()) {
            // Draft invoices cannot be viewed by Clients. 
            // Mark the invoices as 'sent' so that clients can view their invoices when logged in
            // Draft => 1, Sent => 2
            $model->setStatus_id($s->get_setting('mark_invoices_sent_copy') === '1' ? 2 : 1 );            
            null!==$form->getNumber() ? $model->setNumber($form->getNumber()) : '';
            $model->setUser_id((int)$user->getId());
            $model->setUrl_key(Random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(1);
            $model->setDate_due($s);            
       }
       $this->repository->save($model);
       return $model;
    }
    
    /**
     * 
     * @param User $user
     * @param Inv $model
     * @param InvForm $form
     * @param SR $s
     * @param GR $gR
     * @return Inv $model
     */
    public function saveInv(User $user, Inv $model, InvForm $form, SR $s, GR $gR): Inv 
    {  
       //$before_save = $model; 
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style(null!==$form->getDate_created()? $form->getDate_created() : new \DateTime());
       $datetimeimmutable = new \DateTimeImmutable($datetime instanceof \DateTime ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate_created($datetimeimmutable);
       $model->setDate_due($s);
       
       null!==$form->getClient_id() ? $model->setClient($model->getClient()?->getClient_id() == $form->getClient_id() ? $model->getClient() : null): '';
       $model->setClient_id((int)$form->getClient_id());
       
       null!==$form->getGroup_id() ? $model->setGroup($model->getGroup()?->getId() == $form->getGroup_id() ? $model->getGroup() : null): '';
       $model->setGroup_id((int)$form->getGroup_id());       
       
       null!==$form->getStatus_id() ? $model->setStatus_id($form->getStatus_id()) : '';
       null!==$form->getDiscount_percent() ? $model->setDiscount_percent($form->getDiscount_percent()) : '';
       null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : '';
       null!==$form->getUrl_key() ? $model->setUrl_key($form->getUrl_key()) : '';
       null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
       null!==$form->getPayment_method() ? $model->setPayment_method($form->getPayment_method()) : '';
       null!==$form->getTerms() ? $model->setTerms($form->getTerms()) : ''; 
       null!==$form->getCreditinvoice_parent_id() ? $model->setCreditinvoice_parent_id($form->getCreditinvoice_parent_id() ?: 0) : '';
       if ($model->isNewRecord()) {
            $model->setStatus_id(1);            
            null!==$form->getNumber() ? $model->setNumber($form->getNumber()) : '';
            $model->setUser_id((int)$user->getId());
            $model->setUrl_key(Random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(0);
            $model->setDate_due($s);
            $model->setDiscount_amount(0.00);
       }
       $this->repository->save($model);// Regenerate invoice numbers if the setting is changed
       if (!$model->isNewRecord() && $s->get_setting('generate_invoice_number_for_draft') === '1') {
            $model->setNumber((string)$gR->generate_number((int)$form->getGroup_id(), true));  
       }
       $this->repository->save($model);
       return $model;
    }
    
    /**
     * @param Inv $model
     * @param ICR $icR
     * @param ICS $icS
     * @param IIR $iiR
     * @param IIS $iiS
     * @param ITRR $itrR
     * @param ITRS $itrS
     * @param IAR $iaR
     * @param IAS $iaS
     * @return void
     */
    public function deleteInv(Inv $model, ICR $icR, ICS $icS, IIR $iiR, IIS $iiS, ITRR $itrR, ITRS $itrS, IAR $iaR, IAS $iaS): void
    {
        $inv_id = $model->getId();
        // Invs with no items: If there are no invoice items there will be no invoice amount record
        // so check if there is a invoice amount otherwise null error will occur.
        if (null!==$inv_id){
            $count = $iaR->repoInvAmountCount((int)$inv_id);        
            if ($count > 0) {
                $inv_amount = $iaR->repoInvquery((int)$inv_id);
                null!==$inv_amount ? $iaS->deleteInvAmount($inv_amount) : '';            
            }
            /** @var InvItem $item */
            foreach ($iiR->repoInvItemIdquery($inv_id) as $item) {
                $iiS->deleteInvItem($item);
            }        
            /** @var InvTaxRate */
            foreach ($itrR->repoInvquery($inv_id) as $inv_tax_rate) {
                $itrS->deleteInvTaxRate($inv_tax_rate);
            }
            /** @var InvCustom */
            foreach ($icR->repoFields($inv_id) as $inv_custom) {
                $icS->deleteInvCustom($inv_custom);
            }
        }
        $this->repository->delete($model);
    }
    
    /**
     * @param User $user
     * @param Inv $model
     * @param array $details
     * @param SR $s
     * @return void
     */
    public function saveInv_from_recurring(User $user, Inv $model, array $details, SR $s): void
    {  
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style(null!==$details['date_created'] ? $details['date_created'] : new \DateTime());
       $datetimeimmutable = new \DateTimeImmutable($datetime instanceof \DateTime ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate_created($datetimeimmutable);
       $model->setDate_due($s);
       //$model->setDate_created($form->getDate_created());
       $model->setClient_id((int)$details['client_id']);
       $model->setGroup_id((int)$details['group_id']);
       $model->setStatus_id((int)$details['status_id']);
       $model->setDiscount_percent((float)$details['discount_percent']);
       $model->setDiscount_amount((float)$details['discount_amount']);
       $model->setUrl_key((string)$details['url_key']);
       $model->setPassword((string)$details['password']);
       $model->setPayment_method((int)$details['payment_method']);
       $model->setTerms((string)$details['terms']); 
       $model->setCreditinvoice_parent_id((int)$details['creditinvoice_parent_id'] ?: 0);
       if ($model->isNewRecord()) {
            $model->setStatus_id(1);            
            $model->setNumber((string)$details['number']);
            $random = new Random();            
            $model->setUser($user);
            $model->setUrl_key($random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(0);
            $model->setDate_due($s);
            $model->setDiscount_amount(0.00);
       }
       $this->repository->save($model);
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(string $level, string $message): Flash{
        $flash = new Flash($this->session);
        $flash->set($level, $message); 
        return $flash;
    }
}

