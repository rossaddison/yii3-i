<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Modal;

/**
 * @var \App\Invoice\Entity\CompanyPrivate $companyprivate
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $canEdit
 * @var string $id
 */

?>
<h1><?= $company_private; ?></h1>
<?php echo $alert; ?>
<div>
<?php
    if ($canEdit) {
        echo Html::a('Add',
        $urlGenerator->generate('companyprivate/add'),
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
     );
    //list all the items
    foreach ($companyprivates as $companyprivate){
      echo Html::br();
      $label = $companyprivate->getId() . " ";
      echo Html::label($label);
      echo Html::a('Edit',
      $urlGenerator->generate('companyprivate/edit', ['id' => $companyprivate->getId()]),
            ['class' => 'btn btn-info btn-sm ms-2']
          );
      echo Html::a('View',
      $urlGenerator->generate('companyprivate/view', ['id' => $companyprivate->getId()]),
      ['class' => 'btn btn-warning btn-sm ms-2']
             );      
      //modal delete button
      echo Html::a('Delete',
      $urlGenerator->generate('companyprivate/delete', ['id' => $companyprivate->getId()]),
            ['class' => 'btn btn-danger btn-sm ms-2', 'onclick'=>"return confirm('".$s->trans('delete').'?'."')" ]
          );
      echo Html::tag('button',$companyprivate->getLogo_filename() ?: 'No logo',['class'=>'btn btn-primary btn-sm ms-2']);
      echo Html::br();
    }
    }
?>
</div>