@extends('layouts.clock') {{-- Estende o layout principal do relógio --}}

@section('content') {{-- Inicia a seção de conteúdo da página --}}

<div class="container-fluid">
    <div class="fixedcenter">
        <div class="clockwrapper">
            <div class="clockinout">
                <button class="btnclock timein active" data-type="timein">{{ __("Time In") }}</button> {{-- Botão para registrar a entrada do usuário --}}
                <button class="btnclock timeout" data-type="timeout">{{ __("Time Out") }}</button> {{-- Botão para registrar a saída do usuário --}}
            </div>
        </div>
        <div class="clockwrapper">
            <div class="timeclock">
                <span id="show_day" class="clock-text"></span> {{-- Mostra o dia atual --}}
                <span id="show_time" class="clock-time"></span> {{-- Mostra a hora atual --}}
                <span id="show_date" class="clock-day"></span> {{-- Mostra a data atual --}}
            </div>
        </div>

        <div class="clockwrapper">
            <div class="userinput">
                <form action="" method="get" accept-charset="utf-8" class="ui form">
                    @isset($cc) {{-- Verifica se a configuração de comentários está ativa --}}
                        @if($cc == "on") 
                            <div class="inline field comment">
                                <textarea name="comment" class="uppercase lightblue" rows="1" placeholder="{{ __("Enter comment") }}" value=""></textarea> {{-- Campo de texto para entrada de comentários pelo usuário --}}
                            </div> 
                        @endif
                    @endisset
                    <div class="inline field">
                        <input @if($rfid == 'on') id="rfid" @endif class="enter_idno uppercase @if($rfid == 'on') mr-0 @endif" name="idno" value="" type="text" placeholder="{{ __("ENTER YOUR ID") }}" required autofocus> {{-- Campo de entrada para ID do usuário, condicionalmente mostrando RFID --}}

                        @if($rfid !== "on")
                            <button id="btnclockin" type="button" class="ui positive large icon button">{{ __("Confirm") }}</button> {{-- Botão de confirmação para usuários sem RFID --}}
                        @endif
                        <input type="hidden" id="_url" value="{{url('/')}}"> {{-- Armazena a URL base para uso em scripts --}}
                    </div>
                </form>
            </div>
        </div>

        <div class="message-after">
                <p> 
                    <span id="greetings">{{ __("Welcome!") }}</span> {{-- Mensagem de boas-vindas exibida ao usuário --}}
                    <span id="fullname"></span> {{-- Espaço reservado para exibir o nome completo do usuário --}}
                </p>
                <p id="messagewrap">
                    <span id="type"></span> {{-- Espaço reservado para exibir o tipo de ação (entrada ou saída) --}}
                    <span id="message"></span> {{-- Mensagem contextual baseada na ação do usuário --}}
                    <span id="time"></span> {{-- Horário em que a ação foi registrada --}}
                </p>
            </div>
        </div>

    </div>

@endsection {{-- Encerra a seção de conteúdo da página --}}


@section('scripts')
<script type="text/javascript">
    
// Referências aos elementos HTML de dia, hora e data
var elTime = document.getElementById('show_time');
var elDate = document.getElementById('show_date');
var elDay = document.getElementById('show_day');

// Função para configurar o relógio sem o atraso inicial de 1 segundo
var setTime = function() {
    // Inicializa o relógio com a timezone configurada
    var time = moment().tz(timezone);

    // Define o formato da hora no HTML
    @if($tf == 1) 
        elTime.innerHTML= time.format("hh:mm:ss A"); // Formato de 12 horas
    @else
        elTime.innerHTML= time.format("kk:mm:ss"); // Formato de 24 horas
    @endif

    // Traduz e define a data no HTML
    var meses = {
        'January': 'Janeiro', 'February': 'Fevereiro', 'March': 'Março',
        'April': 'Abril', 'May': 'Maio', 'June': 'Junho',
        'July': 'Julho', 'August': 'Agosto', 'September': 'Setembro',
        'October': 'Outubro', 'November': 'Novembro', 'December': 'Dezembro'
    };
    elDate.innerHTML = time.format('D [de] ') + meses[time.format('MMMM')] + time.format(' [de] YYYY');

    // Traduz e define o dia da semana no HTML
    var diasDaSemana = {
        'Monday': 'Segunda-feira', 'Tuesday': 'Terça-feira', 'Wednesday': 'Quarta-feira',
        'Thursday': 'Quinta-feira', 'Friday': 'Sexta-feira',
        'Saturday': 'Sábado', 'Sunday': 'Domingo'
    };
    elDay.innerHTML = diasDaSemana[time.format('dddd')];
}

setTime(); // Executa a função imediatamente para evitar o atraso inicial
setInterval(setTime, 1000); // Atualiza o relógio a cada segundo

