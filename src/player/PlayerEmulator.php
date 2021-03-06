<?php


namespace Game\Battleship;

require_once __DIR__ . '/../gameunit/Utils.php';
require_once __DIR__ . '/../items/ShipFactory.php';
require_once __DIR__ . '/../listeners/ReadyListener.php';
require_once __DIR__ . '/../positioning/ShipLocation.php';
require_once __DIR__ . '/../states/GameState.php';
require_once 'RandomAttackStrategy.php';

use Exception;

class PlayerEmulator {

    private $shipsToPlace;

    private $gameUnit;

    private $attackStrategy;

    public function __construct(GameUnit $gameUnit) {
        $this->gameUnit = $gameUnit;
        $this->shipsToPlace = Constants::$DEFAULT_SHIPS_TO_PLACE;
        $this->attackStrategy = new RandomAttackStrategy($this->gameUnit);
    }

    function placeShips() {
        foreach ($this->shipsToPlace as $shipName) {
            $this->searchForShipLocation($shipName);
        }
    }

    private function searchForShipLocation($shipName) {
        $tries = 0;
        while (true) {
            try {
                $shipFactory = new ShipFactory($shipName);
                $shipLocation = $this->getRandomShipLocation();
                error_log('Add ship: ' . $shipName. ' location:' . (string)$shipLocation);
                $ship = $shipFactory->buildWithLocation($shipLocation);
                $this->gameUnit->placeShip($ship);
                break;
            } catch (Exception $exception) {
                error_log('Error: '.$exception->getMessage() . ' trying again');
                if ($tries === $this->getMaxTries()) {
                    throw new Exception('Unable to place ship ' . $shipName);
                }
                $tries++;
            }
        };
        // TODO: If unable to place ship, reset board and try again
    }

    protected function getMaxTries() {
        return 10;
    }

    function makeShot() {
        $this->attackStrategy->makeShot();
    }

    public function setAttackStrategy(AttackStrategy $attackStrategy) {
        $this->attackStrategy = $attackStrategy;
    }

    protected function getRandomLocation() : Location {
        $availableTargetPositions = $this->gameUnit->getFreeAvailableTargetPositions();
        $index = Utils::getRandomNumberFromTo(0, sizeof($availableTargetPositions) - 1);
        $letter = $availableTargetPositions[$index][0];
        $column = $availableTargetPositions[$index][1];
        return new Location($letter, $column);
    }

    protected function getRandomShipLocation() {
        return Utils::getRandomShipLocation();
    }

    protected function getGameUnit(): GameUnit {
        return $this->gameUnit;
    }
}