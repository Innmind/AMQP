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
    Maybe
};

/**
 * @psalm-immutable
 */
final class Generic implements Message
{
    /** @var Maybe<ContentType> */
    private Maybe $contentType;
    /** @var Maybe<ContentEncoding> */
    private Maybe $contentEncoding;
    /** @var Map<string, mixed> */
    private Map $headers;
    /** @var Maybe<DeliveryMode> */
    private Maybe $deliveryMode;
    /** @var Maybe<Priority> */
    private Maybe $priority;
    /** @var Maybe<CorrelationId> */
    private Maybe $correlationId;
    /** @var Maybe<ReplyTo> */
    private Maybe $replyTo;
    /** @var Maybe<ElapsedPeriod> */
    private Maybe $expiration;
    /** @var Maybe<Id> */
    private Maybe $id;
    /** @var Maybe<PointInTime> */
    private Maybe $timestamp;
    /** @var Maybe<Type> */
    private Maybe $type;
    /** @var Maybe<UserId> */
    private Maybe $userId;
    /** @var Maybe<AppId> */
    private Maybe $appId;
    private Str $body;

    private function __construct(Str $body)
    {
        $this->body = $body->toEncoding('ASCII');
        /** @var Map<string, mixed> */
        $this->headers = Map::of();
        /** @var Maybe<ContentType> */
        $this->contentType = Maybe::nothing();
        /** @var Maybe<ContentEncoding> */
        $this->contentEncoding = Maybe::nothing();
        /** @var Maybe<DeliveryMode> */
        $this->deliveryMode = Maybe::nothing();
        /** @var Maybe<Priority> */
        $this->priority = Maybe::nothing();
        /** @var Maybe<CorrelationId> */
        $this->correlationId = Maybe::nothing();
        /** @var Maybe<ReplyTo> */
        $this->replyTo = Maybe::nothing();
        /** @var Maybe<ElapsedPeriod> */
        $this->expiration = Maybe::nothing();
        /** @var Maybe<Id> */
        $this->id = Maybe::nothing();
        /** @var Maybe<PointInTime> */
        $this->timestamp = Maybe::nothing();
        /** @var Maybe<Type> */
        $this->type = Maybe::nothing();
        /** @var Maybe<UserId> */
        $this->userId = Maybe::nothing();
        /** @var Maybe<AppId> */
        $this->appId = Maybe::nothing();
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $body): self
    {
        return new self($body);
    }

    public function contentType(): Maybe
    {
        return $this->contentType;
    }

    public function withContentType(ContentType $contentType): Message
    {
        $self = clone $this;
        $self->contentType = Maybe::just($contentType);

        return $self;
    }

    public function contentEncoding(): Maybe
    {
        return $this->contentEncoding;
    }

    public function withContentEncoding(ContentEncoding $contentEncoding): Message
    {
        $self = clone $this;
        $self->contentEncoding = Maybe::just($contentEncoding);

        return $self;
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

    public function deliveryMode(): Maybe
    {
        return $this->deliveryMode;
    }

    public function withDeliveryMode(DeliveryMode $deliveryMode): Message
    {
        $self = clone $this;
        $self->deliveryMode = Maybe::just($deliveryMode);

        return $self;
    }

    public function priority(): Maybe
    {
        return $this->priority;
    }

    public function withPriority(Priority $priority): Message
    {
        $self = clone $this;
        $self->priority = Maybe::just($priority);

        return $self;
    }

    public function correlationId(): Maybe
    {
        return $this->correlationId;
    }

    public function withCorrelationId(CorrelationId $correlationId): Message
    {
        $self = clone $this;
        $self->correlationId = Maybe::just($correlationId);

        return $self;
    }

    public function replyTo(): Maybe
    {
        return $this->replyTo;
    }

    public function withReplyTo(ReplyTo $replyTo): Message
    {
        $self = clone $this;
        $self->replyTo = Maybe::just($replyTo);

        return $self;
    }

    public function expiration(): Maybe
    {
        return $this->expiration;
    }

    public function withExpiration(ElapsedPeriod $expiration): Message
    {
        $self = clone $this;
        $self->expiration = Maybe::just($expiration);

        return $self;
    }

    public function id(): Maybe
    {
        return $this->id;
    }

    public function withId(Id $id): Message
    {
        $self = clone $this;
        $self->id = Maybe::just($id);

        return $self;
    }

    public function timestamp(): Maybe
    {
        return $this->timestamp;
    }

    public function withTimestamp(PointInTime $timestamp): Message
    {
        $self = clone $this;
        $self->timestamp = Maybe::just($timestamp);

        return $self;
    }

    public function type(): Maybe
    {
        return $this->type;
    }

    public function withType(Type $type): Message
    {
        $self = clone $this;
        $self->type = Maybe::just($type);

        return $self;
    }

    public function userId(): Maybe
    {
        return $this->userId;
    }

    public function withUserId(UserId $userId): Message
    {
        $self = clone $this;
        $self->userId = Maybe::just($userId);

        return $self;
    }

    public function appId(): Maybe
    {
        return $this->appId;
    }

    public function withAppId(AppId $appId): Message
    {
        $self = clone $this;
        $self->appId = Maybe::just($appId);

        return $self;
    }

    public function body(): Str
    {
        return $this->body;
    }
}
