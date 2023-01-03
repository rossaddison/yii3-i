<?php

declare(strict_types=1);

namespace App\User;

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
final class UserRepository extends Select\Repository
{    
    /**
     * 
     * @param EntityWriter $entityWriter
     * @param Select<TEntity> $select
     */
    public function __construct(private EntityWriter $entityWriter, Select $select)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id', 'login'])->withOrder(['id' => 'asc']);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'login'])
                ->withOrder([
                             'id' => 'desc',
                             'login' => 'desc'
                ])
        );
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    public function findAll(array $scope = [], array $orderBy = []): EntityReader
    {
        return new EntityReader($this
            ->select()
            ->where($scope)
            ->orderBy($orderBy));
    }

    /**
     * @param string $id
     *
     * @return User|null
     */
    public function findById(string $id): ?User
    {
        return $this->findByPK($id);
    }

    public function findByLogin(string $login): ?User
    {
        return $this->findBy('login', $login);
    }

    public function findByLoginWithAuthIdentity(string $login): ?User
    {
        return $this
            ->select()
            ->where(['login' => $login])
            ->load('identity')
            ->fetchOne();
    }

    /**
     * @throws Throwable
     */
    public function save(User $user): void
    {
        $this->entityWriter->write([$user]);
    }

    private function findBy(string $field, string $value): ?User
    {
        return $this->findOne([$field => $value]);
    }
}
