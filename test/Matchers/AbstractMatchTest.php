<?php

namespace ZxcvbnPhp\Test\Matchers;

abstract class AbstractMatchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * takes a pattern and list of prefixes/suffixes
     * returns a bunch of variants of that pattern embedded
     * with each possible prefix/suffix combination, including no prefix/suffix
     *
     * @see test-matching.coffee
     *
     * @param  string $pattern
     * @param  array  $prefixes
     * @param  array  $suffixes
     * @return array a list of triplets [variant, i, j] where [i,j] is the start/end of the pattern, inclusive
     */
    protected function generatePasswords($pattern, $prefixes, $suffixes)
    {
        $output = [];

        if (!in_array('', $prefixes)) {
            array_unshift($prefixes, '');
        }
        if (!in_array('', $suffixes)) {
            array_unshift($suffixes, '');
        }

        foreach ($prefixes as $prefix) {
            foreach ($suffixes as $suffix) {
                $i = strlen($prefix);
                $j = strlen($prefix) + strlen($pattern) - 1;

                $output[] = [
                    $prefix . $pattern . $suffix,
                    $i,
                    $j
                ];
            }
        }

        return $output;
    }

    /**
     * [checkMatches description]
     * @param  string       $prefix       This is prepended to the message of any checks that are run
     * @param  array        $matches      [description]
     * @param  array|string $patternNames array of pattern names, or a single pattern which will be repeated
     * @param  array        $patterns     [description]
     * @param  array        $ijs          [description]
     * @param  array        $props        [description]
     */
    protected function checkMatches(
        $prefix,
        $matches,
        $patternNames,
        $patterns,
        $ijs,
        $props
    ) {
        if (is_string($patternNames)) {
            # shortcut: if checking for a list of the same type of patterns,
            # allow passing a string 'pat' instead of array ['pat', 'pat', ...]
            $patternNames = array_fill(0, count($patterns), $patternNames);
        }

        $this->assertEquals(
            count($patterns),
            count($matches),
            $prefix . ": matches.length == " . count($patterns)
        );

        foreach ($patterns as $k => $pattern) {
            $match = $matches[$k];
            $patternName = $patternNames[$k];
            $pattern = $patterns[$k];
            list($i, $j) = $ijs[$k];

            $this->assertEquals(
                $patternName,
                $match->pattern,
                "$prefix matches[$k].pattern == '$patternName'"
            );
            $this->assertEquals(
                [$i, $j],
                [$match->begin, $match->end],
                "$prefix matches[$k] should have [i, j] of [$i, $j]"
            );
            $this->assertEquals(
                $pattern,
                $match->token,
                "$prefix matches[$k].token == '$pattern'"
            );

            foreach ($props as $propName => $propList) {
                $propMessage = var_export($propList[$k], true);
                // prop_msg = "'$prop_msg'" if typeof(prop_msg) == 'string'
                $this->assertEquals(
                    $propList[$k],
                    $match->$propName,
                    "$prefix matches[$k].$propName == $propMessage"
                );
            }
        }
    }
}
