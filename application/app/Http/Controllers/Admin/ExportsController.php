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
use App\Classes\table;
use App\Classes\permission;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Storage;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportsController extends Controller
{
		
	function company(Request $request) 
	{
		if (permission::permitted('company')=='fail'){ return redirect()->route('denied'); }

		$date = date('Y-m-d');
        $time = date('h-i-sa');
		$file = 'companies-'.$date.'T'.$time.'.csv';

		$c = table::company()->get();

		Storage::put($file, '', 'private');

		foreach ($c as $d) 
		{
		    Storage::prepend($file, $d->id .','. $d->company);
		}

		Storage::prepend($file, '"ID"' .','. 'COMPANY');

		return Storage::download($file);
    }

	function department(Request $request) 
	{
		if (permission::permitted('departments')=='fail'){ return redirect()->route('denied'); }

		$d = table::department()->get();

		$date = date('Y-m-d');
        $time = date('h-i-sa');
		$file = 'departments-'.$date.'T'.$time.'.csv';

		Storage::put($file, '', 'private');

		foreach ($d as $i) 
		{
		    Storage::prepend($file, $i->id .','. $i->department);
		}

		Storage::prepend($file, '"ID"' .','. 'DEPARTMENT');

		return Storage::download($file);
    }

	function jobtitle(Request $request) 
	{
		if (permission::permitted('jobtitles')=='fail'){ return redirect()->route('denied'); }

		$j = table::jobtitle()->get();

		$date = date('Y-m-d');
        $time = date('h-i-sa');
		$file = 'jobtitles-'.$date.'T'.$time.'.csv';

		Storage::put($file, '', 'private');

		foreach ($j as $d) 
		{
		    Storage::prepend($file, $d->id .','. $d->jobtitle .','. $d->dept_code);
		}

		Storage::prepend($file, '"ID"' .','. 'DEPARTMENT' .','. 'DEPARTMENT CODE');

		return Storage::download($file);
    }

	function leavetypes(Request $request) 
	{
		if (permission::permitted('leavetypes')=='fail'){ return redirect()->route('denied'); }
		
		$l = table::leavetypes()->get();

		$date = date('Y-m-d');
        $time = date('h-i-sa');
		$file = 'leavetypes-'.$date.'T'.$time.'.csv';

		Storage::put($file, '', 'private');

		foreach ($l as $d) 
		{
		    Storage::prepend($file, $d->id .','. $d->leavetype .','. $d->limit .','. $d->percalendar);
		}

		Storage::prepend($file, '"ID"' .','. 'LEAVE TYPE' .','. 'LIMIT' .','. 'TYPE');

		return Storage::download($file);
    }

	function employeeList() 
	{
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }

		$p = table::people()->get();

		$date = date('Y-m-d');
        $time = date('h-i-sa');
		$file = 'employee-lists-'.$date.'T'.$time.'.csv';

		Storage::put($file, '', 'private');

		foreach ($p as $d) 
		{
		    Storage::prepend($file, $d->id .','. $d->lastname.' '.$d->firstname.' '.$d->mi .','. $d->age .','. $d->gender .','. $d->civilstatus .','. $d->mobileno .','. $d->emailaddress .','. $d->employmenttype .','. $d->employmentstatus);
		}

		Storage::prepend($file, '"ID"' .','. 'EMPLOYEE' .','. 'AGE' .','. 'GENDER' .','. 'CIVILSTATUS' .','. 'MOBILE NUMBER' .','. 'EMAIL ADDRESS' .','. 'EMPLOYMENT TYPE' .','. 'EMPLOYMENT STATUS');

		return Storage::download($file);
	}

	//INICIO DO RELATÓRIO DE PRESENÇA DE USUÁRIO
	public function attendanceReport(Request $request) 
{
    if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }

    $id = $request->emp_id;
    $company = $request->company; // Certifique-se de obter o valor do filtro da empresa
    $datefrom = $request->datefrom;
    $dateto = $request->dateto;
    $format = $request->format; // 'csv' or 'pdf'

    Log::info('attendanceReport request parameters', [
        'emp_id' => $id,
        'company' => $company,
        'datefrom' => $datefrom,
        'dateto' => $dateto,
        'format' => $format,
    ]);

    $query = DB::table('tbl_people_attendance as b')
        ->join('tbl_company_data as a', 'a.idno', '=', 'b.idno')
        ->select('b.date', 'b.idno', 'b.employee', 'a.company', 'a.department', 'b.timein', 'b.timeout', 'b.totalhours');

    if ($id) {
        $query->where('b.idno', $id);
    }

    if ($company) {
        $query->where('a.company', $company);
    }

    if ($datefrom && $dateto) {
        $query->whereBetween('b.date', [$datefrom, $dateto]);
    }

    $data = $query->get();

    Log::info('Generated query data', [
        'data' => $data,
    ]);

    if ($format == 'csv') {
        return $this->generateCSV($data);
    }
    elseif ($format == 'pdf') {
        return $this->generatePDF($data);
    }

    return redirect('reports/employee-attendance')->with('error', trans("Invalid format selected."));
}


