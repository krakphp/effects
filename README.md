# Effects

The effects library is a small set of utilities to help enable side effects in code that you expect to remain pure using PHP generators to transfer ownership.

This is helpful in terms of Domain Driven Design and maintaining a pure domain model.

## Usage

```php
<?php

use function Krak\Effects\{handleEffects, expect};

// Domain Entity
final class ShoppingCart
{
    public function checkOut(CheckOutShoppingCart $checkOutShoppingCart) {
        // ... build up captueCharge command
        $capturedCharge = expect(CapturedCharge::class, yield new CaptureCharge(/* args */));
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

### How it Works

This works by leveraging the fact that PHP generators allow sending values back to a yielded result. The `handleEffects` function simply just iterates over the domain method pulling all of the commands, passing them to the command handler map, and then taking the responses and sending them back to the domain method.

The expect function is just a safety helper to provide type auto completion and assert the expected class in case there was a mapping error to make debugging a bit nicer. It's technically not needed, so if you don't care about auto-completion help with psalm and PHPStorm, then feel free to just use the yield keyword without the `expect` function.

### Nested Effects with yield from

If you end up needing to raise a few effects with one method, it may make sense to have specific methods used to manage and raise those effects. 

You can use the `yield from` statement to raise effects from child methods. here's an example:

```php
final class Product
{
    public function checkout() {
        yield from $this->raiseEffects();
    }
    
    private function raiseEffects() {
        $result = yield new Effect1();
    }
}
```

### Prewk\Result Integration

If you are working with more complex domain methods/services, it can be helpful to structure individual parts of the code in separate functions that return results and can short circuit operations like one would use with a normal Result class.

Let's see how we can acheive that with the MapEffectResults class.

```php

use Prewk\Result;
use Krak\Effects\Bridge\Result\MapEffectResults;
use function Krak\Effects\expect;

final class Product
{
    public function syncInventory() {
        expect(Result::class, yield from MapEffectResults::map(
            $this->fetchInventoryFromERP(),
            $this->fetchPricingRules(),
            $this->pushInventoryToThirdParty()
        ))->mapErr(function() {
            // set some error state maybe.
        })->map(function() {
            // set some success state maybe.
        });
    }
    
    public function fetchInventoryFromERP(){
        return function() {
            return expect(Result::class, yield new FetchInventoryFromERP($this->productId));
        };
    }
    
    public function fetchPricingRules(){
        return function(InventoryFromERP $inventoryFromERP) {
            return expect(Result::class, yield new FetchPricingRulesForProduct($this->productId))
                ->map(function(PricingRules $pricingRules) use ($inventoryFromERP) {
                    return [$inventoryFromERP, $pricingRules];
                });
        };
    }
    
    public function pushInventoryToThirdParty() {
        return function(array $tup) {
            [$inventoryFromERP, $pricingRules] = $tup;
            // calculate final inventory using special logic
            return expect(Result::class, yield new PushInventoryToThirdParty($finalInventory));
        };
    }
}

// in some application service
\Krak\Effects\handleEffects($product->syncInventory(), []); // with handlers accordingly
```

## Installation

Install with composer at `krak/effects`

## Inspiration

This design is inspired from the Elm language design around maintaining pure application code while leaving side effects to be managed by the runtime.

Here are some other helpful resources around domain model purity and side effects:

- [Side Effects in Elm](https://elmprogramming.com/side-effects.html)
- [Domain model purity vs. domain model completeness](https://enterprisecraftsmanship.com/posts/domain-model-purity-completeness/)
