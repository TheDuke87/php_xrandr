<?php

/*
 * The MIT License
 *
 * Copyright 2015 - 2016 René Vögeli <rvoegeli@vhtec.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Xrandr;

/**
 * Class serving as enumeration for the reflection parameter
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
abstract class Reflection
{

  const NORMAL = "normal";
  const X = "x";
  const Y = "y";
  const XY = "xy";

  /**
   * Parse a string containing the current reflection of an output
   * and converts it to a more usable format
   *
   * Named Subpatterns: http://php.net/manual/en/function.preg-match.php Example #4
   *
   * @param string $string String to be parsed
   *
   * @return string
   */
  public static function parseString($string)
  {
    // Using switch just because I think its more "logical" selecting from a case
    switch (true) {
      case ($string == ""):
        return null;
      // X and Y axis
      case preg_match('/X and Y axis/', $string, $result):
        return Reflection::XY;
      // X axis
      case preg_match('/X axis/', $string, $result):
        return Reflection::X;
      // Y axis
      case preg_match('/Y axis/', $string, $result):
        return Reflection::Y;
      default:
        // ToDo: Exeption handling
        if (Xrandr::DEBUG) {
          echo "Reflection string could not be parsed!\n";
        }

        return null;
    }
  }

}
