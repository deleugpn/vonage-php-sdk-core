<?php

namespace Vonage\Messages\MessageType\WhatsApp;

use Vonage\Messages\MessageType\BaseMessage;

class WhatsAppCustom extends BaseMessage
{
    protected string $subType = BaseMessage::MESSAGES_SUBTYPE_CUSTOM;
    protected string $channel = 'whatsapp';
    protected array $custom;

    public function __construct(
        string $to,
        string $from,
        array $custom
    ) {
        $this->to = $to;
        $this->from = $from;
        $this->custom = $custom;
    }

    public function setCustom(array $custom): void
    {
        $this->custom = $custom;
    }

    public function getCustom(): array
    {
        return $this->custom;
    }

    public function toArray(): array
    {
        $returnArray = $this->baseMessageArrayOutput();
        $returnArray['custom'] = $this->getCustom();

        return $returnArray;
    }
}
