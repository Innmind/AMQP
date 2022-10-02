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
    Expiration,
    Id,
    Type,
    UserId,
    AppId,
};
use Innmind\TimeContinuum\{
    PointInTime,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
interface Message
{
    /**
     * @return Maybe<ContentType>
     */
    public function contentType(): Maybe;
    public function withContentType(ContentType $contentType): self;

    /**
     * @return Maybe<ContentEncoding>
     */
    public function contentEncoding(): Maybe;
    public function withContentEncoding(ContentEncoding $contentEncoding): self;

    /**
     * @return Map<string, mixed>
     */
    public function headers(): Map;

    /**
     * @param Map<string, mixed> $headers
     */
    public function withHeaders(Map $headers): self;

    /**
     * @return Maybe<DeliveryMode>
     */
    public function deliveryMode(): Maybe;
    public function withDeliveryMode(DeliveryMode $deliveryMode): self;

    /**
     * @return Maybe<Priority>
     */
    public function priority(): Maybe;
    public function withPriority(Priority $priority): self;

    /**
     * @return Maybe<CorrelationId>
     */
    public function correlationId(): Maybe;
    public function withCorrelationId(CorrelationId $correlationId): self;

    /**
     * @return Maybe<ReplyTo>
     */
    public function replyTo(): Maybe;
    public function withReplyTo(ReplyTo $replyTo): self;

    /**
     * @return Maybe<ElapsedPeriod>
     */
    public function expiration(): Maybe;
    public function withExpiration(ElapsedPeriod $expiration): self;

    /**
     * @return Maybe<Id>
     */
    public function id(): Maybe;
    public function withId(Id $id): self;

    /**
     * @return Maybe<PointInTime>
     */
    public function timestamp(): Maybe;
    public function withTimestamp(PointInTime $timestamp): self;

    /**
     * @return Maybe<Type>
     */
    public function type(): Maybe;
    public function withType(Type $type): self;

    /**
     * @return Maybe<UserId>
     */
    public function userId(): Maybe;
    public function withUserId(UserId $userId): self;

    /**
     * @return Maybe<AppId>
     */
    public function appId(): Maybe;
    public function withAppId(AppId $appId): self;
    public function body(): Str;
}
