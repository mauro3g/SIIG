graficoTermometro = function(ubicacion, datos, colorChosen, categoryChoosen) {

    this.tipo = 'Termómetro';
    var margin = {top: 0, right: 5, bottom: 25, left: 40},
    width = 390 - margin.left - margin.right,
            height = 250 - margin.top - margin.bottom,
            barPadding = 1
            ;

    var currentDatasetChart = datos;
    var zona = ubicacion;
    
    var datasetPrincipal_bk = JSON.parse($('#' + zona).attr('datasetPrincipal_bk'));

    var numero_registros = 0;
    for(var i in datasetPrincipal_bk)
    	numero_registros +=1;

    this.dibujar = function() {
        $('#' + ubicacion + ' .grafico').html('');	
    	  var svg = d3.select("#" + ubicacion + ' .grafico')
          .append("svg:svg")
          .attr("viewBox", '0 -20 440 300')
          .attr("preserveAspectRatio", 'none')
          .attr("id", "ChartGauge");


    	  var rangos_alertas = JSON.parse($('#' + zona + ' .titulo_indicador').attr('rangos_alertas'));
    	  var arreglo = new Array();
    	  for (var i = 0; i < rangos_alertas.length; i++) {
    		  elemento = rangos_alertas[i];
    		  arreglo[i] = {"domain": [elemento.limite_inf, elemento.limite_sup], 
    			  			 "span":[0.05, 0.7] , 
    			  			 "class": elemento.color,
    			  			"customCss" : {'cssText': 'fill: '+elemento.color+' !important;'}};
    			  			
    		}
    	  var gauge = iopctrl.slider()
          .width(50)
          .transitionDuration(2000)
          .events(false)
          .bands(
        		  (rangos_alertas.lenght>0) 
        		  ? [
                    {"domain": [0, 74], "span":[0.05, 0.7] , "class": "", "customCss" : {'cssText': 'fill: red !important;'}},
                    {"domain": [75, 89], "span": [0.05, 0.7] , "class": "" , "customCss" : {'cssText': 'fill: orange !important;'}},
                    {"domain": [90, 100], "span": [0.05, 0.7] , "class": "", "customCss" : {'cssText': 'fill: green !important;'}}
                ] 
		          :
	        	    arreglo
                	)
                .indicator(iopctrl.defaultSliderIndicator)
                .ease("elastic")
                gauge.axis().orient("top")
          //.normalize(true)
          .ticks(12)
          .tickSubdivide(4)
          .tickSize(10, 8, 10)
          .tickPadding(5)
          .scale(d3.scale.linear()
                  .domain([0, 100])
                  .range([0, 400]));

    	  var segDisplay = iopctrl.segdisplay()
          .width(70)
          .digitCount(5)
          .negative(false)
          .decimals(0);

		  svg.append("g")
		          .attr("class", "segdisplay")
		          .attr("transform", "translate(130, 100)")
		          .call(segDisplay);
		
		
		  svg.append("g")
		          .attr("class", "lineargauge")
		          .call(gauge);
		  
			svg.on("click",function(){
				descenderNivelDimension(ubicacion, datasetPrincipal_bk[0].category);
			});
			
			segDisplay.value(datasetPrincipal_bk[0].measure);
			
			gauge.value(((100 >= datasetPrincipal_bk[0].measure) ? datasetPrincipal_bk[0].measure : 100));
    };
}