<?php

function getMapMaker($colour, $shape)
{
    if(preg_match('/^[0-9A-Fa-f]{3,6}$/', $colour))
    {
        $colour = '#' . $colour;
    }

	$svg = '<?xml version="1.0" encoding="utf-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="22px" height="32px">
	<g';
	if($shape == "o")
	{
		$svg .=  sprintf(' transform="scale(0.875,0.875)">
		<path d="m 7.6957238,19.423424 0.0756,5.6365 -3.74754,0 5.49508,2.9665 5.4950902,-2.9665 -3.74755,0 -0.0217,-5.6365 z M 19.361974,9.8699238 c 0,5.2423002 -4.24972,9.4921002 -9.49203,9.4921002 -5.2423002,0 -9.49202021,-4.2498 -9.49202021,-9.4921002 0,-5.2423 4.24972001,-9.49200001 9.49202021,-9.49200001 5.24231,0 9.49203,4.24970001 9.49203,9.49200001 z" style="fill:%s;stroke:#000000;" />
		</g></svg>', $colour);
	}
	elseif($shape == 's')
	{
		$svg .= sprintf(' transform="scale(0.875,0.875)">
		<path d="m 8.4065538,19.652324 0.0756,5.6364 -3.74754,0 5.4950802,2.9665 5.49508,-2.9665 -3.74754,0 -0.0217,-5.6364 z m 11.0668702,-0.1789 0,-19.09550021 -19.09550021,0 0,19.09550021 z" style="fill:%s;stroke:#000000;" />
		</g>
	</svg>', $colour);
	}
	elseif($shape == 't')
	{
		$svg .=  sprintf(' transform="scale(0.875,0.875)">
		<path d="m 8.0652938,19.614224 0.0755,5.6364 -3.74754,0 5.4950802,2.9665 5.49508,-2.9665 -3.74754,0 -0.0217,-5.6364 z M 19.475034,19.475024 9.6979238,0.37792379 0.37792379,19.475024 z" style="fill:%s;stroke:#000000;" />
		</g></svg>', $colour);
	}
	elseif($shape == 'x')
	{
		$svg .=  sprintf(' transform="scale(0.875,0.875)">
		<path d="m 8.0578338,16.096624 0.0755,8.6416 -3.74754,0 5.4950802,2.9665 5.49508,-2.9665 -3.74754,0 -0.0217,-8.6416 -1.8651602,-1.37 z m 1.68951,-1.4428 6.7350302,4.8184 -2.39503,-7.2828 5.38488,-4.4856002 -6.61077,0 -3.1141102,-7.32590001 -3.39156,7.32590001 -5.97786001,0 4.99984001,4.4856002 -2.03277,7.2828 z" style="fill:%s;stroke:#000000;" />
		</g></svg>', $colour);
	}
	else
	{
		$svg .=  sprintf(' transform="translate(0.875,0.875)">
			<path d="m 7.6957238,19.423424 0.0756,5.6365 -3.74754,0 5.49508,2.9665 5.4950902,-2.9665 -3.74755,0 -0.0217,-5.6365 z M 19.361974,9.8699238 c 0,5.2423002 -4.24972,9.4921002 -9.49203,9.4921002 -5.2423002,0 -9.49202021,-4.2498 -9.49202021,-9.4921002 0,-5.2423 4.24972001,-9.49200001 9.49202021,-9.49200001 5.24231,0 9.49203,4.24970001 9.49203,9.49200001 z" style="fill:%s;stroke:#000000;" />
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
			
			$svg .= sprintf('<path style="fill:#%s;stroke:black" d=" 
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
		$svg .= sprintf('<circle style="fill:#%s;stroke:black;" cx="24" cy="24" r="24" />',$colours[0]);
	}
		
	$svg .= sprintf('
		<circle style="fill: #FFFFFF;stroke:black;" cx="24" cy="24" r="16" />
		<text x="24" y="30" style="text-anchor: middle;font-size:16px;font-weight:bold;font-family: Helvetica;">%s</text></g></svg>', $ttl);
	return $svg;
}

?>