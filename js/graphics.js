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
gfx.getWedgePath = function(alpha, beta, r, m, centre)
{
    var basestr = 'M ~cx ~cy L ~x ~y A ~r_ ~r_ 0 ~f 0 ~sx ~sy';
    
    var r_ = r - m;
    
    var x = centre[0] + (r_ * Math.sin(alpha));
    var y = centre[1] + (r_ * Math.cos(alpha));
    var sx = centre[0] + (r_ * Math.sin(beta));
    var sy = centre[1] + (r_ * Math.cos(beta));
    
    var pathstr = basestr.replace(/~r_/g, r_)
        .replace(/~r/g, r)
        .replace(/~x/g, x)
        .replace(/~y/g, y)
        .replace(/~cx/g, centre[0])
        .replace(/~cy/g, centre[1]).replace(/~sx/g, sx)
        .replace(/~sy/g, sy)
        .replace(/~f/,  Math.abs(alpha-beta) < Math.PI ? '0' : '1');
            
      
    return pathstr;
};

/**
 *  Draw a piechart in div of the data in the array data, with radius of r pixels
 */
gfx.drawPie = function(div_id, data, r, roundel, roundel_margin)
{
    var omega = (2 * Math.PI) - 0.000001; //total angle;
    
    var total = 0;
    var labels = [];
    
    var d = r * 2;
    
    var jq = $('#' + div_id);
    
    var raph = Raphael(div_id, jq.width(), jq.height());
    
    var cx = jq.width() / 2;
    var cy = jq.height() / 2;
    
    for ( var i = data.length; i--; )
    {
        total += data[i][1];
        labels.push(data[i][0]);
    }
    
    var alpha = 0;
    
    var pieces = []
    var margin = 10;
    
    for ( var i = data.length; i--; )
    {
        var beta = this.getBeta(alpha, data[i][1], total, omega); // get end angle
        var pathstr = this.getWedgePath(alpha, beta, r, margin, [cx, cy]);
        
        var segment = raph.path(pathstr);
        segment.attr('stroke', '#000');
        segment.attr('fill', data[i].length > 2 ? data[i][2] : Raphael.getColor());
        segment.label = data[i][0];
        segment.value = data[i][1];
        
        segment.hover(function(evt){//hover On
            this._glow = this.glow({ 
                color : this.attr('fill'), 
                width : margin * 2,
                opacity : 0.8
            });
            
            console.debug(total);
            
            this.attr('stroke-width', 2);
            
            var p = evt.clientY - $('#graphOne').offset().top;
            var h = $('#graphOne svg').height();
            
            var top = ( p < r ? h - 50 : 0 );
            
            this.desc = raph.set()
            var pct = ((this.value / total) * 100).toString() ;
            pct = pct.substr(0, Math.max(pct.indexOf('.'), 0) + 3);
           
            this.desc.push( 
                raph.rect(0, top, jq.width(), 50),
                raph.text(jq.width()/2, top + 25, this.label + ' : ' + this.value + ' (' +  pct  + '%)')
            );
            
            this.desc[1].attr('fill', 'rgba(0, 0, 0, 1)');
            this.desc[1].attr('font-weight', 'bold');
            this.desc[1].attr('font-size', '14pt');
            
            this.desc[0].attr('fill', 'rgba(255, 255, 255, 0.8)');
            
        }, function(){ //hover off
            this._glow.remove();
            this.attr('stroke-width', 1);
            
            this.desc.remove();
        });
        
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
};

gfx.drawBarChart = function(div_id, data)
{
    var total = 0;
    var labels = [];
    
    var jq = $('#' + div_id);
    var max = 0;
    
    var w = jq.width(), h = jq.height();
    
    var margins = Math.max(10, Math.min(25, w * 0.05));
    var textSize = Math.max(8, Math.min(16, w * 0.05));
    
    for ( var i = data.length; i--; )
    {
        total += data[i][1];
        labels[i] = data[i][0];
        max = Math.max(max, data[i][1]);
    }
    
    var bar_width = (w - (margins * 3)) / labels.length;
    var max_height = (h - (margins * 3));
    var unit_height = max_height / max;
    
    var raph = Raphael(div_id, w, h);
    
    this.drawCatAxis(raph, labels, margins, w, h, bar_width, textSize);
    this.drawValAxis(raph, max, margins, w, h, textSize);
     
    for ( var i = data.length; i--; )
    {
        this.drawBar(raph, i, data[i][1], data[i][0], margins, data[i].length > 2 ? data[i][2] : Raphael.getColor(), bar_width, unit_height, h);
    }
};

gfx.drawCatAxis = function(raph, labels, margin, width, height, bar_w, text_size){
    var y = height - margin * 2
    var fx = (margin  * 2) // firstx
    
    var ax = raph.path('M ' + fx + ' ' + y + ' L ' + (width - margin) + ' ' + y);
    ax.attr('stroke', '#000');
    
    for( var l = labels.length; l--; )
    {
        var x = fx + bar_w * (l + 1)  ;
    
        var t = raph.path( 'M ' + x + ' ' + y + ' L ' + x  + ' ' + (y + margin));
        t.attr('stroke', '#000');
        
        var tx = raph.text(x - bar_w/2, y + margin/2, labels[l]);
        tx.attr('font-size', text_size + 'pt');
    }
    
    
};

gfx.drawValAxis = function(raph, max, margin, width, height, text_size){
    var x = margin * 2;
    var y = height - margin;
    
    var ax_height = height - margin * 3;
    
    var ax = raph.path('M ' + x + ' ' + (x - margin) + ' L ' + x + ' ' + y);
    ax.attr('stroke', '#000');
    
    var nt = 15;
    var tick_sz = 0;
   
    for ( ; !tick_sz || tick_sz % 1; nt-- )
    {
        tick_sz = max / nt;
    }
        
    nt++;
        
    var t_hgt = ax_height / nt; //height per tick
    var ax_bottom = height - margin * 2;
    
    for( var z = 1; z <= nt; z++ )
    {
        var t_val = z * tick_sz;
        var ty = ax_bottom - (t_hgt * z);
        var txs =  ( x - margin/2)
    
        var tk = raph.path('M ' + txs + ' ' + ty + ' L ' + x + ' ' + ty);
        tk.attr('stroke', '#000');
        
        var tkx = raph.text(txs, ty, t_val);
        tkx.attr('font-size', text_size + 'pt');
        tkx.attr('text-anchor', 'end');
    }
    
    
};

gfx.drawBar = function(raph, i, value, label, margin, colour, barwidth, unitheight, height){
    var padding = 5;
    var _width = barwidth  - padding * 2;
    
    var x = margin * 2;
    var y = height - x;
    
    var _left = x + i * barwidth + padding;
    var _height = unitheight * value;
            
    var _top = y - _height;
    
    var _bar = raph.rect(_left, _top, _width, _height);
    _bar.attr('fill', colour);
    _bar.label = label;
    _bar.value = value;
    
    _bar.hover(function(evt){//hover On
        this._glow = this.glow({ 
            color : this.attr('fill'), 
            width : margin,
            opacity : 0.8
        });
        
        this.attr('stroke-width', 2);
        
        var p = evt.clientY - $('#graphOne').offset().top;
        var h = $('#graphOne svg').height(), w =  $('#graphOne svg').height();
        var r = h/2;
        
        var top = ( p < r ? h - 50 : 0 );
        
        this.desc = raph.set()
        
        this.desc.push( 
            raph.rect(0, top, w, 50),
            raph.text(w/2, top + 25, this.label + ' : ' + this.value)
        );
        
        this.desc[1].attr('fill', 'rgba(0, 0, 0, 1)');
        this.desc[1].attr('font-weight', 'bold');
        this.desc[1].attr('font-size', '14pt');
        
        this.desc[0].attr('fill', 'rgba(255, 255, 255, 0.8)');
        
    }, function(){ //hover off
        this._glow.remove();
        this.attr('stroke-width', 1);
        
        this.desc.remove();
    });
}



