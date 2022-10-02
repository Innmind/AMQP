<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message;
use Innmind\TimeContinuum\{
    PointInTime,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    Map,
    Str,
};

/**
 * @psalm-immutable
 */
final class Generic implements Message
{
    private ?ContentType $contentType = null;
    private ?ContentEncoding $contentEncoding = null;
    /** @var Map<string, mixed> */
    private Map $headers;
    private ?DeliveryMode $deliveryMode = null;
    private ?Priority $priority = null;
    private ?CorrelationId $correlationId = null;
    private ?ReplyTo $replyTo = null;
    private ?ElapsedPeriod $expiration = null;
    private ?Id $id = null;
    private ?PointInTime $timestamp = null;
    private ?Type $type = null;
    private ?UserId $userId = null;
    private ?AppId $appId = null;
    private Str $body;

    public function __construct(Str $body)
    {
        $this->body = $body->toEncoding('ASCII');
        /** @var Map<string, mixed> */
        $this->headers = Map::of();
    }

    public function hasContentType(): bool
    {
        return $this->contentType instanceof ContentType;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function contentType(): ContentType
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->contentType;
    }

    public function withContentType(ContentType $contentType): Message
    {
        $self = clone $this;
        $self->contentType = $contentType;

        return $self;
    }

    public function hasContentEncoding(): bool
    {
        return $this->contentEncoding instanceof ContentEncoding;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function contentEncoding(): ContentEncoding
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->contentEncoding;
    }

    public function withContentEncoding(ContentEncoding $contentEncoding): Message
    {
        $self = clone $this;
        $self->contentEncoding = $contentEncoding;

        return $self;
    }

    public function hasHeaders(): bool
    {
        return $this->headers->size() > 0;
    }

    public function headers(): Map
    {
        return $this->headers;
    }

    public function withHeaders(Map $headers): Message
    {
        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function hasDeliveryMode(): bool
    {
        return $this->deliveryMode instanceof DeliveryMode;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function deliveryMode(): DeliveryMode
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->deliveryMode;
    }

    public function withDeliveryMode(DeliveryMode $deliveryMode): Message
    {
        $self = clone $this;
        $self->deliveryMode = $deliveryMode;

        return $self;
    }

    public function hasPriority(): bool
    {
        return $this->priority instanceof Priority;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function priority(): Priority
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->priority;
    }

    public function withPriority(Priority $priority): Message
    {
        $self = clone $this;
        $self->priority = $priority;

        return $self;
    }

    public function hasCorrelationId(): bool
    {
        return $this->correlationId instanceof CorrelationId;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function correlationId(): CorrelationId
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->correlationId;
    }

    public function withCorrelationId(CorrelationId $correlationId): Message
    {
        $self = clone $this;
        $self->correlationId = $correlationId;

        return $self;
    }

    public function hasReplyTo(): bool
    {
        return $this->replyTo instanceof ReplyTo;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function replyTo(): ReplyTo
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->replyTo;
    }

    public function withReplyTo(ReplyTo $replyTo): Message
    {
        $self = clone $this;
        $self->replyTo = $replyTo;

        return $self;
    }

    public function hasExpiration(): bool
    {
        return $this->expiration instanceof ElapsedPeriod;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function expiration(): ElapsedPeriod
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->expiration;
    }

    public function withExpiration(ElapsedPeriod $expiration): Message
    {
        $self = clone $this;
        $self->expiration = $expiration;

        return $self;
    }

    public function hasId(): bool
    {
        return $this->id instanceof Id;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function id(): Id
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->id;
    }

    public function withId(Id $id): Message
    {
        $self = clone $this;
        $self->id = $id;

        return $self;
    }

    public function hasTimestamp(): bool
    {
        return $this->timestamp instanceof PointInTime;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function timestamp(): PointInTime
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->timestamp;
    }

    public function withTimestamp(PointInTime $timestamp): Message
    {
        $self = clone $this;
        $self->timestamp = $timestamp;

        return $self;
    }

    public function hasType(): bool
    {
        return $this->type instanceof Type;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function type(): Type
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->type;
    }

    public function withType(Type $type): Message
    {
        $self = clone $this;
        $self->type = $type;

        return $self;
    }

    public function hasUserId(): bool
    {
        return $this->userId instanceof UserId;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function userId(): UserId
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->userId;
    }

    public function withUserId(UserId $userId): Message
    {
        $self = clone $this;
        $self->userId = $userId;

        return $self;
    }

    public function hasAppId(): bool
    {
        return $this->appId instanceof AppId;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function appId(): AppId
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->appId;
    }

    public function withAppId(AppId $appId): Message
    {
        $self = clone $this;
        $self->appId = $appId;

        return $self;
    }

    public function body(): Str
    {
        return $this->body;
    }
}
