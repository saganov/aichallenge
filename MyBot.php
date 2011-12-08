<?php

require_once 'Ants.php';

class MyBot
{
    private $ants;

    private $directions = array('n','e','s','w');
    private $orders   = array();
    private $targets  = array();
    private $unseen   = array();
    private $hills    = array();

    public function doSetup($ants)
    {
    	$this->hills = array();
        /*
          self.unseen = []
          for row in range(ants.rows):
             for col in range(ants.cols):
               self.unseen.append((row, col))
         */
        $this->unseen = array();
        for($row=0; $row<$ants->rows; $row++)
        {
	    for($col=0; $col<$ants->cols; $col++)
            {
                $this->unseen[] = array($row, $col);
            }
        }
    }

/*
        # track all moves, prevent collisions
        orders = {}
        def do_move_direction(loc, direction):
            new_loc = ants.destination(loc, direction)
            if (ants.unoccupied(new_loc) and new_loc not in orders):
                ants.issue_order((loc, direction))
                orders[new_loc] = loc
                return True
            else:
                return False

*/
    private function do_move_direction($loc, $direction)
    {
        $new_loc = $this->ants->destination($loc, $direction);
        if ($this->ants->unoccupied($new_loc) && !$this->isLoc($this->orders, $new_loc))
        {
            $this->ants->issueOrder($loc, $direction);
            $this->addLoc($this->orders, $new_loc, $loc);
	    //$this->addLoc($this->orders, $loc, $new_loc);
	    list($nx,$ny) = $new_loc;
	    list($ox,$oy) = $loc;
	    $this->ants->debug('[do_move_direction: issueOrder: store order: [%s-%s] = (%s , %s)]', array($nx, $ny, $ox, $oy));
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
	/*
	$idx = array_search($order, $arr);
	unset($arr[$idx]);
	*/
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
		//list($x,$y) = $loc;
		//$this->ants->debug('do_move_location: (%s : %s) -> %s', array($x, $y, $direction));
                $this->addLoc($this->targets, $dest, $loc);
                return TRUE;
            }
        }
        return FALSE;
    }
    
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
            //$this->removeLoc($this->orders, $hill_loc);
	    $this->addLoc($this->orders, $hill_loc, FALSE);
	    list($x, $y) = $hill_loc;
	    $this->ants->debug('prevent stepping on my hill: add to order: [%s-%s] = true', array($x, $y));
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

	foreach($ant_dist as $elm)
        {
            list($dist, $ant_loc, $food_loc) = $elm;
            if(!$this->isLoc($this->targets, $food_loc) && !$this->inLoc($this->targets, $ant_loc))
            {
                $this->do_move_location($ant_loc, $food_loc);
		list($x,$y) = $ant_loc;
		list($fx,$fy) = $food_loc;
		$this->ants->debug('find close food: (%s : %s) -> (%s : %s)', array($x, $y, $fx, $fy));
            }
        }


/*
        # attack hills
        for hill_loc, hill_owner in ants.enemy_hills():
            if hill_loc not in self.hills:
                self.hills.append(hill_loc)        
        ant_dist = []
        for hill_loc in self.hills:
            for ant_loc in ants.my_ants():
                if ant_loc not in orders.values():
                    dist = ants.distance(ant_loc, hill_loc)
                    ant_dist.append((dist, ant_loc, hill_loc))
        ant_dist.sort()
        for dist, ant_loc, hill_loc in ant_dist:
            do_move_location(ant_loc, hill_loc)

*/
	foreach($ants->enemyHills as $hill_loc)
	{
	    if(!$this->inLoc($this->hills, $hill_loc))
	    {
	        $this->hills[] = $hill_loc;
	    }
	}
	$ant_dist = array();
	foreach($this->hills as $hill_loc)
	{
	    foreach($ants->myAnts as $ant_loc)
	    {
	        if(!$this->inLoc($this->orders, $ant_loc))
		{
		    $dist = $ants->distance($ant_loc, $hill_loc);
		    $ant_dist[] = array($dist, $ant_loc, $hill_loc);
		}
	    }
	}
	asort($ant_dist);
	foreach($ant_dist as $elm)
	{
	    list($dist, $ant_loc, $hill_loc) = $elm;
	    $this->do_move_location($ant_loc, $hill_loc);
	    list($ax, $ay) = $ant_loc;
	    list($hx, $hy) = $hill_loc;
	    $ants->debug('attack hills: ant (%s, %s) to hill (%s, %s)', array($ax, $ay, $hx, $hy));
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
	$unseen_tmp = $this->unseen;
	foreach($unseen_tmp as $idx=>$loc)
	{
		if($ants->visible($loc))
		{
			unset($this->unseen[$idx]);
		}
	}
	unset($unseen_tmp);

	foreach($ants->myAnts as $ant_loc)
	{
		// TODO: there was isLoc
		if(!$this->inLoc($this->orders, $ant_loc))
		{
			$unseen_dist = array();
			foreach($this->unseen as $unseen_loc)
			{
				$dist = $ants->distance($ant_loc, $unseen_loc);
				$unseen_dist[]=array($dist, $unseen_loc, $ant_loc);
			}
			asort($unseen_dist);
			foreach($unseen_dist as $elm)
			{
				list($dist, $unseen_loc, $ant_loc) = $elm;
				if($this->do_move_location($ant_loc, $unseen_loc))
				{
				    list($ax, $ay) = $ant_loc;
				    list($ux, $uy) = $unseen_loc;
				    $ants->debug('explore unseen areas: ant (%s, %s) to area (%s, %s)', array($ax, $ay, $ux, $uy));
					break;
				}
			}
		}
	}



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
            if($this->inLoc($ants->myAnts, $hill_loc) && !$this->inLoc($this->orders, $hill_loc))
            {
                foreach(array('s', 'e', 'w', 'n') as $direction)
                {
                    list($x,$y) = $hill_loc;
		    $this->ants->debug('try to unblock own hills: (%s : %s) -> %s', array($x, $y, $direction));

                    if($this->do_move_direction($hill_loc, $direction))
                    {
			list($x,$y) = $hill_loc;
			$this->ants->debug('unblock own hills: (%s : %s) -> %s', array($x, $y, $direction));
                        break;
                   }
                }
            }
	}
	

/*
        # default move
        for ant_loc in ants.my_ants():
            directions = ('n','e','s','w')
            for direction in directions:
                if do_move_direction(ant_loc, direction):
                    break

*/
/*
	foreach($ants->myAnts as $ant_loc)
	{
	    foreach(array('n', 'e', 's', 'w') as $direction)
	    {
	        if($this->do_move_direction($ant_loc, $direction))
		{
		    break;
		}
	    }
	}
*/
    }
    
}

/**
 * Don't run bot when unit-testing
 */
if( !defined('PHPUnit_MAIN_METHOD') ) {
    Ants::run( new MyBot() );
}
