<?php

declare(strict_types=1);

function view_path(string $name): string
{
    return __DIR__ . '/../../views/' . ltrim($name, '/');
}
