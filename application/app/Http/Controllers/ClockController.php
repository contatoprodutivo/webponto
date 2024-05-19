<?php
namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Classes\table;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClockController extends Controller
{
    // Exibe a página do relógio com as configurações necessárias
    public function clock()
    {
        $data = table::settings()->where('id', 1)->first();
        $cc = $data->clock_comment;
        $tz = $data->timezone;
        $tf = $data->time_format;
        $rfid = $data->rfid;

        return view('clock', compact('cc', 'tz', 'tf', 'rfid'));
    }

    // Adiciona um registro de entrada/saída
    public function add(Request $request)
    {
        if ($request->idno == NULL) {
            return response()->json(["error" => "Por favor, insira seu ID."]);
        }

        $idno = strtoupper($request->idno);


        $ip = $request->ip();
        $validator = \Validator::make($request->all(), [
            'idno' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors()->first()]);
        }

        $currentTime = Carbon::now();
        $date = $currentTime->format('Y-m-d');
        $tf = table::settings()->value('time_format');
        $timeFormatted = $tf == 1 ? $currentTime->format('h:i:s A') : $currentTime->format('H:i:s');

        // Verificar se já existem registros de entrada e saída no mesmo dia
        $fullDayRecord = table::attendance()
                            ->where('idno', $idno)
                            ->where('date', $date)
                            ->whereNotNull('timein')
                            ->whereNotNull('timeout')
                            ->exists();

        if ($fullDayRecord) {
            return response()->json([
                "error" => "Não são permitidos mais registros de entrada hoje."
            ]);
        }

        // Recuperar o último registro
        $lastRecord = table::attendance()
                        ->where('idno', $idno)
                        ->orderBy('date', 'desc')
                        ->orderBy('timein', 'desc')
                        ->first();

        // Recupera os dados pessoais do funcionário da tabela de pessoas usando o ID do funcionário
        $employee_id = table::companydata()->where('idno', $idno)->value('reference');

        // Verifica se o ID de referência do funcionário é nulo e retorna um erro se não for encontrado
        if ($employee_id == null) {
            return response()->json([
                "error" => "Matrícula não localizada, procure a secretaria."
            ]);
        }

        $emp = table::people()->where('id', $employee_id)->first();
        $lastname = $emp->lastname;
        $firstname = $emp->firstname;
        $mi = $emp->mi;
        $employee = mb_strtoupper("$firstname $lastname");

        $type = 'timein'; // Assumindo que a operação padrão é uma nova entrada
        if ($lastRecord) {
            $lastRecordTime = $lastRecord->timein ? Carbon::createFromFormat('Y-m-d h:i:s A', $lastRecord->timein) : null;
            if ($lastRecordTime && $lastRecordTime->format('Y-m-d') == $date) {
                $diffInMinutes = $currentTime->diffInMinutes($lastRecordTime);
                if ($diffInMinutes <= 10) {
                    return response()->json([
                        "error" => "Você deve esperar 10 minutos antes de registrar novamente."
                    ]);
                } elseif (!$lastRecord->timeout) {
                    $type = 'timeout';
                    $timeoutTime = $currentTime->format('Y-m-d h:i:s A');
                    $hoursWorked = $lastRecordTime->diffInMinutes($currentTime) / 60; // Calcula a diferença em minutos e converte para horas

                    table::attendance()
                        ->where('idno', $idno)
                        ->where('date', $date)
                        ->whereNull('timeout')
                        ->update([
                            'timeout' => $timeoutTime, 
                            'status_timeout' => 'Ok', 
                            'totalhours' => number_format($hoursWorked, 2) // Usa number_format para formatar com 2 casas decimais
                        ]);

                    return response()->json([
                        "type" => $type,
                        "time" => $timeFormatted,
                        "idno" => $idno,
                        "employee" => $employee, // Adicionado para garantir que o frontend receba o nome completo
                        "message" => "Registro de saída efetuado com sucesso.",
                        "hours" => number_format($hoursWorked, 2) // Exibe as horas trabalhadas formatadas corretamente
                    ]);
                }
            } else {
                // Se a última entrada foi em um dia diferente, tratar como nova entrada
                $type = 'timein';
            }
        }

        if ($type == 'timein') {
            $attendanceData = [
                'idno' => $idno,
                'date' => $date,
                'timein' => $currentTime->format('Y-m-d h:i:s A'),
                'employee' => $employee, // Agora usando o nome real do funcionário
                'status_timein' => 'Ok'
            ];

            if ($request->filled('comment')) {
                $attendanceData['comment'] = strtoupper($request->comment);
            }

            table::attendance()->insert($attendanceData);

            return response()->json([
                "type" => $type,
                "time" => $timeFormatted,
                "idno" => $idno,
                "employee" => $employee, // Adicionado para garantir que o frontend receba o nome completo
                "message" => "Entrada registrada com sucesso."
            ]);
        }
    }
}
