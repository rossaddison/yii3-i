<?php
declare(strict_types=1); 

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Sort;

use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class InvAmountRepository extends Select\Repository
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
     * @psalm-return DataReaderInterface<int, InvAmount>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, InvAmount>
     */
    public function getReader(): DataReaderInterface
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }
    
    /**
     * @throws Throwable
     */
    public function save(InvAmount $invamount): void
    {
        $this->entityWriter->write([$invamount]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(InvAmount $invamount): void
    {
        $this->entityWriter->delete([$invamount]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoInvAmountCount(int $inv_id) : int {
        $count = $this->select()
                      ->where(['inv_id' => $inv_id])
                      ->count();
        return $count;
    }
    
    public function repoCreditInvoicequery(string $inv_id): ?InvAmount {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv_id' => $inv_id])
                      ->andWhere(['sign' => -1]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function repoInvAmountquery(int $id): ?InvAmount {
        $query = $this->select()
                      ->load('inv')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }

    public function AgingCount($interval_end, $interval_start) : int {        
        $end = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_end.'D'))
                                              ->format('Y-m-d');
        $start = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_start. 'D'))
                                                ->format('Y-m-d');
        $count = $this->select()
                      ->load('inv')
                      ->where('inv.date_due','<=', $end)
                      ->andWhere('inv.date_due','>=', $start)
                      ->andWhere('balance','>',0)
                      ->count();
        return $count;
    }
    
    public function Aging($interval_end, $interval_start) : DataReaderInterface {
        $end = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_end.'D'))
                                              ->format('Y-m-d');
        
        $start = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P'.$interval_start. 'D'))
                                                ->format('Y-m-d');
        $query = $this->select()
                      ->load('inv')
                      ->where('inv.date_due','<=', $end)
                      ->andWhere('inv.date_due','>=', $start)
                      ->andWhere('balance','>',0);
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int $inv_id
     * @return InvAmount|null
     */
    public function repoInvquery(int $inv_id): InvAmount|null {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return  $query->fetchOne() ?: null;    
    } 
    
    public function repoStatusTotals($key, $range, $sR) : DataReaderInterface {        
        $datehelper = new DateHelper($sR);
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv.status_id' => (int)$key])
                      ->andWhere('inv.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('inv.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']));
         return $this->prepareDataReader($query);
    }
    
    public function repoStatusTotals_Num_Total($key, $range, $sR) : int {        
        $datehelper = new DateHelper($sR);
        $query = $this->select()
                      ->load('inv')                
                      ->where(['inv.status_id' => (int)$key])
                      ->andWhere('inv.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('inv.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']))
                      ->count();
        return $query;
    }
    
    /**
     * @param string $period
     */
    public function get_status_totals(IR $iR, SR $sR, $period) : array
    {
        $return = [];
        $range = $sR->range($period);        
        
        // 1 => class: 'draft', href: 1},
        // 2 => class: 'sent', href: 2}, 
        // 3 => class: 'viewed', href: 3}, 
        // 4 => class: 'paid', href: 4}}
        foreach ($iR->getStatuses($sR) as $key => $status) {
            $status_specific_invoices = $this->repoStatusTotals($key, $range, $sR);
            $total = 0.00;
            foreach ($status_specific_invoices as $invoice) {
                $this_total = $invoice->getTotal();
                $total += $this_total;
            }
            $return[$key] = [
                'inv_status_id' => $key,
                'class' => $status['class'],
                'label' => $status['label'],
                'href' => (string) $status['href'],
                'sum_total' => $total,
                'num_total' => $this->repoStatusTotals_Num_Total($key, $range, $sR)
            ];
        }
        return $return;
    }
}