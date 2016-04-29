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
 * Represents a modeline attached to an output
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
class Mode {

	/**
	 *   <name>       <frequency><current><preferred>
	 *   1366x768       60.0*+
	 *   800x600_60.00  ...
	 *
	 * Named Subpatterns: http://php.net/manual/en/function.preg-match.php Example #4
	 * @todo Regex is incomplete since I haven't found a documentation describing the complete line, yet
	 */
	const LINE_REGEX = '/^\s+(?P<name>\w+)\s+(?P<frequency>[\d.]+)(?P<current>[*\s]?)(?P<preferred>[+\s]?)/';

	/**
	 *
	 * @var string $name Name of the mode
	 */
	private $name;

	/**
	 *
	 * @var string $frequency Frequency of the mode
	 */
	private $frequency;

	/**
	 *
	 * @var boolean $current Is current mode
	 */
	private $current;

	/**
	 *
	 * @var boolean $preferred Is preferred mode
	 */
	private $preferred;

	/**
	 *
	 * @param string $name Name of the mode
	 * @param string $frequency Frequency of the mode
	 * @param boolean $current Is current mode
	 * @param boolean $preferred Is preferred mode
	 */
	public function __construct($name, $frequency, $current, $preferred) {
		$this->name = $name;
		$this->frequency = $frequency;
		$this->current = $current;
		$this->preferred = $preferred;
	}

	/**
	 * Get the name of the mode
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the frequency of the mode
	 * @return string
	 */
	public function getFrequency() {
		return $this->frequency;
	}

	/**
	 * Is the mode the currently active mode
	 * @return boolean
	 */
	public function isCurrent() {
		return $this->current;
	}

	/**
	 * Is the mode the preferred mode
	 * @return boolean
	 */
	public function isPreferred() {
		return $this->preferred;
	}

	/**
	 * Parse the name as geometry string and return resulting Geometry
	 * @return \Geometry
	 */
	public function getProbableResolution() {
		return Geometry::parseString($this->name);
	}

	/**
	 * Parse a line (from xrandr's output) containing a mode
	 * @param string $line Line to be parsed
	 * @return \Mode
	 */
	public static function parseLine($line) {
		trim($line);

		if (preg_match(Mode::LINE_REGEX, $line, $result)) {
			return new Mode($result["name"], $result["frequency"], ($result["current"] == "*") ? true : false, ($result["preferred"] == "+") ? true : false);
		}

		// ToDo: Exeption handling
		if (Xrandr::DEBUG) {
			echo "Line could not be parsed!\n";
		}
		return NULL;
	}

}
