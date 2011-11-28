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
	list ($aRow, $aCol) = $loc;
        $new_loc = $this->ants->destination($aRow, $aCol, $direction);
	list($nRow, $nCol) = $new_loc;
        if ($this->ants->unoccupied($nRow, $nCol) && !isset($this->orders[$nRow][$nCol]))
	{
	    $this->ants->issueOrder($aRow, $aCol, $direction);
            $this->orders[$nRow][$nCol] = $loc;
            return True;
	}
        else
	{
            return False;
	}

    }


    public function doTurn( $ants )
    {
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
