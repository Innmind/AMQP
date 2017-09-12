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
    AppId
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Immutable\{
    MapInterface,
    Str
};

interface Message
{
    public function hasContentType(): bool;
    public function contentType(): ContentType;
    public function withContentType(ContentType $contentType): self;
    public function hasContentEncoding(): bool;
    public function contentEncoding(): ContentEncoding;
    public function withContentEncoding(ContentEncoding $contentEncoding): self;
    public function hasHeaders(): bool;

    /**
     * @return MapInterface<string, mixed>
     */
    public function headers(): MapInterface;
    public function withHeaders(MapInterface $headers): self;
    public function hasDeliveryMode(): bool;
    public function deliveryMode(): DeliveryMode;
    public function withDeliveryMode(DeliveryMode $deliveryMode): self;
    public function hasPriority(): bool;
    public function priority(): Priority;
    public function withPriority(Priority $priority): self;
    public function hasCorrelationId(): bool;
    public function correlationId(): CorrelationId;
    public function withCorrelationId(CorrelationId $correlationId): self;
    public function hasReplyTo(): bool;
    public function replyTo(): ReplyTo;
    public function withReplyTo(ReplyTo $replyTo): self;
    public function hasExpiration(): bool;
    public function expiration(): ElapsedPeriod;
    public function withExpiration(ElapsedPeriod $expiration): self;
    public function hasId(): bool;
    public function id(): Id;
    public function withId(Id $id): self;
    public function hasTimestamp(): bool;
    public function timestamp(): PointInTimeInterface;
    public function withTimestamp(PointInTimeInterface $timestamp): self;
    public function hasType(): bool;
    public function type(): Type;
    public function withType(Type $type): self;
    public function hasUserId(): bool;
    public function userId(): UserId;
    public function withUserId(UserId $userId): self;
    public function hasAppId(): bool;
    public function appId(): AppId;
    public function withAppId(AppId $appId): self;
    public function body(): Str;
}
