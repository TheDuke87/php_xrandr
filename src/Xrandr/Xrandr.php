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
 * Represents the xrandr utility
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
class Xrandr
{

  const XRANDR_BIN = "xrandr";
  const DEBUG = false;

  private $raw;
  private $outputs;
  private $screens;

  /**
   *
   * @param array $raw Raw xrandr output for testing
   */
  public function __construct($raw = null)
  {
    $this->raw = $raw;

    $this->parseRaw();
  }

  /**
   * Parsed raw xrandr output and builds lists for screens and outputs
   *
   * @throws Exception
   */
  public function parseRaw()
  {
    $this->screens = array();
    $this->outputs = array();
    $currentOutput = null;
    $outputIndex = 0;

    $raw = $this->getRaw();
    if ($raw == null) {
      return false;
    }

    foreach ($raw as $line) {
      // Using switch just because I think its more "logical" selecting from a case
      switch (true) {
        // Screen
        case preg_match(Screen::LINE_REGEX, $line, $result):
          $newScreen = Screen::parseLine($line);
          $this->screens[$newScreen->getId()] = Screen::parseLine($line);
          break;
        // Output
        case preg_match(Output::LINE_REGEX, $line, $result):
          $currentOutput = Output::parseLine($outputIndex++, $line);
          $this->outputs[$currentOutput->getName()] = $currentOutput;
          break;
        // Mode
        case preg_match(Mode::LINE_REGEX, $line, $result):
          if (!isset($currentOutput)) {
            throw new Exception("parseRawException: Mode line but no currentOutput\n$line");
          }
          $currentOutput->_addExistingMode(Mode::parseLine($line));
          break;
        default:
          // ToDo: Exeption handling
          if (Xrandr::DEBUG) {
            echo "Line could not be parsed!\n";
            echo $line;
            echo "\n";
          }
      }
    }

    return true;
  }

  /**
   * Get the raw xrandr output
   *
   * @return array
   */
  public function getRaw()
  {
    if (!isset($this->raw)) {
      $this->refreshRaw();
    }

    return $this->raw;
  }

  /**
   * Re-query xrandr
   *
   * @return boolean
   */
  private function refreshRaw()
  {
    exec(Xrandr::XRANDR_BIN . " --query", $output, $exitcode);

    if ($exitcode != 0) {
      return false;
    }

    $this->raw = $output;

    return true;
  }

  /**
   * Get first screen
   *
   * @return Screen
   */
  public function getFirstScreen()
  {
    if (count($this->getScreens()) < 1) {
      return null;
    }
    $screens = array_values($this->getScreens());

    return $screens[0];
  }

  /**
   * Get list of screens, keyed by id
   *
   * @return array
   */
  public function getScreens()
  {
    return $this->screens;
  }

  /**
   * Get list of output names
   *
   * @return array
   */
  public function getOutputNames()
  {
    $outputs = $this->getOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_keys($outputs);
  }

  /**
   * Get list of outputs, keyed by name
   *
   * @return array
   */
  public function getOutputs()
  {
    return $this->outputs;
  }

  /**
   * Get primary output
   *
   * @return \Output
   */
  public function getPrimaryOutput()
  {
    $outputs = $this->getOutputs();

    if ($outputs == null) {
      return null;
    }

    $result = array_values(array_filter(
      $outputs, function ($e) {
      return $e->isPrimary() == true;
    }
    ));

    if (count($result) > 0) {
      return $result[0];
    }

    return null;
  }

  /**
   * Get list of connected output names
   *
   * @return array
   */
  public function getConnectedOutputNames()
  {
    $outputs = $this->getConnectedOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_keys($outputs);
  }

  /**
   * Get list of connected outputs, keyed by name
   *
   * @return array
   */
  public function getConnectedOutputs()
  {
    $outputs = $this->getOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_filter(
      $outputs, function ($e) {
      return $e->isConnected() == true;
    }
    );
  }

  /**
   * Get list of connected, non-primary output names
   *
   * @return array
   */
  public function getConnectedSecondaryOutputNames()
  {
    $outputs = $this->getConnectedSecondaryOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_keys($outputs);
  }

  /**
   * Get list of connected, non-primary outputs, keyed by name
   *
   * @return array
   */
  public function getConnectedSecondaryOutputs()
  {
    $outputs = $this->getConnectedOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_filter(
      $outputs, function ($e) {
      return ($e->isPrimary() == false);
    }
    );
  }

  /**
   * Get list of active output names
   *
   * @return array
   */
  public function getActiveOutputNames()
  {
    $outputs = $this->getActiveOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_keys($outputs);
  }

  /**
   * Get list of connected, active outputs, keyed by name
   *
   * @return array
   */
  public function getActiveOutputs()
  {
    $outputs = $this->getOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_filter(
      $outputs, function ($e) {
      return $e->isActive() == true;
    }
    );
  }

  /**
   * Get list of connected, active, non-primary outputs, keyed by name
   *
   * @return array
   */
  public function getActiveSecondaryOutputs()
  {
    $outputs = $this->getActiveOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_filter(
      $outputs, function ($e) {
      return $e->isPrimary() == false;
    }
    );
  }

  /**
   * Get list of connected output names
   *
   * @return array
   */
  public function getActiveSecondaryOutputNames()
  {
    $outputs = $this->getActiveSecondaryOutputNames();

    if ($outputs == null) {
      return null;
    }

    return array_keys($outputs);
  }

  /**
   * Get list of disconnected output names
   *
   * @return array
   */
  public function getDisconnectedOutputNames()
  {
    $outputs = $this->getDisconnectedOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_keys($outputs);
  }

  /**
   * Get list of disconnected outputs, keyed by name
   *
   * @return array
   */
  public function getDisconnectedOutputs()
  {
    $outputs = $this->getOutputs();

    if ($outputs == null) {
      return null;
    }

    return array_filter(
      $outputs, function ($e) {
      return $e->isConnected() == false;
    }
    );
  }

  /**
   * Get output with coordinates 0+0
   *
   * @return \Output
   */
  public function getOutputAtZeroPoint()
  {
    $outputs = $this->getActiveOutputs();

    if ($outputs == null) {
      return null;
    }

    $result = array_values(array_filter(
      $outputs, function ($e) {
      return ($e->getGeometry()->x == 0) && ($e->getGeometry()->y == 0);
    }
    ));

    if (count($result) > 0) {
      return $result[0];
    }

    return null;
  }

  /**
   * Automatically configure outputs based on preferred values
   *
   * @return boolean
   */
  public function setAuto()
  {
    exec(Xrandr::XRANDR_BIN . " --auto", $output, $exitcode);

    if ($exitcode != 0) {
      return false;
    }

    return true;
  }

  /**
   * Refresh xrandr output and parse it
   */
  public function refresh()
  {
    if ($this->refreshRaw()) {
      $this->parseRaw();
    }
  }

}