//CSV do relatório de presença de usuário.
public function generateCSV($data)
{
    $date = date('Y-m-d');
    $time = date('h-i-sa');
    $file = 'Relatorio-presenca-usuario-'.$date.'T'.$time.'.csv';

    Log::info('Generating CSV report', [
        'file' => $file,
    ]);

    $handle = fopen(storage_path('app/'.$file), 'w');
    // Adiciona o BOM para garantir a correta exibição dos caracteres no Excel
    fwrite($handle, "\xEF\xBB\xBF");

    // Cabeçalho do CSV
    fputcsv($handle, ['Data', 'Matrícula', 'Usuário', 'Empresa', 'Turma', 'Entrada', 'Saída', 'Total de horas'], ';');

    foreach ($data as $d) 
    {
        fputcsv($handle, [
            $d->date,
            $d->idno,
            $d->employee,
            $d->company,
            $d->department,
            $d->timein,
            $d->timeout,
            $d->totalhours
        ], ';');
    }

    fclose($handle);

    Log::info('CSV file created successfully', [
        'file' => $file,
    ]);

    return Storage::download($file);
}



    function generatePDF($data)
    {
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);
            $html = '<h1>Attendance Report</h1>';
            $html .= '<table border="1" cellpadding="10">';
            $html .= '<thead><tr><th>ID</th><th>IDNO</th><th>DATE</th><th>EMPLOYEE</th><th>TIME IN</th><th>TIME OUT</th><th>TOTAL HOURS</th><th>STATUS-IN</th><th>STATUS-OUT</th></tr></thead>';
            $html .= '<tbody>';

            foreach ($data as $d) 
            {
                $html .= '<tr>';
                $html .= '<td>'.$d->id.'</td>';
                $html .= '<td>'.$d->idno.'</td>';
                $html .= '<td>'.$d->date.'</td>';
                $html .= '<td>'.$d->employee.'</td>';
                $html .= '<td>'.$d->timein.'</td>';
                $html .= '<td>'.$d->timeout.'</td>';
                $html .= '<td>'.$d->totalhours.'</td>';
                $html .= '<td>'.$d->status_timein.'</td>';
                $html .= '<td>'.$d->status_timeout.'</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('attendance-report.pdf', ['Attachment' => 1]);
        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'There was an error generating the PDF. Please try again.');
        }
    }

	public function testExcelExport()
{
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Adiciona alguns dados de exemplo
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'John Doe');
        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'Jane Doe');

        // Define o nome do arquivo e o caminho para salvá-lo
        $fileName = 'test-report.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/'.$fileName);
        $writer->save($filePath);

        // Força o download do arquivo e o exclui após o envio
        return response()->download($filePath)->deleteFileAfterSend(true);
    } catch (\Exception $e) {
        Log::error('Error generating Excel file: ' . $e->getMessage());
        return redirect()->back()->with('error', 'There was an error generating the Excel file. Please try again.');
    }
}





	function leavesReport(Request $request) 
	{
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		$id = $request->emp_id;
		$datefrom = $request->datefrom;
		$dateto = $request->dateto;

		if ($id == null AND $datefrom == null AND $dateto == null) 
		{
			$data = table::leaves()->get();
			$date = date('Y-m-d');
			$time = date('h-i-sa');
			$file = 'leave-reports-'.$date.'T'.$time.'.csv';

			Storage::put($file, '', 'private');

			foreach ($data as $d) 
			{
				Storage::prepend($file, $d->id .','. $d->idno .','. '"'.$d->employee.'"' .','. $d->type .','. $d->leavefrom .','. $d->leaveto .','. $d->reason .','. $d->status);
			}

			Storage::prepend($file, '"ID"' .','. 'IDNO' .','. 'EMPLOYEE' .','. 'TYPE' .','. 'LEAVE FROM' .','. 'LEAVE TO' .','. 'REASON' .','. 'STATUS');
			
			return Storage::download($file);
		}

		if ($id !== null AND $datefrom !== null AND $dateto !== null) 
		{
			$data = table::leaves()->where('idno', $id)->whereBetween('leavefrom', [$datefrom, $dateto])->get();
			$date = date('Y-m-d');
			$time = date('h-i-sa');
			$file = 'leave-reports-'.$date.'T'.$time.'.csv';

			Storage::put($file, '', 'private');

			foreach ($data as $d) 
			{
				Storage::prepend($file, $d->id .','. $d->idno .','. '"'.$d->employee.'"' .','. $d->type .','. $d->leavefrom .','. $d->leaveto .','. $d->reason .','. $d->status);
			}

			Storage::prepend($file, '"ID"' .','. 'IDNO' .','. 'EMPLOYEE' .','. 'TYPE' .','. 'LEAVE FROM' .','. 'LEAVE TO' .','. 'REASON' .','. 'STATUS');
			
			return Storage::download($file);
		}

		if($id !== null AND $datefrom == null AND $dateto == null ) 
		{
			$data = table::leaves()->where('idno', $id)->get();
			$date = date('Y-m-d');
			$time = date('h-i-sa');
			$file = 'leave-reports-'.$date.'T'.$time.'.csv';

			Storage::put($file, '', 'private');

			foreach ($data as $d) 
			{
				Storage::prepend($file, $d->id .','. $d->idno .','. '"'.$d->employee.'"' .','. $d->type .','. $d->leavefrom .','. $d->leaveto .','. $d->reason .','. $d->status);
			}

			Storage::prepend($file, '"ID"' .','. 'IDNO' .','. 'EMPLOYEE' .','. 'TYPE' .','. 'LEAVE FROM' .','. 'LEAVE TO' .','. 'REASON' .','. 'STATUS');
			
			return Storage::download($file);
		} 

		if ($id == null AND $datefrom !== null AND $dateto !== null) 
		{
			$data = table::leaves()->whereBetween('leavefrom', [$datefrom, $dateto])->get();
			$date = date('Y-m-d');
			$time = date('h-i-sa');
			$file = 'leave-reports-'.$date.'T'.$time.'.csv';

			Storage::put($file, '', 'private');

			foreach ($data as $d) 
			{
				Storage::prepend($file, $d->id .','. $d->idno .','. '"'.$d->employee.'"' .','. $d->type .','. $d->leavefrom .','. $d->leaveto .','. $d->reason .','. $d->status);
			}

			Storage::prepend($file, '"ID"' .','. 'IDNO' .','. 'EMPLOYEE' .','. 'TYPE' .','. 'LEAVE FROM' .','. 'LEAVE TO' .','. 'REASON' .','. 'STATUS');
			
			return Storage::download($file);
		}

		return redirect('reports/employee-leaves')->with('error', trans("Whoops! Please provide date range or select employee."));
	}

	function birthdaysReport() 
	{
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		$c = table::people()->join('tbl_company_data', 'tbl_people.id', '=', 'tbl_company_data.reference')->get();

		$date = date('Y-m-d');
		$time = date('h-i-sa');
		$file = 'employee-birthdays-'.$date.'T'.$time.'.csv';

		Storage::put($file, '', 'private');

		foreach ($c as $d) 
		{
		    Storage::prepend($file, $d->idno .','. $d->lastname.' '.$d->firstname.' '.$d->mi .','. $d->department .','. $d->jobposition .','. $d->birthday .','. $d->mobileno);
		}

		Storage::prepend($file, '"ID"' .','. 'EMPLOYEE NAME' .','. 'DEPARTMENT' .','. 'POSITION' .','. 'BIRTHDAY' .','. 'MOBILE NUMBER' );

		return Storage::download($file);
	}

	function accountReport() 
	{
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		$u = table::users()->get();

		$date = date('Y-m-d');
		$time = date('h-i-sa');
		$file = 'employee-accounts-'.$date.'T'.$time.'.csv';

		Storage::put($file, '', 'private');

		foreach ($u as $a) 
		{
			if($a->acc_type == 2) 
			{
				$a_type = 'Admin';
			} else {
				$a_type = 'Employee';
			}
			Storage::prepend($file, $a->name .','. $a->email .','. $a_type);
			Storage::prepend($file, 'EMPLOYEE NAME' .','. 'EMAIL' .','. 'ACCOUNT TYPE');

			return Storage::download($file);
		}
	}

	function scheduleReport(Request $request) 
	{
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		$id = $request->emp_id;

		if ($id == null) 
		{
			$data = table::schedules()->get();
			$date = date('Y-m-d');
			$time = date('h-i-sa');
			$file = 'schedule-reports-'.$date.'T'.$time.'.csv';

			Storage::put($file, '', 'private');

			foreach ($data as $d) 
			{
				Storage::prepend($file, $d->idno .',"'. $d->employee .'",'. $d->intime .','. '"'.$d->outime.'"' .','. $d->datefrom .','. $d->dateto .','. $d->hours .',"'. $d->restday .'",'. $d->archive);
			}

			Storage::prepend($file, '"IDNO"' .','. 'EMPLOYEE' .','. 'START TIME' .','. 'OFF TIME' .','. 'DATE FROM' .','. 'DATE TO' .','. 'HOURS' .','. 'RESTDAY' .','. 'STATUS');
			
			return Storage::download($file);
		}

		if ($id !== null) 
		{
			$data = table::schedules()->where('idno', $id)->get();
			$date = date('Y-m-d');
			$time = date('h-i-sa');
			$file = 'schedule-reports-'.$date.'T'.$time.'.csv';

			Storage::put($file, '', 'private');

			foreach ($data as $d) 
			{
				Storage::prepend($file, $d->idno .',"'. $d->employee .'",'. $d->intime .','. '"'.$d->outime.'"' .','. $d->datefrom .','. $d->dateto .','. $d->hours .',"'. $d->restday .'",'. $d->archive);
			}

			Storage::prepend($file, '"IDNO"' .','. 'EMPLOYEE' .','. 'START TIME' .','. 'OFF TIME' .','. 'DATE FROM' .','. 'DATE TO' .','. 'HOURS' .','. 'RESTDAY' .','. 'STATUS');
			
			return Storage::download($file);
		}

		return redirect('reports/employee-schedule')->with('error', trans("Whoops! Please select employee."));
	}

}
