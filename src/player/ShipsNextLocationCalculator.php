<?php


namespace Game\Battleship;

require_once __DIR__ . '/../positioning/Direction.php';
require_once __DIR__ . '/../positioning/LocationUtils.php';

class ShipsNextLocationCalculator {

    private const HORIZONTAL_RIGHT = 1;

    private const HORIZONTAL_LEFT = -1;

    private const VERTICAL_UP = -2;

    private const VERTICAL_DOWN = 2;

    private $shipsLocationQueue;

    private $shipsLives;

    private $currentIndex;

    private $gameUnit;

    public function __construct(GameUnit $gameUnit) {
        $this->gameUnit = $gameUnit;
        $this->shipsLocationQueue = [];
        $this->shipsLives = [];
        $this->currentIndex = 1;
    }

    public function createCalculations($shipName, Location $foundLocation, $size) {
        if (!$this->existsInQueue($shipName)) {
            $this->shipsLives[$shipName] = $size - 1;
            $this->shipsLocationQueue[$shipName] = [$foundLocation];
            $this->registerFourPossibleLocationsAroundFoundLocation($this->shipsLocationQueue[$shipName],
                                                                    $foundLocation);
            $this->removeLocationIfFoundInFirstShipQueue($foundLocation);
        }
    }

    private function removeLocationIfFoundInFirstShipQueue() {
        if (sizeof($this->shipsLocationQueue) > 1) {
            $this->removeCurrentLocation();
        }
    }

    private function registerFourPossibleLocationsAroundFoundLocation(&$shipLocations,
                                                                      Location $location) {
        $this->tryAddDecreasedLocationForDirection($shipLocations, $location, Direction::VERTICAL);

        $this->tryAddDecreasedLocationForDirection($shipLocations, $location, Direction::HORIZONTAL);

        $this->tryAddIncreasedLocationForDirection($shipLocations, $location, Direction::VERTICAL);

        $this->tryAddIncreasedLocationForDirection($shipLocations, $location, Direction::HORIZONTAL);
    }

    private function tryAddDecreasedLocationForDirection(&$shipLocations,
                                                         Location $location,
                                                         $direction,
                                                         $times = 1,
                                                         $reThrowException = false) {
        try {
            $newLocation = $location;
            for ($i = 0; $i < $times; $i++) {
                $newLocation = LocationUtils::decrease($newLocation, $direction);
                $this->addWhenTargetLocationsIsFree($shipLocations, $newLocation);
            }
        } catch (InvalidLocationException $exception) {
            if ($reThrowException) {
                throw $exception;
            }
        }
    }

    private function tryAddIncreasedLocationForDirection(&$shipsLocations,
                                                         Location $location,
                                                         $direction,
                                                         $times = 1,
                                                         $reThrowException = false) {
        try {
            $newLocation = $location;
            for ($i = 0; $i < $times; $i++) {
                $newLocation = LocationUtils::increase($newLocation, $direction);
                $this->addWhenTargetLocationsIsFree($shipsLocations, $newLocation);
            }
        } catch (InvalidLocationException $exception) {
            if ($reThrowException) {
                throw $exception;
            }
        }
    }

    private function addWhenTargetLocationsIsFree(&$shipLocations, Location $newLocation) {
        if ($this->gameUnit->isTargetLocationMarked($newLocation)) {
            throw new InvalidLocationException();
        }
        //$firstShipValues = &$this->getFirstShipValues();
        array_push($shipLocations, $newLocation);
    }

    public function hitShip($shipName) {
        if ($this->existsInQueue($shipName)) {
            $this->shipsLives[$shipName]--;

            $this->recalculateNextPossibleLocations();

            $this->currentIndex++;

            $this->destroyShip($shipName);
        }
    }

    public function existsInQueue($shipName) : bool {
        return array_key_exists($shipName, $this->shipsLocationQueue);
    }

    private function recalculateNextPossibleLocations() {
        $startingLocation= $this->getFirstLocation();
        $currentLocation = $this->getCurrentLocation();
        $direction = $this->getNextLocationsDirection($startingLocation, $currentLocation);
        $firstShipValues = &$this->getFirstShipValues();

        array_splice($firstShipValues, $this->currentIndex + 1);
        try {
            $currentLocation = $this->getCurrentLocation();
            $this->fillWithNextLocationsInTheCorrectDirection($firstShipValues, $currentLocation, $direction);
        } catch (InvalidLocationException $exception) {
            $turnAroundDirection = $this->getTurnAroundDirection($direction);
            $firstLocation = $this->getFirstLocation();
            $this->fillWithNextLocationsInTheCorrectDirection($firstShipValues, $firstLocation, $turnAroundDirection);
        }
    }

