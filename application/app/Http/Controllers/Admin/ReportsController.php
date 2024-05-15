<?php
/*
* Workday - Uma aplicação de relógio de ponto para funcionários
* Email: official.codefactor@gmail.com
* Versão: 1.1
* Autor: Brian Luna
* Direitos Autorais 2020 Codefactor
*/

namespace App\Http\Controllers\admin;
use DB;
use DateTimeZone;
use DateTime;
use App\Classes\table;
use App\Classes\permission;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    // Exibe a página principal dos relatórios
	public function index() 
	{
		// Verifica permissão de acesso aos relatórios
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		// Obtém as últimas visualizações dos relatórios
		$lastviews = table::reportviews()->get();

    	// Retorna a view dos relatórios com as últimas visualizações
    	return view('admin.reports', ['lastviews' => $lastviews]);
    }

    // Lista de funcionários: Exibe todos os funcionários e atualiza a última visualização
	public function empList(Request $request) 
	{
		// Verifica permissão de acesso à lista de funcionários
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Define a data atual formatada
		$today = date('M, d Y');
		// Obtém a lista de todos os funcionários
		$empList = table::people()->get();
		// Atualiza a data da última visualização do relatório de funcionários
		table::reportviews()->where('report_id', 1)->update(['last_viewed' => $today]);

		// Retorna a view da lista de funcionários com os dados obtidos
		return view('admin.reports.report-employee-list', compact('empList'));
	}

    // Presença de funcionários: Exibe presenças e atualiza a última visualização
	public function empAtten(Request $request) 
{
		// Verifica permissão de acesso ao relatório de presença de funcionários
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
    
		// Define a data atual formatada
		$today = date('M, d Y');
		
    // Consulta para selecionar dados de presença dos funcionários com join na tabela de dados da empresa
    	$empAtten = DB::table('tbl_company_data as a')
        ->join('tbl_people_attendance as b', 'a.idno', '=', 'b.idno')
        ->select('a.company', 'a.department', 'b.*')
        ->get();

		// Seleciona funcionários ativos e seus dados da empresa associada
		$employee = table::people()
		->join('tbl_company_data', 'tbl_people.id', '=', 'tbl_company_data.reference')
		->where('tbl_people.employmentstatus', 'Active')
		->get();

		// Atualiza a data da última visualização do relatório de presença
		table::reportviews()
		->where('report_id', 2)
		->update(array('last_viewed' => $today));

        // Obtém o formato de tempo das configurações
        $tf = table::settings()->value("time_format");

		// Retorna a view da presença de funcionários com os dados obtidos
		return view('admin.reports.report-employee-attendance', compact('empAtten', 'employee', 'tf'));
	}

    // Licenças de funcionários: Exibe as licenças e atualiza a última visualização
	public function empLeaves(Request $request) 
	{
		// Verifica permissão de acesso ao relatório de licenças de funcionários
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Define a data atual formatada
		$today = date('M, d Y');
		// Seleciona funcionários ativos e seus dados da empresa associada
		$employee = table::people()->join('tbl_company_data', 'tbl_people.id', '=', 'tbl_company_data.reference')->where('tbl_people.employmentstatus', 'Active')->get();
		// Obtém a lista de licenças dos funcionários
		$empLeaves = table::leaves()->get();
		// Atualiza a data da última visualização do relatório de licenças
		table::reportviews()->where('report_id', 3)->update(array('last_viewed' => $today));

		// Retorna a view das licenças de funcionários com os dados obtidos
		return view('admin.reports.report-employee-leaves', compact('empLeaves', 'employee'));
	}

    // Escala de funcionários: Exibe escalas e atualiza a última visualização
	public function empSched(Request $request) 
	{
		// Verifica permissão de acesso ao relatório de escalas de funcionários
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Define a data atual formatada
		$today = date('M, d Y');
		// Obtém a lista de escalas dos funcionários, ordenadas por arquivamento
		$empSched = table::schedules()->orderBy('archive', 'ASC')->get();
		// Seleciona funcionários ativos e seus dados da empresa associada
		$employee = table::people()->join('tbl_company_data', 'tbl_people.id', '=', 'tbl_company_data.reference')->where('tbl_people.employmentstatus', 'Active')->get();
		// Atualiza a data da última visualização do relatório de escalas
		table::reportviews()->where('report_id', 4)->update(array('last_viewed' => $today));
		// Obtém o formato de tempo das configurações
		$tf = table::settings()->value("time_format");

		// Retorna a view das escalas de funcionários com os dados obtidos
		return view('admin.reports.report-employee-schedule', compact('empSched', 'employee', 'tf'));
	}

    // Perfil organizacional: Exibe informações organizacionais e atualiza a última visualização
	public function orgProfile(Request $request) 
	{
		// Verifica permissão de acesso ao relatório do perfil organizacional
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Define a data atual formatada
		$today = date('M, d Y');
		// Seleciona funcionários ativos e seus dados da empresa associada
		$ed = table::people()->join('tbl_company_data', 'tbl_people.id', '=', 'tbl_company_data.reference')->where('tbl_people.employmentstatus', 'Active')->get();
		
		// Conta a quantidade de funcionários por faixa etária
		$age_18_24 = table::people()->where([['age', '>=', '18'], ['age', '<=', '24']])->count();
		$age_25_31 = table::people()->where([['age', '>=', '25'], ['age', '<=', '31']])->count();
		$age_32_38 = table::people()->where([['age', '>=', '32'], ['age', '<=', '38']])->count();
		$age_39_45 = table::people()->where([['age', '>=', '39'], ['age', '<=', '45']])->count();
		$age_46_100 = table::people()->where('age', '>=', '46')->count();
		
		// Inicializa os valores de contagem como zero se forem nulos
		if($age_18_24 == null) {$age_18_24 = 0;};
		if($age_25_31 == null) {$age_25_31 = 0;};
		if($age_32_38 == null) {$age_32_38 = 0;};
		if($age_39_45 == null) {$age_39_45 = 0;};
		if($age_46_100 == null) {$age_46_100 = 0;};	

		// Concatena as contagens de faixa etária
		$age_group = $age_18_24.','.$age_25_31.','.$age_32_38.','.$age_39_45.','.$age_46_100;
		// Variáveis de controle para dados de empresas, departamentos e outros atributos
		$dcc = null; 
		$dpc = null;
		$dgc = null;
		$csc = null;
		$yhc = null;

		// Conta a quantidade de funcionários por empresa, departamento, gênero, estado civil e ano de contratação
		foreach ($ed as $c) { $comp[] = $c->company; $dcc = array_count_values($comp); }
		$cc = ($dcc == null) ? null : implode($dcc, ', ') . ',' ;

		foreach ($ed as $d) { $dept[] = $d->department; $dpc = array_count_values($dept); }
		$dc = ($dpc == null) ? null : implode($dpc, ', ') . ',' ;

		foreach ($ed as $g) { $gender[] = $g->gender; $dgc = array_count_values($gender); }
		$gc = ($dgc == null) ? null : implode($dgc, ', ') . ',' ;

		foreach ($ed as $cs) { $civilstatus[] = $cs->civilstatus; $csc = array_count_values($civilstatus); }
		$cg = ($csc == null) ? null : implode($csc, ', ') . ',' ;

		foreach ($ed as $yearhired) {
			$year[] = date("Y", strtotime($yearhired->startdate));
			asort($year); 
			$yhc = array_count_values($year);
		}
		$yc = ($yhc == null) ? null : implode($yhc, ', ') . ',' ;
		
		// Obtém dados gerais da empresa
		$orgProfile = table::companydata()->get();
		// Atualiza a data da última visualização do relatório do perfil organizacional
		table::reportviews()->where('report_id', 5)->update(array('last_viewed' => $today));

		// Retorna a view do perfil organizacional com os dados obtidos
		return view('admin.reports.report-organization-profile', compact('orgProfile', 'age_group', 'gc', 'dgc', 'cg', 'csc', 'yc', 'yhc', 'dc', 'dpc', 'dcc', 'cc'));
	}

    // Aniversários de funcionários: Exibe e atualiza a última visualização
	public function empBday(Request $request) 
	{
		// Verifica permissão de acesso ao relatório de aniversários de funcionários
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Define a data atual formatada
		$today = date('M, d Y');
		// Obtém a lista de aniversários de funcionários
		$empBday = table::people()->join('tbl_company_data', 'tbl_people.id', '=', 'tbl_company_data.reference')->get();
		// Atualiza a data da última visualização do relatório de aniversários
		table::reportviews()->where('report_id', 7)->update(['last_viewed' => $today]);

		// Retorna a view dos aniversários de funcionários com os dados obtidos
		return view('admin.reports.report-employee-birthdays', compact('empBday'));
	}

    // Contas de usuários: Exibe as contas de usuários e atualiza a última visualização
	public function userAccs(Request $request) 
	{
		// Verifica permissão de acesso ao relatório de contas de usuários
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Define a data atual formatada
		$today = date('M, d Y');
		// Obtém a lista de contas de usuários
		$userAccs = table::users()->get();
		table::reportviews()->where('report_id', 6)->update(['last_viewed' => $today]);

		// Retorna a view das contas de usuários com os dados obtidos
		return view('admin.reports.report-user-accounts', compact('userAccs'));
	}

    // Retorna dados de presença do funcionário baseado em critérios específicos via JSON
	public function getEmpAtten(Request $request) 
	{
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
	
		$id = $request->id;
		$datefrom = $request->datefrom;
		$dateto = $request->dateto;
	
		$query = DB::table('tbl_people_attendance as b')
			->join('tbl_company_data as a', 'a.idno', '=', 'b.idno')
			->select('b.idno', 'b.date', 'b.employee', 'b.timein', 'b.timeout', 'b.totalhours', 'a.company', 'a.department');
	
		if ($id) {
			$query->where('b.idno', $id);
		}
	
		if ($datefrom && $dateto) {
			$query->whereBetween('b.date', [$datefrom, $dateto]);
		}
	
		$data = $query->get();
	
		return response()->json($data);
	}
	

    // Retorna dados de licenças do funcionário baseado em critérios específicos via JSON
	public function getEmpLeav(Request $request) 
	{
		// Verifica permissão de acesso ao método de obtenção de dados de licença
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Obtém os parâmetros da requisição
		$id = $request->id;
		$datefrom = $request->datefrom;
		$dateto = $request->dateto;

		// Filtra e retorna os dados de licença conforme os critérios especificados
		if ($id == null AND $datefrom == null AND $dateto == null) 
		{
			$data = table::leaves()->select('idno', 'employee', 'type', 'leavefrom', 'leaveto', 'status', 'reason')->get();
			return response()->json($data);
		}

		if($id !== null AND $datefrom == null AND $dateto == null ) 
		{
			$data = table::leaves()->where('idno', $id)->select('idno', 'employee', 'type', 'leavefrom', 'leaveto', 'status', 'reason')->get();
			return response()->json($data);
		} elseif ($id !== null AND $datefrom !== null AND $dateto !== null) {
			$data = table::leaves()->where('idno', $id)->whereBetween('leavefrom', [$datefrom, $dateto])->select('idno', 'employee', 'type', 'leavefrom', 'leaveto', 'status', 'reason')->get();
			return response()->json($data);
		} elseif ($id == null AND $datefrom !== null AND $dateto !== null) {
			$data = table::leaves()->whereBetween('leavefrom', [$datefrom, $dateto])->select('idno', 'employee', 'type', 'leavefrom', 'leaveto', 'status', 'reason')->get();
			return response()->json($data);
		} 
	}

    // Retorna dados de escala do funcionário baseado em critérios específicos via JSON
	public function getEmpSched(Request $request) 
	{
		// Verifica permissão de acesso ao método de obtenção de dados de escala
		if (permission::permitted('reports')=='fail'){ return redirect()->route('denied'); }
		
		// Obtém os parâmetros da requisição
		$id = $request->id;
		
		// Filtra e retorna os dados de escala conforme os critérios especificados
		if ($id == null) 
		{
			$data = table::schedules()->select('reference', 'employee', 'intime', 'outime', 'datefrom', 'dateto', 'hours', 'restday', 'archive')->orderBy('archive', 'ASC')->get();
			return response()->json($data);
		}

		if($id !== null) 
		{
		 	$data = table::schedules()->where('idno', $id)->select('reference', 'employee', 'intime', 'outime', 'datefrom', 'dateto', 'hours', 'restday', 'archive')->orderBy('archive', 'ASC')->get();
			return response()->json($data);
		} 
	}
}
