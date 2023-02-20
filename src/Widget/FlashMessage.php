<?php
declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\Bootstrap5\Alert;
final class FlashMessage extends Widget
{
    public function __construct(private FlashInterface $flash)
    {
    }
    
    public function render(): string
    {
        $flashes = $this->flash->getAll();

        $html = [];
        /** @var array $data */
        foreach ($flashes as $type => $data) {
            /** @var array $message */
            foreach ($data as $message) {
                $html[] = Alert::widget()
                    ->options(['class' => "alert-{$type} shadow"])
                    ->body((string)$message['body']);
            }
        }
        return implode($html);
    }
}
