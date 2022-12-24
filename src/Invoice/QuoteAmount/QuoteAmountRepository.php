<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteAmount;

use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Quote\QuoteRepository as QR;
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
final class QuoteAmountRepository extends Select\Repository
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
     * Get quoteamounts  without filter
     *
     * @psalm-return DataReaderInterface<int,QuoteAmount>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, QuoteAmount>
     */
    public function getReader(): DataReaderInterface
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * @throws Throwable
     */
    public function save(QuoteAmount $quoteamount): void
    {
        $this->entityWriter->write([$quoteamount]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(QuoteAmount $quoteamount): void
    {
        $this->entityWriter->delete([$quoteamount]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @param string $quote_id
     */
    public function repoQuoteAmountCount(string $quote_id) : int {
        $count = $this->select()
                      ->where(['quote_id' => $quote_id])
                      ->count();
        return $count;
    }
    
    public function repoQuoteAmountqueryTest(string $quote_id): object|null {
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote_id' => $quote_id]);
        return  $query->fetchOne() ?: null;        
    }   
    
    /**
     * 
     * @param string $quote_id
     * @return object|null
     */
    public function repoQuoteAmountquery(string $quote_id): object|null {
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote_id' => $quote_id]);
        return  $query->fetchOne() ?: null;        
    }    
    
    /**
     * 
     * @param string $quote_id
     * @return object|null
     */
    public function repoQuotequery(string $quote_id): object|null {
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote_id' => $quote_id]);
        return  $query->fetchOne() ?: null;        
    }
   
    public function repoStatusTotals($key, $range, $sR) : DataReaderInterface {        
        $datehelper = new DateHelper($sR);
        $query = $this->select()
                      ->load('quote')
                      ->where(['quote.status_id' => (int)$key])
                      ->andWhere('quote.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('quote.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']));                      
        return $this->prepareDataReader($query);
    }
    
    public function repoStatusTotals_Num_Total($key, $range, $sR) : int {        
        $datehelper = new DateHelper($sR);
        $query = $this->select()
                      ->load('quote')                      
                      ->where(['quote.status_id' => (int)$key])
                      ->andWhere('quote.date_created', '>=' ,$datehelper->date_from_mysql_without_style($range['lower']))
                      ->andWhere('quote.date_created', '<=' ,$datehelper->date_from_mysql_without_style($range['upper']))
                      ->count();
        return $query;
    }
    
    /**
     * @param string $period
     */
    public function get_status_totals(QR $qR, SR $sR, $period) : array
    {
        $return = [];
        
        // $period eg. this-month, last-month derived from $sR->get_setting('invoice or quote_overview_period') 
        $range = $sR->range($period) ?: []; 
        
        // 1 => class: 'draft', href: 1},
        // 2 => class: 'sent', href: 2}, 
        // 3 => class: 'viewed', href: 3}, 
        // 4 => class: 'approved', href: 4}}
        // 5 => class: 'rejected', href: 5}}
        // 6 => class: 'cancelled', href: 6}}
        foreach ($qR->getStatuses($sR) as $key => $status) {
            $status_specific_quotes = $this->repoStatusTotals((int)$key, $range, $sR);
            $total = 0.00;
            foreach ($status_specific_quotes as $quote) {
                $this_total = $quote->getTotal();
                $total += $this_total;
            }
            $return[$key] = [
                'quote_status_id' => $key,
                'class' => $status['class'],
                'label' => $status['label'],
                'href' => (string) $status['href'],
                'sum_total' => $total,
                'num_total' => $this->repoStatusTotals_Num_Total($key, $range, $sR) ?: 0
            ];
        }
        return $return;
    }
}