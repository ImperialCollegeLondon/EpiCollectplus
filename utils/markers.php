<?php

function getMapMaker($colour)
{
	$svg = '<?xml version="1.0" encoding="utf-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="18px" height="32px">
	<g transform="translate(-249.0035,-635.15474)">';
	$svg .= "
		<path d=\"m 257.05406,667.10508 -6.93654,-18.7525 c -6.62806,-17.91854 20.2264,-17.1758 13.87308,0 z\" style=\"fill:$colour;stroke:#000000;\" />
	</g></svg>";
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
			
			$svg .= "<path style=\"fill:{$colours[$i]};stroke:black\" d=\" 
				M 24 24 
				L $sx $sy
				A 22 22 0 " . ($omega < pi() ? "0" : "1") . " 0 ";
			
			$sx = 24 + (22 * sin($theta)); 
			$sy = 24 + (22 * cos($theta)); 	
			
			$svg .= " $sx $sy z\" />";
		}
	}
	else
	{
		$svg .= "<circle style=\"fill: {$colours[0]};stroke:black;\" cx=\"24\" cy=\"24\" r=\"24\" />";
	}
		
	$svg .= "
		<circle style=\"fill: #FFFFFF;stroke:black;\" cx=\"24\" cy=\"24\" r=\"16\" />
		<text x=\"24\" y=\"30\" style=\"text-anchor: middle;font-size:16px;font-weight:bold;font-family: Helvetica;\">$ttl</text></g></svg>";
	return $svg;
}

?>