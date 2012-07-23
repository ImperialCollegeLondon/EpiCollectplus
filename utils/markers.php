<?php

function getMapMaker($colour, $shape)
{
	$svg = '<?xml version="1.0" encoding="utf-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="22px" height="32px">
	<g';
	if($shape == "o")
	{
		$svg .=  sprintf(' transform="scale(0.875,1)">
		<path d="m 8.6507596,19.059154 3.6618004,13.2583 0,0 c 0,0 0.11204,-0.758 4.16688,-13.132 C 29.92728,12.836554 23.84467,0.58955438 12.00274,0.50155438 0.25632963,0.32565438 -5.0857004,15.129754 8.6507596,19.059154 z" style="fill:%s;stroke:#000000;" />
		</g></svg>', $colour);
	}
	elseif($shape == 's')
	{
		$svg .= sprintf(' transform="scale(0.875,1)">
		<path d="m 8.64436,19.2509 3.53553,12.3744 0,0 4.16688,-12.3744 7.89181,0 0,-18.7509 -23.73858,0 0,18.7509 z" style="fill:%s;stroke:#000000;" />
		</g>
	</svg>', $colour);
	}
	elseif($shape == 't')
	{
		$svg .=  sprintf(' transform="scale(0.875,1)">
		<path d="m 8.64436,19.3456 3.44082,12.406 4.04062,-12.406 8.11278,0 L 12.08518,0.5 0.5,19.3456 z" style="fill:%s;stroke:#000000;" />
		</g></svg>', $colour);
	}
	elseif($shape == 'x')
	{
		$svg .=  sprintf(' transform="scale(0.875,1)">
		<path d="m 11.65051,31.4991 3.4645,-14.6472 5.9741,2.8726 L 16.82753,12.5903 23.24077,7.9357 15.36754,8.062 11.65051,0.5 7.61947,8.062 0.5,8.062 l 5.95467,4.5283 -3.5483,7.2605 5.21818,-2.9989 z" style="fill:%s;stroke:#000000;" />
		</g></svg>', $colour);
	}
	else
	{
		$svg .=  sprintf(' transform="translate(-249.0035,-635.15474)">
			<path d="m 257.05406,667.10508 -6.93654,-18.7525 c -6.62806,-17.91854 20.2264,-17.1758 13.87308,0 z" style="fill:%s;stroke:#000000;" />
		</g></svg>', $colour);
	}
	return $svg;
}

function getGroupMarker($colours, $counts)
{
	if(is_string($colours)) $colours = array($colours);
	if(is_string($counts)) $colours = array($counts);
	
	$svg = '<?xml version="1.0" encoding="utf-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="48px" height="48px">
	<g>';
	
	$ttl = 0;
	$sx = 24;
	$sy = 46;
	$theta = 0;
	
	for($i = 0; $i < count($colours); $i++)
	{
		$ttl += $counts[$i];
	}
	
	if(count($colours) > 1)
	{
		for($i = 0; $i < count($colours); $i++)
		{
			$omega = ($counts[$i] / $ttl) * 2 * pi();
			$theta +=  $omega;// chart slice angle
			
			$svg .= sprintf('<path style="fill:%s;stroke:black" d=" 
				M 24 24 
				L %s %s
				A 22 22 0 %s 0 ', $colours[$i], $sx, $sy, ($omega < pi() ? "0" : "1")) ;
			
			$sx = 24 + (22 * sin($theta)); 
			$sy = 24 + (22 * cos($theta)); 	
			
			$svg .= sprintf(' %s %s z" />', $sx, $sy);
		}
	}
	else
	{
		$svg .= sprintf('<circle style="fill:%s;stroke:black;" cx="24" cy="24" r="24" />',$colours[0]);
	}
		
	$svg .= sprintf('
		<circle style="fill: #FFFFFF;stroke:black;" cx="24" cy="24" r="16" />
		<text x="24" y="30" style="text-anchor: middle;font-size:16px;font-weight:bold;font-family: Helvetica;">%s</text></g></svg>', $ttl);
	return $svg;
}

?>