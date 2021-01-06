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
