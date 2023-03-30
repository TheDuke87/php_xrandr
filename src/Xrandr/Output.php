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
 * Represents an output (monitor connector)
 *
 * @author René Vögeli <rvoegeli@vhtec.de>
 */
class Output
{
    /**
     * <name> <dis/connected> <primary> <resolution> <rotation> <reflection> (normal left inverted right x axis y axis)
     * <physicalWidth>mm x <physicalHeight>mm
     * eDP1 connected primary 1360x768+0+0 (normal left inverted right x axis y
     * axis) 344mm x 193mm DVI-I-1 connected primary 1360x768+0+0 (normal left inverted right x axis y axis) 344mm x
     * 193mm DP1 disconnected (normal left inverted right x axis y axis) VGA1 connected primary 1920x1200+0+0 ROTATION
     * REFLECTION ([AVAILABLE ROTATIONS] [AVAILABLE REFLECTIONS]) 519mm x 324mm panning %dx%d+%d+%d tracking %dx%d+%d+%d
     * border %d/%d/%d/%d
     *
     * Named Subpatterns: http://php.net/manual/en/function.preg-match.php Example #4
     *
     * @note Panning, Tracking and Border are not supported, yet
     * @note Available rotations and reflections are not being parsed, yet
     * @todo Regex is incomplete since some features are not needed, yet. Complete line is available above, extracted
     *       from xrandr.c
     */
    public const LINE_REGEX = '/^(?P<name>[\w-]+) (?P<connected>(dis)?connected)\s?(?P<primary>primary)?\s?(?P<geometry>[x+\-\d]+)?\s?(?P<rotation>(normal|left|right|inverted))?\s?(?P<reflection>X?\s?(and)?\s?Y? axis)?\s?(\(normal left inverted right x axis y axis\))?\s?((?P<physicalWidth>\d+)mm x (?P<physicalHeight>\d+)mm)?$/';

    /**
     * @var CommandLineBuilder $commandLineBuilder
     */
    private $commandLineBuilder;

    /**
     *
     * @var boolean $connected Is connected
     */
    private $connected;

    /**
     *
     * @var Geometry $geometry Output geometry
     */
    private $geometry;

    /**
     *
     * @var int $index Index of the output in alphabetical order
     */
    private $index;

    /**
     *
     * @var array $modes List of modes
     */
    private $modes;

    /**
     *
     * @var string $name Name of the output
     */
    private $name;

    /**
     *
     * @var int $physicalHeight Output physical height
     */
    private $physicalHeight;

    /**
     *
     * @var int $physicalWidth Output physical width
     */
    private $physicalWidth;

    /**
     *
     * @var boolean $primary Is primary
     */
    private $primary;

    /**
     *
     * @var string $reflection Output reflection
     */
    private $reflection;

    /**
     *
     * @var string $rotation Output rotation
     */
    private $rotation;

    /**
     * @param int      $index          Index of output
     * @param string   $name           Name of output
     * @param boolean  $connected      Is currently connected
     * @param boolean  $primary        Is primary output
     * @param Geometry $geometry       Output geometry
     * @param string   $rotation       Output rotation
     * @param string   $reflection     Output reflection
     * @param int      $physicalWidth  Output physical width
     * @param int      $physicalHeight Output physical height
     */
    public function __construct(
        $index,
        $name,
        $connected,
        $primary = false,
        $geometry = null,
        $rotation = '',
        $reflection = '',
        $physicalWidth = 0,
        $physicalHeight = 0,
        $commandLineBuilder = null
    ) {
        $this->index = $index;
        $this->name = $name;
        $this->connected = $connected;
        $this->primary = $primary;
        $this->geometry = $geometry;
        $this->rotation = $rotation;
        $this->reflection = $reflection;
        $this->physicalWidth = $physicalWidth;
        $this->physicalHeight = $physicalHeight;
        $this->commandLineBuilder = $commandLineBuilder;
    }

    /**
     * Parse a line (from xrandr's output) containing an output
     *
     * @param int    $index Index
     * @param string $line  Line to be parsed
     *
     * @return Output
     * @todo Exception handling
     */
    public static function parseLine($index, $line, $commandLineBuilder = null)
    {
        if (preg_match(self::LINE_REGEX, $line, $result)) {
            return new Output($index, $result['name'], $result['connected'] === 'connected',
                isset($result['primary']) && $result['primary'] === 'primary',
                isset($result['geometry']) ? Geometry::parseString($result['geometry']) : null,
                !empty($result['rotation']) ? $result['rotation'] : Rotation::NORMAL,
                !empty($result['reflection']) ? Reflection::parseString($result['reflection']) : Reflection::NORMAL,
                isset($result['physicalWidth']) ? $result['physicalWidth'] : 0,
                isset($result['physicalHeight']) ? $result['physicalHeight'] : 0,
                $commandLineBuilder);
        }

        // ToDo: Exeption handling
        if (Xrandr::DEBUG) {
            echo "Output line could not be parsed!\n";
        }

        return null;
    }

    /**
     * Add an existing Mode to the output (used by parser)
     *
     * @param Mode $mode
     */
    public function addExistingMode($mode)
    {
        $this->modes[$mode->getName()] = $mode;
    }

    /**
     * Get the output name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Clear all modes from the list
     */
    public function clearExistingModes()
    {
        $this->modes = array();
    }

    /**
     * Get the output index
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Get all mode names
     *
     * @return string[]
     */
    public function getModeNames()
    {
        $modes = $this->getModes();

        if ($modes === null) {
            return null;
        }

        return array_keys($modes);
    }

