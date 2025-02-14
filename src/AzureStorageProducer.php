<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Enqueue\Client\Config;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Destination;
use Interop\Queue\Producer;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageProducer implements Producer
{
    /**
     * @var QueueRestProxy
     */
    protected $client;

    public function __construct(QueueRestProxy $client)
    {
        $this->client = $client;
    }

    /**
     * @var AzureStorageDestination $destination
     * @var AzureStorageMessage $message
     * @throws InvalidDestinationException if a client uses this method with an invalid destination
     * @throws InvalidMessageException     if an invalid message is specified
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AzureStorageDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, AzureStorageMessage::class);

        $options = new CreateMessageOptions();
        $options->setTimeToLiveInSeconds($message->getProperty(Config::EXPIRE, 7 * 3600 * 24));

        $result = $this->client->createMessage($destination->getName(), $message->getMessageText());
        $resultMessage = $result->getQueueMessage();

        $message->setMessageId($resultMessage->getMessageId());
        $message->setTimestamp($resultMessage->getInsertionDate()->getTimestamp());
        $message->setRedelivered($resultMessage->getDequeueCount() > 1);

        $message->setHeaders([
            'dequeueCount' => $resultMessage->getDequeueCount(),
            'expirationDate' => $resultMessage->getExpirationDate(),
            'popReceipt' => $resultMessage->getPopReceipt(),
            'nextTimeVisible' => $resultMessage->getTimeNextVisible(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        if (null === $deliveryDelay) {
            return $this;
        }
        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setPriority(int $priority = null): Producer
    {
        if (null === $priority) {
            return $this;
        }
        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): ?int
    {
        return null;
    }

    /**
     * @var integer
     */
    protected $timeToLive;

    /**
     * @inheritdoc
     */
    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->timeToLive = $timeToLive;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }
}
