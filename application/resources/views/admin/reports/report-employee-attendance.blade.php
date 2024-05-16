{{-- Extende o layout padrão do sistema --}}
@extends('layouts.default')

{{-- Seção para meta tags específicas da página de relatórios --}}
@section('meta')
    <title>Relatórios | Webponto</title>
    <meta name="description" content="Relatórios de jornada de trabalho, visualização de relatórios e exportação ou download de relatórios.">
@endsection

{{-- Seção para adicionar estilos específicos relacionados aos relatórios --}}
@section('styles')
    <link href="{{ asset('/assets/vendor/air-datepicker/dist/css/datepicker.min.css') }}" rel="stylesheet">
@endsection

{{-- Seção principal de conteúdo da página --}}
@section('content')
<div class="container-fluid">
    <div class="row">
        <h2 class="page-title">{{ __("Relatório de Presença de Usuário") }}
            <a href="{{ url('reports') }}" class="ui basic blue button mini offsettop5 float-right"><i class="ui icon chevron left"></i>{{ __("Return") }}</a>
        </h2> 
    </div>

    <div class="row">
        <div class="box box-success">
            <div class="box-body reportstable">
                <form action="{{ url('export/report/attendance') }}" method="post" accept-charset="utf-8" class="ui small form form-filter" id="filterform">
                    {{-- Proteção contra CSRF --}}
                    @csrf
                    <div class="inline three fields">
                    {{-- Campo de seleção de empresa adicionado --}}
    <div class="three wide field">
        <select name="company" class="ui search dropdown getid">
            <option value="">{{ __("Empresa") }}</option>
            {{-- Iteração sobre a coleção de funcionários para preenchimento das opções de empresa --}}
            @isset($employee)
                @foreach($employee->unique('company') as $e)
                    <option value="{{ $e->company }}" data-id="{{ $e->idno }}">{{ $e->company }}</option>
                @endforeach
            @endisset
        </select>
    </div>
                        {{-- Campo de seleção de usuário --}}
                        <div class="three wide field">
                            <select name="employee" class="ui search dropdown getid">
                                <option value="">{{ __("Usuário") }}</option>
                                @isset($employee)
                                    @foreach($employee as $e)
                                    <option value="{{ $e->idno }}, {{ $e->firstname }}" data-id="{{ $e->idno }}">{{ $e->idno }}, {{ $e->firstname }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        {{-- Campos de data para seleção do período do relatório --}}
                        <div class="two wide field">
                            <input id="datefrom" type="date" name="datefrom" value="" placeholder="dd/mm/yyyy" >
                        </div>
                        <div class="two wide field">
                            <input id="dateto" type="date" name="dateto" value="" placeholder="dd/mm/yyyy" >
                        </div>

                        <input type="hidden" name="emp_id" value="">
                        {{-- Botões de filtro e download de relatórios --}}
                        <button id="btnfilter" class="ui icon button positive small inline-button"><i class="ui icon filter alternate"></i> {{ __("Filter") }}</button>
                        <button type="submit" name="submit" class="ui icon button blue small inline-button"><i class="ui icon download"></i> {{ __("Download") }}</button>
                    </div>
                </form>

                {{-- Tabela para exibição dos dados de presença --}}
                <table width="100%" class="table table-striped table-hover" id="dataTables-example" data-order='[[ 0, "desc" ]]'>
                    <thead>
                        <tr>
                            <th>{{ __("Data") }}</th>
                            <th>{{ __("Matrícula") }}</th>
                            <th>{{ __("Usuário") }}</th>
                            <th>{{ __("Empresa") }}</th>
                            <th>{{ __("Turma") }}</th>                              
                            <th>{{ __("Time In") }}</th>
                            <th>{{ __("Time Out") }}</th>
                            <th>{{ __("Total Hours") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Iteração sobre os dados de presença dos funcionários --}}
                        @isset($empAtten)
                        @foreach ($empAtten as $v)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($v->date)->format('d/m/Y') }}</td>
                            <td>{{ $v->idno }}</td>
                            <td>{{ $v->employee }}</td>     
                            <td>{{ $v->company }}</td>
                            <td>{{ $v->department }}</td>                         
                            <td>
                                @php
                                    if($v->timein != null) {
                                        if($tf == 1) {
                                            echo e(date('h:i:s A', strtotime($v->timein)));
                                        } else {
                                            echo e(date('H:i:s', strtotime($v->timein)));
                                        }
                                    }
                                @endphp
                            </td>
                            <td>
                                @php
                                    if($v->timeout != null) {
                                        if($tf == 1) {
                                            echo e(date('h:i:s A', strtotime($v->timeout)));
                                        } else {
                                            echo e(date('H:i:s', strtotime($v->timeout)));
                                        }
                                    }
                                @endphp
                            </td>
                            <td>{{ $v->totalhours }}</td>
                        </tr>
                        @endforeach
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Seção para scripts específicos da página de relatórios --}}
@section('scripts')


