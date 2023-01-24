<?php

declare(strict_types=1);

use Yiisoft\Form\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\View\WebView;

/**
 * @var Yiisoft\Yii\View\Csrf $csrf
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var WebView $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 */

$this->setTitle($s->trans('add_files'));
?>

<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <?= $s->trans('attachments'); ?>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
            <div class="row">
                <div>
                    <div>
                        <h5><?= Html::encode($this->getTitle()) ?></h5>
                    </div>
                    <div>
                        <?= Form::tag()
                            ->post($urlGenerator->generate(...$action))
                            ->enctypeMultipartFormData()
                            ->csrf($csrf)
                            ->id('InvAttachmentsForm')
                            ->open()
                        ?>
                        <?= Field::file($form, 'attachFile')
                            ->containerClass('mb-3')
                            ->hideLabel()
                        ?>
                    </div>
                    <div>
                        <?= Field::buttonGroup()
                            ->addContainerClass('btn-group')
                            ->buttonsData([
                                [
                                    $translator->translate('Reset'),
                                    'type' => 'reset',
                                    'class' => 'btn btn-sm btn-danger',
                                ],
                                [
                                    $translator->translate('Submit'),
                                    'type' => 'submit',
                                    'class' => 'btn btn-sm btn-primary',
                                    'name' => 'contact-button',
                                ],
                            ]) ?>
                        <?= Form::tag()->close() ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <?= $partial_inv_attachments_list; ?>
            </div>
        </div>

    </div>
</div>