// Evento de clique para os botões de ponto
$('.btnclock').click(function(event) {
    var is_comment = $(this).data("type");
    if (is_comment == "timein") {
        $('.comment').slideDown('200').show(); // Mostra o campo de comentário no Time In
    } else {
        $('.comment').slideUp('200'); // Esconde o campo de comentário
    }
    $('input[name="idno"]').focus(); // Foca no campo de ID
    $('.btnclock').removeClass('active animated fadeIn')
    $(this).toggleClass('active animated fadeIn'); // Animação para o botão clicado
});

// Evento de input para o campo RFID, disparando uma requisição AJAX para registrar a ação
$("#rfid").on("input", function(){
    var url, type, idno, comment;
    url = $("#_url").val(); // URL base
    type = $('.btnclock.active').data("type"); // Tipo de ação (Time In ou Time Out)
    idno = $('input[name="idno"]').val().toUpperCase(); // ID do usuário em maiúsculas
    comment = $('textarea[name="comment"]').val(); // Comentário do usuário, se disponível

    setTimeout(() => {
        $(this).val(""); // Limpa o campo RFID após a leitura
    }, 600);

    $.ajax({ // Requisição AJAX para registrar a ação
        url: url + '/attendance/add', 
        type: 'post', 
        dataType: 'json', 
        data: {idno: idno, type: type, clockin_comment: comment}, 
        headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },

        success: function(response) {
            if(response['error'] != null) 
            {
                $('.message-after').addClass('notok').hide();
                $('#type, #fullname').text("").hide();
                $('#time').html("").hide();
                $('.message-after').removeClass("ok");
                $('#message').text(response['error']);
                $('#fullname').text(response['employee']);
                $('.message-after').slideToggle().slideDown('400');
            } else {
                function type(clocktype) {
                    if (clocktype == "timein") {
                        return "{{ __('Time In at') }}"; // Retorna a mensagem para Time In
                    } else {
                        return "{{ __('Time Out at') }}"; // Retorna a mensagem para Time Out
                    }
                }
                $('.message-after').addClass('ok').hide();
                $('.message-after').removeClass("notok");
                $('#type, #fullname, #message').text("").show();
                $('#time').html("").show();
                $('#type').text(type(response['type']));
                $('#fullname').text(response['firstname'] + ' ' + response['lastname']);
                $('#time').html('<span id=clocktime>' + response['time'] + '</span>' + '.' + '<span id=clockstatus> {{ __("Success!") }}</span>');
                $('.message-after').slideToggle().slideDown('400');
            }
        }
    })
});


$('#btnclockin').click(function(event) {
    var url, type, idno, comment;
    url = $("#_url").val(); // Pega a URL base do formulário
    type = $('.btnclock.active').data("type"); // Determina o tipo de ponto (entrada ou saída) com base no botão ativo
    idno = $('input[name="idno"]').val(); // Pega o ID do funcionário do campo de entrada
    idno.toUpperCase(); // Converte o ID para maiúsculas
    comment = $('textarea[name="comment"]').val(); // Pega o comentário inserido pelo usuário, se houver

    // Realiza uma requisição AJAX para registrar o ponto
    $.ajax({
        url: url + '/attendance/add', // URL para o endpoint de adição de ponto
        type: 'post', // Método HTTP de envio
        dataType: 'json', // Tipo de dados esperado em resposta
        data: {idno: idno, type: type, clockin_comment: comment}, // Dados enviados com a requisição
        headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') }, // Token CSRF para segurança

        // Função de sucesso executada após receber a resposta do servidor
        success: function(response) {
            if(response['error'] != null) // Verifica se a resposta contém um erro
            {
                // Se houver erro, exibe uma mensagem de erro
                $('.message-after').addClass('notok').hide();
                $('#type, #fullname').text("").hide();
                $('#time').html("").hide();
                $('.message-after').removeClass("ok");
                $('#message').text(response['error']);
                $('#fullname').text(response['employee']);
                $('.message-after').slideToggle().slideDown('400');
            } else {
                // Função interna para formatar a mensagem de ponto com base no tipo
                function type(clocktype) {
                    if (clocktype == "timein") {
                        return "{{ __('Time In at') }}"; // Mensagem para entrada
                    } else {
                        return "{{ __('Time Out at') }}"; // Mensagem para saída
                    }
                }
                // Se não houver erro, exibe uma mensagem de sucesso
                $('.message-after').addClass('ok').hide();
                $('.message-after').removeClass("notok");
                $('#type, #fullname, #message').text("").show();
                $('#time').html("").show();
                $('#type').text(type(response['type'])); // Configura a mensagem com base no tipo de ponto
                // Configura e exibe a hora e a mensagem de sucesso
                $('#time').html('<span id=clocktime>' + response['time'] + '</span>' + '.' + '<span id=clockstatus> {{ __("Success!") }}</span>');
                $('.message-after').slideToggle().slideDown('400');
            }
        }
    })
});
</script> 

@endsection