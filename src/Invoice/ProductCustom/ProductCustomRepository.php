<?php

declare(strict_types=1); 

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;
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
final class ProductCustomRepository extends Select\Repository
{
private EntityWriter $entityWriter;
    
    /**
     * @param Select<TEntity> $select
     * 
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get productcustoms  without filter
     *
     * @psalm-return DataReaderInterface<int,ProductCustom>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select()->load('custom_field')->load('product');
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, ProductCustom>
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
    public function save(ProductCustom $productcustom): void
    {
        $this->entityWriter->write([$productcustom]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(ProductCustom $productcustom): void
    {
        $this->entityWriter->delete([$productcustom]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoProductCustomquery(string $id): object|null    {
        $query = $this->select()->load('custom_field')
                                ->load('product')
                                ->where(['id'=>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    
    public function repoFormValuequery(string $product_id, string $custom_field_id): object|null {
        $query = $this->select()->where(['inv_id' =>$product_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function repoProductCustomCount(string $product_id, string $custom_field_id) : int {
        $query = $this->select()->where(['inv_id' =>$product_id])
                                ->andWhere(['custom_field_id' =>$custom_field_id]);
        return $query->count();
    } 
    
    public function repoProductCount(string $product_id) : int {
        $query = $this->select()->where(['product_id' =>$product_id]);
        return $query->count();
    }   
    
    /**
     * Get all fields that have been setup for a particular inv
     *
     * @psalm-return DataReaderInterface<int,ProductCustom>
     */
    public function repoFields(string $product_id): DataReaderInterface
    {
        $query = $this->select()->where(['product_id'=>$product_id]);                
        return $this->prepareDataReader($query);
    }
}