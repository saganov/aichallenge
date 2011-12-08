<?php

define('MY_ANT', 0);
define('ANTS', 0);
define('DEAD', -1);
define('LAND', -2);
define('FOOD', -3);
define('WATER', -4);
define('UNSEEN', -5);

class Ants
{
    public $turns = 0;
    public $rows = 0;
    public $cols = 0;
    public $loadtime = 0;
    public $turntime = 0;
    public $viewradius2 = 0;
    public $attackradius2 = 0;
    public $spawnradius2 = 0;
    public $map;
    public $myAnts = array();
    public $enemyAnts = array();
    public $myHills = array();
    public $enemyHills = array();
    public $deadAnts = array();
    public $food = array();
    public $vision = NULL;

    public $AIM = array(
        'n' => array(-1, 0),
        'e' => array(0, 1),
        's' => array(1, 0),
        'w' => array(0, -1) );
    public $RIGHT = array (
        'n' => 'e',
        'e' => 's',
        's' => 'w',
        'w' => 'n');
    public $LEFT = array (
        'n' => 'w',
        'e' => 'n',
        's' => 'e',
        'w' => 's');
    public $BEHIND = array (
        'n' => 's',
        's' => 'n',
        'e' => 'w',
        'w' => 'e'
        );

    private $vision_ofsets_2 = array();
    public static $turn = 0;	

    public function issueOrder($loc, $direction)
    {
    	list($aRow, $aCol) = $loc;
        printf("o %s %s %s\n", $aRow, $aCol, $direction);
        flush();
    }

    public function debug($format, array $args = array())
    {
	file_put_contents('game_logs/debug.log', self::$turn .' :: '. vsprintf($format, $args) ."\n", FILE_APPEND);
    }

    public function finishTurn()
    {
        echo("go\n");
        flush();
    }
    
    public function setup($data)
    {
        foreach ( $data as $line) {
            if (strlen($line) > 0) {
                $tokens = explode(' ',$line);
                $key = $tokens[0];
                if (property_exists($this, $key)) {
                    $this->{$key} = (int)$tokens[1];
                }
            }
        }
        for ( $row=0; $row < $this->rows; $row++) {
            for ( $col=0; $col < $this->cols; $col++) {
                $this->map[$row][$col] = LAND;
            }
        }
    }

    /** not tested */


    public function update($data)
    {
	$this->vision = NULL;

        // clear ant and food data
        foreach ( $this->myAnts as $ant ) {
            list($row,$col) = $ant;
            $this->map[$row][$col] = LAND;
        }
        $this->myAnts = array();

        foreach ( $this->enemyAnts as $ant ) {
            list($row,$col) = $ant;
            $this->map[$row][$col] = LAND;
        }
        $this->enemyAnts = array();

        foreach ( $this->deadAnts as $ant ) {
            list($row,$col) = $ant;
            $this->map[$row][$col] = LAND;
        }
        $this->deadAnts = array();

        foreach ( $this->food as $ant ) {
            list($row,$col) = $ant;
            $this->map[$row][$col] = LAND;
        }
        $this->food = array();
        
        $this->myHills = array();
        $this->enemyHills = array();

        # update map and create new ant and food lists
        foreach ( $data as $line) {
            if (strlen($line) > 0) {
                $tokens = explode(' ',$line);

                if (count($tokens) >= 3) {
                    $row = (int)$tokens[1];
                    $col = (int)$tokens[2];
                    if ($tokens[0] == 'a') {
                        $owner = (int)$tokens[3];
                        $this->map[$row][$col] = $owner;
                        if( $owner === 0) {
                            $this->myAnts []= array($row,$col);
                        } else {
                            $this->enemyAnts []= array($row,$col);
                        }
                    } elseif ($tokens[0] == 'f') {
                        $this->map[$row][$col] = FOOD;
                        $this->food []= array($row, $col);
                    } elseif ($tokens[0] == 'w') {
                        $this->map[$row][$col] = WATER;
                    } elseif ($tokens[0] == 'd') {
                        if ($this->map[$row][$col] === LAND) {
                            $this->map[$row][$col] = DEAD;
                        }
                        $this->deadAnts []= array($row,$col);
                    } elseif ($tokens[0] == 'h') {
                        $owner = (int)$tokens[3];
                        if ($owner === 0) {
                            $this->myHills []= array($row,$col);
                        } else {
                            $this->enemyHills []= array($row,$col);
                        }
                    }
                }
            }
        }
    }


    public function passable($loc)
    {
    	list($row, $col) = $loc;
        return $this->map[$row][$col] > WATER;
    }

    public function unoccupied($loc) {
    	list($row, $col) = $loc;
        return in_array($this->map[$row][$col], array(LAND, DEAD));
    }

