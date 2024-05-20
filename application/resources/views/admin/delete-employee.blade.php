@extends('layouts.default')

    @section('meta')
        <title>Excluir Usuário | Webponto</title>
        <meta name="description" content="Workday view employees, delete employees, edit employees, add employees">
    @endsection

    @section('content')
    
    <div class="container-fluid">
        <div class="row">
            <div class="box box-success col-md-6">
            <div class="box-header with-border">{{ __('Excluir conta de usuário') }}</div>
                <div class="box-body">
                    <form action="{{ url('profile/delete/employee') }}" class="ui form" method="post" accept-charset="utf-8">
                        {{ csrf_field() }}
                        <input type="hidden" name="id" value="@isset($id) {{$id}} @endisset">
                        <div class="field">
                            <div class="ui header" style="margin:0">{{ __('Tem certeza que deseja excluir o usuário?') }}</div>
                        </div>
                        <div class="field">
                          <!-- <p>{{ __("Deleting this account also deletes the following data: Employee's Attendance, Schedules, Leaves, User Account, or All records associated with this Employee.") }}</p>-->
                        </div>
                        <div class="field">
                            <a href="{{ url('employees') }}" class="ui black deny button">{{ __('Não') }}</a>
                            <button class="ui positive button approve" type="submit" name="submit"><i class="ui checkmark icon"></i>{{ __('Sim') }}</button>
                        </div>
                    </form> 
                </div>
            </div>
        </div>
    </div>

    @endsection
