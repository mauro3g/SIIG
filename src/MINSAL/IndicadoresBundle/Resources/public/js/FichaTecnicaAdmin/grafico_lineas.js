graficoLineas = function(ubicacion, datos, colorChosen, categoryChoosen) {

    this.tipo = 'lineas';
    var margin = {top: 20, right: 5, bottom: 20, left: 70},
    width = 390 - margin.left - margin.right,
            height = 250 - margin.top - margin.bottom
            ;
    var currentDatasetChart = datos;
    var zona = ubicacion;

    var xScale = d3.scale.ordinal()
            .domain(currentDatasetChart.map(function(d) {
        return d.category;
    }))
            .rangeRoundBands([0, width], .9);
    ;
    var max_y;
    var datasetPrincipal_bk = JSON.parse($('#' + zona).attr('datasetPrincipal_bk'));
    max_y = d3.max(datasetPrincipal_bk, function(d) {
        return parseFloat(d.measure);
    });
    if ($('#' + ubicacion + ' .max_y') != null && $('#' + ubicacion + ' .max_y').val() == 'rango_alertas')
        max_y = $('#' + ubicacion + ' .titulo_indicador').attr('data-max_rango');

    var yScale = d3.scale.linear()
            .domain([0, max_y])
            .range([height, 0]);

    var yAxis = d3.svg.axis().scale(yScale).orient("left").ticks(5);
    var xAxis = d3.svg.axis().scale(xScale).orient("bottom");

    var line = d3.svg.line()
        .x(function(d, i) {
            return xScale(d.category);
        })
        .y(function(d) {
            return yScale(parseFloat(d.measure));
        });
    
    var line2 = d3.svg.line()			
        .x(function(d, i)  {
            return xScale(d.category);
        })
        .y(function(d) {
            return yScale(0);
        });

    this.dibujar = function() {
        $('#' + ubicacion + ' .grafico').html('');
        var svg = d3.select("#" + ubicacion + ' .grafico ')
                .append("svg")
                .datum(currentDatasetChart)
                .attr("viewBox", '-5 0 440 310')
                .attr("preserveAspectRatio", 'none')
                .attr("id", "ChartPlot");
        // create group and move it so that margins are respected (space for axis and title)

        var plot = svg
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
                ;

        var long = $('#' + ubicacion + ' .titulo_indicador').attr('data-unidad-medida').length;
        svg.append("g")
                .attr("class", "axis")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
                .call(yAxis).append("text")
                .attr("transform", "rotate(-90)")
                .attr("y", -50)
                .attr("x", -((height / 2) + (long / 2) * 6.5))
                .text($('#' + ubicacion + ' .titulo_indicador').attr('data-unidad-medida'));

        svg.append("g")
                .attr("class", "x axis")
                .attr("transform", "translate(" + margin.left + "," + (margin.top + height ) + ")")
                .call(xAxis);

        if ($('#sala_default').val()==0){
            var duracion = 1000;
            var retraso = 20;
        }else {
            var duracion = 0;
            var retraso = 0;
        }

        plot.append("path")
                .attr("class", "line")
                .attr("d", line2)
                .transition().duration(duracion).delay(retraso)
                .ease("cubic")
                .attr("d", line)
                .attr("stroke", 'steelblue')                
                ;
        
        
        plot.selectAll(".dot")
                .data(currentDatasetChart)
                .enter().append("circle")
                .attr("class", "dot")
                .transition().duration(duracion).delay(retraso)
                .attr("fill", "white")
                .attr("stroke", function(d, i) {
                    return colores_alertas(ubicacion, d.measure, i)
                })                
                .attr("cx", line.x())								
                .attr("cy", line.y())
                .attr("r", 7.5)
        ;
        
        plot.selectAll(".dot")
                .append("title")
                .text(function(d) {
            return d.category + ": " + d.measure;
        })
                ;

        plot.selectAll(".dot").on("click", function(d, i) {
            descenderNivelDimension(ubicacion, d.category);
        })
        .on("mouseover", function(d) {
            d3.select(this)
                .attr("r",5)
                .transition().duration(750)
                .attr("r",10)
                .attr("fill", function(d, i) 
                {
                    return colores_alertas(ubicacion, d.measure, i)
                })
            ;
        })
        .on("mouseout",function(d){
            d3.select(this)
                .transition().duration(750)
                .attr("r",7.5)
                .attr("fill", "white") 
                .style("stroke-width","1.5")                
            ;    
        });
    };
    this.ordenar = function(modo_orden, ordenar_por) {
        var svg = d3.select("#" + zona + ' .grafico ');
        
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

        transition.selectAll(".line").delay(delay).attr("d", line).attr("stroke", 'blue');
        transition.selectAll(".dot").delay(delay).attr("cx", function(d) {
            return x0(d.category);
        });
        transition.select(".x.axis").call(xAxis).selectAll("g").delay(delay);
        
        // Ordenar la tabla de datos
        $('#' + zona).attr('datasetPrincipal', JSON.stringify(currentDatasetChart));

    };
}