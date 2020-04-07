<?php


namespace Game\Battleship;

require_once 'Ship.php';

final class Cruiser extends Ship {

    function __construct() {
        parent::__construct("Cruiser", 3);
    }

}