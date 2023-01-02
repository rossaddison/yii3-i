<?php

declare(strict_types=1); 

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class ClientNoteRepository extends Select\Repository
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
     * Get clientnotes  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
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
     * @throws Throwable
     */
    public function save(ClientNote $clientnote): void
    {
        $this->entityWriter->write([$clientnote]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(ClientNote $clientnote): void
    {
        $this->entityWriter->delete([$clientnote]);
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
    public function repoClientNotequery(string $id): object|null    {
        $query = $this->select()->load('client')->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }    
    
    /**
     * @psalm-return EntityReader
     */
    public function repoClientquery(string $client_id): EntityReader    {
        $query = $this->select()->load('client')->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }
    
    public function repoClientNoteCount(int $client_id) : int {
        $count = $this->select()
                      ->where(['client_id'=>$client_id])
                      ->count();
        return $count;
    }
}