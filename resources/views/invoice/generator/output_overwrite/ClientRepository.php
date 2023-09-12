<?php

declare(strict_types=1); 

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of Client
 * @extends Select\Repository<TEntity>
 */
final class ClientRepository extends Select\Repository
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
     * Get clients  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();        return $this->prepareDataReader($query);
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
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }    
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Client|null $client
     * @psalm-param TEntity $client
     * @throws Throwable 
     * @return void
     */
    public function save(array|Client|null $client): void
    {
        $this->entityWriter->write([$client]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Client|null $client
  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|Client|null $client): void
    {
        $this->entityWriter->delete([$client]);
    }
    
    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }    
    
    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return Client|null
     */
    public function repoClientLoadedquery(string $id): Client|null
    {
        $query = $this->select()->where(['id' =>$id]);        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id) : int {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }   
}