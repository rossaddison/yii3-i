<?php

declare(strict_types=1); 

namespace App\Invoice\Inv;

use App\Invoice\Entity\Inv;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;

use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;

use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;
use Yiisoft\Security\Random;

final class InvRepository extends Select\Repository
{
private EntityWriter $entityWriter;


    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }
    
    /**
     * Get Invoices with filter
     *
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function findAllWithStatus(int $status_id) : DataReaderInterface
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
     * Get Invoices with filter
     *
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function findAllWithClient($client_id) : DataReaderInterface
    {
        $query = $this->select()
                ->where(['client_id' => $client_id]);  
        return $this->prepareDataReader($query);
    }
    
    /**
     * Get invoices without filter
     *
     * @psalm-return DataReaderInterface<int,Inv>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select()
                ->load(['client','group','user']);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function getReader(): DataReaderInterface
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
    
    /**
     * @throws Throwable
     */
    public function save(Inv $inv): void
    {
        $this->entityWriter->write([$inv]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Inv $inv): void
    {
        $this->entityWriter->delete([$inv]);
    }
    
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
     * 
     * @param string $url_key
     * @return Inv|null
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
     * @psalm-return Select<object>
     */
    public function repoClient_guest_count($inv_id, array $user_client = []) : Select {
        $count = $this->select()
                      ->where(['id' => $inv_id])
                      // sent = 2, viewed = 3, paid = 4
                      ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4])]])
                      ->andWhere(['client_id'=>['in'=> new Parameter($user_client)]]);
        return  $count;        
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Inv>
     * @param int $status_id
     * @param array $user_client
     * @return DataReaderInterface
     */
    public function repoGuest_Clients_Sent_Viewed_Paid(int $status_id, array $user_client = []) : DataReaderInterface {
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
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function open() : DataReaderInterface {
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
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function guest_visible() : DataReaderInterface {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2,3,4])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function is_draft() : DataReaderInterface {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([1])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function is_sent() : DataReaderInterface {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function is_viewed() : DataReaderInterface {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([3])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function is_paid() : DataReaderInterface {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([4])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function is_overdue() : DataReaderInterface {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([5])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function by_client($client_id) : DataReaderInterface {
        $query = $this->select()
                      ->where(['client_id'=> $client_id]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @param $client_id
     * @param $status_id
     * @psalm-return DataReaderInterface<int, Inv>
     */
    public function by_client_inv_status(int $client_id, int $status_id): DataReaderInterface
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
     * @return (int|mixed|string)[][]
     *
     * @psalm-return array{1: array{label: mixed, class: 'draft', href: 1}, 2: array{label: mixed, class: 'sent', href: 2}, 3: array{label: mixed, class: 'viewed', href: 3}, 4: array{label: mixed, class: 'paid', href: 4}}
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
    public function with_total($client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTotal() : 0.00);
        }
        return $sum;
    }
    
    // sales without item tax and tax => item_subtotal
    public function with_item_subtotal($client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getItem_subtotal() : 0.00);
        }
        return $sum;
    }
    
    // total = item_subtotal + item_tax_total + tax_total
    // total => sales including item tax and tax
    public function with_total_from_to($client_id, $from, $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTotal() : 0.00);
        }
        return $sum;
    }
    
    // sales without item tax and tax => item_subtotal
    public function with_item_subtotal_from_to($client_id, $from, $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getItem_subtotal() : 0.00);
        }
        return $sum;
    }
    
    // First tax: Item tax total 
    public function with_item_tax_total_from_to($client_id, $from, $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getItem_tax_total() : 0.00);
        }
        return $sum;
    }
    
    // Second tax: Total tax total 
    public function with_tax_total_from_to($client_id, $from, $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTax_total() : 0.00);
        }
        return $sum;
    }
    
    public function with_paid_from_to($client_id, $from, $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getPaid() : 0.00);
        }
        return $sum;
    }

    public function with_total_paid($client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
            $sum += (null!==$invoice_amount ? $invoice_amount->getPaid() : 0.00);
        }
        return $sum;
    }

    public function with_total_balance($client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
            $sum += (null!==$invoice_amount ? $invoice_amount->getBalance() : 0.00);
        }
        return $sum;
    }
    
    public function repoCountByClient($client_id) : int {
        $count = $this->select()
                      ->where(['client_id'=>$client_id])  
                      ->count();
        return $count;
    }
    
    public function repoCountClientLoadedFromToDate($client_id, $from_date, $to_date) : int {
        $count = $this->select()
                      ->load('client')
                      ->where(['client_id'=>$client_id])
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date)
                      ->count();
        return $count;
    }
    
    public function repoClientLoadedFromToDate($client_id, $from_date, $to_date) : DataReaderInterface {
        $query = $this->select()
                      ->load('client')  
                      ->where(['client_id'=>$client_id])
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Inv>
     */
    
    public function repoClient($client_id) : DataReaderInterface { 
        $query = $this->select()
                      ->where(['client_id' => $client_id]); 
        return $this->prepareDataReader($query);
    }      
}