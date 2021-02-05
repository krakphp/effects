<?php

namespace Krak\Effects\Bridge\Result;

use PHPUnit\Framework\TestCase;
use Prewk\Result;
use Prewk\Result\Err;
use Prewk\Result\Ok;
use function Krak\Effects\handleEffects;

final class MapEffectResultsTest extends TestCase
{
    /** 
     * @test
     * @dataProvider provide_for_map_effects
     */
    public function can_map_effect_results(\Generator $mapEffectResults, Result $expectedResult) {
        $res = handleEffects($mapEffectResults, [], function(Div $div) {
            if ($div->denominator === 0) {
                return new Err('cannot divide by 0');
            }

            return new Ok($div->numerator / $div->denominator);
        });
        $this->assertEquals($expectedResult, $res);
    }

    public function provide_for_map_effects() {
        yield 'all success' => [
            'mapEffectResults' => MapEffectResults::map(
                function() {
                    return yield new Div(4, 2);
                },
                function(int $res) {
                    return yield new Div(8, $res);
                }
            ),
            'expectedResult' => new Ok(4)
        ];
        yield 'stops on error' => [
            'mapEffectResults' => MapEffectResults::map(
                function() {
                    return yield new Div(4, 0);
                },
                function(int $res) {
                    return yield new Div(4, 2);
                }
            ),
            'expectedResult' => new Err('cannot divide by 0')
        ];
        yield 'can mix effects with normal' => [
            'mapEffectResults' => MapEffectResults::map(
                function() {
                    return new Ok(4);
                },
                function(int $res) {
                    return yield new Div(4, $res);
                }
            ),
            'expectedResult' => new Ok(1),
        ];
    }
}

final class Div {
    public $numerator;
    public $denominator;

    public function __construct(int $numerator, int $denominator) {
        $this->numerator = $numerator;
        $this->denominator = $denominator;
    }
}
