@extends('layouts.default')

@section('meta')
    <title>Nova Empresa | Webponto</title>
    <meta name="description" content="Workday companies, view companies, and export or download companies.">
@endsection

@section('content')
@include('admin.modals.modal-import-company')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="page-title uppercase">{{ __("Add Company") }}
                <!--<button class="ui basic button mini offsettop5 btn-import float-right"><i class="ui icon upload"></i> {{ __("Import") }}</button>
                <a href="{{ url('export/fields/company' )}}" class="ui basic button mini offsettop5 btn-export float-right"><i class="ui icon download"></i> {{ __("Export") }}</a> -->
            </h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-body">
                    @if ($errors->any())
                    <div class="ui error message">
                        <i class="close icon"></i>
                        <div class="header">{{ __("There were some errors with your submission") }}</div>
                        <ul class="list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <form id="add_company_form" action="{{ url('fields/company/add') }}" class="ui form" method="post" accept-charset="utf-8">
                        @csrf
                        <div class="field">
                            <label>{{ __("Empresa") }}</label>
                            <input class="uppercase" name="company" value="" type="text">
                        </div>
                        <div class="field">
                            <label>{{ __("ID Empresa") }}</label>
                            <input name="id_empresa" type="text" value="">
                        </div>
                        <div class="field">
                            <label>{{ __("CNPJ") }} <span class="help">Insira o CNPJ sem pontos e traços: 98765432000123</span></label>
                            <input name="cnpj" type="text" value="">
                        </div>
                        <div class="actions">
                            <button type="submit" class="ui positive button small"><i class="ui icon check"></i>Salvar</button>
                        </div>          
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
        <div class="box box-success">
            <div class="box-body">
                <table width="100%" class="table table-striped table-hover" id="dataTables-example">
                    <thead>
                        <tr>
                            <th>ID empresa</th>    
                            <th>CNPJ</th>                            
                            <th>Empresa</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @isset($data)
                            @foreach ($data as $company)
                            <tr>
                                <td>{{ $company->id_empresa }}</td>
                                <td>{{ $company->cnpj }}</td>
                                <td>{{ $company->company }}</td>                              
                                
                                <td class="align-right"> 
                                    <a href="{{ url('fields/company/delete/'.$company->id) }}" class="ui circular basic icon button tiny"><i class="icon trash alternate outline"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>

@endsection

    @section('scripts')
    <script type="text/javascript">
    $('#dataTables-example').DataTable({responsive: true,pageLength: 15,lengthChange: false,searching: true,ordering: true});
    function validateFile() {
        var f = document.getElementById("csvfile").value;
        var d = f.lastIndexOf(".") + 1;
        var ext = f.substr(d, f.length).toLowerCase();
        if (ext == "csv") { } else {
            document.getElementById("csvfile").value="";
            $.notify({
            icon: 'ui icon times',
            message: "Please upload only CSV file format."},
            {type: 'danger',timer: 400});
        }
    }
    </script>

    @endsection


  