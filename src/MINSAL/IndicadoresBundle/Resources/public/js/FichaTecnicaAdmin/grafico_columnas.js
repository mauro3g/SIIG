graficoColumnas = function(ubicacion, datos, colorChosen, categoryChoosen) {

    this.tipo = 'columnas';
    var margin = {top: 0, right: 5, bottom: 25, left: 40},
    width = 390 - margin.left - margin.right,
            height = 240 - margin.top - margin.bottom,
            barPadding = 1
            ;        
    
    var currentDatasetChart = datos;
    var zona = ubicacion;
    var xScale = d3.scale.ordinal()
            .domain(currentDatasetChart.map(function(d) {
        return d.category;
    }))
            .rangeRoundBands([0, width], .1)
            ;

    var max_y;

    //El nivel máximo de la escala puede ser el mayor valor de la serie
    // o el mayor valor del rango, el usuario elige
    // se utiliza datasetPrincipal_bk por si se han aplicado filtros
    // Así no usará el máximo valor del filtro
    var datasetPrincipal_bk = JSON.parse($('#' + zona).attr('datasetPrincipal_bk'));
    max_y = d3.max(datasetPrincipal_bk, function(d) {
        return parseFloat(d.measure);
    });
    
    if ($('#' + ubicacion + ' .max_y') != null){
        if ($('#' + ubicacion + ' .max_y:checked').val() == 'rango_alertas') {
            max_y = $('#' + zona + ' .titulo_indicador').attr('data-max_rango');
        }
        else if($('#' + ubicacion + ' .max_y:checked').val() == 'fijo') {
            var max_y_fijo = $('#' + ubicacion + ' .max_y_fijo').val();
            max_y = (isNaN(max_y_fijo)) ? 100 : max_y_fijo;
        }
    }
    // Tiene meta?
    var meta = parseFloat($('#' + ubicacion ).attr("meta"));
    max_y = (meta >0 && max_y < meta) ? meta : max_y;

    var yScale = d3.scale.linear()
            .domain([0, max_y])
            .range([height, 0])
            ;

    var yAxis = d3.svg.axis().scale(yScale).orient("left").ticks(5);
    var xAxis = d3.svg.axis().scale(xScale).orient("bottom");
    
    // Dibuja el gráfico
    this.dibujar = function() {
        $('#' + ubicacion + ' .grafico').html('');
            var svg = d3.select("#" + ubicacion + ' .grafico')
                .append("svg")
                .attr("viewBox", '-20 -20 440 310')
                .attr("preserveAspectRatio", 'none')
                .attr("id", "ChartPlot")
                ;

        var plot = svg
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + 5 + ")")
                ;
        var long = $('#' + ubicacion + ' .titulo_indicador').attr('data-unidad-medida').length;

        svg.append("g")
                .attr("class", "axis")
                .attr("transform", "translate(" + margin.left + ",5)")                
                .call(yAxis)
                .append("text")
                .attr("transform", "rotate(-90)")
                .attr("y", -50)                
                .attr("x", -((height / 2) + (long / 2) * 6.5))
                .text($('#' + ubicacion + ' .titulo_indicador').attr('data-unidad-medida'));       

        svg.append("g")
                .attr("class", "x axis")
                .attr("transform", "translate(" + margin.left + "," + (margin.top + height + 5) + ")")
                .call(xAxis);
        
        if ($('#sala_default').val()==0){
            var duracion = 1000;
            var retraso = 20;
        }else {
            var duracion = 0;
            var retraso = 0;
        }
        
        plot.selectAll("rect")
                .data(currentDatasetChart)
                .enter()
                .append("rect")
                .attr("height",0)
                .attr("y",height)
                .attr("x", function(d, i) {
                    return xScale(d.category);
                })                
                .attr("width", xScale.rangeBand())
                .transition().duration(duracion).delay(retraso)
                .ease("cubic-in-out")
                .attr("y", function(d) {
                    return yScale(parseFloat(d.measure));
                })
                .attr("height", function(d) {
                    return height - yScale(parseFloat(d.measure));
                })
        ;
        
        plot.selectAll("rect").append("title")
                .text(function(d) {
            return d.category + ": " + d.measure;
        });
        plot.selectAll("text")
            .data(currentDatasetChart)
            .enter()
            .append("text")
            .attr("class","label_barra")
            .text(function(d) 
            {
                return d.measure;
            })			
            .attr('x', function(d,i){return (i)*(width/currentDatasetChart.length)+(width/currentDatasetChart.length)/2;})
            .attr('y',height)
            .transition().duration(duracion).delay(retraso)
            .attr('y', function(d){return (height-((height*d.measure)/max_y))-5})
            .attr('text-anchor', 'middle')
            .style("font-family", "Arial, Helvetica, sans-serif")			
            .attr('font-size', 14)
            .attr('fill', '#000')
        ;                
        
        if(meta>0)
        {
            svg.append("line")
                .attr("x1", 40)
                .attr("y1", height-((height*meta)/max_y)+5)
                .attr("x2", width+50)
                .attr("y2", height-((height*meta)/max_y)+5)
                .attr("stroke-width", 1)
                .style("stroke-dasharray",("5","5"))
                .attr("stroke", "steelblue");
        }
        
        plot.selectAll("rect").on("click", function(d, i) {
            descenderNivelDimension(ubicacion, d.category);
        });
        if (colorChosen == null)
            plot.selectAll("rect").attr("fill", function(d, i) {
                //evaluar que color le corresponde
                return colores_alertas(ubicacion, d.measure, i)
            });
        else
            plot.selectAll("rect").attr("fill", colorChosen);
    };
    this.ordenar = function(modo_orden, ordenar_por) {
        
        var svg = d3.select("#" + zona + ' .grafico');
        
        var datos_ordenados = ordenarArreglo(currentDatasetChart, ordenar_por, modo_orden);
        var x0 = xScale.domain(datos_ordenados.map(function(d) {
                return d.category;
            })).copy();

        if ($('#sala_default').val()==0){
            var transition = svg.transition().duration(750);
            var delay = function(d, i) {
                return i * 40;
            };
        }else { 
            var transition = svg.transition().duration(0);
            var delay = 0;
        }
        
        transition.selectAll("#"+ubicacion+" rect")
            .delay(delay)
            .attr("x", function(d) {
                return x0(d.category);
            })
        ;
        transition.select('#'+ubicacion+' .x.axis')
                .call(xAxis)
                .selectAll("g")
                .delay(delay);
        
        transition.selectAll("#"+ubicacion+" .label_barra")
            .attr("x", function(d) {
                return x0(d.category)+(width/currentDatasetChart.length)/2;
            })
        ;
        // Ordenar la tabla de datos
        $('#' + zona).attr('datasetPrincipal', JSON.stringify(currentDatasetChart));
    };
}