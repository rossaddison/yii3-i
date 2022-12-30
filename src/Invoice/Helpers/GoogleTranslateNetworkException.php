<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateNetworkException extends \RuntimeException implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'There appears to be a Network error.';
    }
    
    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
                Please try again later.
            SOLUTION;
    }
}