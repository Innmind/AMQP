<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\{
    Model\Basic\Message,
    Exception\MessageLocked,
};
use Innmind\TimeContinuum\{
    PointInTime,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    Map,
    Str,
};

final class Locked implements Message
{
    private Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function hasContentType(): bool
    {
        return $this->message->hasContentType();
    }

    public function contentType(): ContentType
    {
        return $this->message->contentType();
    }

    public function withContentType(ContentType $contentType): Message
    {
        throw new MessageLocked;
    }

    public function hasContentEncoding(): bool
    {
        return $this->message->hasContentEncoding();
    }

    public function contentEncoding(): ContentEncoding
    {
        return $this->message->contentEncoding();
    }

    public function withContentEncoding(ContentEncoding $contentEncoding): Message
    {
        throw new MessageLocked;
    }

    public function hasHeaders(): bool
    {
        return $this->message->hasHeaders();
    }

    public function headers(): Map
    {
        return $this->message->headers();
    }

    public function withHeaders(Map $headers): Message
    {
        throw new MessageLocked;
    }

    public function hasDeliveryMode(): bool
    {
        return $this->message->hasDeliveryMode();
    }

    public function deliveryMode(): DeliveryMode
    {
        return $this->message->deliveryMode();
    }

    public function withDeliveryMode(DeliveryMode $deliveryMode): Message
    {
        throw new MessageLocked;
    }

    public function hasPriority(): bool
    {
        return $this->message->hasPriority();
    }

    public function priority(): Priority
    {
        return $this->message->priority();
    }

    public function withPriority(Priority $priority): Message
    {
        throw new MessageLocked;
    }

    public function hasCorrelationId(): bool
    {
        return $this->message->hasCorrelationId();
    }

    public function correlationId(): CorrelationId
    {
        return $this->message->correlationId();
    }

    public function withCorrelationId(CorrelationId $correlationId): Message
    {
        throw new MessageLocked;
    }

    public function hasReplyTo(): bool
    {
        return $this->message->hasReplyTo();
    }

    public function replyTo(): ReplyTo
    {
        return $this->message->replyTo();
    }

    public function withReplyTo(ReplyTo $replyTo): Message
    {
        throw new MessageLocked;
    }

    public function hasExpiration(): bool
    {
        return $this->message->hasExpiration();
    }

    public function expiration(): ElapsedPeriod
    {
        return $this->message->expiration();
    }

    public function withExpiration(ElapsedPeriod $expiration): Message
    {
        throw new MessageLocked;
    }

    public function hasId(): bool
    {
        return $this->message->hasId();
    }

    public function id(): Id
    {
        return $this->message->id();
    }

    public function withId(Id $id): Message
    {
        throw new MessageLocked;
    }

    public function hasTimestamp(): bool
    {
        return $this->message->hasTimestamp();
    }

    public function timestamp(): PointInTime
    {
        return $this->message->timestamp();
    }

    public function withTimestamp(PointInTime $timestamp): Message
    {
        throw new MessageLocked;
    }

    public function hasType(): bool
    {
        return $this->message->hasType();
    }

    public function type(): Type
    {
        return $this->message->type();
    }

    public function withType(Type $type): Message
    {
        throw new MessageLocked;
    }

    public function hasUserId(): bool
    {
        return $this->message->hasUserId();
    }

    public function userId(): UserId
    {
        return $this->message->userId();
    }

    public function withUserId(UserId $userId): Message
    {
        throw new MessageLocked;
    }

    public function hasAppId(): bool
    {
        return $this->message->hasAppId();
    }

    public function appId(): AppId
    {
        return $this->message->appId();
    }

    public function withAppId(AppId $appId): Message
    {
        throw new MessageLocked;
    }

    public function body(): Str
    {
        return $this->message->body();
    }
}
