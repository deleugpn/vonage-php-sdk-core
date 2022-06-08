<?php

namespace Vonage\Messages\MessageType\WhatsApp;

use Vonage\Messages\MessageObjects\FileObject;
use Vonage\Messages\MessageObjects\TemplateObject;
use Vonage\Messages\MessageType\BaseMessage;

class WhatsAppTemplate extends BaseMessage
{
    protected string $channel = 'whatsapp';
    protected string $subType = BaseMessage::MESSAGES_SUBTYPE_TEMPLATE;
    protected TemplateObject $templateObject;
    protected string $locale = 'en_US';

    public function __construct(
        string $to,
        string $from,
        TemplateObject $templateObject,
        string $locale
    ) {
        $this->to = $to;
        $this->from = $from;
        $this->templateObject = $templateObject;
        $this->locale = $locale;
    }

    public function toArray(): array
    {
        $returnArray = [
            'template' => $this->templateObject->toArray(),
            'whatsapp' => [
                'policy' => 'deterministic',
                'locale' => $this->getLocale()
            ]
        ];

        return array_merge($this->baseMessageArrayOutput(), $returnArray);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }
}