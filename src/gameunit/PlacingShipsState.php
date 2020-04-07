<?php


namespace Game\Battleship;

require_once 'GameState.php';
require_once 'GameStateFactory.php';

class PlacingShipsState implements GameState {

    private $originalShipsToPlace;

    private $shipsToPlace;

    private $gameController;

    private $current;

    private $opponent;

    public function __construct(GameController $gameController, GameUnit $current, GameUnit $opponent) {
        $this->gameController = $gameController;
        $this->current = $current;
        $this->opponent = $opponent;
        $this->shipsToPlace = [Carrier::NAME, Destroyer::NAME, Submarine::NAME, Battleship::NAME];
        $this->originalShipsToPlace = $this->shipsToPlace;
    }

    function setShipsToPlace($shipsToPlace) {
        $this->shipsToPlace = $shipsToPlace;
        $this->originalShipsToPlace = $shipsToPlace;
    }

    function placingShips(Ship $ship) {
        $this->validateShipIsAllowedToBePlaced($ship);

        $this->current->placeShip($ship);

        if (($key = array_search($ship->getName(), $this->shipsToPlace)) !== false) {
            unset($this->shipsToPlace[$key]);
        }

        // TODO: if not set next -> nextState is to randomize start or CallingShot with random user start

        if (sizeof($this->shipsToPlace) == 0) {
            $this->setNextState(GameStateFactory::makePlacingShipsStateFactory($this->gameController, $this->opponent, $this->current));
        }
    }

    private function isShipInArray($shipName, $shipArray) {
        return (array_search($shipName, $shipArray)) !== false;
    }

    function callingShot(Location $location) {
        throw new GameStateException('Not calling shots yet');
    }

    private function setNextState(GameState $nextGameState) {
        $this->gameController->setState($nextGameState);
    }

    /**
     * @param Ship $ship
     * @throws NotAllowedShipException
     */
    private function validateShipIsAllowedToBePlaced(Ship $ship): void {
        if (!$this->isShipInArray($ship->getName(), $this->originalShipsToPlace)) {
            throw new NotAllowedShipException('Ship ' . $ship->getName() . ' not allowed');
        }
    }
}