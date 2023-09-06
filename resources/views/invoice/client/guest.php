<?php

declare(strict_types=1);

/**
 * @var \App\Invoice\Entity\Client $client
 * @var \App\Invoice\Setting\SettingRepository $s
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var \Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var \Yiisoft\Session\Flash\FlashInterface $flash 
 * @var \Yiisoft\View\WebView $this
 */

use App\Widget\OffsetPagination;
use Yiisoft\Html\Html;

$this->setTitle($s->trans('clients'));

$pagination = OffsetPagination::widget()
  ->paginator($paginator)
  ->urlGenerator(fn ($page) => $urlGenerator->generate('client/index', ['page' => $page, 'active'=>$active]));
?>

<div>
    <?php 
        echo $modal_create_client;
    ?>
</div>

<div>
    <h5><?= Html::encode($this->getTitle()); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>2]); ?>"
                   class="btn <?= $active == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>1]); ?>" style="text-decoration:none"
                   class="btn  <?= $active == 1 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('active'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>0]); ?>" style="text-decoration:none"
                   class="btn  <?= $active == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('inactive'); ?>
                </a>    
            </div>
    </div>
</div>
<div>
    <?= $alert ?>
</div>
<div id="content" class="table-content">
    <?php 
                if ($pagination->isRequired()) {
                   echo $pagination;
                }
    ?>  
        <div class="table-responsive">
        <table class="table table-hover table-striped">
        <thead>
        <tr>
        <th><?= $s->trans('active'); ?></th>
        <th>Peppol</th>
        <th><?= $s->trans('client_name'); ?></th>
         <th><?= $s->trans('birthdate'); ?></th>
        <th><?= $s->trans('email_address'); ?></th>
        <th><?= $s->trans('phone_number'); ?></th>
        <th class="amount"><?= $s->trans('balance'); ?></th>
        <th><?= $s->trans('options'); ?></th>
        </tr>
        </thead>
        <tbody>
            <?php foreach ($paginator->read() as $client) { ?>
            <tr>
		<td>
		    <?= ($client->getClient_active()) ? '<span class="label active">' . $s->trans('yes') . '</span>' : '<span class="label inactive">' . $s->trans('no') . '</span>'; ?>
		</td>
                <td>
		    <?= ($cpR->repoClientCount((string)$client->getClient_id()) !== 0 ) ? '<span class="label active">' . $s->trans('yes') . '</span>' : '<span class="label inactive">' . $s->trans('no') . '</span>'; ?>
		</td>
                <td><?= Html::a($client->getClient_name()." ".$client->getClient_surname(),$urlGenerator->generate('client/view',['id' => $client->getClient_id()]),['class' => 'btn btn-warning ms-2']);?></td>
                <td><?= Html::encode(($client->getClient_birthdate())->format($datehelper->style())); ?></td>
                <td><?= Html::encode($client->getClient_email()); ?></td>
                <td><?= Html::encode($client->getClient_phone() ? $client->getClient_phone() : ($client->getClient_mobile() ? $client->getClient_mobile() : '')); ?></td>
                <td class="amount"><?= $s->format_currency($iR->with_total($client->getClient_id(), $iaR)); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#" style="text-decoration:none">
                            <i class="fa fa-cog"></i> <?= $s->trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($editInv) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/view',['id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-eye fa-margin"></i> <?= $s->trans('view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/edit', ['id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?= $s->trans('edit'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($cpR->repoClientCount((string)$client->getClient_id()) === 0 ) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('clientpeppol/add', ['client_id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-plus fa-margin"></i> <?= $translator->translate('invoice.client.peppol.add'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($cpR->repoClientCount((string)$client->getClient_id()) > 0 ) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('clientpeppol/edit', ['client_id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('invoice.client.peppol.edit'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($editInv) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/delete',['id' => $client->getClient_id()]); ?>" style="text-decoration:none" onclick="return confirm('<?= $s->trans('delete_client_warning').'?'; ?>');">
                                    <i class="fa fa-trash fa-margin"></i><?= $s->trans('delete'); ?>                                    
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php } ?>            
        </tbody>
    </table>
         
    <?php
        $pageSize = $paginator->getCurrentPageSize();
        if ($pageSize > 0) {
            echo Html::p(
                sprintf('Showing %s out of %s clients', $pageSize, $paginator->getTotalItems()),
                ['class' => 'text-muted']
            );
        } else {
            echo Html::p('No records');
        }
    ?>
        
</div>
</div>