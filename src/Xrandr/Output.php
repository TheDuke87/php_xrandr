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

require_once dirname(__FILE__) . '/Geometry.php';
require_once dirname(__FILE__) . '/Mode.php';
require_once dirname(__FILE__) . '/Position.php';
require_once dirname(__FILE__) . '/Reflection.php';
require_once dirname(__FILE__) . '/Rotation.php';

/**
 * Represents an output (monitor connector)
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
class Output {

  /**
   * <name> <dis/connected> <primary> <resolution> <rotation> <reflection> (normal left inverted right x axis y axis) <physicalWidth>mm x <physicalHeight>mm
   * eDP1 connected primary 1360x768+0+0 (normal left inverted right x axis y axis) 344mm x 193mm
   * DVI-I-1 connected primary 1360x768+0+0 (normal left inverted right x axis y axis) 344mm x 193mm
   * DP1 disconnected (normal left inverted right x axis y axis)
   * VGA1 connected primary 1920x1200+0+0 ROTATION REFLECTION ([AVAILABLE ROTATIONS] [AVAILABLE REFLECTIONS]) 519mm x 324mm panning %dx%d+%d+%d tracking %dx%d+%d+%d border %d/%d/%d/%d
   *
   * Named Subpatterns: http://php.net/manual/en/function.preg-match.php Example #4
   * @note Panning, Tracking and Border are not supported, yet
   * @note Available rotations and reflections are not being parsed, yet
   * @todo Regex is incomplete since some features are not needed, yet. Complete line is available above, extracted from xrandr.c
   */
  const LINE_REGEX = '/^(?P<name>[\w-]+) (?P<connected>(dis)?connected)\s?(?P<primary>primary)?\s?(?P<geometry>[x+\-\d]+)?\s?(?P<rotation>(left|right|inverted))?\s?(?P<reflection>X?\s?(and)?\s?Y? axis)?\s?(\(normal left inverted right x axis y axis\))?\s?((?P<physicalWidth>\d+)mm x (?P<physicalHeight>\d+)mm)?$/';

  /**
   *
   * @var string $name Name of the output
   */
  private $name;

  /**
   *
   * @var boolean $connected Is connected
   */
  private $connected;

  /**
   *
   * @var boolean $primary Is primary
   */
  private $primary;

  /**
   *
   * @var \Geometry $geometry Output geometry
   */
  private $geometry;

  /**
   *
   * @var string $rotation Output rotation
   */
  private $rotation;

  /**
   *
   * @var string $reflection Output reflection
   */
  private $reflection;

  /**
   *
   * @var int $physicalWidth Output physical width
   */
  private $physicalWidth;

  /**
   *
   * @var int $physicalHeight Output physical height
   */
  private $physicalHeight;

  /**
   *
   * @var array $modes List of modes
   */
  private $modes;

  /**
   *
   * @param string $name Name of output
   * @param boolean $connected Is currently connected
   * @param boolean $primary Is primary output
   * @param \Geometry $geometry Output geometry
   * @param string $rotation Output rotation
   * @param string $reflection Output reflection
   * @param int $physicalWidth Output physical width
   * @param int $physicalHeight Output physical height
   */
  public function __construct($name, $connected, $primary = false, $geometry = NULL, $rotation = "", $reflection = "", $physicalWidth = 0, $physicalHeight = 0) {
    $this->name = $name;
    $this->connected = $connected;
    $this->primary = $primary;
    $this->geometry = $geometry;
    $this->rotation = $rotation;
    $this->reflection = $reflection;
    $this->physicalWidth = $physicalWidth;
    $this->physicalHeight = $physicalHeight;
  }

  /**
   * Get the output name
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Is the output currently connected
   * @return boolean
   */
  public function isConnected() {
    return $this->connected;
  }

  /**
   * Is the output the primary output
   * @return boolean
   */
  public function isPrimary() {
    return $this->primary;
  }

  /**
   * Is the output active
   * @return boolean
   */
  public function isActive() {
    return ($this->getCurrentMode() != NULL);
  }

  /**
   * Get the output geometry
   * @return \Geometry
   */
  public function getGeometry() {
    return $this->geometry;
  }

  /**
   * Get the output rotation
   * @return string
   */
  public function getRotation() {
    return $this->rotation;
  }

  /**
   * Get the output reflection
   * @return string
   */
  public function getReflection() {
    return $this->reflection;
  }

  /**
   * Get the output physical width
   * @return int
   */
  public function getPhysicalWidth() {
    return $this->physicalWidth;
  }

  /**
   * Get the output physical height
   * @return int
   */
  public function getPhysicalHeight() {
    return $this->physicalHeight;
  }

  /**
   * Get list of modes attached to the output
   * @return array
   */
  public function getModes() {
    return $this->modes;
  }

  /**
   * Add an existing Mode to the output (used by parser)
   * @param \Mode $mode
   */
  public function _addExistingMode($mode) {
    $this->modes[$mode->getName()] = $mode;
  }

  /**
   * Clear all modes from the list
   */
  public function _clearExistingModes() {
    $this->modes = array();
  }

  /**
   * Get all mode names
   * @return array
   */
  public function getModeNames() {
    $modes = $this->getModes();

    if ($modes == NULL) {
      return NULL;
    }

    return array_keys($modes);
  }

  /**
   * Get the currently active mode
   * @return \Mode
   */
  public function getCurrentMode() {
    $modes = $this->getModes();

    if ($modes == NULL) {
      return NULL;
    }

    $result = array_values(array_filter(
                    $modes, function ($e) {
              return $e->isCurrent() == true;
            }
    ));

    if (count($result) > 0) {
      return $result[0];
    }

    return NULL;
  }

  /**
   * Get the preferred mode
   * @return \Mode
   */
  public function getPreferredMode() {
    $modes = $this->getModes();

    if ($modes == NULL) {
      return NULL;
    }

    $result = array_values(array_filter(
                    $modes, function ($e) {
              return $e->isPreferred() == true;
            }
    ));

    if (count($result) > 0) {
      return $result[0];
    }

    return NULL;
  }

  /**
   * Parse a line (from xrandr's output) containing an output
   * @param string $line Line to be parsed
   * @return \Output
   * @todo Exception handling
   */
  public static function parseLine($line) {
    trim($line);

    if (preg_match(Output::LINE_REGEX, $line, $result)) {
      return new Output($result["name"], ($result["connected"] == "connected") ? true : false, (isset($result["primary"]) && $result["primary"] == "primary") ? true : false, isset($result["geometry"]) ? Geometry::parseString($result["geometry"]) : NULL, (isset($result["rotation"]) && $result["rotation"] != "") ? $result["rotation"] : NULL, isset($result["reflection"]) ? Reflection::parseString($result["reflection"]) : "", isset($result["physicalWidth"]) ? $result["physicalWidth"] : 0, isset($result["physicalHeight"]) ? $result["physicalHeight"] : 0);
    }

    // ToDo: Exeption handling
    if (Xrandr::DEBUG) {
      echo "Output line could not be parsed!\n";
    }
    return NULL;
  }

  /**
   * Executes a command on the output
   * @param string $command Command to be executed
   * @return boolean
   */
  private function _executeCommand($command) {
    exec(Xrandr::XRANDR_BIN . " --output {$this->name} {$command}", $output, $exitcode);

    if ($exitcode != 0) {
      return false;
    }

    return true;
  }

  /**
   * Set the output mode to 'auto'
   * @return boolean
   */
  public function setModeAuto() {
    return $this->_executeCommand("--auto");
  }

  /**
   * Set the output mode to 'preferred'
   * @return boolean
   */
  public function setModePreferred() {
    return $this->_executeCommand("--preferred");
  }

  /**
   * Set the output mode to 'off'
   * @return boolean
   */
  public function setModeOff() {
    return $this->_executeCommand("--off");
  }

  /**
   * Set the output mode
   * @param \Mode $mode Mode to be switched to
   * @return boolean
   */
  public function setMode($mode) {
    return $this->_executeCommand("--mode {$mode->getName()}");
  }

  /**
   * Set the output reflection
   * @param string $reflection Reflection to be set (use Reflection enum)
   * @return boolean
   */
  public function setReflection($reflection) {
    return $this->_executeCommand("--reflect {$reflection}");
  }

  /**
   * Set the output rotation
   * @param string $rotation Rotation to be set (use Rotation enum)
   * @return boolean
   */
  public function setRotation($rotation) {
    return $this->_executeCommand("--rotate {$rotation}");
  }

  /**
   * Set output position relative to another output
   * @param string $position Position to be set (use Position enum)
   * @param \Output $otherOutput Other output to position relative to
   * @return boolean
   */
  public function setPosition($position, $otherOutput) {
    return $this->_executeCommand("--{$position} {$otherOutput}");
  }

  /**
   * Set this output as primary
   * @return boolean
   */
  public function setPrimary() {
    return $this->_executeCommand("--primary");
  }

  /**
   * Set output scale
   * @param \Geometry $resolution Resolution to be scaled to
   * @return boolean
   */
  public function setScale($resolution) {
    return $this->_executeCommand("--scale " . $resolution->getResolutionString());
  }

  /**
   * Set output scale-from
   * @param \Geometry $resolution Resolution to be scaled to
   * @return boolean
   */
  public function setScaleFrom($resolution) {
    return $this->_executeCommand("--scale-from " . $resolution->getResolutionString());
  }

  /**
   * Set output scale-from output
   * @param \Output $output Active output the resolution is to be scaled to
   * @return boolean
   */
  public function setScaleFromOutput($output) {
    if (!$output->isActive()) {
      return false;
    }

    return $this->_executeCommand("--scale-from " . $output->getGeometry()->getResolutionString());
  }

  /**
   * Reset scale-from to current mode's resolution
   * @return boolean
   */
  public function resetScaleFrom() {
    $nativeGeometry = $this->getCurrentMode()->getProbableGeometry();

    if ($nativeGeometry == null) {
      return false;
    }

    return $this->_executeCommand("--scale-from " . $nativeGeometry->getResolutionString());
  }

}
