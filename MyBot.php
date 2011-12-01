<?php

require_once 'Ants.php';

class MyBot
{
    private $ants;

    private $directions = array('n','e','s','w');
    private $orders   = array();
    private $targets  = array();
    private $unseen   = array();

    public function doSetup($ants)
    {
        /*
          self.unseen = []
          for row in range(ants.rows):
             for col in range(ants.cols):
               self.unseen.append((row, col))
         */
        $this->unseen = array();
        foreach($ants->rows as $row)
        {
            foreach($ants->cols as $col)
            {
                $this->unseen[] = array($row, $col);
            }
        }
    }
    
    // track all moves, prevent collisions
    private function do_move_direction($loc, $direction)
    {
        $new_loc = $this->ants->destination($loc, $direction);
        if ($this->ants->unoccupied($new_loc) && !$this->isLoc($this->orders, $new_loc))
        {
            $this->ants->issueOrder($loc, $direction);
            $this->addLoc($this->orders, $new_loc, $loc);
            return True;
        }
        else
        {
            return False;
        }
    }
    
    private function isLoc($arr, $loc)
    {
    	list($row, $col) = $loc;
    	return (isset($arr[$row .'-'. $col]));
    }

    private function inLoc($arr, $loc)
    {
    	return (in_array($loc ,$arr));
    }

    private function addLoc(&$arr, $order, $loc)
    {
    	list($row, $col) = $order;
        $arr[$row .'-'. $col] = $loc;
    }

    private function removeLoc(&$arr, $order)
    {
    	list($row, $col) = $order;
        unset($arr[$row .'-'. $col]);
    }

/*
        def do_move_location(loc, dest):
            directions = ants.direction(loc, dest)
            for direction in directions:
                if do_move_direction(loc, direction):
                    targets[dest] = loc
                    return True
            return False

*/
    private function do_move_location($loc, $dest)
    {
    	$directions = $this->ants->direction($loc, $dest);
        foreach($directions as $direction)
        {
            if($this->do_move_direction($loc, $direction))
            {
                $this->addLoc($this->targets, $dest, $loc);
                return TRUE;
            }
        }
        return FALSE;
    }
    
/*
        # find close food
        ant_dist = []
        for food_loc in ants.food():
            for ant_loc in ants.my_ants():
                dist = ants.distance(ant_loc, food_loc)
                ant_dist.append((dist, ant_loc, food_loc))
        ant_dist.sort()
        for dist, ant_loc, food_loc in ant_dist:
            if food_loc not in targets and ant_loc not in targets.values():
                do_move_location(ant_loc, food_loc)

*/
    public function doTurn( $ants )
    {
    	$this->orders   = array();
    	$this->targets  = array();
        $this->ants     = $ants;
/*

    # prevent stepping on own hill
    for hill_loc in ants.my_hills():
        orders[hill_loc] = None

*/

        foreach($ants->myHills as $hill_loc)
        {
            $this->removeLoc($this->orders, $hill_loc);
        }

        $ant_dist = array();
        foreach($ants->food as $food_loc)
        {
            foreach($ants->myAnts as $ant_loc)
            {
                $dist = $ants->distance($ant_loc, $food_loc);
                $ant_dist[] = array($dist, $ant_loc, $food_loc);
            }
            
        }
        asort($ant_dist);

        foreach($ant_dist as $a_dist)
        {
            list($dist, $ant_loc, $food_loc) = $a_dist;
            if(!$this->isLoc($this->targets, $food_loc) && !$this->inLoc($this->targets, $ant_loc))
            {
                $this->do_move_location($ant_loc, $food_loc);
            }
        }

/*
    # explore unseen areas
    for loc in self.unseen[:]:
        if ants.visible(loc):
            self.unseen.remove(loc)
    for ant_loc in ants.my_ants():
        if ant_loc not in orders.values():
            unseen_dist = []
            for unseen_loc in self.unseen:
                dist = ants.distance(ant_loc, unseen_loc)
                unseen_dist.append((dist, unseen_loc))
            unseen_dist.sort()
            for dist, unseen_loc in unseen_dist:
                if do_move_location(ant_loc, unseen_loc):
                    break
*/

        // TODO:



/*
    # unblock own hill
    for hill_loc in ants.my_hills():
        if hill_loc in ants.my_ants() and hill_loc not in orders.values():
            for direction in ('s','e','w','n'):
                if do_move_direction(hill_loc, direction):
                    break

*/
        foreach($ants->myHills as $hill_loc)
        {
            if($this->inLoc($ants->myAnts, $hill_loc) && !$this->isLoc($this->orders, $hill_loc))
            {
                foreach(array('s', 'e', 'w', 'n') as $direction)
                {
                    if($this->do_move_direction($hill_loc, $direction))
                    {
                        break;
                    }
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
