<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

?>
<h1><?= Html::encode($title) ?></h1>
<div class="row">
 <div class="mb3 form-group">
   <label for="successful" class="form-label" style="background:lightblue">Successful</label>
   <?= Html::encode($body['successful'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="date" class="form-label" style="background:lightblue"><?= $s->trans('date'); ?></label>
   <?php
        $date = $body['date'];
        if ($date && $date != "0000-00-00") {
                //use the DateHelper
            $datehelper = new DateHelper($s);
            $date = $datehelper->date_from_mysql($date);
        } else {
            $date = null;
        }
    ?>      
    <?= Html::encode($date); ?> 
 </div>
 <div class="mb3 form-group">
   <label for="driver" class="form-label" style="background:lightblue">Driver</label>
   <?= Html::encode($body['driver'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="response" class="form-label" style="background:lightblue">Response</label>
   <?= Html::encode($body['response'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="reference" class="form-label" style="background:lightblue">Reference</label>
   <?= Html::encode($body['reference'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="inv_id" class="form-label" style="background:lightblue">Inv</label>
   <?= $merchant->getInv()->getId();?>
 </div>
</div>
