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
                            <select name="employee" class="ui search dropdown getid">
                                <option value="">{{ __("Empresa") }}</option>
                                {{-- Iteração sobre a coleção de funcionários para preenchimento das opções de empresa --}}
                                @isset($employee)
                                    @foreach($employee as $e)
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
                            <th>{{ __("Usuário") }}</th>
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
                            <td>{{ $v->employee }}</td>
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
<script src="{{ asset('/assets/vendor/air-datepicker/dist/js/datepicker.min.js') }}"></script>
<script src="{{ asset('/assets/vendor/air-datepicker/dist/js/i18n/datepicker.en.js') }}"></script>

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
        var parts = date.split('/');
        return parts[2] + '-' + parts[1] + '-' + parts[0];
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
        var date_from = $('#datefrom').val();
        var date_to = $('#dateto').val();
        var url = $("#_url").val();
        var gtr = 0;

        // Formatar data para envio ao backend
        date_from = formatDateToYMD(date_from);
        date_to = formatDateToYMD(date_to);

        $.ajax({
            url: url + '/get/employee-attendance/',
            type: 'get',
            dataType: 'json',
            data: {
                id: emp_id,
                datefrom: date_from,
                dateto: date_to
            },
            headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                // Processamento e exibição dos dados de presença
                showdata(response);
                function showdata(jsonresponse) {
                    var employee = jsonresponse;
                    var tbody = $('#dataTables-example tbody');
                    
                    // Limpar dados e reinicializar DataTable
                    $('#dataTables-example').DataTable().destroy();
                    tbody.children('tr').remove();

                    // Adicionar dados dos funcionários na tabela
                    for (var i = 0; i < employee.length; i++) {
                        var formattedDate = formatDateToDMY(employee[i].date);  // Formatar data
                        var time_in = employee[i].timein;
                        var t_in = time_in.split(" ");
                        var time_out = employee[i].timeout;
                        var t_out = time_out.split(" ");

                        tbody.append("<tr>"+ 
                                        "<td>"+ formattedDate +"</td>" + 
                                        "<td>"+employee[i].employee+"</td>" + 
                                        "<td>"+ t_in[1]+" "+t_in[2] +"</td>" + 
                                        "<td>"+ t_out[1]+" "+t_out[2] +"</td>" + 
                                        "<td>"+employee[i].totalhours+"</td>" + 
                                    "</tr>");
                    }

                    // Inicialização do DataTable
                    $('#dataTables-example').DataTable({
                        responsive: true,
                        pageLength: 15,
                        lengthChange: false,
                        searching: false,
                        ordering: false
                    });
                }
            }
        });
    });
</script>
@endsection
