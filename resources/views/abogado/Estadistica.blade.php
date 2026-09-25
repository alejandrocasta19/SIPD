@extends('layouts.master')

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-md-12">

            <div class="card">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Estadísticas de Procesos Disciplinarios
                    </h3>
                </div>

                <div class="card-body">

                    <!-- FILTRO -->
                    <div class="filter-box">

                        <div class="filter-header">
                            <i class="fas fa-filter"></i>
                            Filtrar Estadísticas
                        </div>

                        <div class="filter-body">

                            <div class="row">

                                <!-- FECHA DESDE -->
                                <div class="col-md-3">

                                    <label>Fecha Desde</label>

                                    <input type="date"
                                           id="fecha_desde"
                                           class="form-control">

                                </div>

                                <!-- FECHA HASTA -->
                                <div class="col-md-3">

                                    <label>Fecha Hasta</label>

                                    <input type="date"
                                           id="fecha_hasta"
                                           class="form-control">

                                </div>

                                <!-- MODALIDAD / CARGO -->
                                <div class="col-md-4">

                                    <label>Modalidad / Cargo</label>

                                    <select id="modalidad"
                                            class="form-control">

                                        <option value="">
                                            Todas las modalidades
                                        </option>

                                        <option value="Premium">Premium</option>

                                        <option value="Dobleyo">Dobleyo</option>

                                        <option value="Platino Express">
                                            Platino Express
                                        </option>

                                        <option value="Platino Jet">
                                            Platino Jet
                                        </option>

                                        <option value="Administrativo">
                                            Administrativo
                                        </option>

                                        <option value="Call Center">
                                            Call Center
                                        </option>

                                        <option value="Asistente de Ventas">
                                            Asistente de Ventas
                                        </option>

                                        <option value="Monitoreo">
                                            Monitoreo
                                        </option>

                                        <option value="Urbano">
                                            Urbano
                                        </option>

                                        <option value="Aerovans">
                                            Aerovans
                                        </option>

                                        <option value="Encomiendas">
                                            Encomiendas
                                        </option>

                                        <option value="Mixtos">
                                            Mixtos
                                        </option>

                                    </select>

                                </div>

                                <!-- BOTÓN -->
                                <div class="col-md-2">

                                    <label>&nbsp;</label>

                                    <button id="btnFiltrar"
                                            class="btn btn-primary btn-block">

                                        <i class="fas fa-search"></i>
                                        Filtrar

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- GRÁFICAS -->
                    <div class="row">

                        <!-- BARRAS -->
                        <div class="col-md-7">

                            <div class="chart-box">

                                <div class="chart-header">

                                    <h4>Comparación por Estado</h4>

                                    <p>
                                        Cantidad de procesos disciplinarios
                                    </p>

                                </div>

                                <div class="chart-body">

                                    <canvas id="comparacionChart"></canvas>

                                </div>

                            </div>

                        </div>

                        <!-- TORTA -->
                        <div class="col-md-5">

                            <div class="chart-box">

                                <div class="chart-header">

                                    <h4>Distribución General</h4>

                                    <p>
                                        Distribución porcentual de procesos
                                    </p>

                                </div>

                                <div class="chart-body">

                                    <canvas id="pieChart"></canvas>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- LEYENDA -->
                    <div class="chart-legend">

                        <ul class="legend-list">

                            <li>
                                <span class="legend-color"
                                      style="background:#dc3545;">
                                </span>

                                Pendientes
                            </li>

                            <li>
                                <span class="legend-color"
                                      style="background:#ffc107;">
                                </span>

                                En Proceso
                            </li>

                            <li>
                                <span class="legend-color"
                                      style="background:#198754;">
                                </span>

                                Sancionados
                            </li>

                            <li>
                                <span class="legend-color"
                                      style="background:#6c757d;">
                                </span>

                                Archivados
                            </li>

                        </ul>

                    </div>

                    <!-- TARJETAS -->
                    <div class="row mt-4">

                        <div class="col-md-3">

                            <div class="stats-card">

                                <div class="stats-icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>

                                <div class="stats-info">

                                    <h3 id="totalPendientes">0</h3>

                                    <p>Procesos Pendientes</p>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="stats-card">

                                <div class="stats-icon">
                                    <i class="fas fa-clock"></i>
                                </div>

                                <div class="stats-info">

                                    <h3 id="totalProceso">0</h3>

                                    <p>Procesos En Proceso</p>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="stats-card">

                                <div class="stats-icon">
                                    <i class="fas fa-gavel"></i>
                                </div>

                                <div class="stats-info">

                                    <h3 id="totalSancionados">0</h3>

                                    <p>Procesos Sancionados</p>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="stats-card">

                                <div class="stats-icon">
                                    <i class="fas fa-folder"></i>
                                </div>

                                <div class="stats-info">

                                    <h3 id="totalArchivados">0</h3>

                                    <p>Procesos Archivados</p>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="card-footer text-center">

                    <p>
                        © 2023–2025 Procesos Disciplinarios.
                        Todos los derechos reservados.
                    </p>

                    <small>
                        Versión 3.2.0 | 26 de agosto de 2025
                    </small>

                </div>

            </div>

        </div>
    </div>
</div>

<style>

.filter-box{
    background:#f8f9fa;
    border:1px solid #e0e0e0;
    border-radius:8px;
    margin-bottom:25px;
    overflow:hidden;
}

.filter-header{
    background:#007bff;
    color:white;
    padding:10px 15px;
    font-weight:bold;
}

.filter-body{
    padding:15px;
}

.filter-body label{
    font-size:12px;
    font-weight:600;
    margin-bottom:5px;
    color:#555;
}

