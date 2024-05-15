<?php
/*
* Workday - A time clock application for employees
* Email: official.codefactor@gmail.com
* Version: 1.1
* Author: Brian Luna
* Copyright 2020 Codefactor
*/
namespace App\Http\Controllers\admin;
use DB;
use Carbon\Carbon;
use App\Classes\table;
use App\Classes\permission;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Controller;

class AttendanceController extends Controller
{
    
    public function index() 
    {   
        // Verifica permissão de acesso
        if (permission::permitted('attendance')=='fail'){ return redirect()->route('denied'); }

        // Obtém dados de presença para exibição na página inicial
        $data = table::attendance()->orderBy('date', 'desc')->take(60)->get();
        $ss = table::settings()->select('clock_comment', 'time_format')->first();
        $employees = table::people()
		->join('tbl_company_data', 'tbl_people.id', '=', 'tbl_company_data.reference')
		->get();
        $tf = table::settings()->value("time_format");
        $cc = table::settings()->value("clock_comment");
        
        return view('admin.attendance', compact('data', 'ss', 'employees', 'tf', 'cc'));
    }
    
    public function clock()
    {   // Retorna a view do relógio para registro de presença
        return view('clock');
    }

    public function edit($id, Request $request)
    {
        if (permission::permitted('attendance-edit')=='fail'){ return redirect()->route('denied'); }

        // Obtém dados para edição de presença
        $a = table::attendance()->where('id', $id)->first();
        $e_id = ($a->id == null) ? 0 : Crypt::encryptString($a->id) ;
        $tf = table::settings()->value("time_format");

        return view('admin.edits.edit-attendance', compact('a', 'e_id', 'tf'));
    }

    public function delete($id, Request $request)
    {   // Verifica permissão de exclusão
        if (permission::permitted('attendance-delete')=='fail'){ return redirect()->route('denied'); }

        $id = $request->id;
        // Deleta entrada de presença
        table::attendance()->where('id', $id)->delete();

        return redirect('attendance')->with('success', trans("Deleted!"));
    }

    public function update(Request $request)
    {   // Verifica permissão de edição
        if (permission::permitted('attendance-edit')=='fail') { return redirect()->route('denied'); }

        $v = $request->validate([
            //'id' => 'required|max:200',
           // 'idno' => 'required|max:100',
           // 'timein' => 'required|max:15',
           // 'timeout' => 'required|max:15',
            //'reason' => 'required|max:255',
        ]);

        $id = Crypt::decryptString($request->id);
        $idno = $request->idno;
        $timeIN = date("Y-m-d h:i:s A", strtotime($request->timein_date." ".$request->timein));
        $timeOUT = date("Y-m-d h:i:s A", strtotime($request->timeout_date." ".$request->timeout));
        $reason = $request->reason;

        // Lógica para calcular horários e status de entrada e saída
        // Atualiza os dados de presença
        // Redireciona para a página de presença com mensagem de sucesso

        $sched_in_time = table::schedules()->where([
            ['idno', '=', $idno], 
            ['archive', '=', '0'],
        ])->value('intime');

        if($sched_in_time == null)
        {
            $status_in = "Ok";
        } else {
            $sched_clock_in_time_24h = date("H.i", strtotime($sched_in_time));
            $time_in_24h = date("H.i", strtotime($timeIN));

            if ($time_in_24h <= $sched_clock_in_time_24h) 
            {
                $status_in = 'In Time';
            } else {
                $status_in = 'Late In';
            }
        }

        $sched_out_time = table::schedules()->where([
            ['idno', '=', $idno], 
            ['archive','=','0'],
        ])->value('outime');
        
        if($sched_out_time == null) 
        {
            $status_out = "Ok";
        } else {
            $sched_clock_out_time_24h = date("H.i", strtotime($sched_out_time));
            $time_out_24h = date("H.i", strtotime($timeOUT));

            if($time_out_24h >= $sched_clock_out_time_24h) 
            {
                $status_out = 'On Time';
            } else {
                $status_out = 'Early Out';
            }
        }

        $t1 = Carbon::createFromFormat("Y-m-d h:i:s A", $timeIN); 
        $t2 = Carbon::createFromFormat("Y-m-d h:i:s A", $timeOUT); 
        $th = $t1->diffInHours($t2);
        $tm = floor(($t1->diffInMinutes($t2) - (60 * $th)));
        $totalhour = $th.".".$tm;

        table::attendance()->where('id', $id)->update([
            'timein' => $timeIN,
            'timeout' => $timeOUT,
            'reason' => $reason, 
            'totalhours' => $totalhour,
            'status_timein' => $status_in,
            'status_timeout' => $status_out,
        ]);

        return redirect('attendance')->with('success', trans("Os dados foram atualizados com sucesso."));
    }

