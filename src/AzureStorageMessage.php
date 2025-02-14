<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;

class AzureStorageMessage implements Message
{
    use MessageTrait;
    
    public function __construct(string $body = '', array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;

        $this->redelivered = false;
    }

    public function getMessageText(): string
    {
        $messageText = [
            'body' => $this->body,
            'properties' => $this->properties,
        ];
        return base64_encode(json_encode($messageText));
    }
}
