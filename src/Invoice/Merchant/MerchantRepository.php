<?php

declare(strict_types=1); 

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;

// Cycle
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
// Yiisoft
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

use Throwable;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class MerchantRepository extends Select\Repository
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
     * Get merchants  without filter
     *
     * @psalm-return DataReaderInterface<int,Merchant>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Merchant>
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
    public function save(Merchant $merchant): void
    {
        $this->entityWriter->write([$merchant]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Merchant $merchant): void
    {
        $this->entityWriter->delete([$merchant]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoMerchantquery(string $id): object|null    {
        $query = $this->select()->load('inv')
                                ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    // Find all merchant responses associated with a user's clients ie. their client list / client_id_array
    
    /**
     * Get payments  with filter
     * @param array $client_id_array
     * @psalm-return DataReaderInterface<int,Merchant >
     */
    public function findOneUserManyClientsMerchantResponses(array $client_id_array): DataReaderInterface
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv.client_id'=>['in'=> new Parameter($client_id_array)]]);        
        return   $this->prepareDataReader($query);
    }
}