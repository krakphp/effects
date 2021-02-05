<?php

namespace Krak\Effects\Bridge\Result;

use Prewk\Result;

final class MapEffectResults
{
    private $actions;

    public function __construct(callable ...$actions) {
        $this->actions = $actions;
    }

    public static function map(callable ...$actions): \Generator {
        return (new self(...$actions))();
    }

    public function __invoke($firstValue = null): \Generator {
        $args = [];
        $result = new Result\Ok($firstValue);
        foreach ($this->actions as $action) {
            $actionRes = $action($result->unwrap());
            $result = $this->wrapResult(
                $actionRes instanceof \Generator
                    ? yield from $actionRes
                    : $actionRes
            );

            if ($result->isErr()) {
                return $result;
            }
        }

        return $result;
    }

    private function wrapResult($res) {
        return $res instanceof Result ? $res : new Result\Ok($res);
    }
}