    /**
     * Get list of modes attached to the output
     *
     * @return Mode[]
     */
    public function getModes()
    {
        return $this->modes;
    }

    /**
     * Get the output physical height
     *
     * @return int
     */
    public function getPhysicalHeight()
    {
        return $this->physicalHeight;
    }

    /**
     * Get the output physical width
     *
     * @return int
     */
    public function getPhysicalWidth()
    {
        return $this->physicalWidth;
    }

    /**
     * Get the preferred mode
     *
     * @return Mode
     */
    public function getPreferredMode()
    {
        $modes = $this->getModes();

        if ($modes === null) {
            return null;
        }

        $result = array_values(array_filter(
            $modes, static function ($e) {
            return $e->isPreferred();
        }
        ));

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * Get the output reflection
     *
     * @return string
     */
    public function getReflection()
    {
        return $this->reflection;
    }

    /**
     * Set the output reflection
     *
     * @param string $reflection Reflection to be set (use Reflection enum)
     *
     * @return bool
     */
    public function setReflection($reflection)
    {
        return $this->executeCommand("--reflect {$reflection}");
    }

    /**
     * Executes a command on the outputor adds it to the command line builder
     *
     * @param string $command Command to be executed
     *
     * @return bool
     */
    private function executeCommand($command)
    {
        $command = "--output {$this->name} {$command}";

        if ($this->commandLineBuilder) {
            $this->commandLineBuilder->addCommand($command);

            return true;
        }

        return Xrandr::executeCommand($command);
    }

    /**
     * Get the output rotation
     *
     * @return string
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * Set the output rotation
     *
     * @param string $rotation Rotation to be set (use Rotation enum)
     *
     * @return bool
     */
    public function setRotation($rotation)
    {
        return $this->executeCommand("--rotate {$rotation}");
    }

    /**
     * Is the output currently connected
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Is the output the primary output
     *
     * @return bool
     */
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * Set this output as primary
     *
     * @return bool
     */
    public function setPrimary()
    {
        return $this->executeCommand('--primary');
    }

    /**
     * Reset scale-from to current mode's resolution
     *
     * @return bool
     */
    public function resetScaleFrom()
    {
        $nativeGeometry = $this->getCurrentMode()->getProbableResolution();

        if ($nativeGeometry === null) {
            return false;
        }

        return $this->executeCommand('--scale-from ' . $nativeGeometry->getResolutionString());
    }

    /**
     * Get the currently active mode
     *
     * @return Mode
     */
    public function getCurrentMode()
    {
        $modes = $this->getModes();

        if ($modes === null) {
            return null;
        }

        $result = array_values(array_filter(
            $modes, static function ($e) {
            return $e->isCurrent();
        }
        ));

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * Set the output mode
     *
     * @param Mode $mode Mode to be switched to
     *
     * @return bool
     */
    public function setMode($mode)
    {
        return $this->executeCommand("--mode {$mode->getName()}");
    }

    /**
     * Set the output mode to 'auto'
     *
     * @return bool
     */
    public function setModeAuto()
    {
        return $this->executeCommand('--auto');
    }

    /**
     * Set the output mode to 'off'
     *
     * @return bool
     */
    public function setModeOff()
    {
        return $this->executeCommand('--off');
    }

    /**
     * Set the output mode to 'preferred'
     *
     * @return bool
     */
    public function setModePreferred()
    {
        return $this->executeCommand('--preferred');
    }

    /**
     * Set output position relative to another output
     *
     * @param string $position    Position to be set (use Position enum)
     * @param Output $otherOutput Other output to position relative to
     *
     * @return bool
     */
    public function setPosition($position, $otherOutput)
    {
        return $this->executeCommand("--{$position} {$otherOutput->getName()}");
    }

    /**
     * Set output position by Geometry
     *
     * @param Geometry $geometry Geometry to be used for positioning
     *
     * @return bool
     */
    public function setPositionByGeometry($geometry)
    {
        /** @var Geometry $geometry */
        return $this->executeCommand("--pos {$geometry->x}x{$geometry->y}");
    }

    /**
     * Set output scale
     *
     * @param Geometry $resolution Resolution to be scaled to
     *
     * @return bool
     */
    public function setScale($resolution)
    {
        return $this->executeCommand('--scale ' . $resolution->getResolutionString());
    }

    /**
     * Set output scale-from
     *
     * @param Geometry $resolution Resolution to be scaled to
     *
     * @return bool
     */
    public function setScaleFrom($resolution)
    {
        return $this->executeCommand('--scale-from ' . $resolution->getResolutionString());
    }

    /**
     * Set output scale-from output
     *
     * @param Output $output Active output the resolution is to be scaled to
     *
     * @return bool
     */
    public function setScaleFromOutput($output)
    {
        if (!$output->isActive()) {
            return false;
        }

        return $this->executeCommand('--scale-from ' . $output->getGeometry()->getResolutionString());
    }

    /**
     * Is the output active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->getCurrentMode() !== null;
    }

    /**
     * Get the output geometry
     *
     * @return Geometry
     */
    public function getGeometry()
    {
        return $this->geometry;
    }

    /**
     * Set output transformation matrix
     *
     * @param double $a
     * @param double $b
     * @param double $c
     * @param double $d
     * @param double $e
     * @param double $f
     * @param double $g
     * @param double $h
     * @param double $i
     *
     * @return bool
     */
    public function setTransform($a, $b, $c, $d, $e, $f, $g, $h, $i)
    {
        return $this->executeCommand("--transform {$a},{$b},{$c},{$d},{$e},{$f},{$g},{$h},{$i}");
    }
}
