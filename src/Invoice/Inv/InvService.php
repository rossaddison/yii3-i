<?php
declare(strict_types=1); 

namespace App\Invoice\Inv;
// Entities
use App\Invoice\Entity\Inv;
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
     * @param User $user
     * @param Inv $model
     * @param InvForm $form
     * @param SR $s
     * @return void
     */ 
    public function addInv(User $user, Inv $model, InvForm $form, SR $s): void
    {        
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style($form->getDate_created());
       $datetimeimmutable = new \DateTimeImmutable(!empty($datetime) ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate_created($datetimeimmutable);
       $model->setDate_due($s);
       $model->setClient_id((int)$form->getClient_id());       
       $model->setGroup_id((int)$form->getGroup_id());
       $model->setStatus_id($form->getStatus_id()); 
       $model->setDiscount_amount($form->getDiscount_amount());
       $model->setDiscount_percent($form->getDiscount_percent());
       $model->setUrl_key($form->getUrl_key());
       $model->setPassword($form->getPassword());
       $model->setPayment_method($form->getPayment_method());
       $model->setTerms($form->getTerms()); 
       $model->setCreditinvoice_parent_id($form->getCreditinvoice_parent_id() ?? 0);
       if ($model->isNewRecord()) {
            // Draft invoices cannot be viewed by Clients. 
            // Mark the invoices as 'sent' so that clients can view their invoices when logged in
            // Draft => 1, Sent => 2
            $model->setStatus_id($s->get_setting('mark_invoices_sent_copy') === '1' ? 2 : 1 );            
            $model->setNumber($form->getNumber());
            $model->setUser($user);
            $model->setUrl_key(Random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(1);
            $model->setDate_due($s);            
       }
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param User $user
     * @param Inv $model
     * @param InvForm $form
     * @param SR $s
     * @param GR $gR
     * @return void
     */
    public function saveInv(User $user, Inv $model, InvForm $form, SR $s, GR $gR): void
    {  
       //$before_save = $model; 
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style($form->getDate_created());
       $datetimeimmutable = new \DateTimeImmutable($datetime->format('Y-m-d H:i:s'));
       $model->setDate_created($datetimeimmutable);
       $model->setDate_due($s);
       null!==$form->getClient_id() ? $model->setClient($model->getClient()->getClient_id() == $form->getClient_id() ? $model->getClient() : null): '';
       $model->setClient_id((int)$form->getClient_id());
       null!==$form->getGroup_id() ? $model->setGroup($model->getGroup()->getId() == $form->getGroup_id() ? $model->getGroup() : null): '';
       $model->setGroup_id((int)$form->getGroup_id());       
       $model->setStatus_id($form->getStatus_id());
       $model->setDiscount_percent($form->getDiscount_percent());
       $model->setDiscount_amount($form->getDiscount_amount());
       $model->setUrl_key($form->getUrl_key());
       $model->setPassword($form->getPassword());
       $model->setPayment_method($form->getPayment_method());
       $model->setTerms($form->getTerms()); 
       $model->setCreditinvoice_parent_id($form->getCreditinvoice_parent_id() ?? 0);
       if ($model->isNewRecord()) {
            $model->setStatus_id(1);            
            $model->setNumber($form->getNumber());
            $model->setUser($user);
            $model->setUrl_key(Random::string(32));            
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setTime_created((new \DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPayment_method(0);
            $model->setDate_due($s);
            $model->setDiscount_amount(0.00);
       }
       $this->repository->save($model);// Regenerate invoice numbers if the setting is changed
       if (!$model->isNewRecord() && $s->get_setting('generate_invoice_number_for_draft') === '1') {
            $model->setNumber($gR->generate_number((int)$form->getGroup_id(), true));  
       }
       $this->repository->save($model);
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
        $count = $iaR->repoInvAmountCount((int)$inv_id);        
        if ($count > 0) {
            $inv_amount = $iaR->repoInvquery((int)$inv_id);
            $iaS->deleteInvAmount($inv_amount);            
        }
        foreach ($iiR->repoInvItemIdquery((string)$inv_id) as $item) {
                 $iiS->deleteInvItem($item);
        }        
        foreach ($itrR->repoInvquery((string)$inv_id) as $inv_tax_rate) {
                 $itrS->deleteInvTaxRate($inv_tax_rate);
        }
        foreach ($icR->repoFields((string)$inv_id) as $inv_custom) {
                 $icS->deleteInvCustom($inv_custom);
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
       $datetime = $datehelper->get_or_set_with_style($details['date_created']);
       $datetimeimmutable = new \DateTimeImmutable(!empty($datetime) ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate_created($datetimeimmutable);
       $model->setDate_due($s);
       //$model->setDate_created($form->getDate_created());
       $model->setClient_id($details['client_id']);
       $model->setGroup_id($details['group_id']);
       $model->setStatus_id($details['status_id']);
       $model->setDiscount_percent($details['discount_percent']);
       $model->setDiscount_amount($details['discount_amount']);
       $model->setUrl_key($details['url_key']);
       $model->setPassword($details['password']);
       $model->setPayment_method($details['payment_method']);
       $model->setTerms($details['terms']); 
       $model->setCreditinvoice_parent_id($details['creditinvoice_parent_id'] ?? 0);
       if ($model->isNewRecord()) {
            $model->setStatus_id(1);            
            $model->setNumber($details['number']);
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

