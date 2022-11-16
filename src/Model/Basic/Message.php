<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Message\{
    ContentType,
    ContentEncoding,
    DeliveryMode,
    Priority,
    CorrelationId,
    ReplyTo,
    Id,
    Type,
    UserId,
    AppId,
};
use Innmind\TimeContinuum\{
    PointInTime,
    ElapsedPeriod,
};
use Innmind\Filesystem\{
    File\Content,
    Chunk,
};
use Innmind\Immutable\{
    Map,
    Str,
    Maybe,
    Sequence,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 */
final class Message
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
    /** @var Sequence<Str> */
    private Sequence $chunks;
    /** @var int<0, max> */
    private int $length;

    /**
     * @param Sequence<Str> $chunks
     * @param int<0, max> $length
     */
    private function __construct(Sequence $chunks, int $length)
    {
        $this->chunks = $chunks->map(
            static fn($chunk) => $chunk->toEncoding('ASCII'),
        );
        $this->length = $length;
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
        return new self(
            Sequence::of($body),
            $body->toEncoding('ASCII')->length(),
        );
    }

    /**
     * Since the length of the body must be known in advance using a content
     * that can only be streamed once (ie output of a process) won't work here
     */
    public static function file(Content $content): self
    {
        $chunks = (new Chunk)($content)->map(
            static fn($chunk) => $chunk->toEncoding('ASCII'),
        );
        /** @var int<0, max> */
        $size = $content->size()->match(
            static fn($size) => $size->toInt(),
            static fn() => $chunks
                ->map(static fn($chunk) => $chunk->length())
                ->reduce(
                    0,
                    static fn(int $a, int $b) => $a + $b,
                ),
        );

        return new self($chunks, $size);
    }

    /**
     * @return Maybe<ContentType>
     */
    public function contentType(): Maybe
    {
        return $this->contentType;
    }

    public function withContentType(ContentType $contentType): self
    {
        $self = clone $this;
        $self->contentType = Maybe::just($contentType);

        return $self;
    }

    /**
     * @return Maybe<ContentEncoding>
     */
    public function contentEncoding(): Maybe
    {
        return $this->contentEncoding;
    }

    public function withContentEncoding(ContentEncoding $contentEncoding): self
    {
        $self = clone $this;
        $self->contentEncoding = Maybe::just($contentEncoding);

        return $self;
    }

    /**
     * @return Map<string, mixed>
     */
    public function headers(): Map
    {
        return $this->headers;
    }

    /**
     * @param Map<string, mixed> $headers
     */
    public function withHeaders(Map $headers): self
    {
        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    /**
     * @return Maybe<DeliveryMode>
     */
    public function deliveryMode(): Maybe
    {
        return $this->deliveryMode;
    }

    public function withDeliveryMode(DeliveryMode $deliveryMode): self
    {
        $self = clone $this;
        $self->deliveryMode = Maybe::just($deliveryMode);

        return $self;
    }

    /**
     * @return Maybe<Priority>
     */
    public function priority(): Maybe
    {
        return $this->priority;
    }

    public function withPriority(Priority $priority): self
    {
        $self = clone $this;
        $self->priority = Maybe::just($priority);

        return $self;
    }

    /**
     * @return Maybe<CorrelationId>
     */
    public function correlationId(): Maybe
    {
        return $this->correlationId;
    }

    public function withCorrelationId(CorrelationId $correlationId): self
    {
        $self = clone $this;
        $self->correlationId = Maybe::just($correlationId);

        return $self;
    }

    /**
     * @return Maybe<ReplyTo>
     */
    public function replyTo(): Maybe
    {
        return $this->replyTo;
    }

    public function withReplyTo(ReplyTo $replyTo): self
    {
        $self = clone $this;
        $self->replyTo = Maybe::just($replyTo);

        return $self;
    }

    /**
     * @return Maybe<ElapsedPeriod>
     */
    public function expiration(): Maybe
    {
        return $this->expiration;
    }

    public function withExpiration(ElapsedPeriod $expiration): self
    {
        $self = clone $this;
        $self->expiration = Maybe::just($expiration);

        return $self;
    }

    /**
     * @return Maybe<Id>
     */
    public function id(): Maybe
    {
        return $this->id;
    }

    public function withId(Id $id): self
    {
        $self = clone $this;
        $self->id = Maybe::just($id);

        return $self;
    }

    /**
     * @return Maybe<PointInTime>
     */
    public function timestamp(): Maybe
    {
        return $this->timestamp;
    }

    public function withTimestamp(PointInTime $timestamp): self
    {
        $self = clone $this;
        $self->timestamp = Maybe::just($timestamp);

        return $self;
    }

    /**
     * @return Maybe<Type>
     */
    public function type(): Maybe
    {
        return $this->type;
    }

    public function withType(Type $type): self
    {
        $self = clone $this;
        $self->type = Maybe::just($type);

        return $self;
    }

    /**
     * @return Maybe<UserId>
     */
    public function userId(): Maybe
    {
        return $this->userId;
    }

    public function withUserId(UserId $userId): self
    {
        $self = clone $this;
        $self->userId = Maybe::just($userId);

        return $self;
    }

    /**
     * @return Maybe<AppId>
     */
    public function appId(): Maybe
    {
        return $this->appId;
    }

    public function withAppId(AppId $appId): self
    {
        $self = clone $this;
        $self->appId = Maybe::just($appId);

        return $self;
    }

    public function body(): Str
    {
        return $this
            ->chunks
            ->fold(new Concat)
            ->toEncoding('ASCII');
    }

    /**
     * @return Sequence<Str>
     */
    public function chunks(): Sequence
    {
        return $this->chunks;
    }

    /**
     * @return int<0, max>
     */
    public function length(): int
    {
        return $this->length;
    }
}