    private function getTurnAroundDirection($direction) {
        switch ($direction) {
            case self::HORIZONTAL_RIGHT:
                return self::HORIZONTAL_LEFT;
            case self::HORIZONTAL_LEFT:
                return self::HORIZONTAL_RIGHT;
            case self::VERTICAL_UP:
                return self::VERTICAL_DOWN;
            case self::VERTICAL_DOWN:
                return self::VERTICAL_UP;
        }
    }

    private function fillWithNextLocationsInTheCorrectDirection(&$shipLocations, Location $fromLocation, $direction) {
        $times = $this->getCurrentSize();
        if (self::HORIZONTAL_LEFT === $direction) {
            $this->tryAddDecreasedLocationForDirection($shipLocations, $fromLocation, Direction::HORIZONTAL, $times, true);
        } elseif (self::HORIZONTAL_RIGHT === $direction) {
            $this->tryAddIncreasedLocationForDirection($shipLocations, $fromLocation, Direction::HORIZONTAL, $times, true);
        } elseif (self::VERTICAL_UP === $direction) {
            $this->tryAddDecreasedLocationForDirection($shipLocations, $fromLocation, Direction::VERTICAL, $times, true);
        } elseif (self::VERTICAL_DOWN === $direction) {
            $this->tryAddIncreasedLocationForDirection($shipLocations, $fromLocation, Direction::VERTICAL, $times, true);
        }
    }

    public function removeCurrentLocation() {
        if (!isset($this->shipsLocationQueue) || (sizeof($this->shipsLocationQueue) === 0)) {
            return;
        }
        $firstShipValues = &$this->getFirstShipValues();
        unset($firstShipValues[$this->currentIndex]);
        $firstShipValues = array_values($firstShipValues);
    }

    private function getFirstLocation() : Location {
        $firstShipValues = $this->getFirstShipValues();
        return $firstShipValues[0];
    }

    public function getCurrentLocation() : Location {
        $firstShipValues = $this->getFirstShipValues();
        return $firstShipValues[$this->currentIndex];
    }

    private function getNextLocationsDirection(Location $firstLocation, Location $secondLocation) : int {
        $firstColumn = $firstLocation->getColumn();
        $firstLetter = ord($firstLocation->getLetter());
        $secondColumn = $secondLocation->getColumn();
        $secondLetter = ord($secondLocation->getLetter());
        if ($firstLocation->getColumn() == $secondLocation->getColumn()) {
            return ($firstLetter - $secondLetter) > 0 ? self::VERTICAL_UP : self::VERTICAL_DOWN;
        } elseif ($firstLocation->getLetter() == $secondLocation->getLetter()) {
            return ($firstColumn - $secondColumn) > 0 ? self::HORIZONTAL_LEFT : self::HORIZONTAL_RIGHT;
        } else {
            throw new InvalidLocationException('Invalid locations to verify: '
                                               .(string)$firstLocation.' '.(string)$secondLocation);
        }
    }

    public function getCurrentSize() : int {
        if (sizeof($this->shipsLocationQueue) === 0) {
            return -1;
        }
        $firstShipName = array_keys($this->shipsLocationQueue)[0];
        return $this->shipsLives[$firstShipName];
    }

    public function getNumberOfStoredShips() {
        return sizeof($this->shipsLocationQueue);
    }

    public function __toString() {
        if (sizeof($this->shipsLocationQueue) === 0) {
            return '[]';
        }

        $firstShipValues = $this->getFirstShipValues();
        $resultString = '[ ';
        $resultString .= implode(', ', $firstShipValues);
        $resultString = rtrim($resultString, ', ');
        $resultString .= ' ]';
        return $resultString;
    }

    private function &getFirstShipValues() : array {
        $firstShipName = array_keys($this->shipsLocationQueue)[0];
        return $this->shipsLocationQueue[$firstShipName];
    }

    private function destroyShip($shipName): void {
        if ($this->shipsLives[$shipName] === 0) {
            unset($this->shipsLocationQueue[$shipName]);
            unset($this->shipsLives[$shipName]);
            $this->currentIndex = 1;
        }
    }
}