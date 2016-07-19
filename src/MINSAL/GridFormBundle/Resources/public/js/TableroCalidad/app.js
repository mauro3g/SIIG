    function ifLoading($http) {
      return {
        restrict: 'A',
        link: function(scope, elem) {
            scope.isLoading = isLoading;

            scope.$watch(scope.isLoading, toggleElement);

            function toggleElement(loading) {
              (loading) ? elem.show() : elem.hide();           
            }

            function isLoading() {
              return $http.pendingRequests.length > 0;
            }
        }
      };
    }

    ifLoading.$inject = ['$http'];
    var tableroCalidadApp = angular.module('tableroCalidadApp', ['servicios', "chart.js"])
        .config(['$interpolateProvider', function ($interpolateProvider) {
                $interpolateProvider.startSymbol('[[');
                $interpolateProvider.endSymbol(']]');
            }])
        .directive('ifLoading', ifLoading)
        .filter('quitarpunto',function() {
            return function (value) {
                return (!value) ? '' : value.replace(/\./g, '');
            };
        })
        .controller('mainCtrl', function AppCtrl ($scope, Periodos, Establecimientos, Evaluaciones, Criterios, EncabezadoFrm, HistorialEstablecimiento) {
            $scope.options = {width: 300, height: 250, 'bar': 'aaa'};
            $scope.data = [0];
            $scope.hovered = function(d){
                $scope.barValue = d;
                $scope.$apply();
            };
            $scope.periodoSeleccionado = '';
            $scope.mostrarListadoEstablecimientos = true;
            $scope.fila = 1;
            $scope.establecimientoSeleccionado = '';
            $scope.evaluacionSeleccionada = '';
            $scope.mes_ = '';
            $scope.titulo_grafico1 = 'Establecimientos vs calificación(%)'
            
            $scope.titulo = 'Monitoreo y Evaluación de la Gestión de la calidad en RIISS';
            $scope.subtitulo = 'Unidad Nacional Gestión de Calidad de la RIISS - VMSS';
            $scope.establecimientos = [];
            $scope.datosGrafico1 = [];
            $scope.datosGrafico2 =  [];
            $scope.evaluaciones = [];
            $scope.criterios = [];
            
            //Las areas del gráfico
            $scope.capameta = 1;
            $scope.calificaciones = [];
            $scope.metas = [];
            $scope.brechas = [];
            $scope.options_bar_line = {
                scales: {                                     
                    yAxes: [{ ticks: { min: 0, max: 100} }]
                }
            };
            
            
            $scope.periodos = Periodos.query();
            
            $scope.getValorMes = function(criterio){
                return criterio['mes_check_'+$scope.mes_];
            };
            
            $scope.getAlertas =  function(criterio, cellValue){
                var estilo='';
                if (criterio.alertas != ''){
                    var tipo_control = criterio.codigo_tipo_control;
                    // Si es tiempo pasarlo a minutos
                    if (tipo_control == 'time'){
                        partes = cellValue.split(':');
                        valor = parseInt(partes[0]) * 60 + parseInt(partes[1]);
                    }
                    var rangos = criterio.alertas.split(',');
                    rangos.forEach(function(nodo, index){
                        var limites = nodo.split('-');
                        //Si no existe alguno de los límites del rango ponerle valores 
                        // muy grandes (si falta lim sup) o muy pequeño( si falta el lim inf)
                        limites[0] = (limites[0]=='') ? -1000000 : limites[0]; 
                        limites[1] = (limites[1]=='') ? 1000000 : limites[1];
                        if (!(isNaN(valor))){
                            if (valor >= parseFloat(limites[0]) && parseFloat(valor) <= parseFloat(limites[1])){                                                    
                                estilo = "border-style: solid; border-color:"+limites[2]+ " white ; color: " +limites[2]+" ; font-size:14pt; font-weight: bold; ";
                                criterio.rango = limites[2];
                            }
                        }
                    });
                }
                return estilo;
            };
            
            $scope.getValorExpediente = function(exp){
                var respuesta='';
                if (exp.search(':') !== -1){
                    valor = exp.split(':');
                    valor.shift();
                    respuesta =  valor.join(':');
                } else {
                    respuesta = exp.trim();
                }
                return (respuesta == 'NaN') ? '': respuesta;
            };
            
            $scope.getExpedientes = function(criterio){
                var expedientes = [];
                angular.forEach(criterio, function(value, key) {
                    if (key.search('num_expe_') !== -1 || key.search('cant_mensual') !== -1 || key.search('dias_mes') !== -1 || key.search('mes_check') !== -1 )
                        this.push(key+":"+value);
                }, expedientes);
                
                return expedientes;
            };
            
            $scope.getEstablecimientos = function() {
                $scope.mes_ = ($scope.periodoSeleccionado.mes < 10) ? '0' + $scope.periodoSeleccionado.mes : $scope.periodoSeleccionado.mes ;
                
                Establecimientos.query({ periodo: $scope.periodoSeleccionado.periodo })
                    .$promise.then(
                        function (data) {                            
                            $scope.establecimientos = (data != '') ? data : [];
                            $scope.datosGrafico1 = [];
                            $scope.etiquetasGrafico1 = [];
                            $scope.establecimientos.forEach(function(nodo, index){
                                $scope.datosGrafico1.push(nodo.calificacion);
                                $scope.etiquetasGrafico1.push(nodo.nombre_corto);
                            });
                            $scope.evaluaciones = [];
                            $scope.titulo_grafico1 = 'Establecimientos vs calificación';
                            $scope.criterios = [];                            
                        },
                        function (error) {
                            alert(error);
                        }
                    );
            };
           
            
            $scope.mostrarEvaluacionesComplementarias = function(establecimiento) {
                $scope.establecimientoEvalExt = establecimiento;
                $('#modalEvaluacionesComplementarias').modal('show')
            };
            $scope.getEvaluaciones = function(establecimientoSel) {
                $scope.establecimientoSeleccionado = establecimientoSel;
                $scope.mostrarListadoEstablecimientos = false;
                
                $(".establecimiento").removeClass('establecimientoSeleccionado');
                $("."+establecimientoSel.establecimiento).addClass('establecimientoSeleccionado');

                
                Evaluaciones.query({ establecimiento: establecimientoSel.establecimiento, periodo: $scope.periodoSeleccionado.periodo })
                    .$promise.then(
                        function (data) {                            
                            $scope.evaluaciones = (data != '') ? data : [];
                            $scope.options_radar =  {
                                scale: {
                                    ticks: {
                                        beginAtZero: true
                                    }
                                }
                            };

                            var calificaciones = [];
                            var metas = [];
                            $scope.etiquetasGrafico2 = [];
                            $scope.datosGrafico2 = [];
                            $scope.evaluaciones.forEach(function(nodo, index){
                                if (nodo.forma_evaluacion == 'lista_chequeo'){
                                    calificaciones.push(nodo.measure);
                                    metas.push(nodo.meta);
                                    $scope.etiquetasGrafico2.push(nodo.codigo);
                                }
                            });
                    
                            $scope.datosGrafico2.push(calificaciones);
                            $scope.datosGrafico2.push(metas);
                            $scope.colors = ['#45b7cd', '#ff6384'];
                        },
                        function (error) {
                            alert(error);
                        }
                    );
                $scope.criterios = [];         
                //Cambiar el gráfico 1
                $scope.titulo_grafico1 = 'Historial de calificaciones';
                //$scope.titulo = 'Evaluación de Calidad :: '+ establecimientoSel.nombre;
                HistorialEstablecimiento.query({ establecimiento: establecimientoSel.establecimiento,
                                                periodo: $scope.periodoSeleccionado.periodo,})
                    .$promise.then(
                        function (data) {                            
                            var datos = (data != '') ? data : [];
                            var aux = [];
                            $scope.datosGrafico1 = [];
                            $scope.etiquetasGrafico1 = [];
                            
                            datos.forEach(function(nodo, index){
                                aux.push(nodo.calificacion);
                                $scope.etiquetasGrafico1.push(nodo.category);
                            });
                            $scope.datosGrafico1.push(aux);
                        },
                        function (error) {
                            alert(error);
                        }
                    );                
            };
            
            $scope.resaltarCriterios =  function(codIndicador){                
                $(".fila_criterios").removeClass('seleccionado');
                $("."+codIndicador.replace(/\./g, '')).addClass('seleccionado');
            };
            
            $scope.getCriterios = function(evaluacionSel) {
                $scope.evaluacionSeleccionada = evaluacionSel;
                EncabezadoFrm.query(
                        { establecimiento: $scope.establecimientoSeleccionado.establecimiento, 
                            periodo: $scope.periodoSeleccionado.periodo,
                            evaluacion: evaluacionSel.codigo
                        })
                        .$promise.then(
                        function (data) {                            
                            $scope.encabezado = data[0];
                        }
                    );
                Criterios.query(
                        { establecimiento: $scope.establecimientoSeleccionado.establecimiento, 
                            periodo: $scope.periodoSeleccionado.periodo,
                            evaluacion: evaluacionSel.codigo
                        })
                        .$promise.then(
                        function (data) {                            
                            $scope.criterios = data;
                            $scope.resumenIndicadores = data[0].resumen_indicadores;
                            $scope.labels_rec = [];
                            $scope.data_rec = [];
                            $scope.series_rec = ['Series A'];
                            $scope.datasetOverride_rec = [{ xAxisID: 'x-axis-1' }, { xAxisID: 'x-axis-2' }];
                            $scope.options_rec = {
                              scales: {                                    
                                    xAxes: [
                                        {
                                          id: 'x-axis-1',
                                          type: 'linear',
                                          display: true,
                                          position: 'top',
                                          ticks: {
                                                    min: 0,
                                                    max: 100
                                                }
                                        },
                                        {
                                          id: 'x-axis-2',
                                          type: 'linear',
                                          display: true,
                                          position: 'bottom',
                                          ticks: {
                                                    min: 0,
                                                    max: 100
                                                }
                                        }
                                    ]
                                }
                            };
                            angular.forEach(data[0].resumen_criterios, function(value, key) {
                                    this.push(value.descripcion_variable);
                                    $scope.data_rec.push(value.porc_cumplimiento);
                            }, $scope.labels_rec);
                            
                        },
                        function (error) {
                            alert(error);
                        }
                    );
            };
        });