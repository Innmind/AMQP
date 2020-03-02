<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message;
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};
use function Innmind\Immutable\assertMap;

final class Generic implements Message
{
    private ?ContentType $contentType = null;
    private ?ContentEncoding $contentEncoding = null;
    private Map $headers;
    private ?DeliveryMode $deliveryMode = null;
    private ?Priority $priority = null;
    private ?CorrelationId $correlationId = null;
    private ?ReplyTo $replyTo = null;
    private ?ElapsedPeriod $expiration = null;
    private ?Id $id = null;
    private ?PointInTimeInterface $timestamp = null;
    private ?Type $type = null;
    private ?UserId $userId = null;
    private ?AppId $appId = null;
    private Str $body;

    public function __construct(Str $body)
    {
        $this->body = $body->toEncoding('ASCII');
        $this->headers = new Map('string', 'mixed');
    }

    public function hasContentType(): bool
    {
        return $this->contentType instanceof ContentType;
    }

    public function contentType(): ContentType
    {
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

    public function contentEncoding(): ContentEncoding
    {
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

    /**
     * {@inheritdoc}
     */
    public function headers(): MapInterface
    {
        return $this->headers;
    }

    public function withHeaders(MapInterface $headers): Message
    {
        assertMap('string', 'mixed', $headers, 1);

        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function hasDeliveryMode(): bool
    {
        return $this->deliveryMode instanceof DeliveryMode;
    }

    public function deliveryMode(): DeliveryMode
    {
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

    public function priority(): Priority
    {
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

    public function correlationId(): CorrelationId
    {
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

    public function replyTo(): ReplyTo
    {
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

    public function expiration(): ElapsedPeriod
    {
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

    public function id(): Id
    {
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
        return $this->timestamp instanceof PointInTimeInterface;
    }

    public function timestamp(): PointInTimeInterface
    {
        return $this->timestamp;
    }

    public function withTimestamp(PointInTimeInterface $timestamp): Message
    {
        $self = clone $this;
        $self->timestamp = $timestamp;

        return $self;
    }

    public function hasType(): bool
    {
        return $this->type instanceof Type;
    }

    public function type(): Type
    {
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

    public function userId(): UserId
    {
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

    public function appId(): AppId
    {
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
