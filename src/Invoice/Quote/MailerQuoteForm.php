<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;

final class MailerQuoteForm extends FormModel
{
    
    private string $to_email = '';
    private string $email_template = '';
    private string $from_name = '';
    private string $from_email = '';
    private string $cc = '';
    private string $bcc = '';
    private string $subject = '';
    private string $pdf_template = '';
    private string $body = '';
    private ?array $attachFiles = null;
    private string $guest_url = '';

    public function getFormName(): string
    {
        return 'MailerQuoteForm';
    }

    public function getRules(): array
    {
        return [
            'to_email' => [new Required(), new Email()],
            'from_name' => [new Required()],            
            'from_email' => [new Required(), new Email()],
            'subject' => [new Required()],
            'body' => [new Required()],
        ];
    }
}
