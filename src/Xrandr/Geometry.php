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
 * Represents size and/or position of a window, or resolution of an output
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
class Geometry
{

  /**
   *
   * @var int $width Width in pixels
   */
  public $width = 0;

  /**
   *
   * @var int $height Height in pixels
   */
  public $height = 0;

  /**
   *
   * @var int $x X coordinate of position in pixels
   */
  public $x = 0;

  /**
   *
   * @var int $y Y coordinate of position in pixels
   */
  public $y = 0;

  /**
   * @param int $width  Width in pixels
   * @param int $height Height in pixels
   * @param int $x      X coordinate of position in pixels
   * @param int $y      Y coordinate of position in pixels
   */
  public function __construct($width, $height, $x, $y)
  {

    $this->width = $width;
    $this->height = $height;
    $this->x = $x;
    $this->y = $y;
  }

  /**
   * Parse a string containing either a full geometry, only a resolution or only a position
   *
   * Named Subpatterns: http://php.net/manual/en/function.preg-match.php Example #4
   *
   * @param string $string String to be parsed
   *
   * @return Geometry
   */
  public static function parseString($string)
  {
    // First, remove every whitespace in the input string
    $string = trim($string);
    $string = preg_replace('/\s/', '', $string);

    // Using switch just because I think its more "logical" selecting from a case
    switch (true) {
      case ($string == ""):
        return null;
      // 1360x768+200+50
      // 1360x768-200-50
      case preg_match('/(?P<width>\d+)x(?P<height>\d+)(?P<x>[+\-]\d+)(?P<y>[+\-]\d+)/', $string, $result):
        return new Geometry($result["width"], $result["height"], $result["x"], $result["y"]);
      // 1360x768
      case preg_match('/(?P<width>\d+)x(?P<height>\d+)/', $string, $result):
        return new Geometry($result["width"], $result["height"], 0, 0);
      // +0+0
      // -0-0
      // 0+0
      case preg_match('/(?P<x>[+-]?\d+)(?P<y>[+-]\d+)/', $string, $result):
        return new Geometry(0, 0, $result["x"], $result["y"]);
      default:
        // ToDo: Exeption handling
        if (Xrandr::DEBUG) {
          echo "Geometry string could not be parsed!\n";
        }

        return null;
    }
  }

  /**
   * Get a string representing the geometry
   * e.g. 1920x1080-200+50
   *
   * @return string
   */
  public function getGeometryString()
  {
    return "{$this->getResolutionString()}{$this->getPositionString()}";
  }

  /**
   * Get a string representing the resolution
   * e.g. 1920x1080
   *
   * @return string
   */
  public function getResolutionString()
  {
    return "{$this->width}x{$this->height}";
  }

  /**
   * Get a string representing the position
   * e.g. -200+50
   *
   * @return string
   */
  public function getPositionString()
  {
    $signedX = sprintf("%+d", $this->x);
    $signedY = sprintf("%+d", $this->y);

    return "{$signedX}{$signedY}";
  }

  /**
   * Adds to the width of the geometry
   *
   * @param int $width
   */
  public function addWidth($width)
  {
    $this->width += $width;
  }

  /**
   * Adds to the height of the geometry
   *
   * @param int $height
   */
  public function addHeight($height)
  {
    $this->height += $height;
  }

  /**
   * Adds to the x coordinate of the geometry
   *
   * @param int $x
   */
  public function addX($x)
  {
    $this->x += $x;
  }

  /**
   * Adds to the y coordinate of the geometry
   *
   * @param int $y
   */
  public function addY($y)
  {
    $this->y += $y;
  }

}
