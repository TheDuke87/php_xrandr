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

use Exception;

/**
 * Represents the xrandr utility
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
class Xrandr
{
    public const CVT_BIN = 'cvt';
    public const DEBUG = false;
    public const TIMEOUT_BIN = 'timeout';
    public const XRANDR_BIN = 'xrandr';
    private $commandLineBuilder;
    private $outputs;
    private $raw;
    private $screens;

    /**
     * @param array $raw Raw xrandr output for testing
     */
    public function __construct($commandBuilderMode = false, $raw = null)
    {
        $this->raw = $raw;

        if ($commandBuilderMode) {
            $this->commandLineBuilder = new CommandLineBuilder();
        }

        $this->parseRaw();
    }

    /**
     * Parsed raw xrandr output and builds lists for screens and outputs
     *
     * @return bool
     * @throws Exception
     */
    public function parseRaw()
    {
        $this->screens = array();
        $this->outputs = array();
        $currentOutput = null;
        $outputIndex = 0;

        $raw = $this->getRaw();
        if ($raw === null) {
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
                    $currentOutput = Output::parseLine($outputIndex++, $line, $this->commandLineBuilder);
                    $this->outputs[$currentOutput->getName()] = $currentOutput;
                    break;
                // Mode
                case preg_match(Mode::LINE_REGEX, $line, $result):
                    if (!isset($currentOutput)) {
                        throw new Exception("parseRawException: Mode line but no currentOutput\n$line");
                    }
                    $currentOutput->addExistingMode(Mode::parseLine($line));
                    break;
                default:
                    // ToDo: Exeption handling
                    if (self::DEBUG) {
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
     * @return bool
     */
    private function refreshRaw()
    {
        exec(self::TIMEOUT_BIN . ' --signal=KILL 10s ' . self::XRANDR_BIN . ' --query 2>/dev/null', $output,
            $exitcode);

        if ($exitcode !== 0) {
            return false;
        }

        $this->raw = $output;

        return true;
    }

    /**
     * Add a custom mode to an output
     *
     * @param Output $output
     * @param Mode   $mode
     *
     * @return bool
     */
    public function addMode($output, $mode)
    {
        exec(self::XRANDR_BIN . " --addmode {$output->getName()} {$mode->getName()}", $output, $exitcode);

        if ($exitcode !== 0) {
            return false;
        }

        return 0;
    }

    /**
     * Execute commands collected by CommandLineBuilder
     *
     * @return bool
     */
    public function executeCommands()
    {
        if ($this->commandLineBuilder) {
            return self::executeCommand($this->commandLineBuilder->getCommandLine());
        }

        return false;
    }

    /**
     * Execute a xrandr command
     *
     * @param $command
     *
     * @return bool
     */
    public static function executeCommand($command)
    {
        exec(self::XRANDR_BIN . " {$command}", $output, $exitcode);

        if (self::DEBUG) {
            echo self::XRANDR_BIN . " {$command}\n";

            if (!empty($output)) {
                $output = implode("\n\t", $output);
                echo "\t{$output}\n";
            }
        }

        return $exitcode === 0;
    }

    /**
     * Get list of active output names
     *
     * @return string[]
     */
    public function getActiveOutputNames()
    {
        $outputs = $this->getActiveOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_keys($outputs);
    }

    /**
     * Get list of connected, active outputs, keyed by name
     *
     * @return Output[]
     */
    public function getActiveOutputs()
    {
        $outputs = $this->getOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_filter(
            $outputs, static function ($e) {
            return $e->isActive();
        }
        );
    }

    /**
     * Get list of outputs, keyed by name
     *
     * @return Output[]
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * Get list of connected output names
     *
     * @return string[]
     */
    public function getActiveSecondaryOutputNames()
    {
        $outputs = $this->getActiveSecondaryOutputNames();

        if ($outputs === null) {
            return null;
        }

        return array_keys($outputs);
    }

    /**
     * Get list of connected, active, non-primary outputs, keyed by name
     *
     * @return Output[]
     */
    public function getActiveSecondaryOutputs()
    {
        $outputs = $this->getActiveOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_filter(
            $outputs, static function ($e) {
            return !$e->isPrimary();
        }
        );
    }

    /**
     * Get list of connected output names
     *
     * @return string[]
     */
    public function getConnectedOutputNames()
    {
        $outputs = $this->getConnectedOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_keys($outputs);
    }

    /**
     * Get list of connected outputs, keyed by name
     *
     * @return Output[]
     */
    public function getConnectedOutputs()
    {
        $outputs = $this->getOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_filter(
            $outputs, static function ($e) {
            return $e->isConnected();
        }
        );
    }

    /**
     * Get list of connected, non-primary output names
     *
     * @return string[]
     */
    public function getConnectedSecondaryOutputNames()
    {
        $outputs = $this->getConnectedSecondaryOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_keys($outputs);
    }

    /**
     * Get list of connected, non-primary outputs, keyed by name
     *
     * @return Output[]
     */
    public function getConnectedSecondaryOutputs()
    {
        $outputs = $this->getConnectedOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_filter(
            $outputs, static function ($e) {
            return !$e->isPrimary();
        }
        );
    }

    /**
     * Get list of disconnected output names
     *
     * @return string[]
     */
    public function getDisconnectedOutputNames()
    {
        $outputs = $this->getDisconnectedOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_keys($outputs);
    }

    /**
     * Get list of disconnected outputs, keyed by name
     *
     * @return Output[]
     */
    public function getDisconnectedOutputs()
    {
        $outputs = $this->getOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_filter(
            $outputs, static function ($e) {
            return !$e->isConnected();
        }
        );
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
     * @return Screen[]
     */
    public function getScreens()
    {
        return $this->screens;
    }

    /**
     * Get output with coordinates 0+0
     *
     * @return Output
     */
    public function getOutputAtZeroPoint()
    {
        $outputs = $this->getActiveOutputs();

        if ($outputs === null) {
            return null;
        }

        $result = array_values(array_filter(
            $outputs, static function ($e) {
            return ($e->getGeometry()->x === 0) && ($e->getGeometry()->y === 0);
        }
        ));

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * Get list of output names
     *
     * @return string[]
     */
    public function getOutputNames()
    {
        $outputs = $this->getOutputs();

        if ($outputs === null) {
            return null;
        }

        return array_keys($outputs);
    }

    /**
     * Get primary output
     *
     * @return Output
     */
    public function getPrimaryOutput()
    {
        $outputs = $this->getConnectedOutputs();

        if ($outputs === null) {
            return null;
        }

        $result = array_values(array_filter(
            $outputs, static function ($e) {
            return $e->isPrimary();
        }
        ));

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isCommandBuilderMode()
    {
        return $this->commandLineBuilder !== null;
    }

    /**
     * Add a new custom mode
     *
     * @param Geometry $geometry
     * @param int      $frequency
     *
     * @return Mode|string|bool
     */
    public function newMode($geometry, $frequency = null)
    {
        if (!isset($frequency)) {
            $frequency = 60;
        }

        exec(self::CVT_BIN . " {$geometry->width} {$geometry->height} {$frequency}", $output, $exitcode);

        if ($exitcode !== 0) {
            return false;
        }

        $outputArray = explode(' ', $output[1], 3);
        exec(self::XRANDR_BIN . " --newmode {$outputArray[1]} {$outputArray[2]}", $output, $exitcode);
        if ($exitcode !== 0) {
            return $outputArray[1];
        }

        return new Mode($outputArray[1], $frequency);
    }

    /**
     * Refresh xrandr output and parse it
     */
    public function refresh()
    {
        if ($this->commandLineBuilder) {
            $this->commandLineBuilder->clearCommands();
        }

        if ($this->refreshRaw()) {
            $this->parseRaw();
        }
    }

    /**
     * Automatically configure outputs based on preferred values
     *
     * @return bool
     */
    public function setAuto()
    {
        exec(self::XRANDR_BIN . ' --auto', $output, $exitcode);

        return $exitcode === 0;
    }
}
