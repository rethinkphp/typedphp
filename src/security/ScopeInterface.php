<?php

namespace rethink\typedphp\security;

interface ScopeInterface
{
    public function name(): string;

    public function description(): string;

    public function schemeName(): string;

    public function security(): ?SecurityScheme;
}
