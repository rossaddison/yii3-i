<?php
   declare(strict_types=1);
      
   use App\Widget\OffsetPagination;
   use Yiisoft\Html\Html;
   /**    
    * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
    */
   echo $alert;
?>
<div id="headerbar">
    <h1 class="headerbar-title"><?= $s->trans('email_templates'); ?></h1>
    <div class="headerbar-item pull-right">
        <a class="btn btn-sm btn-primary" href="<?php echo $urlGenerator->generate('emailtemplate/add'); ?>">
            <i class="fa fa-plus"></i> <?= $s->trans('new'); ?>
        </a>
    </div>
    <div class="headerbar-item pull-right">
        <?php
            $pagination = OffsetPagination::widget()
            ->paginator($paginator)
            ->urlGenerator(fn ($page) => $urlGenerator->generate('emailtemplate/index', ['page' => $page]));
        ?>
        <?php
            if ($pagination->isRequired()) {
                 echo $pagination;
            }
        ?>
    </div>
</div>
<div>
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th><?= $s->trans('title'); ?></th>
            <th><?= $s->trans('type'); ?></th>
            <th><?= $s->trans('options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($email_templates as $email_template) { ?>
            <tr>
                <td><?= Html::encode($email_template->getEmail_template_title()); ?></td>
                <td><?= ucfirst($email_template->getEmail_template_type()); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#"><i
                                    class="fa fa-cog"></i> <?= $s->trans('options'); ?></a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?= $urlGenerator->generate('emailtemplate/edit',['email_template_id'=>$email_template->getEmail_template_id()]); ?>" style="text-decoration: none ">
                                    <i class="fa fa-edit fa-margin"></i><?= $s->trans('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?= $urlGenerator->generate('emailtemplate/delete',['email_template_id'=>$email_template->getEmail_template_id()]); ?>"
                                      method="POST" style="text-decoration: none">
                                    <?php $csrf; ?>
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?= $s->trans('delete_record_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?= $s->trans('delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
