<?php

declare(strict_types=1);

namespace Yiisoft\View;

interface FragmentCacheInterface
{
    public const STATUS_BEGIN = 2;
    public const STATUS_END = 3;
    public const STATUS_IN_CACHE = 4;
    public const STATUS_INIT = 1;
    public const STATUS_NO = 0;

    public function beginCache(?View $view, string $id, array $params = [], array $vars = []): self;

    public function endCache(): void;

    public function getStatus(): int;

    public function renderVar(string $name): string;
}
