<?php

declare(strict_types=1); 

namespace App\Invoice\Inv;

use App\Invoice\Entity\Inv;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;

use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;

use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;
use Yiisoft\Security\Random;

/**
 * @template TEntity of Inv
 * @extends Select\Repository<TEntity>
 */
final class InvRepository extends Select\Repository
{
private EntityWriter $entityWriter;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }
    
    /**
     * 
     * @param int $status_id
     * @return EntityReader
     */
    public function findAllWithStatus(int $status_id) : EntityReader
    {
        if (($status_id) > 0) {
        $query = $this->select()
                ->load(['client','group','user'])
                ->where(['status_id' => $status_id]);  
         return $this->prepareDataReader($query);
       } else {
         return $this->findAllPreloaded();  
       }       
    }
    
    /**
     * 
     * @param int $client_id
     * @return EntityReader
     */
    public function findAllWithClient(int $client_id) : EntityReader
    {
        $query = $this->select()
                ->where(['client_id' => $client_id]);  
        return $this->prepareDataReader($query);
    }
    
    /**
     * Get invoices without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                ->load(['client','group','user']);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        // Provide the latest invoice at the top of the list and order additionally according to status
        return Sort::only(['id', 'status'])->withOrder(['id' => 'desc', 'status' => 'asc']);
    }
    
    public function save(array|Inv|null $inv): void
    {
        $this->entityWriter->write([$inv]);
    }
    
    public function delete(array|Inv|null $inv): void
    {
        $this->entityWriter->delete([$inv]);
    }
    
    /**
     * 
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc'])
        );
    }
    
    /**
     * 
     * @param string $id
     * @return int
     */
    public function repoCount(string $id) : int {
        $count = $this->select()
                ->where(['id' => $id]) 
                ->count();
        return $count;
    }
    
    /**
     * 
     * @return int
     */
    public function repoCountAll() : int {
        $count = $this->select() 
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $invoice_id
     * @param int $status_id
     * @return Inv|null
     */
    public function repoInvStatusquery(int $invoice_id, int $status_id) : Inv|null {
        $query = $this->select()
                      ->where(['id' => $invoice_id])
                      ->where(['status_id'=>$status_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $id
     * @return Inv|null
     */
    public function repoInvUnLoadedquery(string $id): Inv|null {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * 
     * @param string $id
     * @return Inv|null
     */
    public function repoInvLoadedquery(string $id): Inv|null {
        $query = $this->select()
                      ->load(['client','group','user']) 
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * @return Inv|null
     *
     * @psalm-return TEntity|null
     */
    public function repoUrl_key_guest_loaded(string $url_key) : Inv|null {
        $query = $this->select()
                       ->load('client') 
                       ->where(['url_key' => $url_key])
                       ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4])]]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $url_key
     * @return int
     */
    public function repoUrl_key_guest_count(string $url_key) : int {
        $count = $this->select()
                      ->where(['url_key' => $url_key])
                      ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4])]])
                      ->count();
        return  $count;        
    }
    
    /**
     * @psalm-return Select<TEntity>
     */
    public function repoClient_guest_count(int $inv_id, array $user_client = []) : Select {
        $count = $this->select()
                      ->where(['id' => $inv_id])
                      // sent = 2, viewed = 3, paid = 4
                      ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4])]])
                      ->andWhere(['client_id'=>['in'=> new Parameter($user_client)]]);
        return  $count;        
    }
    
    /**
     * @psalm-return EntityReader
     * @param int $status_id
     * @param array $user_client
     */
    public function repoGuest_Clients_Sent_Viewed_Paid(int $status_id, array $user_client = []) : EntityReader {
        // Get specific statuses
        if ($status_id > 0) {
            $query = $this->select()
                    // sent = 2, viewed = 3, paid = 4
                    ->where(['status_id'=>$status_id])
                    ->where(['client_id'=>['in'=> new Parameter($user_client)]])                      
                    ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4])]]);
            return $this->prepareDataReader($query);
       } else
       // Get all the invoices that are either sent, viewed, or paid
       {
            $query = $this->select()
                    // sent = 2, viewed = 3, paid = 4
                    ->where(['client_id'=>['in'=> new Parameter($user_client)]])                      
                    ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4])]]);
            return $this->prepareDataReader($query);
       }
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function open() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2,3])]]);
        return $this->prepareDataReader($query);    
    }
    
     public function open_count() : int {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        // 2,3 => There is still a balance available => Not paid
        $count = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2,3])]])
                      ->count();
        return $count;    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function guest_visible() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2,3,4])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_draft() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([1])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_sent() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_viewed() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([3])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_paid() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([4])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_overdue() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([5])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @param int $client_id
     * @return EntityReader
     */
    public function by_client(int $client_id) : EntityReader {
        $query = $this->select()
                      ->where(['client_id'=> $client_id]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @param $client_id
     * @param $status_id
     *
     * @psalm-return EntityReader
     */
    public function by_client_inv_status(int $client_id, int $status_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id]);
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int $client_id
     * @param int $status_id
     * @return int
     */
    public function by_client_inv_status_count(int $client_id, int $status_id): int
    {
        $count = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->count();        
        return $count; 
    }
    
    /**
     * @return (int|string)[][]
     *
     * @psalm-return array{1: array{label: string, class: 'draft', href: 1}, 2: array{label: string, class: 'sent', href: 2}, 3: array{label: string, class: 'viewed', href: 3}, 4: array{label: string, class: 'paid', href: 4}}
     */
    public function getStatuses(SR $s): array
    {
        return array(
            '1' => array(
                'label' => $s->trans('draft'),
                'class' => 'draft',
                'href' => 1
            ),
            '2' => array(
                'label' => $s->trans('sent'),
                'class' => 'sent',
                'href' => 2
            ),
            '3' => array(
                'label' => $s->trans('viewed'),
                'class' => 'viewed',
                'href' => 3
            ),
            '4' => array(
                'label' => $s->trans('paid'),
                'class' => 'paid',
                'href' => 4
            )
        );       
    }
    
    /**
     * @param string $invoice_date_created
     * @return string
     */
    public function get_date_due($invoice_date_created, SR $sR)
    {
        $invoice_date_due = new \DateTime($invoice_date_created);
        $invoice_date_due->add(new \DateInterval('P' . $sR->get_setting('invoices_due_after') . 'D'));
        return $invoice_date_due->format('Y-m-d');
    }
    
    /**
     * @return string
     */
    public function get_url_key()
    {
        $random = new Random();
        return $random::string(32);
    }
    
    /**
     * @param string $group_id
     * @return mixed
     */
    public function get_inv_number(string $group_id, GR $gR) : mixed
    {   
        return $gR->generate_number((int) $group_id);
    }
    
    // total = item_subtotal + item_tax_total + tax_total
    // total => sales including item tax and tax
    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_total(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTotal() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    // sales without item tax and tax => item_subtotal
    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_item_subtotal(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getItem_subtotal() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    // total = item_subtotal + item_tax_total + tax_total
    // total => sales including item tax and tax
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_total_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {  
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTotal() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    // sales without item tax and tax => item_subtotal
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_item_subtotal_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getItem_subtotal() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    // First tax: Item tax total 
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_item_tax_total_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getItem_tax_total() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    // Second tax: Total tax total 
    public function with_tax_total_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTax_total() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_paid_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getPaid() ?? 0.00 : 0.00);            
        }
        return $sum;
    }
    
    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_total_paid(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
            $sum += (null!==$invoice_amount ? $invoice_amount->getPaid() ?? 0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_total_balance(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) { 
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
            $sum += (null!==$invoice_amount ? $invoice_amount->getBalance() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    /**
     * 
     * @param int|null $client_id
     * @return int
     */
    public function repoCountByClient(int|null $client_id) : int {
        $count = $this->select()
                      ->where(['client_id'=>$client_id])  
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return int
     */
    public function repoCountClientLoadedFromToDate(int $client_id, string $from_date, string $to_date) : int {
        $count = $this->select()
                      ->load('client')
                      ->where(['client_id'=>$client_id])
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date)
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoClientLoadedFromToDate(int $client_id, string $from_date, string $to_date) : EntityReader {
        $query = $this->select()
                      ->load('client')  
                      ->where(['client_id'=>$client_id])
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date);
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int|null $client_id
     * @return EntityReader
     */
    public function repoClient(int|null $client_id) : EntityReader { 
        $query = $this->select()
                      ->where(['client_id' => $client_id]); 
        return $this->prepareDataReader($query);
    }      
}