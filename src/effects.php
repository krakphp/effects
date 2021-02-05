<?php

namespace Krak\Effects;

/**
 * Simple assertion wrapper to streamline type hinting/assertion
 *
 * (Suppress psalm here because defining param T $returnedMessage will cause other errors with psalm because it can't handle the yield keyword)
 * @psalm-suppress InvalidReturnType,InvalidReturnStatement
 * @template T of object
 * @param class-string<T> $expectedMessageClass
 * @return T
 */
function expect(string $expectedMessageClass, object $returnedMessage): object {
    if (!is_a($returnedMessage, $expectedMessageClass) && !is_subclass_of($returnedMessage, $expectedMessageClass)) {
        throw new \RuntimeException('Expected a message of ' . $expectedMessageClass . ', but received an instance of ' . get_class($returnedMessage) . '. Make sure there is a yield keyword to raise the effect or that the effect handler is configured properly.');
    }

    return $returnedMessage;
}

/**
 * @param \Generator<object> $effects
 * @param array<class-string, callable> $effectHandlerMap
 */
function handleEffects(\Generator $effects, array $effectHandlerMap, ?callable $defaultEffectHandler = null) {
    $defaultEffectHandler = $defaultEffectHandler ?: function($effect) {
        throw new \RuntimeException('No effect handler for effect ' . get_class($effect) . '. You need to provide a handler for that class or provide a default effect handler.');
    };
    while ($effects->valid()) {
        $effect = $effects->current();
        $result = ($effectHandlerMap[get_class($effect)] ?? $defaultEffectHandler)($effect);
        $effects->send($result);
    }

    return $effects->getReturn();
}
