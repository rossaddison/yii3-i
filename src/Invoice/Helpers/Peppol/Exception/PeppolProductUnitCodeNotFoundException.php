<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use App\Invoice\Entity\Product;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class PeppolProductUnitCodeNotFoundException extends \RuntimeException implements FriendlyExceptionInterface {

  private TranslatorInterface $translator;
  private Product $product;

  public function __construct(TranslatorInterface $translator, Product $product) {
    $this->translator = $translator;
    $this->product = $product;
  }

  public function getName(): string {
    return (!empty($this->product->getProduct_id()) && 
           !empty($this->product->getProduct_name())) ? 
      'Product id: '. 
      (!empty($this->product->getProduct_id()) ?  $this->product->getProduct_id() : ''). 
      str_repeat(' ', 2). 
      (!empty($this->product->getProduct_name()) ? $this->product->getProduct_name() : ''). 
      str_repeat(' ', 2). 
      $this->translator->translate('invoice.product.unit.code.not.found')
                                 : 
      $this->translator->translate('invoice.product.unit.code.not.found');
  }

  /**
   * @return string
   * @psalm-return '    Please try again'
   */
  public function getSolution(): ?string {
    return <<<'SOLUTION'
                Please try again
            SOLUTION;
  }

}
