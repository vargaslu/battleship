<?php


namespace Game\Battleship;

require_once __DIR__.'/../items/ShipFactory.php';
require_once __DIR__.'/../states/GameState.php';
require_once 'GameUtils.php';

use Exception;

class PlayerEmulator {

    private $gameUtils;

    private $shipsToPlace;

    private $gameUnit;

    private $listener;

    public function __construct(GameUnit $gameUnit) {
        $this->gameUnit = $gameUnit;
        $this->gameUtils = new GameUtils();
        $this->shipsToPlace = GameConstants::$DEFAULT_SHIPS_TO_PLACE;
    }

    final function addPropertyChangeListener(PropertyChangeListener $listener) {
        $this->listener = $listener;
        return $this;
    }

    function placeShips() {
        foreach ($this->shipsToPlace as $shipName) {
            $this->searchForLocation($shipName);
        }

        $this->listener->fireUpdate($this->gameUnit, ReadyListener::READY, true);
    }

    private function searchForLocation($shipName) {
        do {
            try {
                $shipFactory = new ShipFactory($shipName);
                $location = $this->gameUtils->getRandomLocation();
                $direction = $this->gameUtils->getRandomDirection();
                $ship = $shipFactory->buildWithLocation($location, $direction);
                $this->gameUnit->placeShip($ship);
                break;
            } catch (Exception $exception) {
            }
        } while (0);
    }
}