<?php
/*
* Workday - Uma aplicação de relógio de ponto para funcionários
* Email: official.codefactor@gmail.com
* Version: 1.1
* Author: Brian Luna
* Copyright 2020 Codefactor
*/
namespace App\Http\Controllers;
use DB;
use Carbon\Carbon;
use App\Classes\table;
use App\Classes\permission;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClockController extends Controller
{
    // Método para exibir o relógio de ponto
    public function clock()
    {
        $data = table::settings()->where('id', 1)->first();
        $cc = $data->clock_comment;
        $tz = $data->timezone;
        $tf = $data->time_format;
        $rfid = $data->rfid;

        return view('clock', compact('cc', 'tz', 'tf', 'rfid'));
    }

    // Método para adicionar um registro de ponto
    public function add(Request $request)
    {
        // Verifica se o ID do funcionário ou tipo é nulo
        if ($request->idno == NULL || $request->type == NULL) 
        {
            return response()->json([
                "error" => trans("Please enter your ID.")
            ]);
        }

        // Verifica se o ID do funcionário é válido
        if(strlen($request->idno) >= 20 || strlen($request->type) >= 20) 
        {
            return response()->json([
                "error" => trans("Invalid Employee ID.")
            ]);
        }

        $idno = strtoupper($request->idno);
        $type = $request->type;
        $date = date('Y-m-d');
        $time = date('h:i:s A');
        $comment = strtoupper($request->clockin_comment);
        $ip = $request->ip();

        // Recurso de comentário no ponto
        $clock_comment = table::settings()->value('clock_comment');
        $tf = table::settings()->value('time_format');
        $time_val = ($tf == 1) ? $time : date("H:i:s", strtotime($time));

        // Verifica se o comentário é necessário
        if ($clock_comment == "on") 
        {
            if ($comment == NULL) 
            {
                return response()->json([
                    "error" => trans("Please provide your comment!")
                ]);
            }
        }

        // Restrição de IP
        $iprestriction = table::settings()->value('iprestriction');
        if ($iprestriction != NULL) 
        {
            $ips = explode(",", $iprestriction);

            if(in_array($ip, $ips) == false) 
            {
                $msge = trans("Whoops! You are not allowed to Clock In or Out from your IP address")." ".$ip;
                return response()->json([
                    "error" => $msge,
                ]);
            }
        } 

        // Busca o ID de referência do funcionário na tabela de dados da empresa usando o ID fornecido
        $employee_id = table::companydata()->where('idno', $idno)->value('reference');

        // Verifica se o ID de referência do funcionário é nulo e retorna um erro se for inválido
        if($employee_id == null) {
            return response()->json([
                "error" => trans("You enter an invalid ID. $idno")
            ]);
        }


                // Recupera os dados pessoais do funcionário da tabela de pessoas usando o ID do funcionário
            $emp = table::people()->where('id', $employee_id)->first();
            $lastname = $emp->lastname;     // Sobrenome do funcionário
            $firstname = $emp->firstname;   // Primeiro nome do funcionário
            $mi = $emp->mi;                 // Inicial do nome do meio do funcionário
            $employee = mb_strtoupper($lastname.', '.$firstname.' '.$mi); // Monta o nome completo do funcionário em maiúsculas

            // Verifica se o tipo de registro é de entrada (timein)
            if ($type == 'timein') 
            {
                // Verifica se já existe um registro de entrada para o funcionário na data especificada
                $has = table::attendance()->where([['idno', $idno],['date', $date]])->exists();

                // Se já existe um registro de entrada, recupera o horário e informa que já foi realizado
                if ($has == 1) 
                {
                    $hti = table::attendance()->where([['idno', $idno],['date', $date]])->value('timein');
                    $hti = date('h:i A', strtotime($hti)); // Converte o horário para o formato AM/PM
                    $hti_24 = ($tf == 1) ? $hti : date("H:i", strtotime($hti)); // Converte para formato 24 horas se necessário

                    return response()->json([
                        "employee" => $employee, // Retorna o nome completo do funcionário
                        "error" => trans("You already Time In today at")." ".$hti_24, // Informa que já existe um registro de entrada
                    ]);
                
            


        } else {
            // Conta quantas vezes o funcionário fez um registro de entrada sem correspondente de saída
            $last_in_notimeout = table::attendance()->where([['idno', $idno],['timeout', NULL]])->count();
        
            // Se houver um registro sem saída, pede para o funcionário realizar o registro de saída
            if($last_in_notimeout >= 1)
            {
                return response()->json([
                    "error" => trans("Please Clock Out from your last Clock In.")
                ]);
        
            } else {
                // Recupera o horário programado de entrada do funcionário
                $sched_in_time = table::schedules()->where([['idno', $idno], ['archive', 0]])->value('intime');
                
                // Define o status de entrada com base no horário programado
                if($sched_in_time == NULL)
                {
                    $status_in = "Ok";
                } else {
                    // Converte o horário programado e o horário atual para formato de 24 horas para comparação
                    $sched_clock_in_time_24h = date("H.i", strtotime($sched_in_time));
                    $time_in_24h = date("H.i", strtotime($time));
        
                    // Define o status de entrada como "No Horário" ou "Atrasado"
                    if ($time_in_24h <= $sched_clock_in_time_24h) 
                    {
                        $status_in = 'In Time';
                    } else {
                        $status_in = 'Late In';
                    }
                }
        
                // Se o comentário no registro de ponto está habilitado e um comentário foi fornecido
                if($clock_comment == "on" && $comment != NULL) 
                {
                    // Insere o registro de entrada com comentário na tabela de frequência
                    table::attendance()->insert([
                        [
                            'idno' => $idno,
                            'reference' => $employee_id,
                            'date' => $date,
                            'employee' => $employee,
                            'timein' => $date." ".$time,
                            'status_timein' => $status_in,
                            'comment' => $comment,
                        ],
                    ]);
                } else {
                    // Insere o registro de entrada sem comentário na tabela de frequência
                    table::attendance()->insert([
                        [
                            'idno' => $idno,
                            'reference' => $employee_id,
                            'date' => $date,
                            'employee' => $employee,
                            'timein' => $date." ".$time,
                            'status_timein' => $status_in,
                        ],
                    ]);
                }
        
                // Retorna a resposta JSON com os dados do registro de ponto realizado
                return response()->json([
                    "type" => $type,
                    "time" => $time_val,
                    "date" => $date,
                    "lastname" => $lastname,
                    "firstname" => $firstname,
                    "mi" => $mi,
                ]);
            }
        }
    }

  
       // Verifica se o tipo de registro é de saída (timeout)
if ($type == 'timeout') 
{
    // Recupera o último horário de entrada ainda não fechado com saída
    $timeIN = table::attendance()->where([['idno', $idno], ['timeout', NULL]])->value('timein');
    $clockInDate = table::attendance()->where([['idno', $idno],['timeout', NULL]])->value('date');
    // Verifica se já existe um registro de saída para a data atual
    $hasout = table::attendance()->where([['idno', $idno],['date', $date]])->value('timeout');
    // Define o horário de saída atual
    $timeOUT = date("Y-m-d h:i:s A", strtotime($date." ".$time));

    // Se não houver horário de entrada, retorna erro pedindo para registrar a entrada primeiro
    if($timeIN == NULL) 
    {
        return response()->json([
            "error" => trans("Please Clock In before Clocking Out.")
        ]);
    } 

    // Se já existe um registro de saída para o dia, informa ao usuário
    if ($hasout != NULL) 
    {
        $hto = table::attendance()->where([['idno', $idno],['date', $date]])->value('timeout');
        $hto = date('h:i A', strtotime($hto));
        $hto_24 = ($tf == 1) ? $hto : date("H:i", strtotime($hto));

        return response()->json([
            "employee" => $employee,
            "error" => trans("You already Time Out today at")." ".$hto_24,
        ]);

    } else {
        // Recupera o horário programado de saída
        $sched_out_time = table::schedules()->where([['idno', $idno], ['archive', 0]])->value('outime');
        
        // Determina o status de saída com base no horário programado
        if($sched_out_time == NULL) 
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

        // Calcula a duração total do turno trabalhado
        $time1 = Carbon::createFromFormat("Y-m-d h:i:s A", $timeIN); 
        $time2 = Carbon::createFromFormat("Y-m-d h:i:s A", $timeOUT); 
        $th = $time1->diffInHours($time2);
        $tm = floor(($time1->diffInMinutes($time2) - (60 * $th)));
        $totalhour = $th.".".$tm;

        // Atualiza o registro de frequência com o horário de saída e a duração total
        table::attendance()->where([['idno', $idno],['date', $clockInDate]])->update(array(
            'timeout' => $timeOUT,
            'totalhours' => $totalhour,
            'status_timeout' => $status_out)
        );
        
        // Retorna a resposta JSON com os dados do registro de saída realizado
        return response()->json([
            "type" => $type,
            "time" => $time_val, 
            "date" => $date,
            "lastname" => $lastname,
            "firstname" => $firstname,
            "mi" => $mi,
        ]);
    }
}
}
}