    public function destination($loc, $direction)
    {
    	list($row, $col) = $loc;
        list($dRow, $dCol) = $this->AIM[$direction];
        $nRow = ($row + $dRow) % $this->rows;
        $nCol = ($col +$dCol) % $this->cols;
        if ($nRow < 0) $nRow += $this->rows;
        if ($nCol < 0) $nCol += $this->cols;
        return array( $nRow, $nCol );
    }

    public function distance($loc1, $loc2) {
	list($row1, $col1) = $loc1;
	list($row2, $col2) = $loc2;

	$dRow = abs($row1 - $row2);
        $dCol = abs($col1 - $col2);

        $dRow = min($dRow, $this->rows - $dRow);
        $dCol = min($dCol, $this->cols - $dCol);

        return sqrt($dRow * $dRow + $dCol * $dCol);
    }

    public function direction($loc1, $loc2) {
    	list($row1, $col1) = $loc1;
	list($row2, $col2) = $loc2;

        $d = array();
        $row1 = $row1 % $this->rows;
        $row2 = $row2 % $this->rows;
        $col1 = $col1 % $this->cols;
        $col2 = $col2 % $this->cols;

        if ($row1 < $row2) {
            if ($row2 - $row1 >= $this->rows/2) {
                $d []= 'n';
            }
            if ($row2 - $row1 <= $this->rows/2) {
                $d []= 's';
            }
        } elseif ($row2 < $row1) {
            if ($row1 - $row2 >= $this->rows/2) {
                $d []= 's';
            }
            if ($row1 - $row2 <= $this->rows/2) {
                $d []= 'n';
            }
        }
        if ($col1 < $col2) {
            if ($col2 - $col1 >= $this->cols/2) {
                $d []= 'w';
            }
            if ($col2 - $col1 <= $this->cols/2) {
                $d []= 'e';
            }
        } elseif ($col2 < $col1) {
            if ($col1 - $col2 >= $this->cols/2) {
                $d []= 'e';
            }
            if ($col1 - $col2 <= $this->cols/2) {
                $d []= 'w';
            }
        }
        return $d;

    }

/*
    def visible(self, loc):
        ' determine which squares are visible to the given player '

        if self.vision == None:
            if not hasattr(self, 'vision_offsets_2'):
                # precalculate squares around an ant to set as visible
                self.vision_offsets_2 = []
                mx = int(sqrt(self.viewradius2))
                for d_row in range(-mx,mx+1):
                    for d_col in range(-mx,mx+1):
                        d = d_row**2 + d_col**2
                        if d <= self.viewradius2:
                            self.vision_offsets_2.append((
                                # Create all negative offsets so vision will
                                # wrap around the edges properly
                                (d_row % self.rows) - self.rows,
                                (d_col % self.cols) - self.cols
                            ))
            # set all spaces as not visible
            # loop through ants and set all squares around ant as visible
            self.vision = [[False]*self.cols for row in range(self.rows)]
            for ant in self.my_ants():
                a_row, a_col = ant
                for v_row, v_col in self.vision_offsets_2:
                    self.vision[a_row + v_row][a_col + v_col] = True
        row, col = loc
        return self.vision[row][col]

*/
    public function visible($loc)
    {
    	if(!isset($this->vision))
	{
	    if(empty($this->vision_offsets_2))
	    {
	        $this->vision_offsets_2 = array();
		$mx = (int)sqrt($this->viewradius2);
		foreach(range(-$mx, $mx+1) as $d_row)
		{
		    foreach(range(-$mx, $mx+1) as $d_col)
		    {
		        $d = $d_row*$d_row + $d_col*$d_col;
			if($d <= $this->viewradius2)
			{
			    $this->vision_offsets_2[] = array(
			    	($d_row % $this->rows) - $this->rows,
			    	($d_col % $this->cols) - $this->cols
			    );
			}
		    }
		}
	    }
	    for($row=0; $row<$this->rows; $row++)
	    {
	    	for($col=0; $col<$this->cols; $col++)
		{
		    $this->vision[$row][$col] = FALSE;
		}

	    }
	    foreach($this->myAnts as $ant)
	    {
	    	list($a_row, $a_col) = $ant;
		foreach($this->vision_ofsets_2 as $elm)
		{
		    list($v_row, $v_col) = $elm;
		    $this->vision[$a_row+$v_row][$a_col+$v_col] = TRUE;
		}
	    }

	}
	list($row, $col) = $loc;
	return $this->vision[$row][$col];
    }

    public static function run($bot)
    {
        $ants = new Ants();
        $map_data = array();
        while(true) {
            $current_line = fgets(STDIN,1024);
            $current_line = trim($current_line);
            if ($current_line === 'ready') {
                $ants->setup($map_data);
                $bot->doSetup($ants);
                $ants->finishTurn();
                $map_data = array();
            } elseif ($current_line === 'go') {
	    	self::$turn++;
                $ants->update($map_data);
                $bot->doTurn($ants);
                $ants->finishTurn();
                $map_data = array();
            } else {
                $map_data []= $current_line;
            }
        }

    }
}
