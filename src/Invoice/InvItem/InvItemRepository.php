<?php

declare(strict_types=1); 

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class InvItemRepository extends Select\Repository
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
     * Get invitems  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load(['tax_rate','product','inv']);
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
    
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $invitem
     * @throws Throwable 
     * @return void
     */
    public function save(array|object|null $invitem): void
    {
        $this->entityWriter->write([$invitem]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $invitem
     * @throws Throwable 
     * @return void
     */
    public function delete(array|object|null $invitem): void
    {
        $this->entityWriter->delete([$invitem]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoInvItemquery(string $id):object|null    {
        $query = $this->select()->load(['tax_rate','product','inv'])->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    
    /**
     * Get all items id's that belong to a specific inv
     *
     * @psalm-return EntityReader
     */
    public function repoInvItemIdquery(string $inv_id):  EntityReader {
        $query = $this->select()
                      ->load(['tax_rate','product','inv'])
                      ->where(['inv_id' => $inv_id]);
        return $this->prepareDataReader($query); 
    }
    
    /**
     * Get all items belonging to inv
     *
     * @psalm-return EntityReader
     */
    public function repoInvquery(string $inv_id): EntityReader { 
        $query = $this->select()
                      ->load(['tax_rate','product','inv'])
                      ->where(['inv_id' => $inv_id]);                                
        return $this->prepareDataReader($query);        
    }
    
    public function repoCount(string $inv_id) : int {
        $count = $this->select()
                      ->where(['inv_id' => $inv_id])                                
                      ->count();
        return $count; 
    }
    
    public function repoInvItemCount(string $id) : int {
        $count = $this->select()
                      ->where(['id' => $id])                                
                      ->count();
        return $count; 
    }
        
    /**
     * Get selection of inv items from all inv_items
     *
     * @psalm-return EntityReader
     */
     
    public function findinInvItems(array $item_ids) : EntityReader {
        $query = $this->select()->where(['id'=>['in'=> new Parameter($item_ids)]]);
        return $this->prepareDataReader($query);    
    } 
}
