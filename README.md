# Effects

The effects library is a small set of utilities to help enable side effects in code that you expect to remain pure using PHP generators to transfer ownership.

This is helpful in terms of Domain Driven Design and maintaining a pure domain model.

## Usage

```php
<?php

use Krak\Effects\{handleEffects, raise};

// Domain Entity
final class ShoppingCart
{
    public function checkOut(CheckOutShoppingCart $checkOutShoppingCart) {
        // ... build up captueCharge command
        $capturedCharge = raise(yield new CaptureCharge(/* args */), CapturedCharge::class);
    }
}

// Domain Commands/Effects
final class CheckOutShoppingCart {}
final class CaptureCharge {}
final class CapturedCharge {}

// Application Command Handler
final class HandleCheckOutShoppingCart
{
    public function __invoke(CheckOutShoppingCart $checkOutShoppingCart): void {
        $shoppingCart = $this->shoppingCarts->get($checkOutShoppingCart->shoppingCart());
        handleEffects($shoppingCart->checkOut($checkOutShoppingCart), [
            CaptureCharge::class => function(CaptureCharge $captureCharge) {
                return $this->paymentGateway->capture($captureCharge); // returns a CapturedCharge instance
            }
        ]);
    }
}
```

## Installation

Install with composer at `krak/effects`

## Inspiration

This design is inspired from the Elm language design around maintaining pure application code while leaving side effects to be managed by the runtime.

Here are some other helpful resources around domain model purity and side effects:

- [Side Effects in Elm](https://elmprogramming.com/side-effects.html)
- [Domain model purity vs. domain model completeness](https://enterprisecraftsmanship.com/posts/domain-model-purity-completeness/)
