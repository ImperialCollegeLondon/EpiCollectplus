/** 
  * Library to use Raphael to perform graphical work to EpiCollect
  */
var gfx = function(){};

/**
 * get the SVG path string to draw part of a pie chart from angle alpha to angle beta with radius r
 *
 * @param alpha double Start angle in radians
 * @param beta double End angle in radians
 * @param r int radius in pixels
 */
gfx.getWedgePath = function(alpha, beta, r)
{
    var basestr = 'M ~r ~r L ~x ~y A ~r ~r 0 ~f 0 ~sx ~sy';
    
    var x = r + (r * Math.sin(alpha));
    var y = r + (r * Math.cos(alpha));
    var sx = r + (r * Math.sin(beta));
    var sy = r + (r * Math.cos(beta));
    
    var pathstr = basestr.replace(/~r/g, r)
        .replace(/~x/g, x)
        .replace(/~y/g, y)
        .replace(/~sx/g, sx)
        .replace(/~sy/g, sy)
        .replace(/~f/,  Math.abs(alpha-beta) < Math.PI ? '0' : '1');
            
    return pathstr;
};

/**
 *  Draw a piechart in div of the data in the array data, with radius of r pixels
 */
gfx.drawPie = function(div_id, data, r, roundel, roundel_margin)
{
    var omega = 2 * Math.PI; //total angle;
    
    var total = 0;
    var labels = [];
    
    var d = r * 2;
    
    var raph = Raphael(div_id, d, d);
    
    for ( var i = data.length; i--; )
    {
        total += data[i][1];
        labels.push(data[i][0]);
    }
    
    var alpha = 0;
    
    var pieces = []

    for ( var i = data.length; i--; )
    {
        var beta = this.getBeta(alpha, data[i][1], total, omega); // get end angle
        var pathstr = this.getWedgePath(alpha, beta, r);
        
        var segment = raph.path(pathstr);
        segment.attr('stroke', '#000');
        segment.attr('fill', data[i].length > 2 ? data[i][2] : Raphael.getColor());
        
        alpha = beta;
        
    }
    
    if(roundel)
    {
        if(!roundel_margin) roundel_margin = r * 0.2;
        this.drawRoundel(raph, r - roundel_margin, r, total); 
    }
};

/**
  * Takes an omega so you could potentially make semi-cicular graphs
  */
gfx.getBeta = function(alpha, n, N, omega)
{
    return alpha + ((n / N) * omega);
};

gfx.drawRoundel = function(raph, r, c, text)
{
    var circ = raph.circle(c, c, r);
    circ.attr('fill', '#FFF');
    circ.attr('stroke', '#000');
    
    var txt = raph.text(c, c, text);
    txt.attr('fill', '#000');
    txt.attr('font-size', r);
    txt.attr('font-weight', 'bold');
    
    
}

gfx.getMarkerPath = function(shape)
{
    if( shape === "x" )
    {
        return "m 8.0578338,16.096624 0.0755,8.6416 -3.74754,0 5.4950802,2.9665 5.49508,-2.9665 -3.74754,0 -0.0217,-8.6416 -1.8651602,-1.37 z m 1.68951,-1.4428 6.7350302,4.8184 -2.39503,-7.2828 5.38488,-4.4856002 -6.61077,0 -3.1141102,-7.32590001 -3.39156,7.32590001 -5.97786001,0 4.99984001,4.4856002 -2.03277,7.2828 z";
    }
    else if( shape === 's' )
    {
        return "m 8.4065538,19.652324 0.0756,5.6364 -3.74754,0 5.4950802,2.9665 5.49508,-2.9665 -3.74754,0 -0.0217,-5.6364 z m 11.0668702,-0.1789 0,-19.09550021 -19.09550021,0 0,19.09550021 z";
    }
    else if( shape ==='t' )
    {   
        return "m 8.0652938,19.614224 0.0755,5.6364 -3.74754,0 5.4950802,2.9665 5.49508,-2.9665 -3.74754,0 -0.0217,-5.6364 z M 19.475034,19.475024 9.6979238,0.37792379 0.37792379,19.475024 z";
    }
    else
    {
        return "m 7.6957238,19.423424 0.0756,5.6365 -3.74754,0 5.49508,2.9665 5.4950902,-2.9665 -3.74755,0 -0.0217,-5.6365 z M 19.361974,9.8699238 c 0,5.2423002 -4.24972,9.4921002 -9.49203,9.4921002 -5.2423002,0 -9.49202021,-4.2498 -9.49202021,-9.4921002 0,-5.2423 4.24972001,-9.49200001 9.49202021,-9.49200001 5.24231,0 9.49203,4.24970001 9.49203,9.49200001 z";
    }
};   

gfx.makeRichMarker = function(lat, lng, content)
{
    var ll = new google.maps.LatLng(lat, lng);
    mkr = new RichMarker({ 
        position : ll, 
        map : map,
        content : content,
        shadow : false,
        flat: true
    });
    return mkr;
}

gfx.createMarker = function(lat, lng, colour, shape)
{
    var mkr = this.makeRichMarker(lat, lng, this.getMarkerContent(colour, shape));
    return mkr;
};

gfx.getMarkerContent = function(colour, shape)
{
    var mkr_div_id = shape + colour.replace('#', '_').replace(/[\(\)]/g, '_');

    if(!$('#' + mkr_div_id).length)
    {
        $(document.body).append('<div id="' + mkr_div_id + '"></div>');
        var r = Raphael(mkr_div_id, 32, 32);
        var c = r.path(this.getMarkerPath(shape));
        c.attr('fill',  colour);
        c.attr('stroke', '#000');
        $('#' + mkr_div_id).hide(); 
    }
    
    return  $('#' + mkr_div_id).html();
};

gfx.drawPieMarker = function(lat, lng, data, r)
{
    $(document.body).append('<div id="gfx_pie"></div>');
    
    var mkr_div = $('#gfx_pie');
    
    this.drawPie('gfx_pie', data, r, true);
  
    var mkr = this.makeRichMarker(lat, lng, mkr_div.html());
    
    mkr_div.remove();
    
    return mkr;
}
