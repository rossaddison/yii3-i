<?php
declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\DataColumn;

/**
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator 
 * @var WebView $this
 */
?>

<div>
    <?= GridView::widget()
            ->columns(
                DataColumn::create()
                    ->attribute('file_name_original')
                    ->label($s->trans('id'))
                    ->value(static fn ($model): string => ($model->getFile_name_original())), 
                DataColumn::create()
                    ->attribute('uploaded_date')
                    ->label($s->trans('id'))
                    ->value(static fn ($model): string => ($model->getUploaded_date())->format($datehelper->style())),
                DataColumn::create()
                    ->label($s->trans('download'))
                    ->value(static function ($model) use ($urlGenerator): string {
                        return Html::a(Html::tag('button',
                            Html::tag('i','',['class'=>'fa fa-download fa-margin']),
                            [
                                'type'=>'submit', 
                                'class'=>'dropdown-button'
                            ]
                            ),
                            $urlGenerator->generate('inv/download_file',['upload_id'=>$model->getId()]),[]                                         
                    )->render();
                }),
                DataColumn::create()
                    ->label($s->trans('delete'))
                    ->value(static function ($model) use ($s, $urlGenerator): string {
                        return Html::a( Html::tag('button',
                            Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                            [
                                'type'=>'submit', 
                                'class'=>'dropdown-button',
                                'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
                            ]
                            ),
                            $urlGenerator->generate('upload/delete',['id'=>$model->getId()]),[]                                         
                        )->render();
                    }),    
            )
            ->paginator($paginator)
            ->rowAttributes(['class' => 'align-middle'])
            ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
            ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-inv'])
    ?>       
</div>
