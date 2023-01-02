<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\Modal;

/**
 * @var \App\Invoice\Entity\Company $company
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $canEdit
 * @var string $id
 * @var \Yiisoft\Session\Flash\FlashInterface $flash 
 */

?>
<h1><?= $company_public; ?></h1>
<?php
        $danger = $flash->get('danger');
        if ($danger != null) {
            $alert =  Alert::widget()
            ->body($danger)
            ->options(['class' => ['alert-danger shadow'],])
            ->render();
            echo $alert;
        }
        $info = $flash->get('info');
        if ($info != null) {
            $alert =  Alert::widget()
            ->body($info)
            ->options(['class' => ['alert-info shadow'],])
            ->render();
            echo $alert;
        }
        $warning = $flash->get('warning');
        if ($warning != null) {
            $alert =  Alert::widget()
            ->body($warning)
            ->options(['class' => ['alert-warning shadow'],])
            ->render();
            echo $alert;
        }
        

?>
<div>
<?php
    if ($canEdit) {
        echo Html::a('Add',
        $urlGenerator->generate('company/add'),
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
     );
    //list all the items
    foreach ($companies as $company){
      echo Html::br();
      $label = $company->getId() . " ";
      echo Html::label($label);
      echo Html::a('Edit',
      $urlGenerator->generate('company/edit', ['id' => $company->getId()]),
            ['class' => 'btn btn-info btn-sm ms-2']
          );
      echo Html::a('View',
      $urlGenerator->generate('company/view', ['id' => $company->getId()]),
      ['class' => 'btn btn-warning btn-sm ms-2']
             );
      }
      echo Html::a('Delete',
      $urlGenerator->generate('company/delete', ['id' => $company->getId()]),
      ['class' => 'btn btn-danger btn-sm ms-2', 
       'onclick'=>"return confirm('". $s->trans('delete_record_warning'). "')"
      ]
      );
    }
?>
</li>