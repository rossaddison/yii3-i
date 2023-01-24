<?php

declare(strict_types=1);

namespace App\Auth;

use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class IdentityRepository extends Select\Repository implements IdentityRepositoryInterface
{
    /**
     * 
     * @param EntityWriter $entityWriter
     * @param Select<TEntity> $select
     */
    public function __construct(private EntityWriter $entityWriter, Select $select)
    {
        parent::__construct($select);
    }
    
    /**
     * @psalm-suppress MoreSpecificReturnType
     */
    public function findIdentity(string $id): ?Identity
    {
        /**
         * @psalm-suppress LessSpecificReturnStatement
         */
        return $this->findOne(['user_id' => $id]);
    }

    /**
     * @throws Throwable
     */
    public function save(Identity $identity): void
    {
        $this->entityWriter->write([$identity]);
    }
}