    public function addEntry(Request $request)
    {
        // Verifica permissão de acesso
        if (permission::permitted('attendance')=='fail'){ return redirect()->route('denied'); }

        // Validação dos dados do formulário e lógica para adicionar uma nova entrada de presença
        // Redireciona para a página de presença com mensagem de sucesso ou erro

        if ($request->ref == NULL) {
            return redirect('attendance')->with('error', trans("A operação de registro de presença não pôde ser concluída. Por favor, tente novamente ou entre em contato com o suporte."));
        }

        $v = $request->validate([
            'ref' => 'required|max:250',
            'date' => 'required|max:15',
            'timein' => 'required|max:15',
            'timeout' => 'nullable|max:15',
        ]);

        $reference = $request->ref;
        $date = date('Y-m-d', strtotime($request->date));
        $timein = date('h:i:s A', strtotime($request->timein));
        $timeout = ($request->timeout != null) ? date('h:i:s A', strtotime($request->timeout)) : null ;
        $ip = $request->ip();
        $tf = table::settings()->value('time_format');

        // ip resriction
        $iprestriction = table::settings()->value('iprestriction');
        if ($iprestriction != NULL) 
        {
            $ips = explode(",", $iprestriction);

            if(in_array($ip, $ips) == false) 
            {
                return redirect('attendance')->with('error', trans("Opa! Você não tem permissão para entrar ou sair do seu endereço IP")." ".$ip);
            }
        } 

        $emp_id = table::companydata()->where('id', $reference)->value('reference');
        $emp_idno = table::companydata()->where('id', $reference)->value('idno');
        
        if($emp_id == null || $emp_idno == null) {
            return redirect('attendance')->with('error', trans("Employee is not found."));
        }

        $emp = table::people()->where('id', $emp_id)->first();
        $lastname = $emp->lastname;
        $firstname = $emp->firstname;
        $mi = $emp->mi;
        $employee = mb_strtoupper($firstname);

        if ($timeout == null) 
        {
            $has = table::attendance()->where([['idno', $emp_idno],['date', $date]])->exists();

            if ($has == 1) 
            {
                $hti = table::attendance()->where([['idno', $emp_idno],['date', $date]])->value('timein');
                $hti = date('h:i A', strtotime($hti));
                $hti_24 = ($tf == 1) ? $hti : date("H:i", strtotime($hti)) ;

                return redirect('attendance')->with('error', trans("The employee already clock in today at")." ".$hti_24);

            } else {

                $sched_in_time = table::schedules()->where([['idno', $emp_idno], ['archive', 0]])->value('intime');
                
                if($sched_in_time == NULL)
                {
                    $status_in = "Ok";
                } else {
                    $sched_clock_in_time_24h = date("H.i", strtotime($sched_in_time));
                    $time_in_24h = date("H.i", strtotime($timein));

                    if ($time_in_24h <= $sched_clock_in_time_24h) 
                    {
                        $status_in = 'In Time';
                    } else {
                        $status_in = 'Late In';
                    }
                }

                table::attendance()->insert([
                    [
                        'idno' => $emp_idno,
                        'reference' => $emp_id,
                        'date' => $date,
                        'employee' => $employee,
                        'timein' => $date." ".$timein,
                        'status_timein' => $status_in,
                    ],
                ]);

                return redirect('attendance')->with('success', trans("Presença adicionada com sucesso."));
            }
        }

        if ($timeout != null && $timein != null) 
        {
            $has = table::attendance()->where([['idno', $emp_idno],['date', $date]])->exists();

            if ($has == 1) 
            {
                $hti = table::attendance()->where([['idno', $emp_idno],['date', $date]])->value('timein');
                $hti = date('h:i A', strtotime($hti));
                $hti_24 = ($tf == 1) ? $hti : date("H:i", strtotime($hti)) ;

                return redirect('attendance')->with('error', trans("The employee already clock in today at")." ".$hti_24);

            } else {

                $sched_in_time = table::schedules()->where([['idno', $emp_idno], ['archive', 0]])->value('intime');
                $sched_out_time = table::schedules()->where([['idno', $emp_idno], ['archive', 0]])->value('outime');
                
                if($sched_in_time == NULL)
                {
                    $status_in = "Ok";
                } else {
                    $sched_clock_in_time_24h = date("H.i", strtotime($sched_in_time));
                    $time_in_24h = date("H.i", strtotime($timein));

                    if ($time_in_24h <= $sched_clock_in_time_24h) 
                    {
                        $status_in = 'In Time';
                    } else {
                        $status_in = 'Late In';
                    }
                }

                if($sched_out_time == NULL) 
                {
                    $status_out = "Ok";
                } else {
                    $sched_clock_out_time_24h = date("H.i", strtotime($sched_out_time));
                    $time_out_24h = date("H.i", strtotime($timeout));
                    
                    if($time_out_24h >= $sched_clock_out_time_24h) 
                    {
                        $status_out = 'On Time';
                    } else {
                        $status_out = 'Early Out';
                    }
                }

                $time1 = Carbon::createFromFormat("Y-m-d h:i:s A", $date." ".$timein); 
                $time2 = Carbon::createFromFormat("Y-m-d h:i:s A", $date." ".$timeout); 
                $th = $time1->diffInHours($time2);
                $tm = floor(($time1->diffInMinutes($time2) - (60 * $th)));
                $totalhour = $th.".".$tm;

                table::attendance()->insert([
                    [
                        'idno' => $emp_idno,
                        'reference' => $emp_id,
                        'date' => $date,
                        'employee' => $employee,
                        'timein' => $date." ".$timein,
                        'status_timein' => $status_in,
                        'timeout' => $date." ".$timeout,
                        'totalhours' => $totalhour,
                        'status_timeout' => $status_out,
                    ],
                ]);

                return redirect('attendance')->with('success', trans("Presença adicionada com sucesso."));
            }
        }
    }

    public function getFilter(Request $request) 
	{
		// Verifica permissão de acesso
        if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		$datefrom = $request->datefrom;
		$dateto = $request->dateto;

        // Obtém dados filtrados por data, se fornecidos, e retorna como JSON
		
		if ($datefrom == null AND $dateto == null) 
		{
			$data = table::attendance()->select('id', 'idno', 'date', 'employee', 'timein', 'timeout', 'totalhours', 'comment', 'status_timein', 'status_timeout')->get();
			return response()->json($data);
		}

        if ($datefrom !== null AND $dateto !== null) 
        {
			$data = table::attendance()->whereBetween('date', [$datefrom, $dateto])->select('id', 'idno', 'date', 'employee', 'timein', 'timeout', 'totalhours', 'comment', 'status_timein', 'status_timeout')->get();
			return response()->json($data);
		} 
	}
}
