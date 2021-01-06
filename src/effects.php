<?php

namespace Krak\Effects;

function raise(?object $returnedMessage, ?string $expectedMessageClass = null): ?object {
    if ($returnedMessage === null && $expectedMessageClass === null) {
        return null;
    }
    if ($returnedMessage === null || get_class($returnedMessage) !== $expectedMessageClass) {
        throw new \RuntimeException('Incorrect effect handler return value when ' . $expectedMessageClass . ' was expected.');
    }
    return $returnedMessage;
}

function handleEffects(\Generator $effects, array $effectHandlerMap, ?callable $defaultEffectHandler = null) {
    $defaultEffectHandler = $defaultEffectHandler ?: function($effect) {
        throw new \RuntimeException('No effect handler for effect ' . get_class($effect) . '.');
    };
    while ($effects->valid()) {
        $effect = $effects->current();
        $result = ($effectHandlerMap[get_class($effect)] ?? $defaultEffectHandler)($effect);
        $effects->send($result);
    }

    return $effects->getReturn();
}