<script src="{{ asset('/assets/vendor/mdtimepicker/mdtimepicker.min.js') }}"></script>
    <script src="{{ asset('/assets/vendor/air-datepicker/dist/js/datepicker.min.js') }}"></script>
    <script src="{{ asset('/assets/vendor/air-datepicker/dist/js/i18n/datepicker.en.js') }}"></script>
    <script src="{{ asset('/assets/vendor/momentjs/moment.min.js') }}"></script>
    <script src="{{ asset('/assets/vendor/momentjs/moment-timezone-with-data.js') }}"></script>


    <script type="text/javascript">
    // Configuração e funcionalidades adicionais do DataTable
    $('#dataTables-example').DataTable({
        responsive: true,
        pageLength: 15,
        lengthChange: false,
        searching: false,
        ordering: true
    });

    // Funções de formatação de data para ajuste de formato entre front-end e back-end
    function formatDateToYMD(date) {
        var parts = date.split('-');
        return parts[0] + '-' + parts[1] + '-' + parts[2];
    }

    function formatDateToDMY(date) {
        var parts = date.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    // Lógica para transferência de ID do funcionário entre os componentes de seleção
    $('.ui.dropdown.getid').dropdown({
        onChange: function(value, text, $selectedItem) {
            $('select[name="employee"] option').each(function() {
                if ($(this).val() == value) {
                    var id = $(this).attr('data-id');
                    $('input[name="emp_id"]').val(id);
                }
            });
        }
    });

    // Manipulação do botão de filtro para envio dos dados ao servidor e tratamento da resposta
    $('#btnfilter').click(function(event) {
        event.preventDefault();
        var emp_id = $('input[name="emp_id"]').val();
        var company = $('select[name="company"]').val(); // Captura o valor da empresa selecionada
        var date_from = $('#datefrom').val();
        var date_to = $('#dateto').val();
        var url = $("#_url").val();

        // Formatar data para envio ao backend
        if (date_from) {
            date_from = formatDateToYMD(date_from);
        }
        if (date_to) {
            date_to = formatDateToYMD(date_to);
        }

        $.ajax({
            url: url + '/get/employee-attendance/',
            type: 'get',
            dataType: 'json',
            data: {
                id: emp_id,
                company: company,  // Adiciona o filtro de empresa à solicitação
                datefrom: date_from,
                dateto: date_to
            },
            headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                showdata(response);
            }
        });

        function showdata(jsonresponse) {
            var employee = jsonresponse;
            var tbody = $('#dataTables-example tbody');
            
            // Limpar dados e reinicializar DataTable
            $('#dataTables-example').DataTable().destroy();
            tbody.children('tr').remove();

            // Adicionar dados dos funcionários na tabela
            for (var i = 0; i < employee.length; i++) {
                var formattedDate = formatDateToDMY(employee[i].date);  // Formatar data
                var idno = employee[i].idno;
                var time_in = employee[i].timein;
                var time_out = employee[i].timeout;
                var total_hours = employee[i].totalhours;
                var company = employee[i].company;
                var department = employee[i].department;
                var employee_name = employee[i].employee;

                // Formatando os horários Time In e Time Out usando Moment.js
                var formatted_time_in = time_in ? moment(time_in, "YYYY-MM-DD hh:mm:ss A").format("HH:mm") : "";
                var formatted_time_out = time_out ? moment(time_out, "YYYY-MM-DD hh:mm:ss A").format("HH:mm") : "";

                // Adicionando a linha com todos os campos necessários
                tbody.append("<tr>"+ 
                                "<td>"+ formattedDate +"</td>" + 
                                "<td>"+ idno +"</td>" + 
                                "<td>"+ employee_name +"</td>" + 
                                "<td>"+ company +"</td>" + 
                                "<td>"+ department +"</td>" + 
                                "<td>"+ formatted_time_in +"</td>" + 
                                "<td>"+ formatted_time_out +"</td>" + 
                                "<td>"+ total_hours +"</td>" + 
                            "</tr>");
            }

            // Inicialização do DataTable
            $('#dataTables-example').DataTable({
                responsive: true,
                pageLength: 15,
                lengthChange: false,
                searching: false,
                ordering: true
            });
        }
    });
</script>

@endsection
