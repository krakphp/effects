<?php

namespace Krak\Effects;

final class EffectsTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function can_yield_and_handle_effects() {
        $sum = handleEffects((function() {
            $result = expect(Result::class, yield new Add(1, 2));
            return $result->value;
        })(), [
            Add::class => function(Add $add) {
                return new Result(array_sum($add->values));
            }
        ]);

        $this->assertEquals(3, $sum);
    }

    /** @test */
    public function unhandled_effects_by_default_will_throw_exception() {
        $this->expectExceptionMessage('No effect handler for effect Krak\Effects\Add.');
        $sum = handleEffects((function() {
            $result = expect(Result::class, yield new Add(1, 2));
            return $result->value;
        })(), []);
    }

    /** @test */
    public function can_supply_a_default_effect_handler() {
        $sum = handleEffects((function() {
            $result = expect(Result::class, yield new Add(1, 2));
            return $result->value;
        })(), [], function () { return new Result(1); });

        $this->assertEquals(1, $sum);
    }

    /** @test */
    public function write_only_effects_are_supported() {
        $sum = handleEffects((function() {
            yield new Add(1, 2);
            return 1;
        })(), [], function () {});

        $this->assertEquals(1, $sum);
    }

    /** @test */
    public function invalid_return_messages_from_effect_handler_results_in_error() {
        $this->expectExceptionMessage('Expected a message of Krak\Effects\Add, but received an instance of Krak\Effects\Result. Make sure there is a yield keyword to raise the effect or that the effect handler is configured properly.');
        $sum = handleEffects((function() {
            expect(Add::class, yield new Add(1, 2));
        })(), [], function () { return new Result(1); });
    }

    /** @test */
    public function supports_parent_classes_in_returned_messages() {
        $status = handleEffects((function() {
            return expect(Status::class, yield new Add(1, 2));
        })(), [], function() { return new ErrorStatus(); });

        $this->assertInstanceOf(ErrorStatus::class, $status);
    }
}

final class Add {
    public $values;
    public function __construct(int ... $values) {
        $this->values = $values;
    }
}

final class Result {
    public $value;
    public function __construct(int $value) {
        $this->value = $value;
    }
}

abstract class Status {}
final class ErrorStatus extends Status {}
