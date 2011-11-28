<?php

require_once 'Ants.php';

class MyBot
{
    private $ants;

    private $directions = array('n','e','s','w');
    private $orders = array();
    private $targets = array();

    // track all moves, prevent collisions
    private function do_move_direction($loc, $direction)
    {
        $new_loc = $this->ants->destination($loc, $direction);
        if ($this->ants->unoccupied($new_loc) && !$this->inOrder($new_loc))
	{
	    $this->ants->issueOrder($loc, $direction);
            $this->addOrder($new_loc, $loc);
            return True;
	}
        else
	{
            return False;
	}

    }

    private function inOrder($loc)
    {
    	list($row, $col) = $loc;
    	return (isset($this->orders[$row][$col]));
    }

    private function addOrder($order, $loc)
    {
    	list($row, $col) = $order;
	$this->orders[$row][$col] = $loc;
    }


    public function doTurn( $ants )
    {
    	$this->orders = array();
    	$this->ants = $ants;
        foreach ( $ants->myAnts as $ant_loc )
	{
            foreach ($this->directions as $direction)
	    {
                if ($this->do_move_direction($ant_loc, $direction))
		{
		   break;
		}
            }
        }
    }
    
}

/**
 * Don't run bot when unit-testing
 */
if( !defined('PHPUnit_MAIN_METHOD') ) {
    Ants::run( new MyBot() );
}
