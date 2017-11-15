<?php
// This script will generate a key pattern
if(!is_dir('tmp') && !mkdir('tmp'))
	die('Cannot creating tmp dir');

$key_patterns	= [
	[ [ 4,10,12,6,4,4,6,14,12,6 ], [5,7,6,4,5,6,6,6,6,5,4,6,7,5] ],		// number 0
	[ [ 2,2,3,14,14,14 ], [3,4,6,6,3,3,3,3,3,3,3,3,3,3] ],				// number 1
	[ [ 3,6,8,7,6,6,8,10,8,2 ], [6,8,6,5,2,2,3,3,3,2,2,2,10,10] ],			// number 2
	[ [ 2,6,8,6,6,6,10,14,11,3 ], [6,8,6,4,2,3,4,5,3,3,6,8,8,6] ],		// number 3
	[ [ 3,4,6,5,6,5,14,14,2,2 ], [2,3,4,4,4,5,4,4,5,10,10,2,2,2] ],		// number 4
	[ [ 6,11,11,7,6,7,8,9,8 ], [8,8,2,2,3,7,9,6,2,2,4,8,7,5] ],			// number 5
	[ [ 7,11,13,9,6,6,9,12,9,3 ], [5,7,6,2,3,8,9,7,6,6,6,6,8,6] ],		// number 6
	[ [ 2,2,3,5,8,8,9,7,4,2 ], [10,10,2,3,2,2,3,2,3,3,2,3,2,3] ],			// number 7
	[ [ 1,8,13,11,8,6,9,13,11,4 ], [5,7,6,5,5,6,6,7,7,5,5,6,8,6] ],	// number 8
	[ [ 6,11,9,7,6,9,12,12,9 ], [5,7,7,5,4,4,8,8,8,3,3,6,7,6] ],		// number 9
];

$url	= "http://hj.bola88.com/Public/img.aspx?r=437035075";
$stream	= file_get_contents($url);
$name	= microtime(true);

$img	= imagecreatefromstring($stream);
$im_x	= imagesx($img);
$im_y	= imagesy($img);
$raw	= [];

// TOP LEFT is a BG COLOR
$bgcolor	= imagecolorat($img, 0, 0);

//METHOD 1 for comparissons
for ($y=0; $y < $im_y ; $y++)
{
	//if(!isset($raw[$x]))
	$raw[$y]	= '';
	for ($x=0; $x < $im_x ; $x++)
	{
		$raw[$y]	.= imagecolorat($img, $x, $y) == $bgcolor ? ' ' : '*';
	}
}
file_put_contents("tmp/$name.gif", $stream);
file_put_contents("tmp/$name.txt", "Image Size: $im_x X $im_y\nBG COLOR: $bgcolor\n" . implode("\n", $raw) . "\n");

$captured	= [];
$patterns	= [];
$results	= [];
$raw		= [];
$x_start	= false;
$y_lowest	= $im_y;
$y_highest	= 0;
$horizontal		= [];

// Setiap karakter harus memiliki satu garis vertikal pemisah

for ($x = 0; $x < $im_x ; $x++)
{
	$y_total	= 0;
	$vertical	= [];
	for ($y = 0; $y < $im_y ; $y++)
	{
		$vertical[$y] = imagecolorat($img, $x, $y) == $bgcolor ? 0 : 1;

		if( $vertical[$y] )
		{
			if( $y < $y_lowest)
			{
				$y_lowest	= $y;
			}elseif( $y > $y_highest )
			{
				$y_highest = $y;
			}
		}

		$y_total += $vertical[$y];
	}

	//Begining capture
	if($x_start === false && $y_total > 0)
	{
		$x_start	= 0;
	}

	if( $x_start !== false )
	{
		//End Capture
		if( $y_total === 0)
		{
			//preform trim, and build pattern
			$pattern_v	= [];
			$pattern_h	= [];
			foreach ($horizontal as &$v_row)
			{
				$v_row	= array_slice($v_row, $y_lowest, $y_highest - $y_lowest + 1);
				$pattern_v[]	= array_sum($v_row);

				foreach ($v_row as $k => $v)
				{
					if( !isset($pattern_h[ $k ]) ){
						$pattern_h[ $k ] = 0;
					}
					$pattern_h[ $k ] += $v;
				}
			}

			$captured[]	= $horizontal;
			$patterns[]	= [ $pattern_v, $pattern_h ];
			$scores	= [];
			// $score_horizontal = [];

			//Compute score of pattern for each number
			foreach ($key_patterns as $i => $key_pattern_vh)
			{
				$key_pattern= $key_pattern_vh[0]; // Vertical key
				$k_count	= count($key_pattern);
				$p_count	= count($pattern_v);
				$max		= $k_count > $p_count ? $k_count : $p_count;

				$scores[ $i ]		= [];
				$scores[ $i ][]		= abs($p_count - $k_count); // V key count differences

				// compute VERTICAL
				for($k_i = 0; $k_i < $max; $k_i++)
				{
					$s_k = isset($key_pattern[$k_i]) ? $key_pattern[$k_i] : 0;
					$s_p = isset($pattern_v[$k_i]) ? $pattern_v[$k_i] : 0;
					$scores[ $i ][]	= abs($s_k - $s_p);
				}

				// compute HORIZONTAL
				$key_pattern= $key_pattern_vh[1]; // Horizontal key
				$k_count	= count($key_pattern);
				$p_count	= count($pattern_h);
				$max		= $k_count > $p_count ? $k_count : $p_count;

				$scores[ $i ][]		= abs($p_count - $k_count);// H key count differences
				for($k_i = 0; $k_i < $max; $k_i++)
				{
					$s_k = isset($key_pattern[$k_i]) ? $key_pattern[$k_i] : 0 ;
					$s_p = isset($pattern_h[$k_i]) ? $pattern_h[$k_i] : 0 ;
					$scores[ $i ][]	= abs($s_k - $s_p);
				}
				
			}

			//Compute scores
			$lowest_score	= null;
			$lowest_index	= null;
			$avg_score		= [];
			foreach ($scores as $i => $score)
			{
				$avg_score[ $i ]	= array_sum($score) / count($score);
				if(is_null($lowest_score) || $avg_score[ $i ] < $lowest_score){
					$lowest_score	= $avg_score[ $i ];
					$lowest_index	= $i;
				}
				$scores[ $i ]	= implode(',', $score);
			}

			$results[]	= [
							'average'=> $avg_score,
							'lowest'=> $lowest_score,
							'match'	=> $lowest_index,
							'scores'=> $scores
						];
			//Reset
			$horizontal		= [];
			$y_lowest	= $im_y;
			$y_highest	= 0;
			$x_start	= false;

		}else{
			$horizontal[$x_start++] = $vertical;
		}
	}
}

//Draw captured, Rotate axis
foreach ($captured as $key => $horizontal)
{
	$log	= "\nchar at $key\n";
	$tmp	= [];
	foreach ($horizontal as $i => $vertical)
	{
		foreach ($vertical as $k => $value)
		{
			if(!isset($tmp[$k]))
				$tmp[$k]	= '';
			$tmp[$k]	.= $value ? '*' : ' ';
		}
	}
	$log .= implode("\n", $tmp) . "\n";
	$log .= sprintf(
					"PATTERN:\n[ [ %s ], [%s] ]\n",
					implode(',', $patterns[ $key ][0]),
					implode(',', $patterns[ $key ][1])
				);
	$log .= "\n" . json_encode($results[ $key ], JSON_PRETTY_PRINT);
	file_put_contents("tmp/$name.txt", $log, FILE_APPEND);
}