.form-control{
    border-radius:4px;
    border:1px solid #ccc;
    padding:6px 10px;
    font-size:13px;
}

.chart-box{
    background:#fff;
    border:1px solid #e0e0e0;
    border-radius:8px;
    padding:15px;
    margin-bottom:20px;
    box-shadow:0 2px 4px rgba(0,0,0,0.05);
}

.chart-header{
    text-align:center;
    margin-bottom:15px;
    border-bottom:1px solid #f0f0f0;
    padding-bottom:10px;
}

.chart-header h4{
    margin:0;
    color:#333;
    font-size:18px;
}

.chart-header p{
    margin:5px 0 10px;
    color:#777;
    font-size:12px;
}

.chart-body{
    text-align:center;
    min-height:280px;
    display:flex;
    justify-content:center;
    align-items:center;
}

.chart-legend{
    margin-top:12px;
    text-align:center;
}

.legend-list{
    list-style:none;
    padding:0;
    margin:0;
    display:inline-flex;
    gap:20px;
    flex-wrap:wrap;
    justify-content:center;
}

.legend-list li{
    font-size:12px;
    color:#555;
}

.legend-color{
    display:inline-block;
    width:10px;
    height:10px;
    border-radius:50%;
    margin-right:5px;
}

.stats-card{
    background:#fff;
    border:1px solid #e0e0e0;
    border-radius:8px;
    padding:15px;
    display:flex;
    align-items:center;
    gap:12px;
    transition:0.2s;
}

.stats-card:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(0,0,0,0.1);
}

.stats-icon{
    font-size:32px;
    color:#007bff;
}

.stats-info h3{
    margin:0;
    font-size:24px;
    font-weight:bold;
    color:#333;
}

.stats-info p{
    margin:3px 0 0;
    color:#777;
    font-size:11px;
}

.mt-4{
    margin-top:15px;
}

.btn-primary{
    background:#007bff;
    border:none;
    padding:6px 12px;
    font-size:13px;
    border-radius:4px;
}

</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    let datosEstados = [0,0,0,0];

    // =========================
    // GRÁFICA BARRAS
    // =========================
    const ctxBar =
        document.getElementById('comparacionChart')
        .getContext('2d');

    let comparacionChart = new Chart(ctxBar, {

        type: 'bar',

        data: {

            labels: [
                'Pendientes',
                'En Proceso',
                'Sancionados',
                'Archivados'
            ],

            datasets: [{

                label: 'Cantidad',

                data: datosEstados,

                backgroundColor: [
                    '#dc3545',
                    '#ffc107',
                    '#198754',
                    '#6c757d'
                ],

                borderRadius: 8,
                barPercentage: 0.6,
                categoryPercentage: 0.7

            }]
        },

        options: {

            responsive: true,
            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {
                    beginAtZero: true
                }

            }

        }

    });

    // =========================
    // GRÁFICA TORTA
    // =========================
    const ctxPie =
        document.getElementById('pieChart')
        .getContext('2d');

    let pieChart = new Chart(ctxPie, {

        type: 'pie',

        data: {

            labels: [
                'Pendientes',
                'En Proceso',
                'Sancionados',
                'Archivados'
            ],

            datasets: [{

                data: datosEstados,

                backgroundColor: [
                    '#dc3545',
                    '#ffc107',
                    '#198754',
                    '#6c757d'
                ]

            }]

        },

        options: {

            responsive: true,
            maintainAspectRatio: false

        }

    });

    // =========================
    // CARGAR DATOS
    // =========================
    function cargarDatos(desde = '', hasta = '', modalidad = '') {

        fetch(`/abogado/estadisticas/datos?fecha_desde=${desde}&fecha_hasta=${hasta}&modalidad=${modalidad}`)

        .then(response => response.json())

        .then(data => {

            let nuevosDatos = [

                data.pendientes,
                data.proceso,
                data.sancionados,
                data.archivados

            ];

            comparacionChart.data.datasets[0].data = nuevosDatos;
            comparacionChart.update();

            pieChart.data.datasets[0].data = nuevosDatos;
            pieChart.update();

            document.getElementById('totalPendientes').innerText =
                data.pendientes;

            document.getElementById('totalProceso').innerText =
                data.proceso;

            document.getElementById('totalSancionados').innerText =
                data.sancionados;

            document.getElementById('totalArchivados').innerText =
                data.archivados;

            document.querySelector('.legend-list').innerHTML = `

                <li>
                    <span class="legend-color"
                          style="background:#dc3545;"></span>

                    Pendientes (${data.pendientes})
                </li>

                <li>
                    <span class="legend-color"
                          style="background:#ffc107;"></span>

                    En Proceso (${data.proceso})
                </li>

                <li>
                    <span class="legend-color"
                          style="background:#198754;"></span>

                    Sancionados (${data.sancionados})
                </li>

                <li>
                    <span class="legend-color"
                          style="background:#6c757d;"></span>

                    Archivados (${data.archivados})
                </li>

            `;

        });

    }

    // =========================
    // BOTÓN FILTRAR
    // =========================
    document.getElementById('btnFiltrar')

    .addEventListener('click', function() {

        let desde =
            document.getElementById('fecha_desde').value;

        let hasta =
            document.getElementById('fecha_hasta').value;

        let modalidad =
            document.getElementById('modalidad').value;

        cargarDatos(desde, hasta, modalidad);

        Swal.fire({

            icon: 'success',

            title: 'Filtro aplicado correctamente',

            timer: 1500,

            showConfirmButton: false

        });

    });

    // =========================
    // CARGA INICIAL
    // =========================
    cargarDatos();

});

</script>

@endsection