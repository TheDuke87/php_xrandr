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
 * Represents a (virtual) screen
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
class Screen
{

  /**
   * Screen <id>: minimum <minimumGeometry>, current <currentGeometry>, maximum <maximumGeometry>
   * Screen 0: minimum 8 x 8, current 1360 x 768, maximum 32767 x 32767
   *
   * Named Subpatterns: http://php.net/manual/en/function.preg-match.php Example #4
   */
  const LINE_REGEX = '/^Screen (?P<id>\d+): minimum (?P<minimumGeometry>\d+\s?x\s?\d+), current (?P<currentGeometry>\d+\s?x\s?\d+), maximum (?P<maximumGeometry>\d+\s?x\s?\d+)$/';

  /**
   * @var int $id Id of the screen
   */
  private $id;

  /**
   * @var Geometry $minimumGeometry Minimum geometry of the screen
   */
  private $minimumGeometry;

  /**
   * @var Geometry $currentGeometry Current geometry of the screen
   */
  private $currentGeometry;

  /**
   * @var Geometry $maximumGeometry Maximum geometry of the screen
   */
  private $maximumGeometry;

  /**
   *
   * @param int      $id              Id of the screen
   * @param Geometry $minimumGeometry Minimum geometry of the screen
   * @param Geometry $currentGeometry Current geometry of the screen
   * @param Geometry $maximumGeometry Maximum geometry of the screen
   */
  public function __construct($id, $minimumGeometry, $currentGeometry, $maximumGeometry)
  {
    $this->id = $id;
    $this->minimumGeometry = $minimumGeometry;
    $this->currentGeometry = $currentGeometry;
    $this->maximumGeometry = $maximumGeometry;
  }

  /**
   * Parse a line (from xrandr's output) containing a screen
   *
   * @param string $line Line to be parsed
   *
   * @return Screen
   */
  public static function parseLine($line)
  {
    trim($line);

    if (preg_match(Screen::LINE_REGEX, $line, $result)) {
      return new Screen($result["id"], Geometry::parseString($result["minimumGeometry"]),
        Geometry::parseString($result["currentGeometry"]), Geometry::parseString($result["maximumGeometry"]));
    }

    // ToDo: Exeption handling
    if (Xrandr::DEBUG) {
      echo "Screen line could not be parsed!\n";
    }

    return null;
  }

  /**
   * Get the id of the screen
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Get the minimum geometry of the screen
   *
   * @return Geometry
   */
  public function getMinimumGeometry()
  {
    return $this->minimumGeometry;
  }

  /**
   * Get the current geometry of the screen
   *
   * @return Geometry
   */
  public function getCurrentGeometry()
  {
    return $this->currentGeometry;
  }

  /**
   * Get the maximum geometry of the screen
   *
   * @return Geometry
   */
  public function getMaximumGeometry()
  {
    return $this->maximumGeometry;
  }

}
